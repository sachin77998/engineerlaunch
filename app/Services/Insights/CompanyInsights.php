<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CompanyFinancialStatement;
use Illuminate\Support\Collection;

/**
 * Company check: recognises a CIN, GSTIN, PAN or name, decodes the identifiers, finds the company and turns its
 * filed financial statements (AOC-4) into year-wise ratios. Nothing is estimated: a ratio is blank when an input is.
 */
class CompanyInsights
{
    public const CIN = '/^[LU]\d{5}[A-Z]{2}\d{4}[A-Z]{3}\d{6}$/';
    public const GSTIN = '/^\d{2}[A-Z]{5}\d{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/';
    public const PAN = '/^[A-Z]{5}\d{4}[A-Z]$/';
    public const DIN = '/^\d{8}$/';

    // CIN and GSTIN state codes.
    private const CIN_STATES = [
        'AN' => 'Andaman and Nicobar Islands', 'AP' => 'Andhra Pradesh', 'AR' => 'Arunachal Pradesh', 'AS' => 'Assam', 'BR' => 'Bihar', 'CH' => 'Chandigarh',
        'CT' => 'Chhattisgarh', 'DD' => 'Daman and Diu', 'DL' => 'Delhi', 'DN' => 'Dadra and Nagar Haveli', 'GA' => 'Goa', 'GJ' => 'Gujarat', 'HP' => 'Himachal Pradesh',
        'HR' => 'Haryana', 'JH' => 'Jharkhand', 'JK' => 'Jammu and Kashmir', 'KA' => 'Karnataka', 'KL' => 'Kerala', 'LD' => 'Lakshadweep', 'MH' => 'Maharashtra',
        'ML' => 'Meghalaya', 'MN' => 'Manipur', 'MP' => 'Madhya Pradesh', 'MZ' => 'Mizoram', 'NL' => 'Nagaland', 'OR' => 'Odisha', 'PB' => 'Punjab', 'PY' => 'Puducherry',
        'RJ' => 'Rajasthan', 'SK' => 'Sikkim', 'TG' => 'Telangana', 'TN' => 'Tamil Nadu', 'TR' => 'Tripura', 'UP' => 'Uttar Pradesh', 'UR' => 'Uttarakhand', 'WB' => 'West Bengal',
    ];
    private const GST_STATES = [
        '01' => 'Jammu and Kashmir', '02' => 'Himachal Pradesh', '03' => 'Punjab', '04' => 'Chandigarh', '05' => 'Uttarakhand', '06' => 'Haryana', '07' => 'Delhi',
        '08' => 'Rajasthan', '09' => 'Uttar Pradesh', '10' => 'Bihar', '11' => 'Sikkim', '12' => 'Arunachal Pradesh', '13' => 'Nagaland', '14' => 'Manipur', '15' => 'Mizoram',
        '16' => 'Tripura', '17' => 'Meghalaya', '18' => 'Assam', '19' => 'West Bengal', '20' => 'Jharkhand', '21' => 'Odisha', '22' => 'Chhattisgarh', '23' => 'Madhya Pradesh',
        '24' => 'Gujarat', '26' => 'Dadra and Nagar Haveli and Daman and Diu', '27' => 'Maharashtra', '29' => 'Karnataka', '30' => 'Goa', '31' => 'Lakshadweep', '32' => 'Kerala',
        '33' => 'Tamil Nadu', '34' => 'Puducherry', '35' => 'Andaman and Nicobar Islands', '36' => 'Telangana', '37' => 'Andhra Pradesh', '38' => 'Ladakh', '97' => 'Other Territory',
    ];
    private const CIN_CLASSES = [
        'PLC' => 'Public limited company', 'PTC' => 'Private limited company', 'GOI' => 'Company owned by the Government of India', 'SGC' => 'State Government company',
        'FLC' => 'Financial lease company', 'GAP' => 'Public company with guarantee / association', 'GAT' => 'Private company with guarantee / association',
        'NPL' => 'Not-for-profit licensed company (Section 8)', 'ULL' => 'Public unlimited company', 'ULT' => 'Private unlimited company', 'OPC' => 'One Person Company',
        'FTC' => 'Subsidiary of a foreign company', 'GOV' => 'Government company',
    ];
    private const PAN_HOLDERS = ['C' => 'Company', 'P' => 'Individual', 'H' => 'Hindu Undivided Family', 'F' => 'Firm / LLP', 'A' => 'Association of Persons',
        'T' => 'Trust', 'B' => 'Body of Individuals', 'L' => 'Local authority', 'J' => 'Artificial juridical person', 'G' => 'Government'];

