<?php

namespace App\Services\Money;

// Compares legal ways to reduce tax on a capital gain, and on salary through deductions.
// Family transfers follow the clubbing rules: gifts to a spouse or minor child are taxed back in the giver's hands
// (s.64(1)(iv), s.64(1A)); gifts to adult children, parents and siblings are tax-free gifts (s.56(2)(x)) and the gain
// is theirs when they sell, with the original cost and holding period carried over (s.49, s.2(42A)).
class TaxPlanner
{
    public function __construct(private TaxEngine $tax) {}

    /**
     * @param array $in asset (equity|debt|property|gold|fd), gain, holding_months, salary, other_income, age, regime,
     *                  years (spread over), family [{relation, name, income, age}], reinvest_house (bool)
     */
    public function capitalGains(array $in): array
    {
        $asset = $in['asset'] ?? 'equity';
        $rule = config('money.tax.capital_gains.' . $asset) ?? config('money.tax.capital_gains.equity');
        $gain = max(0, (float) ($in['gain'] ?? 0));
        $months = (int) ($in['holding_months'] ?? 36);
        $long = $rule['ltcg_months'] !== null && $months >= $rule['ltcg_months'];
        $base = ['salary' => (float) ($in['salary'] ?? 0), 'other_income' => (float) ($in['other_income'] ?? 0), 'age' => (int) ($in['age'] ?? 50), 'regime' => $in['regime'] ?? 'new', 'deductions' => (float) ($in['deductions'] ?? 0)];
        $component = fn (float $g) => match (true) {
            $asset === 'equity' && $long => ['ltcg_equity' => $g],
            $asset === 'equity' => ['stcg_equity' => $g],
            in_array($asset, ['property', 'gold'], true) && $long => ['ltcg_other' => $g],
            default => ['other_income' => $g],
        };
        $section = $asset === 'equity' ? ($long ? '112A' : '111A') : (in_array($asset, ['property', 'gold'], true) && $long ? '112' : ($asset === 'debt' ? '50AA (slab)' : 'slab rate'));
        $now = $this->tax->marginal($base, $component($gain));
        $options = [];
        $options[] = ['key' => 'all_now', 'title' => 'Sell everything this financial year', 'tax' => $now, 'section' => $section, 'how' => 'Whole gain taxed in one year in your name.'];

        // 1. Spread the sale across financial years: equity gets a fresh ₹1.25 lakh exemption each year; slab income gets lower slabs.
        $years = max(1, min(10, (int) ($in['years'] ?? 5)));
        if ($years > 1) {
            $per = $gain / $years;
            $spreadTax = $this->tax->marginal($base, $component($per)) * $years;
            $options[] = ['key' => 'spread', 'title' => "Sell in parts over {$years} financial years", 'tax' => $spreadTax, 'section' => $section . ($asset === 'equity' && $long ? ' — ₹1.25 lakh exemption every year' : ''),
                'how' => 'Redeem about ' . $this->inr($per) . ' a year (e.g. part in March, part in April) so each year\'s gain stays in lower brackets' . ($asset === 'equity' && $long ? ' and uses a new ₹1.25 lakh exemption' : '') . '. Assumes your other income stays the same.'];
        }

        // 2. Tax-gain harvesting for equity: book ₹1.25 lakh of gains every year and reinvest, so the final gain is smaller.
        if ($asset === 'equity' && $long) {
            $harvestYears = max(1, (int) ($in['harvest_years'] ?? $years));
            $harvested = min($gain, 125000 * $harvestYears);
            $options[] = ['key' => 'harvest', 'title' => 'Tax-gain harvesting every year', 'tax' => $this->tax->marginal($base, $component($gain - $harvested)), 'section' => '112A exemption',
                'how' => 'Each year sell units with up to ₹1.25 lakh of long-term gain and buy them back the same day; this resets your cost. Over ' . $harvestYears . ' years about ' . $this->inr($harvested) . ' of gain becomes tax-free.'];
        }

        // 3. Family: share units with family members who are not clubbed with you.
        $family = collect($in['family'] ?? [])->filter(fn ($m) => !empty($m['relation']));
        $clubbed = $family->filter(fn ($m) => in_array($m['relation'], ['spouse', 'minor_child'], true));
        $free = $family->filter(fn ($m) => in_array($m['relation'], ['adult_child', 'parent', 'sibling'], true));
        if ($free->isNotEmpty()) {
            $people = $free->count() + 1;
            $share = $gain / $people;
            $mine = $this->tax->marginal($base, $component($share));
            $theirs = $free->map(function ($m) use ($share, $component, $base) {
                $their = ['salary' => 0, 'other_income' => (float) ($m['income'] ?? 0), 'age' => (int) ($m['age'] ?? 30), 'regime' => 'new'];
                return ['name' => $m['name'] ?? ucfirst(str_replace('_', ' ', $m['relation'])), 'relation' => $m['relation'], 'tax' => $this->tax->marginal($their, $component($share))];
            });
            $options[] = ['key' => 'family', 'title' => 'Gift units to adult children / parents before selling', 'tax' => $mine + $theirs->sum('tax'), 'section' => 's.56(2)(x) gift exemption, s.49 cost carry-over',
                'how' => 'Transfer units (or the asset) by gift deed to ' . $free->pluck('relation')->map(fn ($r) => str_replace('_', ' ', $r))->unique()->implode(', ') . '; each sells their share (' . $this->inr($share) . ') and pays tax at their own lower rate with their own exemption. The money then legally belongs to them.',
                'split' => $theirs->prepend(['name' => 'You', 'relation' => 'self', 'tax' => $mine])->values()->all()];
        }
        if ($clubbed->isNotEmpty()) {
            $options[] = ['key' => 'clubbed', 'title' => 'Gift to spouse or minor child', 'tax' => $now, 'section' => 's.64(1)(iv) / s.64(1A) clubbing',
                'how' => 'No saving: income and gains from assets gifted to your spouse or a minor child are added back to your income. It only works if your spouse invests their own earned money, or you give a genuine loan at a market interest rate.'];
        }

        // 4. Reinvestment exemptions.
        if ($long && $asset !== 'fd' && $asset !== 'debt') {
            if ($asset === 'property') {
                $bond = min($gain, (float) config('money.tax.54EC_cap'));
                $options[] = ['key' => '54ec', 'title' => 'Invest the gain in 54EC bonds (NHAI/REC/PFC/IRFC)', 'tax' => $this->tax->marginal($base, $component($gain - $bond)), 'section' => '54EC',
                    'how' => 'Invest up to ₹50 lakh of the gain within 6 months of sale; 5-year lock-in, interest about 5.25% (taxable).'];
                $options[] = ['key' => '54', 'title' => 'Buy another residential house', 'tax' => 0, 'section' => '54',
                    'how' => 'Reinvest the gain in one house (bought 1 year before / 2 years after, or built within 3 years). Exemption up to ₹10 crore; use the Capital Gains Account Scheme if not invested before filing.'];
            } else {
                $options[] = ['key' => '54f', 'title' => 'Reinvest the full sale amount in a residential house', 'tax' => 0, 'section' => '54F',
                    'how' => 'Exemption is proportional to the sale amount reinvested; you must not own more than one other house on the date of sale. Deposit in the Capital Gains Account Scheme until you buy.'];
            }
        }
        // 5. Retire first: in a year with no salary the whole nil slab is available against the gain.
        if ($base['salary'] > 0) {
            $retired = ['salary' => 0, 'other_income' => $base['other_income'], 'age' => max(60, $base['age']), 'regime' => $base['regime']];
            $options[] = ['key' => 'after_retirement', 'title' => 'Sell in the first year after retirement', 'tax' => $this->tax->marginal($retired, $component($gain)), 'section' => 'Unused basic exemption',
                'how' => 'With no salary that year, the unused nil slab is set off against the gain first.'];
        }
        $best = collect($options)->sortBy('tax')->first();
        return ['gain' => $gain, 'asset' => $asset, 'long_term' => $long, 'section' => $section, 'base_tax' => $now,
            'options' => collect($options)->map(fn ($o) => $o + ['saved' => max(0, $now - $o['tax'])])->sortBy('tax')->values()->all(), 'best' => $best + ['saved' => max(0, $now - $best['tax'])]];
    }

