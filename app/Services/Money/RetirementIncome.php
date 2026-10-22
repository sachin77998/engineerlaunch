<?php

namespace App\Services\Money;

use Illuminate\Support\Facades\DB;

// Turns a retirement corpus into a monthly income: sovereign schemes first (SCSS, POMIS), an annuity slice
// for lifelong income, and a systematic withdrawal plan from a hybrid fund for the rest. Also sizes insurance.
class RetirementIncome
{
    public function __construct(private TaxEngine $tax) {}

    public function plan(float $corpus, array $p, ?float $targetMonthly = null): array
    {
        $prod = config('money.income_products');
        $age = max(60, (int) $p['retirement_age']);
        $couple = collect($p['family'] ?? [])->contains('relation', 'spouse');
        $people = $couple ? 2 : 1;
        $target = $targetMonthly ?? (float) $p['target_monthly_income'];
        $left = $corpus; $buckets = [];
        $take = function (string $key, float $want) use (&$left, &$buckets, $prod) {
            $amt = max(0, min($left, $want));
            if ($amt < 1000) return;
            $rate = $key === 'swp' ? $prod['swp']['withdrawal'] : $prod[$key]['rate'];
            $buckets[] = ['key' => $key, 'label' => $prod[$key]['label'], 'amount' => round($amt), 'rate' => $rate, 'monthly' => round($amt * $rate / 100 / 12),
                'payout' => $prod[$key]['payout'], 'tax' => $prod[$key]['tax'], 'risk' => $prod[$key]['risk']];
            $left -= $amt;
        };
        // Keep 2 years of expenses liquid before locking money in.
        $emergency = min($left, max(0, (float) $p['monthly_expenses']) * 24);
        $left -= $emergency;
        $take('scss', $prod['scss']['max_per_person'] * $people);
        $take('pomis', $couple ? $prod['pomis']['max_joint'] : $prod['pomis']['max_per_person']);
        // Conservative investors hold more in annuity; aggressive ones keep more in SWP for growth.
        $annuityShare = ['conservative' => 0.45, 'moderate' => 0.3, 'aggressive' => 0.15][$p['risk']] ?? 0.3;
        $take('annuity', $left * $annuityShare);
        $take('rbi_frsb', $left * ($p['risk'] === 'conservative' ? 0.3 : 0.15));
        $take('swp', $left);
        $monthly = collect($buckets)->sum('monthly');
        // Annual tax on this income at 60+ under the new regime: interest and annuity at slab, SWP gains (~35% of withdrawals) as equity LTCG.
        $slabIncome = collect($buckets)->whereNotIn('key', ['swp'])->sum('monthly') * 12;
        $swpGain = collect($buckets)->where('key', 'swp')->sum('monthly') * 12 * 0.35;
        $tax = $this->tax->compute(['other_income' => $slabIncome, 'ltcg_equity' => $swpGain, 'age' => $age, 'regime' => 'new']);
        return [
            'corpus' => round($corpus), 'emergency' => round($emergency), 'buckets' => $buckets, 'monthly' => $monthly, 'target' => round($target),
            'shortfall' => max(0, round($target - $monthly)), 'annual_tax' => $tax['total'], 'monthly_after_tax' => round($monthly - $tax['total'] / 12),
            'couple' => $couple, 'plans' => config('money.plans'), 'insurers' => $this->insurers(),
        ];
    }

    /** Life, health and pension cover the person should hold now. */
    public function protection(array $p): array
    {
        $annual = $p['monthly_salary'] * 12;
        $years = max(0, $p['retirement_age'] - $p['age']);
        // Income replacement until retirement (capped at 15x), plus loans, minus cover already held.
        $multiple = min(15, max(10, $years / 2));
        $term = max(0, round(($annual * $multiple + $p['liabilities'] - $p['existing_life_cover']) / 100000) * 100000);
        $health = ($p['city_tier'] === 'metro' ? 1000000 : 500000) * (count($p['family']) > 1 ? 1.5 : 1);
        $termPremium = $this->termPremium($p['age'], $term);
        $mixed = collect($p['investments'])->filter(fn ($i) => in_array($i['type'], ['endowment', 'ulip'], true))->sum('monthly');
        return [
            'term_cover' => $term, 'term_premium_month' => $termPremium, 'term_years' => $years, 'health_cover' => $health,
            'health_premium_month' => round($this->healthPremium($p['age'], $health) / 12),
            'mixed_premium' => $mixed, 'freed_if_term' => max(0, $mixed - $termPremium),
        ];
    }

    // Indicative pure-term premium per month for a non-smoker (₹ per lakh of cover rises with age).
    private function termPremium(int $age, float $cover): int
    {
        $perLakh = $age < 30 ? 7 : ($age < 35 ? 9 : ($age < 40 ? 13 : ($age < 45 ? 20 : ($age < 50 ? 32 : 55))));
        return (int) round($cover / 100000 * $perLakh * 12 / 12);
    }

    private function healthPremium(int $age, float $cover): int
    {
        $base = $age < 35 ? 9000 : ($age < 45 ? 13000 : ($age < 55 ? 20000 : ($age < 65 ? 32000 : 50000)));
        return (int) round($base * $cover / 1000000);
    }

    private function insurers(): array
    {
        return rescue(fn () => DB::table('insurers')->where('is_active', true)->whereNotNull('claim_settlement_ratio')
            ->orderByDesc('claim_settlement_ratio')->limit(12)->get(['name', 'sector', 'claim_settlement_ratio', 'solvency_ratio', 'website'])
            ->groupBy('sector')->map(fn ($g) => $g->take(4)->values())->all(), [], false);
    }
}
