<?php
namespace App\Services\Insights;

use App\Models\Company;

/** Read-only vendor due diligence from existing insights records; never issues a credit rating. */
class VendorAssessment
{
    public function assess(Company $company, float $revenueChange = -10): array
    {
        $filings = $company->filings;
        $checks = [];
        $sec = $company->usListings ?? collect();
        $secFiler = $sec->whereNotNull('cik')->first();
        $fetched = $company->financialStatements->sortBy('financial_year');
        if ($secFiler) {
            // US-listed: annual reports are filed with the SEC (10-K / 20-F); there is no AOC-4 or MGT-7.
            $years = $fetched->filter(fn($s)=>str_starts_with((string)$s->source,'SEC '))->pluck('financial_year');
            $checks[] = ['name'=>'Financial statements (SEC annual report)', 'status'=>$years->isEmpty() ? 'Being fetched' : 'Read from SEC EDGAR',
                'detail'=>$years->isEmpty() ? 'Read automatically from SEC EDGAR (CIK '.$secFiler->cik.') within a few minutes.' : 'Consolidated accounts, FY '.$years->first().' to '.$years->last().', converted to rupees.'];
            $checks[] = ['name'=>'Company registration', 'status'=>'SEC registrant', 'detail'=>($secFiler->exchange ?: 'US').': '.$sec->pluck('ticker')->implode(', ').'; not registered under the Companies Act.'];
        } else {
            foreach (['AOC-4'=>'Financial statements', 'MGT-7'=>'Annual return / ownership'] as $prefix=>$label) {
                $matching = $filings->filter(fn($f)=>str_starts_with($f->form, $prefix));
                $other = $prefix === 'AOC-4' ? $fetched->pluck('source')->filter()->unique()->implode(', ') : '';
                $checks[] = ['name'=>$label, 'status'=>$matching->isEmpty() ? ($other !== '' ? 'Available' : 'Being fetched') : ($matching->contains(fn($f)=>$f->status !== 'parsed')?'Review required':'Parsed; authenticity not verified'),
                    'detail'=>$matching->map(fn($f)=>$f->form.' '.($f->financial_year ?? 'year unknown').' ('.$f->status.')')->implode('; ')
                        ?: ($other !== '' ? 'Figures for FY '.$fetched->pluck('financial_year')->unique()->implode(', ').' from '.$other.'.' : 'Read automatically from the MCA '.$prefix.' when a data provider returns it.')];
            }
            $checks[] = ['name'=>'Company registration', 'status'=>$company->registryDetail?->company_status ?? $company->registry_status ?? 'Unknown',
                'detail'=>'Recorded registry status is not proof that all statutory obligations are satisfied.'];
        }
        foreach (['GST filing and tax compliance','Default / overdue EMI history','Fraud, litigation and insolvency checks','Bank account and vendor identity verification','Agreed payment terms and invoice ageing'] as $label) {
            $checks[] = ['name'=>$label,'status'=>'Not verified','detail'=>'Requires dated supporting evidence; absence of a record is not a clean result.'];
        }
        // A company obligor must not be assessed using its consolidated group figures; SEC filers publish consolidated accounts only.
        $statements = $company->financialStatements->where('is_consolidated', false)->sortBy('financial_year')->values();
        if ($statements->isEmpty() && $secFiler) $statements = $company->financialStatements->where('is_consolidated', true)->sortBy('financial_year')->values();
        $latest = $statements->last();
        $number = fn($v)=>$v !== null && is_numeric($v) ? (float)$v : null;
        $divide = fn($a,$b)=>$a !== null && $b !== null && $b > 0 ? round($a/$b,2) : null;
        $value = fn($key)=>$number($latest?->{$key});
        $debt = $value('total_debt');
        if ($debt === null && $value('long_term_borrowings') !== null && $value('short_term_borrowings') !== null) $debt = $value('long_term_borrowings') + $value('short_term_borrowings');
        $equity = $value('net_worth');
        $ebit = $value('profit_before_tax') !== null && $value('finance_cost') !== null ? $value('profit_before_tax') + $value('finance_cost') : null;
        $metrics = [
            ['name'=>'Current ratio','value'=>$divide($value('current_assets'),$value('current_liabilities')),'formula'=>'Current assets / current liabilities'],
            ['name'=>'Debt / equity','value'=>$divide($debt,$equity),'formula'=>'Total borrowings / positive net worth'],
            ['name'=>'Interest coverage (EBIT)','value'=>$divide($ebit,$value('finance_cost')),'formula'=>'(Profit before tax + finance cost) / finance cost'],
            ['name'=>'DSCR','value'=>null,'formula'=>'Not calculated: cash available for debt service and scheduled principal + interest are required.'],
        ];
        $risks = [];
        if (!$latest) $risks[] = 'No financial statements available yet.';
        if ($latest && !$latest->period_end) $risks[] = 'Financial statement date is unknown.';
        if ($latest?->period_end && $latest->period_end->lt(now()->subMonths(18))) $risks[] = 'Financial statements are over 18 months old (review freshness threshold).';
        if ($latest?->period_end && $latest->period_end->isFuture()) $risks[] = 'Statement period is in the future; check the imported date.';
        if ($equity !== null && $equity <= 0) $risks[] = 'Non-positive net worth.';
        if ($value('profit_after_tax') !== null && $value('profit_after_tax') < 0) $risks[] = 'Reported loss in the latest period.';
        if ($metrics[0]['value'] !== null && $metrics[0]['value'] < 1) $risks[] = 'Current liabilities exceed current assets.';
        if ($metrics[2]['value'] !== null && $metrics[2]['value'] < 1) $risks[] = 'Reported EBIT does not cover finance cost.';
        $previous = null;
        $trends = $statements->map(function($s) use (&$previous,$number) {
            $growth = null;
            if ($previous && (int)$s->financial_year === (int)$previous->financial_year + 1 && $number($previous->revenue) > 0 && $number($s->revenue) !== null) {
                $growth = round(($s->revenue-$previous->revenue)/$previous->revenue*100,2);
            }
            $row = ['year'=>$s->financial_year,'revenue'=>$s->revenue,'profit'=>$s->profit_after_tax,'growth'=>$growth,'source'=>$s->source];
            $previous = $s;
            return $row;
        });
        $revenueChange = max(-100, min(100, $revenueChange));
        $scenarioRevenue = $value('revenue') !== null && $value('revenue') > 0 ? $value('revenue') * (1 + $revenueChange / 100) : null;
        $scenarioEbit = $scenarioRevenue !== null && $ebit !== null ? $ebit + ($scenarioRevenue - $value('revenue')) : null;
        $scenario = ['change'=>$revenueChange,'revenue'=>$scenarioRevenue,'ebit'=>$scenarioEbit,'interest_cover'=>$divide($scenarioEbit,$value('finance_cost'))];
        return ['basis'=>$latest && $latest->is_consolidated ? 'Consolidated' : 'Standalone','scenario'=>$scenario,'checks'=>$checks,'metrics'=>$metrics,'risks'=>$risks,'trends'=>$trends,
            'year'=>$latest?->financial_year,'period_end'=>$latest?->period_end?->toDateString(),
            'decision'=>'Review required - payment ability not established',
            'reports'=>$filings->where('form','RATING REPORT')->sortByDesc(fn($f)=>$f->parsed_fields['report_date'] ?? ''),
            'next_steps'=>['Verify CIN, GSTIN, bank beneficiary and authorized signatory.',
                'Reconcile AOC-4 and MGT-7 entity, period and standalone basis; inspect auditor qualifications and filing acknowledgements.',
                'Obtain current cash-flow statements, debt repayment schedule, overdue creditors and customer receivable ageing.',
                'Verify the latest facility-specific rating directly with the agency; retain dated default and legal-search evidence.',
                'Record proposed exposure, agreed credit days, security and payment milestones before the authorized reviewer decides.']];
    }
}
