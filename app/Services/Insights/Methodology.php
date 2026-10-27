<?php

namespace App\Services\Insights;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Credit methodology read from the database (credit_methodology_* tables), cached until edited.
 * The tables are seeded from config/care_methodology.php the first time they are empty, so the config file is
 * only the starting point; the owner edits live values at /admin/methodology.
 */
class Methodology
{
    private const CACHE = 'credit-methodology:v1';
    private ?array $data = null;

    public function ratios(): array { return $this->load()['ratios']; }
    public function ratio(string $key): ?array { return $this->load()['ratios'][$key] ?? null; }
    public function sectors(): array { return $this->load()['sectors']; }
    public function sector(string $key): ?array { return $this->load()['sectors'][$key] ?? $this->load()['sectors']['manufacturing'] ?? null; }
    public function setting(string $key, $default = null) { return $this->load()['settings'][$key] ?? $default; }

    public function flush(): void
    {
        Cache::forget(self::CACHE);
        $this->data = null;
    }

    private function load(): array
    {
        if ($this->data) return $this->data;
        if (!Schema::hasTable('credit_methodology_sectors')) return $this->data = $this->fromConfig();
        return $this->data = Cache::rememberForever(self::CACHE, function () {
            if (!DB::table('credit_methodology_sectors')->exists()) $this->seed();
            $ratios = DB::table('credit_methodology_ratios')->where('is_active', true)->orderBy('sort_order')->get()
                ->mapWithKeys(fn ($r) => [$r->key => ['label' => $r->label, 'dir' => $r->direction, 'band' => array_map('floatval', json_decode($r->band, true)), 'pillar' => $r->pillar]])->all();
            $sectors = DB::table('credit_methodology_sectors')->where('is_active', true)->orderBy('match_order')->get()
                ->mapWithKeys(fn ($s) => [$s->key => array_filter([
                    'name' => $s->name, 'criteria' => $s->criteria, 'financial_ratios' => (bool) $s->financial_ratios,
                    'match' => array_filter(['industry' => $s->match_industry, 'name' => $s->match_name, 'nic' => json_decode((string) $s->nic_prefixes, true)]),
                    'ratios' => json_decode((string) $s->ratios, true), 'skip' => json_decode((string) $s->skip, true), 'override' => json_decode((string) $s->overrides, true),
                    'weights' => json_decode((string) $s->weights, true), 'checks' => json_decode((string) $s->checks, true), 'notes' => json_decode((string) $s->notes, true),
                ], fn ($v) => $v !== null)])->all();
            $settings = DB::table('credit_methodology_settings')->pluck('value', 'key')->map(fn ($v) => json_decode($v, true))->all();
            return compact('ratios', 'sectors', 'settings');
        });
    }

    /** Sectors are matched in this order (specific before generic); unknown keys go last. */
    private const MATCH_ORDER = ['bank', 'hfc', 'broking', 'life_insurance', 'insurance', 'reit', 'nbfc', 'financial', 'investment_holding', 'power_distribution', 'wind_power', 'solar_power',
        'thermal_power', 'airport', 'port', 'shipping', 'road_annuity', 'toll_road', 'telecom', 'hotel', 'hospital', 'education', 'fertiliser', 'cement', 'diamond',
        'manmade_yarn', 'cotton_textile', 'non_ferrous', 'commercial_vehicles', 'auto_components', 'services', 'manufacturing'];
    private const FINANCIAL = ['bank', 'financial', 'nbfc', 'hfc', 'broking', 'life_insurance', 'insurance'];

