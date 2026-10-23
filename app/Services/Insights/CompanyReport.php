<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Services\Export\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

// Downloadable company report (PDF and Excel): profile, financials, credit ratings, rated facilities,
// funding lifecycle, lenders and borrowing capacity. Premium-only sections are left out for other viewers.
class CompanyReport
{
    public function __construct(private FundingLifecycle $lifecycle, private CreditProfile $credit, private CareAssessment $assessment) {}

    public function data(Company $company, bool $full): array
    {
        $company->loadMissing(['registryDetail', 'financialStatements', 'charges', 'officers']);
        $years = $full ? $company->financialStatements->sortByDesc('financial_year')->groupBy('financial_year')->map(fn ($g) => $g->firstWhere('is_consolidated', false) ?? $g->first())->take(12)->values() : collect();
        return [
            'company' => $company, 'full' => $full, 'generated' => now(),
            'registry' => $company->registryDetail,
            'years' => $years,
            'credit' => rescue(fn () => $this->credit->build($company), null, false),
            'funding' => rescue(fn () => $this->lifecycle->build($company, $full), null, false),
            'assessment' => rescue(fn () => $this->assessment->stored($company, $full), null, false),
            'directors' => $company->officers->take(25),
        ];
    }

    public function pdf(Company $company, bool $full)
    {
        return Pdf::loadView('insights.report-pdf', $this->data($company, $full))->setPaper('a4')->setOption('isFontSubsettingEnabled', true);
    }