    /** @return array{type:string,value:string} */
    public function identify(string $query): array
    {
        $value = strtoupper(preg_replace('/\s+/', '', $query));
        foreach (['cin' => self::CIN, 'gstin' => self::GSTIN, 'pan' => self::PAN, 'din' => self::DIN] as $type => $pattern) {
            if (preg_match($pattern, $value)) return ['type' => $type, 'value' => $value];
        }
        return ['type' => 'name', 'value' => trim($query)];
    }

    public function decodeCin(string $cin): array
    {
        return [
            'Listing' => $cin[0] === 'L' ? 'Listed on a stock exchange' : 'Unlisted',
            'Industry (NIC code)' => substr($cin, 1, 5),
            'State of registration' => self::CIN_STATES[substr($cin, 6, 2)] ?? substr($cin, 6, 2),
            'Year of incorporation' => substr($cin, 8, 4),
            'Company type' => self::CIN_CLASSES[substr($cin, 12, 3)] ?? substr($cin, 12, 3),
            'Registration number' => substr($cin, 15, 6),
        ];
    }

    public function decodeGstin(string $gstin): array
    {
        $pan = substr($gstin, 2, 10);
        return [
            'State' => self::GST_STATES[substr($gstin, 0, 2)] ?? substr($gstin, 0, 2),
            'PAN' => $pan,
            'Holder type' => self::PAN_HOLDERS[$pan[3]] ?? $pan[3],
            'Registration number in state' => substr($gstin, 12, 1),
            'Check digit' => $this->gstinCheckDigit($gstin) === substr($gstin, 14, 1) ? 'Valid' : 'Invalid – please re-check the GSTIN',
        ];
    }