    /**
     * Copies config/care_methodology.php into the tables. Without --reset it adds new ratios, sectors and settings and refreshes
     * rows the owner has never edited (updated_at still equals created_at); rows changed at /admin/methodology are left alone.
     * Returns the number of rows written.
     */
    public function seed(bool $reset = false): int
    {
        $cfg = config('care_methodology');
        $now = now();
        $n = 0;
        DB::transaction(function () use ($cfg, $now, $reset, &$n) {
            if ($reset) foreach (['credit_methodology_ratios', 'credit_methodology_sectors', 'credit_methodology_settings'] as $t) DB::table($t)->delete();
            $put = function (string $table, array $key, array $values) use ($now, &$n) {
                $row = DB::table($table)->where($key)->first();
                if ($row && (string) $row->updated_at !== (string) $row->created_at) return;   // edited by the owner
                if ($row && collect($values)->every(fn ($v, $k) => (string) (is_bool($v) ? (int) $v : $v) === (string) $row->$k)) return;   // unchanged
                DB::table($table)->updateOrInsert($key, $values + ['created_at' => $row->created_at ?? $now, 'updated_at' => $row->created_at ?? $now]);
                $n++;
            };
            $i = 0;
            foreach ($cfg['ratios'] as $key => $r) $put('credit_methodology_ratios', ['key' => $key], ['label' => $r['label'], 'direction' => $r['dir'], 'band' => json_encode($r['band']), 'pillar' => $r['pillar'], 'sort_order' => $i++, 'is_active' => true]);
            foreach ($cfg['sectors'] as $key => $s) $put('credit_methodology_sectors', ['key' => $key], [
                'name' => $s['name'], 'criteria' => $s['criteria'] ?? null, 'match_industry' => $s['match']['industry'] ?? null, 'match_name' => $s['match']['name'] ?? null,
                'nic_prefixes' => isset($s['match']['nic']) ? json_encode($s['match']['nic']) : null, 'ratios' => isset($s['ratios']) ? json_encode($s['ratios']) : null,
                'skip' => isset($s['skip']) ? json_encode($s['skip']) : null, 'overrides' => isset($s['override']) ? json_encode($s['override']) : null,
                'weights' => isset($s['weights']) ? json_encode($s['weights']) : null, 'checks' => isset($s['checks']) ? json_encode($s['checks']) : null,
                'notes' => isset($s['notes']) ? json_encode($s['notes'], JSON_UNESCAPED_UNICODE) : null, 'financial_ratios' => in_array($key, self::FINANCIAL, true),
                'match_order' => (($p = array_search($key, self::MATCH_ORDER, true)) === false ? 99 : $p) * 10, 'is_active' => true,
            ]);
            $settings = ['score_bands' => $cfg['bands'], 'short_term_map' => $cfg['short_term'], 'default_weights' => $cfg['default_weights'],
                'overlays' => ['gov_notch_51' => 1, 'gov_notch_75' => 2, 'group_support_notch' => 1, 'group_support_years' => 3, 'inc_cap_months' => 6, 'inc_cap' => 'BB+',
                    'cure_non_ig_days' => 90, 'cure_ig_days' => 365, 'post_default_cap' => 'BB+', 'stale_days' => 7],
                'regulatory_minimums' => $cfg['regulatory_minimums'], 'instrument_notching' => $cfg['instrument_notching'],
                'ccb_table' => $cfg['ccb_table'], 'insurance_hybrid_haircut' => $cfg['insurance_hybrid_haircut'],
                'fund_pd_annual' => config('fund_portfolios.fund_pd_annual'), 'fund_short_equivalent' => config('fund_portfolios.fund_short_equivalent'),
                'fund_rules' => config('fund_portfolios.fund_rules'), 'icra_fund' => config('fund_portfolios.icra')];
            foreach ($settings as $k => $v) $put('credit_methodology_settings', ['key' => $k], ['value' => json_encode($v)]);
        });
        $this->flush();
        return $n;
    }

    private function fromConfig(): array
    {
        $cfg = config('care_methodology');
        return ['ratios' => $cfg['ratios'], 'sectors' => $cfg['sectors'], 'settings' => ['score_bands' => $cfg['bands'], 'short_term_map' => $cfg['short_term'], 'default_weights' => $cfg['default_weights'], 'overlays' => []]];
    }
}