    public function xlsx(Company $company, bool $full): string
    {
        $d = $this->data($company, $full);
        $cr = fn ($v) => $v === null ? null : round((float) $v / 1e7, 2);
        $r = $d['registry'];
        $sheets = ['Overview' => [['Field', 'Value'], array_values(array_filter([
            ['Company', $company->name], ['CIN', $r?->cin ?? $company->registry_cin], ['Status', $r?->company_status ?? $company->registry_status], ['Industry', $company->industry],
            ['City / State', trim(($r?->city ?? '') . ', ' . ($company->registered_state ?? ''), ', ')], ['Website', $company->website], ['Incorporated', $r?->incorporation_date?->format('d M Y')],
            ['Report generated', $d['generated']->format('d M Y H:i')], ['Figures', 'Financials in ₹ crore'],
        ], fn ($row) => $row[1] !== null && $row[1] !== ''))]];
        if ($d['years']->isNotEmpty()) {
            $fields = ['revenue' => 'Revenue', 'ebitda' => 'EBITDA', 'finance_cost' => 'Finance cost', 'profit_after_tax' => 'Net profit', 'net_worth' => 'Net worth', 'long_term_borrowings' => 'Long-term borrowings',
                'short_term_borrowings' => 'Short-term borrowings', 'total_debt' => 'Total debt', 'cash_and_bank' => 'Cash & bank', 'total_assets' => 'Total assets', 'trade_receivables' => 'Receivables', 'inventories' => 'Inventories'];
            $sheets['Financials'] = [array_merge(['Metric (₹ crore)'], $d['years']->pluck('financial_year')->all()),
                collect($fields)->map(fn ($label, $k) => array_merge([$label], $d['years']->map(fn ($y) => $cr($y->$k))->all()))->values()->all()];
        }
        if ($d['credit']) {
            $sheets['Credit ratings'] = [['Date', 'Agency', 'Action', 'Long-term', 'Short-term', 'Outlook', 'Rated (₹ cr)', 'Liquidity', 'Report'],
                $d['credit']['history']->map(fn ($x) => [$x->published_on?->format('Y-m-d'), $x->agency, $x->action, $x->rating_long, $x->rating_short, $x->outlook, $x->total_rated_crore, $x->liquidity, $x->source_url])->all()];
            $latest = $d['credit']['history']->first(fn ($x) => !empty($x->instruments));
            if ($latest) $sheets['Rated facilities'] = [['Facility', 'Type', 'Previous (₹ cr)', 'Current (₹ cr)', 'Rating', 'Action'],
                collect($latest->instruments)->map(fn ($i) => [$i['instrument'] ?? '', $i['type'] ?? '', $i['previous'] ?? null, $i['current'] ?? null, trim(($i['rating'] ?? '') . ' ' . ($i['outlook'] ?? '')), $i['action'] ?? ''])->all()];
        }
        if ($a = $d['assessment']) {
            $sheets['Credit assessment'] = [['Measure', 'Value', 'Sector median', 'Band (good / weak)', 'Score (1-5)'], array_merge(
                [['Sector methodology', $a['sector'] . ' – ' . $a['criteria'], null, null, null], ['Accounts basis', $a['basis'], null, null, null],
                 ['Indicative long-term', $a['indicative'], null, null, null], ['Indicative short-term', $a['short_term'], null, null, null], ['Score / 100', $a['score'], null, null, null],
                 ['Liquidity', $a['liquidity']['level'], null, null, null], ['Direction', $a['outlook']['watch'] ? 'Rating watch' : $a['outlook']['direction'], null, null, null]],
                array_map(fn ($x) => ['Adjustment ' . $x['from'] . ' → ' . $x['to'], $x['why'], null, null, null], $a['adjustments']),
                array_map(fn ($r) => [$r['label'], $r['value'], $r['peer'], $r['band'][1] . ' / ' . $r['band'][3], $r['score']], array_values($a['rows'])),
                array_map(fn ($x) => ['Check: ' . $x['label'], $x['value'], null, null, $x['status']], $a['checks'])
            )];
        }
        if ($f = $d['funding']) {
            $sheets['Funding timeline'] = [['Date', 'Event', 'Amount (₹ cr)', 'Source', 'Evidence'],
                $f['timeline']->map(fn ($e) => [$e['date']->format('Y-m-d'), $e['title'], $e['amount'] !== null ? (float) $e['amount'] : null, $e['source'] ?? '', $e['evidence'] ?? ''])->all()];
            $c = $f['capacity'];
            $sheets['Borrowing capacity'] = [['Measure', 'Value'], array_merge([
                ['Verdict', $c['verdict']], ['Next rating stable or better (probability)', $c['outlook'] ? round($c['outlook']['probability'] * 100, 1) . '%' : '—'],
                ['Rated limits (₹ cr)', $c['rated_total']], ['Unallocated rated limits (₹ cr)', $c['unallocated']], ['Extra debt within ' . $c['debt_cap_multiple'] . '× EBITDA (₹ cr)', $c['room_crore']],
                ['Interest coverage (×)', $c['interest_coverage']], ['Working-capital utilisation (%)', $c['utilisation']], ['Open bank charges (₹ cr)', $c['open_charges']],
            ], array_map(fn ($w) => ['Reason', $w], $c['why']))];
            if ($f['lenders']->isNotEmpty()) $sheets['Lenders'] = [['Lender', 'Charges', 'Open', 'Open amount (₹ cr)', 'First loan', 'Last closed'],
                $f['lenders']->map(fn ($l) => [$l['lender'], $l['charges'], $l['open'], $l['amount_open'], $l['first'] ? substr((string) $l['first'], 0, 10) : null, $l['last_closed'] ? substr((string) $l['last_closed'], 0, 10) : null])->all()];
        }
        if ($full && $company->charges->isNotEmpty()) $sheets['Charges'] = [['Charge ID', 'Lender', 'Amount (₹ cr)', 'Status', 'Created', 'Modified', 'Satisfied', 'Assets charged'],
            $company->charges->map(fn ($c) => [$c->charge_id, $c->lender, $cr($c->amount), $c->status, $c->created_on ? substr((string) $c->created_on, 0, 10) : null, $c->modified_on ? substr((string) $c->modified_on, 0, 10) : null, $c->satisfied_on ? substr((string) $c->satisfied_on, 0, 10) : null, $c->assets_charged])->all()];
        if ($d['directors']->isNotEmpty()) $sheets['Directors'] = [['Name', 'Designation', 'Appointed'], $d['directors']->map(fn ($o) => [$o->name, $o->designation, $o->appointed_on?->format('Y-m-d')])->all()];
        return app(Xlsx::class)->write($sheets);
    }
}
