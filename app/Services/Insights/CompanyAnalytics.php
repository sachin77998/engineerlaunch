<?php

namespace App\Services\Insights;

use App\Models\Company;
use Illuminate\Support\Collection;

/**
 * Chart-ready series for a company page, read from the company tables (financial statements, charges, officers,
 * shareholdings, related parties): year-on-year changes, CAGR, material consumption with a next-year forecast,
 * supplier payment days, borrowing history by lender and board tenure.
 */
class CompanyAnalytics
{
    private const CRORE = 10000000;

    public function build(Company $company): array
    {
        $statements = $this->statements($company);
        return [
            'financial' => $this->financial($statements),
            'materials' => $this->materials($statements),
            'payments' => $this->payments($statements),
            'charges' => $this->charges($company),
            'board' => $this->board($company),
            'ownership' => $this->ownership($company),
            'concentration' => $this->concentration($company, $statements),
        ];
    }

    /** One row per financial year, standalone preferred; oldest first, at most 10 years. */
    private function statements(Company $company): Collection
    {
        return $company->financialStatements->groupBy('financial_year')
            ->map(fn ($rows) => $rows->firstWhere('is_consolidated', false) ?? $rows->first())
            ->sortKeys()->take(-10);
    }

    public static function change(?float $now, ?float $before): ?float
    {
        return $now === null || $before === null || $before == 0.0 ? null : ($now - $before) / abs($before) * 100;
    }

    public static function cagr(?float $end, ?float $start, int $years): ?float
    {
        return $end > 0 && $start > 0 && $years > 0 ? (pow($end / $start, 1 / $years) - 1) * 100 : null;
    }

    private static function cr($value): ?float
    {
        return $value === null ? null : round((float) $value / self::CRORE, 2);
    }

    private function financial(Collection $statements): array
    {
        $metrics = ['revenue' => 'Revenue', 'ebitda' => 'EBITDA', 'profit_after_tax' => 'Profit after tax', 'net_worth' => 'Net worth',
            'total_assets' => 'Total assets', 'total_debt' => 'Total debt'];
        $years = $statements->keys()->values();
        $series = [];
        foreach ($metrics as $field => $label) {
            $values = $statements->map(fn ($s) => $s->{$field} === null ? null : (float) $s->{$field})->values();
            $yoy = $values->map(fn ($v, $i) => $i ? self::change($v, $values[$i - 1]) : null);
            $series[$field] = ['label' => $label, 'values' => $values, 'crore' => $values->map(fn ($v) => self::cr($v)), 'yoy' => $yoy,
                'latest' => $values->last(), 'latest_yoy' => $yoy->last(),
                'cagr3' => $values->count() > 3 ? self::cagr($values->last(), $values[$values->count() - 4], 3) : null,
                'cagr5' => $values->count() > 5 ? self::cagr($values->last(), $values[$values->count() - 6], 5) : null];
        }
        $margin = fn ($num) => $statements->map(fn ($s) => $s->revenue > 0 && $s->{$num} !== null ? round($s->{$num} / $s->revenue * 100, 2) : null)->values();
        return ['years' => $years, 'series' => $series, 'ebitda_margin' => $margin('ebitda'), 'pat_margin' => $margin('profit_after_tax')];
    }

    /** Raw materials consumed plus traded goods bought, their share of revenue, and a least-squares forecast for next year. */
    private function materials(Collection $statements): array
    {
        $rows = $statements->filter(fn ($s) => $s->cost_of_materials_consumed !== null || $s->purchases_of_stock_in_trade !== null);
        $years = $rows->keys()->values();
        $consumed = $rows->map(fn ($s) => (float) $s->cost_of_materials_consumed + (float) $s->purchases_of_stock_in_trade)->values();
        $intensity = $rows->map(fn ($s) => $s->revenue > 0 ? round(((float) $s->cost_of_materials_consumed + (float) $s->purchases_of_stock_in_trade) / $s->revenue * 100, 2) : null)->values();
        $forecast = $this->trend($consumed->take(-5)->values());
        $nextYear = $years->isNotEmpty() && preg_match('/^(\d{4})-(\d{2})$/', $years->last(), $m) ? ($m[1] + 1) . '-' . str_pad((string) (($m[2] + 1) % 100), 2, '0', STR_PAD_LEFT) : null;
        return ['years' => $years, 'consumed' => $consumed, 'crore' => $consumed->map(fn ($v) => self::cr($v)), 'intensity' => $intensity,
            'raw' => $rows->map(fn ($s) => self::cr($s->cost_of_materials_consumed))->values(), 'traded' => $rows->map(fn ($s) => self::cr($s->purchases_of_stock_in_trade))->values(),
            'latest_yoy' => $consumed->count() > 1 ? self::change($consumed->last(), $consumed[$consumed->count() - 2]) : null,
            'forecast' => $forecast !== null ? max(0, $forecast) : null, 'forecast_year' => $nextYear,
            'forecast_change' => $forecast !== null ? self::change(max(0, $forecast), $consumed->last()) : null];
    }

    /** Linear regression on the last points, evaluated one period ahead. */
    private function trend(Collection $values): ?float
    {
        $n = $values->count();
        if ($n < 3) return null;
        $xMean = ($n - 1) / 2;
        $yMean = $values->avg();
        $num = 0.0; $den = 0.0;
        foreach ($values as $x => $y) { $num += ($x - $xMean) * ($y - $yMean); $den += ($x - $xMean) ** 2; }
        return $den ? $yMean + ($num / $den) * ($n - $xMean) : null;
    }

