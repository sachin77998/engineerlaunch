<?php

namespace App\Services\Money;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Money planner rules stored in the money_* tables, rebuilt into the config('money') structure the calculators use.
 * Applied at boot (AppServiceProvider) so every tax, retirement and income calculation reads the live values.
 * Tables are seeded from config/money.php the first time they are empty.
 */
class MoneyRules
{
    private const CACHE = 'money-rules:v1';
    private const SLAB_KEYS = ['new' => ['new', 'slabs'], 'old' => ['old', 'slabs'], 'old_senior' => ['old', 'slabs_senior'], 'old_super_senior' => ['old', 'slabs_super_senior']];

    /** Overrides config('money') with the stored rules; leaves the file values in place if the tables are unavailable. */
    public static function apply(): void
    {
        $rules = rescue(fn () => Cache::rememberForever(self::CACHE, fn () => (new self)->build()), null, false);
        if ($rules) config(['money' => $rules]);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE);
    }

    public function build(): ?array
    {
        if (!Schema::hasTable('money_settings')) return null;
        $file = require config_path('money.php');
        if (!DB::table('money_settings')->exists()) $this->seed($file);
        $set = DB::table('money_settings')->pluck('value', 'key')->map(fn ($v) => json_decode($v, true));
        $num = fn ($v) => $v === null ? null : (float) $v;
        $tax = ['cess' => $num($set['cess'] ?? 4), 'capital_gains_surcharge_cap' => $num($set['capital_gains_surcharge_cap'] ?? 15), '54EC_cap' => $num($set['54EC_cap'] ?? 5000000),
            'gift_exempt_relatives' => $set['gift_exempt_relatives'] ?? []];
        foreach (['new', 'old'] as $r) {
            $tax[$r] = ['standard_deduction' => $num($set["{$r}_standard_deduction"] ?? 0), 'rebate_limit' => $num($set["{$r}_rebate_limit"] ?? 0), 'rebate_max' => $num($set["{$r}_rebate_max"] ?? 0),
                'surcharge' => DB::table('money_tax_surcharges')->where('regime', $r)->orderBy('above')->get()->map(fn ($s) => [(float) $s->above, (float) $s->rate])->all()];
        }
        foreach (DB::table('money_tax_slabs')->orderBy('regime')->orderBy('sort_order')->get()->groupBy('regime') as $regime => $rows) {
            if (!isset(self::SLAB_KEYS[$regime])) continue;
            [$r, $k] = self::SLAB_KEYS[$regime];
            $tax[$r][$k] = $rows->map(fn ($s) => [$s->upto === null ? null : (float) $s->upto, (float) $s->rate])->all();
        }
        $tax['capital_gains'] = DB::table('money_capital_gains_rules')->get()->mapWithKeys(fn ($g) => [$g->asset => ['ltcg_months' => $g->ltcg_months, 'stcg_rate' => $num($g->stcg_rate),
            'ltcg_rate' => $num($g->ltcg_rate), 'ltcg_exemption' => (float) $g->ltcg_exemption, 'section_st' => $g->section_st, 'section_lt' => $g->section_lt]])->all();
        $tax['deductions'] = DB::table('money_deductions')->where('is_active', true)->orderBy('sort_order')->get()->mapWithKeys(fn ($d) => [$d->section => array_filter([
            'limit' => $num($d->limit), 'limit_senior' => $num($d->limit_senior), 'parents' => $num($d->parents), 'parents_senior' => $num($d->parents_senior),
            'limit_pct_basic' => $num($d->limit_pct_basic), 'regimes' => json_decode($d->regimes, true), 'label' => $d->label], fn ($v) => $v !== null) + ['limit' => $num($d->limit)]])->all();
        return [
            'tax_year' => $set['tax_year'] ?? $file['tax_year'],
            'tax' => $tax,
            'instruments' => DB::table('money_instruments')->where('is_active', true)->orderBy('sort_order')->get()->mapWithKeys(fn ($i) => [$i->key => ['label' => $i->label,
                'return' => (float) $i->expected_return, 'tax' => $i->tax_class, 'liquid' => (bool) $i->liquid]])->all(),
            'income_products' => DB::table('money_income_products')->where('is_active', true)->orderBy('sort_order')->get()->mapWithKeys(fn ($p) => [$p->key => array_filter([
                'label' => $p->label, 'rate' => (float) $p->rate, 'withdrawal' => $num($p->withdrawal), 'max_joint' => $num($p->max_joint), 'min_age' => $p->min_age,
                'payout' => $p->payout, 'tax' => $p->tax_note, 'risk' => $p->risk], fn ($v) => $v !== null) + ['max_per_person' => $num($p->max_per_person)]])->all(),
            'plans' => DB::table('money_plan_examples')->orderBy('sort_order')->get()->groupBy('category')->map(fn ($g) => $g->pluck('name')->all())->all(),
            'defaults' => $set['defaults'] ?? $file['defaults'],
        ];
    }

    /** Copies config/money.php into the tables. */
    public function seed(?array $file = null, bool $reset = false): void
    {
        $file ??= require config_path('money.php');
        $now = now();
        $ts = ['created_at' => $now, 'updated_at' => $now];
        DB::transaction(function () use ($file, $ts, $reset) {
            if ($reset) foreach (['money_tax_slabs', 'money_tax_surcharges', 'money_capital_gains_rules', 'money_deductions', 'money_instruments', 'money_income_products', 'money_plan_examples', 'money_settings'] as $t) DB::table($t)->delete();
            $tax = $file['tax'];
            foreach (self::SLAB_KEYS as $regime => [$r, $k]) foreach ($tax[$r][$k] ?? [] as $i => [$upto, $rate])
                DB::table('money_tax_slabs')->insert(['regime' => $regime, 'upto' => $upto, 'rate' => $rate, 'sort_order' => $i] + $ts);
            foreach (['new', 'old'] as $r) foreach ($tax[$r]['surcharge'] as [$above, $rate]) DB::table('money_tax_surcharges')->insert(['regime' => $r, 'above' => $above, 'rate' => $rate] + $ts);
            foreach ($tax['capital_gains'] as $asset => $g) DB::table('money_capital_gains_rules')->insert(['asset' => $asset] + $g + $ts);
            $i = 0;
            foreach ($tax['deductions'] as $sec => $d) DB::table('money_deductions')->insert(['section' => $sec, 'label' => $d['label'], 'limit' => $d['limit'] ?? null, 'limit_senior' => $d['limit_senior'] ?? null,
                'parents' => $d['parents'] ?? null, 'parents_senior' => $d['parents_senior'] ?? null, 'limit_pct_basic' => $d['limit_pct_basic'] ?? null, 'regimes' => json_encode($d['regimes']), 'sort_order' => $i++] + $ts);
            $i = 0;
            foreach ($file['instruments'] as $key => $x) DB::table('money_instruments')->insert(['key' => $key, 'label' => $x['label'], 'expected_return' => $x['return'], 'tax_class' => $x['tax'], 'liquid' => $x['liquid'], 'sort_order' => $i++] + $ts);
            $i = 0;
            foreach ($file['income_products'] as $key => $p) DB::table('money_income_products')->insert(['key' => $key, 'label' => $p['label'], 'rate' => $p['rate'], 'withdrawal' => $p['withdrawal'] ?? null,
                'max_per_person' => $p['max_per_person'] ?? null, 'max_joint' => $p['max_joint'] ?? null, 'min_age' => $p['min_age'] ?? null, 'payout' => $p['payout'], 'tax_note' => $p['tax'] ?? null, 'risk' => $p['risk'] ?? null, 'sort_order' => $i++] + $ts);
            $i = 0;
            foreach ($file['plans'] as $cat => $names) foreach ($names as $name) DB::table('money_plan_examples')->insert(['category' => $cat, 'name' => $name, 'sort_order' => $i++] + $ts);
            $settings = ['tax_year' => $file['tax_year'], 'cess' => $tax['cess'], 'capital_gains_surcharge_cap' => $tax['capital_gains_surcharge_cap'], '54EC_cap' => $tax['54EC_cap'],
                'gift_exempt_relatives' => $tax['gift_exempt_relatives'], 'defaults' => $file['defaults']];
            foreach (['new', 'old'] as $r) foreach (['standard_deduction', 'rebate_limit', 'rebate_max'] as $k) $settings["{$r}_$k"] = $tax[$r][$k];
            foreach ($settings as $k => $v) DB::table('money_settings')->insert(['key' => $k, 'value' => json_encode($v, JSON_UNESCAPED_UNICODE)] + $ts);
        });
        self::flush();
    }
}
