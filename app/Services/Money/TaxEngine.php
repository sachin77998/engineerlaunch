<?php

namespace App\Services\Money;

// Income-tax for a resident individual under the new or old regime (rules in config/money.php).
// Handles slab income, equity STCG (111A) / LTCG (112A) and other LTCG (112), the unused basic exemption
// against capital gains, rebate u/s 87A, surcharge (capped at 15% on capital gains) and 4% cess.
// Marginal relief on surcharge is not applied, so results near ₹50 lakh / ₹1 crore can be slightly high.
class TaxEngine
{
    public function compute(array $in): array
    {
        $regime = ($in['regime'] ?? 'new') === 'old' ? 'old' : 'new';
        $rules = config('money.tax.' . $regime);
        $age = (int) ($in['age'] ?? 30);
        $salary = max(0, (float) ($in['salary'] ?? 0));
        $other = max(0, (float) ($in['other_income'] ?? 0));
        $deductions = $regime === 'old' ? max(0, (float) ($in['deductions'] ?? 0)) : max(0, (float) ($in['deductions_new'] ?? 0));
        $std = $salary > 0 ? min($salary, $rules['standard_deduction']) : 0;
        $slabIncome = max(0, $salary - $std + $other - $deductions);
        $stcg = max(0, (float) ($in['stcg_equity'] ?? 0));
        $ltcgEqGross = max(0, (float) ($in['ltcg_equity'] ?? 0));
        $exemption = min($ltcgEqGross, (float) config('money.tax.capital_gains.equity.ltcg_exemption'));
        $ltcgEq = $ltcgEqGross - $exemption;
        $ltcgOther = max(0, (float) ($in['ltcg_other'] ?? 0));

        $slabs = $regime === 'old' ? ($age >= 80 ? config('money.tax.old.slabs_super_senior') : ($age >= 60 ? config('money.tax.old.slabs_senior') : $rules['slabs'])) : $rules['slabs'];
        $nil = (float) $slabs[0][0];
        // A resident may use the unused part of the nil slab against capital gains taxed at special rates.
        $shortfall = max(0, $nil - $slabIncome);
        [$stcgT, $shortfall] = $this->absorb($stcg, $shortfall);
        [$ltcgEqT, $shortfall] = $this->absorb($ltcgEq, $shortfall);
        [$ltcgOtherT, $shortfall] = $this->absorb($ltcgOther, $shortfall);

        $slabTax = $this->slabTax($slabIncome, $slabs);
        $cg = config('money.tax.capital_gains');
        $stcgTax = $stcgT * $cg['equity']['stcg_rate'] / 100;
        $ltcgEqTax = $ltcgEqT * $cg['equity']['ltcg_rate'] / 100;
        $ltcgOtherTax = $ltcgOtherT * $cg['property']['ltcg_rate'] / 100;

        $totalIncome = $slabIncome + $stcg + $ltcgEqGross + $ltcgOther;
        $rebate = 0;
        if ($totalIncome - $exemption <= $rules['rebate_limit']) {
            // 87A is not available against 112A tax; in the new regime it is limited to slab-rate tax.
            $eligible = $slabTax + ($regime === 'old' ? $stcgTax : 0);
            $rebate = min($eligible, $rules['rebate_max']);
        }
        $beforeSurcharge = max(0, $slabTax + $stcgTax + $ltcgEqTax + $ltcgOtherTax - $rebate);
        $rate = 0;
        foreach ($rules['surcharge'] as [$limit, $pct]) if ($totalIncome > $limit) $rate = $pct;
        $cgTax = $stcgTax + $ltcgEqTax + $ltcgOtherTax;
        $surcharge = max(0, ($beforeSurcharge - $cgTax)) * $rate / 100 + $cgTax * min($rate, config('money.tax.capital_gains_surcharge_cap')) / 100;
        $cess = ($beforeSurcharge + $surcharge) * config('money.tax.cess') / 100;
        $total = round($beforeSurcharge + $surcharge + $cess);
        return [
            'regime' => $regime, 'taxable_slab_income' => round($slabIncome), 'standard_deduction' => $std, 'deductions' => $deductions,
            'slab_tax' => round($slabTax), 'stcg_tax' => round($stcgTax), 'ltcg_equity_tax' => round($ltcgEqTax), 'ltcg_other_tax' => round($ltcgOtherTax),
            'ltcg_exemption_used' => round($exemption), 'rebate' => round($rebate), 'surcharge' => round($surcharge), 'cess' => round($cess),
            'total' => $total, 'total_income' => round($totalIncome), 'effective_rate' => $totalIncome > 0 ? round($total / $totalIncome * 100, 2) : 0,
        ];
    }

    // Tax that a given extra income adds on top of what the person already earns.
    public function marginal(array $base, array $extra): int
    {
        $with = $base;
        foreach ($extra as $k => $v) $with[$k] = ($with[$k] ?? 0) + $v;
        return (int) ($this->compute($with)['total'] - $this->compute($base)['total']);
    }

    public function slabTax(float $income, array $slabs): float
    {
        $tax = 0; $prev = 0;
        foreach ($slabs as [$upto, $pct]) {
            $top = $upto === null ? $income : min($income, (float) $upto);
            if ($top > $prev) $tax += ($top - $prev) * $pct / 100;
            if ($upto === null || $income <= $upto) break;
            $prev = (float) $upto;
        }
        return $tax;
    }

    private function absorb(float $amount, float $shortfall): array
    {
        $used = min($amount, $shortfall);
        return [$amount - $used, $shortfall - $used];
    }
}