    /** GSTIN check character (mod-36 Luhn variant). */
    private function gstinCheckDigit(string $gstin): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $sum = 0;
        for ($i = 0; $i < 14; $i++) {
            $product = strpos($chars, $gstin[$i]) * ($i % 2 ? 2 : 1);
            $sum += intdiv($product, 36) + $product % 36;
        }
        return $chars[(36 - $sum % 36) % 36];
    }

    /** Companies matching the query (exact identifier first, then name). */
    public function search(string $query, int $limit = 30): Collection
    {
        ['type' => $type, 'value' => $value] = $this->identify($query);
        $base = Company::query()->where('is_active', true)->with('registryDetail');
        return match ($type) {
            'cin' => $base->where(fn ($q) => $q->where('registry_cin', $value)->orWhereHas('registryDetail', fn ($r) => $r->where('cin', $value)))->limit($limit)->get(),
            'din' => $base->whereHas('officers', fn ($o) => $o->where('din', $value))->limit($limit)->get(),
            'gstin', 'pan' => $base->whereHas('registryDetail', fn ($r) => $r->where('pan', $type === 'pan' ? $value : substr($value, 2, 10))
                ->orWhereJsonContains('gstins', $value))->limit($limit)->get(),
            default => $this->byName($base, $value, $limit),
        };
    }

    /** Legal-name variants match: "happy forgings ltd" finds "HAPPY FORGINGS LIMITED"; short tokens match spaced acronyms ("G N A"). */
    private function byName($base, string $name, int $limit): Collection
    {
        $ignored = ['ltd', 'limited', 'pvt', 'private', 'public', 'company', 'co', 'llp', 'opc', 'the'];
        $tokens = collect(preg_split('/[^a-z0-9]+/', mb_strtolower($name)))
            ->reject(fn ($t) => $t === '' || in_array($t, $ignored, true))
            ->map(fn ($t) => strlen($t) >= 5 ? substr($t, 0, strlen($t) - 1) : $t)->unique()->values();
        if ($tokens->isEmpty()) return collect();
        $query = $base;
        foreach ($tokens as $token) {
            $query->where('name', 'like', strlen($token) <= 3 ? '%' . implode('%', str_split($token)) . '%' : '%' . $token . '%');
        }
        return $query->withCount('financialStatements')
            ->orderByRaw('CASE WHEN LOWER(name) = ? THEN 0 ELSE 1 END', [mb_strtolower(trim($name))])
            ->orderByDesc('financial_statements_count')->orderByRaw('registry_cin IS NULL')->orderBy('name')->limit($limit)->get();
    }

    /** Year-wise ratios from filed statements, oldest first. */
    public function ratios(Collection $statements): Collection
    {
        $previous = null;
        return $statements->sortBy('financial_year')->values()->map(function (CompanyFinancialStatement $s) use (&$previous) {
            $debt = $s->total_debt ?? (($s->long_term_borrowings !== null || $s->short_term_borrowings !== null) ? (float) $s->long_term_borrowings + (float) $s->short_term_borrowings : null);
            $equity = $s->net_worth ?? (($s->share_capital !== null || $s->reserves !== null) ? (float) $s->share_capital + (float) $s->reserves : null);
            $ebitda = $s->ebitda ?? ($s->profit_before_tax !== null ? (float) $s->profit_before_tax + (float) $s->finance_cost + (float) $s->depreciation : null);
            $ratio = fn ($a, $b, $scale = 100) => ($a === null || !$b) ? null : round($a / $b * $scale, 2);
            $row = [
                'year' => $s->financial_year, 'revenue' => $s->revenue, 'ebitda' => $ebitda, 'pat' => $s->profit_after_tax, 'net_worth' => $equity,
                'total_debt' => $debt, 'total_assets' => $s->total_assets,
                'revenue_growth' => $previous ? $ratio($s->revenue !== null && $previous->revenue ? $s->revenue - $previous->revenue : null, $previous->revenue) : null,
                'profit_growth' => $previous && $previous->profit_after_tax > 0 ? $ratio($s->profit_after_tax !== null ? $s->profit_after_tax - $previous->profit_after_tax : null, $previous->profit_after_tax) : null,
                'ebitda_margin' => $ratio($ebitda, $s->revenue),
                'net_margin' => $ratio($s->profit_after_tax, $s->revenue),
                'roe' => $equity > 0 ? $ratio($s->profit_after_tax, $equity) : null,
                'roce' => ($equity + (float) $debt) > 0 && $s->profit_before_tax !== null ? $ratio((float) $s->profit_before_tax + (float) $s->finance_cost, $equity + (float) $debt) : null,
                'roa' => $ratio($s->profit_after_tax, $s->total_assets),
                'debt_to_equity' => $equity > 0 ? $ratio($debt, $equity, 1) : null,
                'debt_to_ebitda' => $ebitda > 0 ? $ratio($debt, $ebitda, 1) : null,
                'interest_cover' => $s->finance_cost > 0 && $ebitda !== null ? $ratio($ebitda, $s->finance_cost, 1) : null,
                'current_ratio' => $ratio($s->current_assets, $s->current_liabilities, 1),
                'asset_turnover' => $ratio($s->revenue, $s->total_assets, 1),
                'equity_multiplier' => $equity > 0 ? $ratio($s->total_assets, $equity, 1) : null,
                'sales_to_fixed_assets' => $ratio($s->revenue, $s->fixed_assets, 1),
                'eps' => $s->eps ?? ($s->shares_outstanding ? round($s->profit_after_tax / $s->shares_outstanding, 2) : null),
            ];
            // P/E needs a market price, which filings do not contain: only shown when a price is recorded for the year.
            $row['pe'] = $s->market_price && $row['eps'] > 0 ? round($s->market_price / $row['eps'], 2) : null;
            $previous = $s;
            return $row;
        });
    }

    /**
     * Financial health score (0–100) from the latest year's ratios and the filing record. Every point comes from a
     * stated rule, so the page can list the reasons (risk factors and strengths) next to the score.
     */
    public function health(Collection $ratios, ?\App\Models\CompanyRegistryDetail $registry, Collection $charges): ?array
    {
        $latest = $ratios->last();
        if (!$latest || $latest['revenue'] === null) return null;
        $risks = [];
        $strengths = [];
        $score = fn (?float $v, float $bad, float $good) => $v === null ? null : max(0, min(1, ($v - $bad) / ($good - $bad)));

        $profitability = collect([$score($latest['net_margin'], 0, 10), $score($latest['roe'], 0, 20), $score($latest['roce'], 5, 20)])->filter(fn ($v) => $v !== null);
        $growthYears = $ratios->slice(-5);
        $profitableYears = $growthYears->filter(fn ($r) => ($r['pat'] ?? 0) > 0)->count();
        $growth = collect([$score($latest['revenue_growth'], -10, 15), $growthYears->count() > 1 ? $profitableYears / $growthYears->count() : null])->filter(fn ($v) => $v !== null);
        $solvency = collect([$latest['debt_to_equity'] === null ? null : 1 - $score($latest['debt_to_equity'], 0.3, 2), $score($latest['interest_cover'], 1.5, 6), $score($latest['current_ratio'], 0.8, 1.8)])->filter(fn ($v) => $v !== null);
        $efficiency = collect([$score($latest['asset_turnover'], 0.3, 1.5), $score($latest['ebitda_margin'], 3, 15)])->filter(fn ($v) => $v !== null);
        $parts = ['Profitability' => [$profitability, 'Returns and margins'], 'Growth' => [$growth, 'Revenue and profit consistency'], 'Solvency' => [$solvency, 'Leverage and liquidity'], 'Efficiency' => [$efficiency, 'Asset use and operating margin']];
        $breakdown = [];
        foreach ($parts as $name => [$values, $hint]) if ($values->isNotEmpty()) $breakdown[$name] = ['value' => (int) round($values->avg() * 100), 'hint' => $hint];
        if (!$breakdown) return null;
        $total = (int) round(collect($breakdown)->avg('value'));

        if ($latest['pat'] !== null && $latest['pat'] < 0) $risks[] = ['High', 'Loss-making in FY ' . $latest['year']];
        if ($latest['debt_to_equity'] !== null && $latest['debt_to_equity'] > 2) $risks[] = ['High', 'High leverage (debt/equity ' . number_format($latest['debt_to_equity'], 2) . ')'];
        elseif ($latest['debt_to_equity'] !== null && $latest['debt_to_equity'] > 1) $risks[] = ['Moderate', 'Debt above equity (debt/equity ' . number_format($latest['debt_to_equity'], 2) . ')'];
        if ($latest['interest_cover'] !== null && $latest['interest_cover'] < 2) $risks[] = ['Moderate', 'Low interest cover (' . number_format($latest['interest_cover'], 1) . '×)'];
        if ($latest['roce'] !== null && $latest['roce'] < 10) $risks[] = ['Low', 'Below-average ROCE (' . number_format($latest['roce'], 1) . '%)'];
        if ($latest['net_margin'] !== null && $latest['net_margin'] >= 0 && $latest['net_margin'] < 3) $risks[] = ['Low', 'Thin net margin (' . number_format($latest['net_margin'], 1) . '%)'];
        if ($latest['revenue_growth'] !== null && $latest['revenue_growth'] < 0) $risks[] = ['Low', 'Revenue fell ' . number_format(abs($latest['revenue_growth']), 1) . '% year on year'];
        if ($latest['current_ratio'] !== null && $latest['current_ratio'] < 1) $risks[] = ['Moderate', 'Current liabilities exceed current assets (current ratio ' . number_format($latest['current_ratio'], 2) . ')'];
        $lenders = $charges->filter(fn ($c) => !preg_match('/satisf|closed/i', (string) $c->status))->pluck('lender')->filter()->unique()->count();
        if ($lenders >= 8) $risks[] = ['Moderate', "High lender concentration ({$lenders} lenders)"];
        foreach ($this->compliance($registry) as $item) if ($item['overdue']) $risks[] = ['Moderate', $item['label'] . ' overdue (due ' . $item['due']->format('d M Y') . ')'];

        if ($growthYears->count() > 1 && $profitableYears === $growthYears->count()) $strengths[] = "Profitable in all {$profitableYears} years analysed";
        if ($latest['revenue_growth'] !== null && $latest['revenue_growth'] >= 10) $strengths[] = 'Revenue grew ' . number_format($latest['revenue_growth'], 1) . '% year on year';
        if ($latest['debt_to_equity'] !== null && $latest['debt_to_equity'] <= 0.5) $strengths[] = 'Low leverage (debt/equity ' . number_format($latest['debt_to_equity'], 2) . ')';
        if ($latest['asset_turnover'] !== null && $latest['asset_turnover'] >= 1) $strengths[] = 'High asset turnover (' . number_format($latest['asset_turnover'], 1) . '×)';
        if ($latest['roe'] !== null && $latest['roe'] >= 15) $strengths[] = 'Strong return on equity (' . number_format($latest['roe'], 1) . '%)';
        if ($registry && preg_match('/^active/i', (string) $registry->company_status)) $strengths[] = 'Company status is Active';

        $label = match (true) { $total >= 80 => 'Excellent', $total >= 65 => 'Very good', $total >= 50 => 'Good', $total >= 35 => 'Fair', default => 'Poor' };
        return ['score' => $total, 'label' => $label, 'year' => $latest['year'], 'breakdown' => $breakdown, 'risks' => $risks, 'strengths' => $strengths];
    }

    /**
     * Statutory filing calendar after the last balance sheet: AGM within six months of year end (30 Sep for a
     * March year end), AOC-4 within 30 days of the AGM, MGT-7 within 60 days of the AGM.
     */
    public function compliance(?\App\Models\CompanyRegistryDetail $registry): array
    {
        if (!$registry?->last_balance_sheet_date && !$registry?->last_agm_date) return [];
        $yearEnd = ($registry->last_balance_sheet_date ?? $registry->last_agm_date->copy()->subMonths(6)->endOfMonth())->copy();
        $nextYearEnd = $yearEnd->copy()->addYear();
        $agmDue = $nextYearEnd->copy()->addMonthsNoOverflow(6)->endOfMonth();
        $agmHeld = $registry->last_agm_date && $registry->last_agm_date->gt($nextYearEnd);
        $base = $agmHeld ? $registry->last_agm_date : $agmDue;
        $items = [
            ['label' => 'AGM for FY ' . FilingParser::financialYear($nextYearEnd), 'due' => $agmDue, 'done' => $agmHeld],
            ['label' => 'AOC-4 for FY ' . FilingParser::financialYear($nextYearEnd), 'due' => $base->copy()->addDays(30), 'done' => false],
            ['label' => 'MGT-7 for FY ' . FilingParser::financialYear($nextYearEnd), 'due' => $base->copy()->addDays(60), 'done' => false],
        ];
        return array_map(fn ($i) => $i + ['overdue' => !$i['done'] && $i['due']->isPast(), 'days' => (int) now()->startOfDay()->diffInDays($i['due'], false)], $items);
    }

    /** Companies in the same sub-category with filed financials, compared on their latest year (largest revenue first). */
    public function peers(Company $company, int $limit = 6): Collection
    {
        if (!$company->sector) return collect();
        $rows = Company::where('sector', $company->sector)->where('is_active', true)
            ->whereHas('financialStatements', fn ($q) => $q->whereNotNull('revenue'))
            ->with(['financialStatements' => fn ($q) => $q->where('is_consolidated', false)])
            ->limit(60)->get();
        if (!$rows->contains('id', $company->id)) $rows->push($company->loadMissing('financialStatements'));
        return $rows->map(function (Company $peer) {
            $latest = $this->ratios($peer->financialStatements)->last();
            return $latest ? (object) ['company' => $peer, 'year' => $latest['year'], 'revenue' => $latest['revenue'], 'pat' => $latest['pat'],
                'net_margin' => $latest['net_margin'], 'roe' => $latest['roe'], 'roce' => $latest['roce'], 'debt_to_equity' => $latest['debt_to_equity'],
                'revenue_growth' => $latest['revenue_growth']] : null;
        })->filter()->sortByDesc('revenue')->take($limit)->values();
    }

    /** "₹1,234.5 Cr" style figure for rupee amounts. */
    public static function money($value): string
    {
        if ($value === null) return '—';
        $abs = abs((float) $value);
        $sign = $value < 0 ? '−' : '';
        if ($abs >= 1e7) return $sign . '₹' . number_format($abs / 1e7, 2) . ' Cr';
        if ($abs >= 1e5) return $sign . '₹' . number_format($abs / 1e5, 2) . ' L';
        return $sign . '₹' . number_format($abs, 0);
    }
}
