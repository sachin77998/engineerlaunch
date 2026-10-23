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

    /** Copies config/care_methodology.php into the tables (only runs when they are empty, or with --reset). */
    public function seed(bool $reset = false): void
    {
        $cfg = config('care_methodology');
        $now = now();
        DB::transaction(function () use ($cfg, $now, $reset) {
            if ($reset) foreach (['credit_methodology_ratios', 'credit_methodology_sectors', 'credit_methodology_settings'] as $t) DB::table($t)->delete();
            $i = 0;
            foreach ($cfg['ratios'] as $key => $r) DB::table('credit_methodology_ratios')->updateOrInsert(['key' => $key], ['label' => $r['label'], 'direction' => $r['dir'], 'band' => json_encode($r['band']), 'pillar' => $r['pillar'], 'sort_order' => $i++, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            $order = ['bank', 'financial', 'investment_holding', 'telecom', 'hotel', 'hospital', 'education', 'fertiliser', 'cement', 'diamond', 'manmade_yarn', 'cotton_textile', 'non_ferrous', 'commercial_vehicles', 'auto_components', 'services', 'manufacturing'];
            foreach ($cfg['sectors'] as $key => $s) DB::table('credit_methodology_sectors')->updateOrInsert(['key' => $key], [
                'name' => $s['name'], 'criteria' => $s['criteria'] ?? null, 'match_industry' => $s['match']['industry'] ?? null, 'match_name' => $s['match']['name'] ?? null,
                'nic_prefixes' => isset($s['match']['nic']) ? json_encode($s['match']['nic']) : null, 'ratios' => isset($s['ratios']) ? json_encode($s['ratios']) : null,
                'skip' => isset($s['skip']) ? json_encode($s['skip']) : null, 'overrides' => isset($s['override']) ? json_encode($s['override']) : null,
                'weights' => isset($s['weights']) ? json_encode($s['weights']) : null, 'checks' => isset($s['checks']) ? json_encode($s['checks']) : null,
                'notes' => isset($s['notes']) ? json_encode($s['notes'], JSON_UNESCAPED_UNICODE) : null, 'financial_ratios' => in_array($key, ['bank', 'financial'], true),
                'match_order' => (($p = array_search($key, $order, true)) === false ? 99 : $p) * 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach (['score_bands' => $cfg['bands'], 'short_term_map' => $cfg['short_term'], 'default_weights' => $cfg['default_weights'],
                'overlays' => ['gov_notch_51' => 1, 'gov_notch_75' => 2, 'group_support_notch' => 1, 'group_support_years' => 3, 'inc_cap_months' => 6, 'inc_cap' => 'BB+',
                    'cure_non_ig_days' => 90, 'cure_ig_days' => 365, 'post_default_cap' => 'BB+', 'stale_days' => 7]] as $k => $v)
                DB::table('credit_methodology_settings')->updateOrInsert(['key' => $k], ['value' => json_encode($v), 'created_at' => $now, 'updated_at' => $now]);
        });
        $this->flush();
    }

    private function fromConfig(): array
    {
        $cfg = config('care_methodology');
        return ['ratios' => $cfg['ratios'], 'sectors' => $cfg['sectors'], 'settings' => ['score_bands' => $cfg['bands'], 'short_term_map' => $cfg['short_term'], 'default_weights' => $cfg['default_weights'], 'overlays' => []]];
    }
}