    /** Days payable (how fast it pays suppliers) and days receivable (how fast customers pay it). */
    private function payments(Collection $statements): array
    {
        $years = $statements->keys()->values();
        $dpo = $statements->map(function ($s) {
            $purchases = (float) $s->cost_of_materials_consumed + (float) $s->purchases_of_stock_in_trade + (float) $s->other_expenses;
            $base = $purchases > 0 ? $purchases : ($s->total_expenses !== null ? (float) $s->total_expenses - (float) $s->employee_cost - (float) $s->finance_cost - (float) $s->depreciation : 0);
            return $s->trade_payables !== null && $base > 0 ? round($s->trade_payables / $base * 365) : null;
        })->values();
        $dso = $statements->map(fn ($s) => $s->trade_receivables !== null && $s->revenue > 0 ? round($s->trade_receivables / $s->revenue * 365) : null)->values();
        $latest = $dpo->filter(fn ($v) => $v !== null)->last();
        $band = $latest === null ? null : ($latest <= 45 ? ['Pays on time', 'up'] : ($latest <= 90 ? ['Moderate delays', ''] : ['Slow payer', 'down']));
        return ['years' => $years, 'dpo' => $dpo, 'dso' => $dso, 'latest_dpo' => $latest, 'band' => $band];
    }

    private function charges(Company $company): array
    {
        $charges = $company->charges->sortBy('created_on')->values();
        $closed = fn ($c) => (bool) $c->satisfied_on || preg_match('/satisf|closed/i', (string) $c->status);
        $open = $charges->reject($closed);
        $done = $charges->filter($closed);
        $timeline = [];
        foreach ($charges as $c) {
            if ($c->created_on) $timeline[$c->created_on->year]['new'] = ($timeline[$c->created_on->year]['new'] ?? 0) + 1;
            if ($c->satisfied_on) $timeline[$c->satisfied_on->year]['closed'] = ($timeline[$c->satisfied_on->year]['closed'] ?? 0) + 1;
        }
        ksort($timeline);
        $lenders = $charges->groupBy(fn ($c) => trim((string) $c->lender) ?: 'Unknown')->map(fn ($rows, $name) => [
            'name' => $name, 'count' => $rows->count(), 'taken' => self::cr($rows->sum('amount')),
            'closed' => self::cr($rows->filter($closed)->sum('amount')), 'active' => self::cr($rows->reject($closed)->sum('amount')),
            'open_count' => $rows->reject($closed)->count(),
        ])->sortByDesc('taken')->values();
        return ['list' => $charges, 'total' => $charges->count(), 'open' => $open->count(), 'closed' => $done->count(),
            'open_amount' => $open->sum('amount'), 'closed_amount' => $done->sum('amount'), 'lenders' => $lenders,
            'timeline' => ['years' => array_keys($timeline), 'new' => array_map(fn ($y) => $y['new'] ?? 0, array_values($timeline)), 'closed' => array_map(fn ($y) => $y['closed'] ?? 0, array_values($timeline))]];
    }

    /** Board members as tenure bars (appointed → ceased or today). */
    private function board(Company $company): array
    {
        $rows = $company->officers->filter(fn ($o) => $o->appointed_on)->sortBy('appointed_on')->values()->map(fn ($o) => [
            'name' => $o->name, 'designation' => $o->designation, 'din' => $o->din,
            'from' => round($o->appointed_on->year + ($o->appointed_on->dayOfYear - 1) / 365, 2),
            'to' => round(($o->ceased_on ?? now())->year + (($o->ceased_on ?? now())->dayOfYear - 1) / 365, 2),
            'active' => !$o->ceased_on || $o->ceased_on->isFuture()]);
        return ['rows' => $rows->take(-25)->values()];
    }

    private function ownership(Company $company): array
    {
        $years = $company->shareholdings->filter(fn ($h) => preg_match('/^\d{4}-\d{2}$/', (string) $h->financial_year))->groupBy('financial_year')->sortKeys()->take(-6);
        $pick = fn ($rows, string $pattern) => $rows->first(fn ($r) => preg_match($pattern, strtolower((string) $r->category)))?->percentage;
        $promoter = $years->map(fn ($rows) => $pick($rows, '/^promoter/') === null ? null : (float) $pick($rows, '/^promoter/'))->values();
        $public = $years->map(fn ($rows) => $pick($rows, '/^public/') === null ? null : (float) $pick($rows, '/^public/'))->values();
        return ['years' => $years->keys()->values(), 'promoter' => $promoter, 'public' => $public,
            'latest' => ['promoter' => $promoter->last(), 'public' => $public->last()]];
    }

    /** Sales to related parties as a share of revenue, by party, for the latest year with disclosures. */
    private function concentration(Company $company, Collection $statements): array
    {
        $parties = $company->relatedParties ?? collect();
        $sales = $parties->filter(fn ($p) => $p->amount > 0 && preg_match('/sale|revenue|income from|services rendered|supply of/i', (string) $p->transaction_description));
        $year = $sales->max('financial_year');
        $revenue = $year ? $statements->get($year)?->revenue : null;
        $rows = $sales->where('financial_year', $year)->groupBy('party_name')->map(fn ($r, $name) => [
            'name' => $name, 'amount' => self::cr($r->sum('amount')), 'share' => $revenue > 0 ? round($r->sum('amount') / $revenue * 100, 2) : null,
        ])->sortByDesc('amount')->values()->take(10);
        return ['year' => $year, 'rows' => $rows, 'top_share' => $rows->sum('share') ?: null];
    }
}
