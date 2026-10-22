<?php

namespace App\Services\Money;

use Illuminate\Support\Facades\DB;

// Builds a personal fund mix from the profile (age, risk, horizon) and fills each slot with the funds the model ranks highest.
class FundAdvisor
{
    public const SIGNALS = ['buy' => 'Good time to add', 'hold' => 'Continue SIP', 'watch' => 'Watch', 'book_profit' => 'Book partial profit', 'exit' => 'Review / exit', 'pending' => 'Analysing'];

    public function __construct(private RetirementPlanner $planner) {}

    public function recommend(array $p, ?float $monthly = null): array
    {
        $years = max(1, $p['retirement_age'] - $p['age']);
        $mix = $this->planner->allocation($p['age'], $p['risk']);
        $monthly ??= (float) collect($p['investments'])->whereIn('type', ['equity_mf', 'hybrid_mf', 'debt_mf', 'elss'])->sum('monthly') ?: 5000;
        // Short horizons cannot ride out equity falls, so move equity into balanced funds.
        if ($years < 5) { $mix['debt'] += (int) round($mix['equity'] / 2); $mix['equity'] -= (int) round($mix['equity'] / 2); }
        $equitySplit = ['conservative' => ['Large Cap' => 50, 'Flexi Cap' => 30, 'Mid Cap' => 20], 'moderate' => ['Large Cap' => 35, 'Flexi Cap' => 30, 'Mid Cap' => 20, 'Small Cap' => 15],
            'aggressive' => ['Large Cap' => 25, 'Flexi Cap' => 25, 'Mid Cap' => 25, 'Small Cap' => 25]][$p['risk']];
        if ($p['regime'] === 'old') { $equitySplit['ELSS'] = 20; $equitySplit['Flexi Cap'] = max(0, $equitySplit['Flexi Cap'] - 20); }
        $slots = [];
        foreach ($equitySplit as $cat => $w) if ($w > 0) $slots[] = ['bucket' => 'Equity', 'category' => $cat, 'weight' => $mix['equity'] * $w / 100];
        $debtSplit = $years < 3 ? ['Liquid' => 40, 'Short Duration' => 60] : ['Balanced Advantage' => 40, 'Corporate Bond' => 35, 'Short Duration' => 25];
        foreach ($debtSplit as $cat => $w) $slots[] = ['bucket' => 'Debt & hybrid', 'category' => $cat, 'weight' => $mix['debt'] * $w / 100];
        $slots[] = ['bucket' => 'Gold', 'category' => 'Gold', 'weight' => $mix['gold']];
        foreach ($slots as &$s) {
            $s['monthly'] = (int) (round($monthly * $s['weight'] / 100 / 100) * 100);
            $s['funds'] = $this->top($s['category'], 2);
        }
        return ['mix' => $mix, 'monthly' => $monthly, 'slots' => array_values(array_filter($slots, fn ($s) => $s['weight'] > 0)), 'model' => app(FundModel::class)->model()];
    }

    /** Highest model-ranked Direct-Growth funds in a category (falls back to 3-year return before the model runs). */
    public function top(string $category, int $limit = 3)
    {
        $like = ['Large Cap' => '%Large Cap Fund%', 'Flexi Cap' => '%Flexi Cap%', 'Mid Cap' => '%Mid Cap Fund%', 'Small Cap' => '%Small Cap%', 'ELSS' => '%ELSS%',
            'Liquid' => '%Liquid%', 'Short Duration' => '%Short Duration%', 'Corporate Bond' => '%Corporate Bond%', 'Balanced Advantage' => '%Balanced Advantage%', 'Gold' => '%Gold%'][$category] ?? '%' . $category . '%';
        return DB::table('mf_schemes as m')->leftJoin('mf_model_scores as s', 's.scheme_code', '=', 'm.scheme_code')
            ->where('m.is_active', true)->where('m.plan', 'Direct')->where('m.option', 'Growth')->where('m.category', 'like', $like)
            ->when($category !== 'Gold', fn ($q) => $q->where('m.asset_class', '!=', 'ETF'))->when($category === 'Large Cap', fn ($q) => $q->where('m.category', 'not like', '%Large & Mid%'))
            ->whereNotNull('m.return_1y')
            ->orderByRaw('s.score IS NULL')->orderByDesc('s.score')->orderByRaw('m.return_3y IS NULL')->orderByDesc('m.return_3y')->limit($limit)
            ->get(['m.id', 'm.scheme_code', 'm.name', 'm.amc', 'm.category', 'm.return_1y', 'm.return_3y', 'm.return_5y', 'm.nav', 'm.risk_level', 's.score', 's.signal', 's.reasons']);
    }

    /** Signals for funds the person holds (Do Trading holdings), with tax-aware notes for exits. */
    public function holdings(?int $userId)
    {
        if (!$userId) return collect();
        return DB::table('mf_holdings as h')->join('trading_clients as c', 'c.id', '=', 'h.trading_client_id')->where('c.user_id', $userId)->where('h.units', '>', 0)
            ->join('mf_schemes as m', 'm.scheme_code', '=', 'h.scheme_code')->leftJoin('mf_model_scores as s', 's.scheme_code', '=', 'h.scheme_code')
            ->get(['m.id', 'm.scheme_code', 'm.name', 'm.amc', 'm.category', 'm.nav', 'h.units', 'h.invested', 'h.created_at', 's.score', 's.signal', 's.reasons'])
            ->map(function ($h) {
                $h->value = round($h->units * $h->nav);
                $h->gain = $h->value - $h->invested;
                $months = (int) now()->diffInMonths($h->created_at);
                $h->tax_note = in_array($h->signal, ['exit', 'book_profit'], true)
                    ? ($months < 12 ? 'Held under 12 months: gain taxed at 20% (STCG); waiting until ' . \Carbon\Carbon::parse($h->created_at)->addYear()->format('M Y') . ' makes it long-term.' : 'Long-term: first ₹1.25 lakh of gains each year is tax-free.')
                    : null;
                return $h;
            });
    }
}