    /** Salary tax under both regimes and the extra tax each deduction section saves in the old regime. */
    public function salary(array $in): array
    {
        $annual = (float) ($in['annual_salary'] ?? ((float) ($in['monthly_salary'] ?? 0) * 12));
        $age = (int) ($in['age'] ?? 30);
        $basic = $annual * ((float) ($in['basic_pct'] ?? 40)) / 100;
        $used = ['80C' => (float) ($in['d_80c'] ?? 0), '80CCD(1B)' => (float) ($in['d_nps'] ?? 0), '80D' => (float) ($in['d_80d'] ?? 0), '24(b)' => (float) ($in['d_home_loan'] ?? 0), 'HRA' => (float) ($in['d_hra'] ?? 0)];
        $limits = ['80C' => 150000, '80CCD(1B)' => 50000, '80D' => $age >= 60 ? 50000 : 25000, '24(b)' => 200000, 'HRA' => $used['HRA']];
        foreach ($used as $k => $v) $used[$k] = min($v, $limits[$k]);
        $employerNps = min((float) ($in['employer_nps'] ?? 0), $basic * 0.14);
        $new = $this->tax->compute(['salary' => $annual, 'other_income' => (float) ($in['other_income'] ?? 0), 'age' => $age, 'regime' => 'new', 'deductions_new' => $employerNps]);
        $oldBase = ['salary' => $annual, 'other_income' => (float) ($in['other_income'] ?? 0), 'age' => $age, 'regime' => 'old'];
        $old = $this->tax->compute($oldBase + ['deductions' => array_sum($used) + min($employerNps, $basic * 0.10)]);
        // How much more each section could still save if fully used (old regime).
        $room = [];
        foreach (['80C' => 'ELSS / PPF / EPF / term-plan premium', '80CCD(1B)' => 'NPS Tier-I', '80D' => 'Health insurance'] as $sec => $how) {
            $gap = $limits[$sec] - $used[$sec];
            if ($gap <= 0) continue;
            $fuller = $used; $fuller[$sec] = $limits[$sec];
            $room[] = ['section' => $sec, 'unused' => $gap, 'how' => $how, 'saves' => (int) ($old['total'] - $this->tax->compute($oldBase + ['deductions' => array_sum($fuller) + min($employerNps, $basic * 0.10)])['total'])];
        }
        return ['annual_salary' => $annual, 'new' => $new, 'old' => $old, 'better' => $new['total'] <= $old['total'] ? 'new' : 'old', 'difference' => abs($new['total'] - $old['total']),
            'used' => $used, 'room' => $room, 'employer_nps' => $employerNps, 'monthly_take_home_new' => round(($annual - $new['total']) / 12)];
    }

    private function inr(float $v): string
    {
        return '₹' . Format::inr($v);
    }
}
