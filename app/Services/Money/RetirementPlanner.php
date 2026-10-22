<?php

namespace App\Services\Money;

// Projects every monthly investment to the retirement age, then checks whether the corpus can pay the
// monthly income the person wants (rising with inflation until life expectancy).
class RetirementPlanner
{
    public function plan(array $p): array
    {
        $p = Profile::normalize($p);
        $age = $p['age']; $ret = max($age + 1, $p['retirement_age']); $life = max($ret + 1, $p['life_expectancy']);
        $months = ($ret - $age) * 12;
        $inflation = $p['inflation'] / 100;
        $rows = [];
        $series = array_fill(0, $ret - $age + 1, 0.0);
        foreach ($p['investments'] as $inv) {
            $def = config('money.instruments.' . $inv['type']) ?? ['label' => ucfirst($inv['type']), 'return' => 6];
            $rate = $inv['return'] ?? $def['return'];
            $step = $inv['step_up'] ?? $p['step_up'];
            [$fv, $paid, $yearly] = $this->grow((float) $inv['monthly'], (float) $rate, (float) $step, $months);
            foreach ($yearly as $y => $v) $series[$y + 1] += $v;
            $rows[] = ['type' => $inv['type'], 'label' => $inv['label'] ?? $def['label'], 'monthly' => (float) $inv['monthly'], 'return' => (float) $rate, 'step_up' => (float) $step,
                'invested' => round($paid), 'corpus' => round($fv), 'gain' => round($fv - $paid), 'protection' => in_array($inv['type'], ['term', 'health'], true)];
        }
        // Money already saved grows at the post-retirement (balanced) rate.
        $existing = $p['current_savings'] * (1 + $p['post_retirement_return'] / 100) ** ($ret - $age);
        foreach ($series as $y => $v) $series[$y] = $v + $p['current_savings'] * (1 + $p['post_retirement_return'] / 100) ** $y;
        $corpus = collect($rows)->sum('corpus') + $existing;
        $invested = collect($rows)->sum('invested') + $p['current_savings'];
        $monthlyTotal = collect($rows)->sum('monthly');

        $target = $p['target_in_todays_money'] ? $p['target_monthly_income'] * (1 + $inflation) ** ($ret - $age) : $p['target_monthly_income'];
        $factor = $this->annuityFactor($p['post_retirement_return'], $p['inflation'], ($life - $ret) * 12);
        $required = $target * $factor;
        $income = $factor > 0 ? $corpus / $factor : 0;
        $gap = max(0, $required - $corpus);
        [$perRupee] = $this->grow(1, (float) config('money.instruments.equity_mf.return'), (float) $p['step_up'], $months);
        $extraSip = $gap > 0 && $perRupee > 0 ? ceil($gap / $perRupee / 100) * 100 : 0;

        // What the money in low-return products would have become in an equity fund.
        $switch = collect($rows)->filter(fn ($r) => in_array($r['type'], ['endowment', 'ulip', 'rd', 'fd'], true) && $r['monthly'] > 0)->map(function ($r) use ($months, $p) {
            [$fv] = $this->grow($r['monthly'], (float) config('money.instruments.equity_mf.return'), $r['step_up'], $months);
            return ['label' => $r['label'], 'monthly' => $r['monthly'], 'now' => $r['corpus'], 'in_equity' => round($fv), 'difference' => round($fv - $r['corpus'])];
        })->values()->all();

        return [
            'profile' => $p, 'years' => $ret - $age, 'retirement_years' => $life - $ret, 'rows' => $rows, 'existing' => round($existing),
            'corpus' => round($corpus), 'corpus_today' => round($corpus / (1 + $inflation) ** ($ret - $age)), 'invested' => round($invested), 'gain' => round($corpus - $invested),
            'monthly_total' => $monthlyTotal, 'savings_rate' => $p['monthly_salary'] > 0 ? round($monthlyTotal / $p['monthly_salary'] * 100, 1) : null,
            'target_monthly' => round($target), 'target_today' => round($target / (1 + $inflation) ** ($ret - $age)), 'required' => round($required),
            'income' => round($income), 'income_today' => round($income / (1 + $inflation) ** ($ret - $age)), 'gap' => round($gap), 'extra_sip' => $extraSip,
            'on_track' => $gap <= 0, 'series' => collect($series)->map(fn ($v, $y) => ['age' => $age + $y, 'corpus' => round($v)])->values()->all(),
            'drawdown' => $this->drawdown($corpus, $income, $p, $ret, $life), 'switch' => $switch,
            'allocation' => $this->allocation($age, $p['risk']),
            'safety' => ['emergency_fund' => $p['monthly_expenses'] * 6, 'term_cover' => max(0, $p['monthly_salary'] * 12 * 15 + $p['liabilities'] - $p['existing_life_cover']),
                'health_cover' => $p['city_tier'] === 'metro' ? 1000000 : 500000],
        ];
    }

    /** Future value of a monthly SIP with a yearly step-up; returns [fv, total paid, value at end of each year]. */
    public function grow(float $monthly, float $annualRate, float $stepUp, int $months): array
    {
        $r = (1 + $annualRate / 100) ** (1 / 12) - 1;
        $fv = 0; $paid = 0; $amt = $monthly; $yearly = [];
        for ($m = 1; $m <= $months; $m++) {
            $fv = ($fv + $amt) * (1 + $r);
            $paid += $amt;
            if ($m % 12 === 0) { $yearly[] = $fv; $amt *= 1 + $stepUp / 100; }
        }
        return [$fv, $paid, $yearly];
    }

    /** Corpus needed per ₹1 of first-month income that rises with inflation for n months (paid at month start). */
    public function annuityFactor(float $returnPct, float $inflationPct, int $n): float
    {
        $r = (1 + $returnPct / 100) ** (1 / 12) - 1;
        $g = (1 + $inflationPct / 100) ** (1 / 12) - 1;
        if ($n <= 0) return 0;
        if (abs($r - $g) < 1e-9) return $n;
        return (1 - ((1 + $g) / (1 + $r)) ** $n) / ($r - $g) * (1 + $r);
    }

    // Year-by-year balance after retirement while withdrawing the sustainable income.
    private function drawdown(float $corpus, float $income, array $p, int $ret, int $life): array
    {
        $out = []; $bal = $corpus; $w = $income * 12;
        for ($a = $ret; $a <= $life; $a++) {
            $out[] = ['age' => $a, 'balance' => round(max(0, $bal)), 'withdrawal' => round($w)];
            $bal = ($bal - $w) * (1 + $p['post_retirement_return'] / 100);
            $w *= 1 + $p['inflation'] / 100;
        }
        return $out;
    }

    // Age- and risk-based mix: equity share falls as retirement approaches.
    public function allocation(int $age, string $risk): array
    {
        $equity = max(20, min(85, 100 - $age + (['conservative' => -15, 'moderate' => 0, 'aggressive' => 10][$risk] ?? 0)));
        $gold = 10;
        $debt = 100 - $equity - $gold;
        return ['equity' => $equity, 'debt' => $debt, 'gold' => $gold];
    }
}
