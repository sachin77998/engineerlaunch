<?php

namespace App\Http\Controllers;

use App\Services\Insights\AgencyCriteria;
use App\Services\Insights\Methodology;
use App\Services\Insights\RatingRationaleParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Owner page for the credit methodology tables: ratio definitions and bands, sector profiles, score bands,
// short-term mapping and overlay rules. Saving marks stored assessments stale so the nightly run recomputes them.
class AdminMethodologyController extends Controller
{
    public const CHECKS = ['capex_vs_networth', 'negative_cfo_proxy', 'group_support', 'commodity_margin_volatility', 'forex_exposure', 'inventory_build', 'receivable_stretch', 'seasonality', 'keyman', 'related_party_heavy', 'ice_obsolescence',
        'capital_cushion', 'ccb_restriction', 'rapid_growth', 'off_book', 'solvency_cushion', 'reit_leverage', 'market_dependence', 'offtaker_receivables', 'payables_to_gencos', 'refinancing'];
    private const PILLARS = ['business', 'financial', 'liquidity', 'management'];

    public function index(Methodology $m, AgencyCriteria $criteria)
    {
        $m->sectors();   // seeds the tables on first visit
        $criteria->all();
        return view('admin.methodology', [
            'agencyCriteria' => \Illuminate\Support\Facades\Schema::hasTable('credit_agency_criteria') ? DB::table('credit_agency_criteria')->orderBy('agency')->orderBy('name')->get() : collect(),
            'ratios' => DB::table('credit_methodology_ratios')->orderBy('sort_order')->get(),
            'sectors' => DB::table('credit_methodology_sectors')->orderBy('match_order')->get(),
            'settings' => DB::table('credit_methodology_settings')->pluck('value', 'key')->map(fn ($v) => json_decode($v, true)),
            'checks' => self::CHECKS, 'pillars' => self::PILLARS, 'scale' => RatingRationaleParser::LONG_SCALE, 'short' => RatingRationaleParser::SHORT_SCALE,
            'stats' => ['assessed' => DB::table('company_credit_assessments')->count(), 'last' => DB::table('company_credit_assessments')->max('computed_at'),
                'stale' => DB::table('company_credit_assessments')->whereNull('computed_at')->count(), 'medians' => DB::table('sector_ratio_medians')->count(),
                'model' => DB::table('credit_model_runs')->where('is_active', true)->latest('trained_at')->first()],
        ]);
    }
    public function ratios(Request $r, Methodology $m)
    {
        $data = $r->validate(['ratios' => 'required|array', 'ratios.*.label' => 'required|string|max:120', 'ratios.*.direction' => 'required|in:up,down',
            'ratios.*.pillar' => 'required|in:' . implode(',', self::PILLARS), 'ratios.*.band' => 'required|array|size:4', 'ratios.*.band.*' => 'required|numeric']);
        foreach ($data['ratios'] as $id => $row) {
            $band = array_map('floatval', array_values($row['band']));
            $sorted = $band; $row['direction'] === 'up' ? rsort($sorted) : sort($sorted);
            if ($sorted !== $band) return back()->withErrors(["Bands for '{$row['label']}' must run from excellent to weak (" . ($row['direction'] === 'up' ? 'highest first' : 'lowest first') . ').'])->withInput();
            DB::table('credit_methodology_ratios')->where('id', $id)->update(['label' => $row['label'], 'direction' => $row['direction'], 'pillar' => $row['pillar'], 'band' => json_encode($band),
                'is_active' => !empty($r->input("active.$id")), 'updated_at' => now()]);
        }
        return $this->saved($m, 'Ratio definitions saved.');
    }
    public function sector(Request $r, int $id, Methodology $m)
    {
        $data = $r->validate(['name' => 'required|string|max:120', 'criteria' => 'nullable|string|max:250', 'match_industry' => 'nullable|string|max:250', 'match_name' => 'nullable|string|max:250',
            'nic' => 'nullable|string|max:200', 'match_order' => 'required|integer|min:0|max:999', 'weights' => 'required|array', 'weights.*' => 'required|numeric|min:0|max:100',
            'ratios' => 'nullable|array', 'checks' => 'nullable|array', 'notes' => 'nullable|string|max:1000', 'override' => 'nullable|array'])
            + ['criteria' => null, 'match_industry' => null, 'match_name' => null, 'nic' => '', 'notes' => ''];
        foreach (['match_industry', 'match_name'] as $f) if (!empty($data[$f]) && @preg_match($data[$f], '') === false) return back()->withErrors(["$f is not a valid pattern (use /.../i)."])->withInput();
        if (abs(array_sum($data['weights']) - 100) > 0.01) return back()->withErrors(['Pillar weights must add up to 100.'])->withInput();
        $overrides = [];
        foreach ((array) ($data['override'] ?? []) as $o) {
            if (empty($o['ratio']) || count(array_filter($o['band'] ?? [], 'is_numeric')) !== 4) continue;
            $overrides[$o['ratio']] = array_map('floatval', array_values($o['band']));
        }
        DB::table('credit_methodology_sectors')
              ->where('id', $id)
              ->update([
               'name' => $data['name'],
               'criteria' => $data['criteria'],
               'match_industry' => $data['match_industry'] ?: null,
               'match_name' => $data['match_name'] ?: null,
               'nic_prefixes' => ($nic = array_values(array_filter(array_map('trim', explode(',', (string) $data['nic']))))) ? json_encode($nic) : null,
               'match_order' => $data['match_order'], 'weights' => json_encode(array_map('floatval', $data['weights'])),
               'ratios' => !empty($data['ratios']) ? json_encode(array_values($data['ratios'])) : null, 'checks' => json_encode(array_values($data['checks'] ?? [])),
               'overrides' => $overrides ? json_encode($overrides) : null,
               'notes' => trim((string) $data['notes']) !== '' ? json_encode(array_values(array_filter(array_map('trim', preg_split('/\R/', $data['notes'])))), JSON_UNESCAPED_UNICODE) : null,
               'is_active' => $r->boolean('is_active'), 'financial_ratios' => $r->boolean('financial_ratios'), 'updated_at' => now(),
        ]);
        return $this->saved($m, "Sector '{$data['name']}' saved.");
    }
    public function settings(Request $r, Methodology $m)
    {
        $scale = RatingRationaleParser::LONG_SCALE; $short = RatingRationaleParser::SHORT_SCALE;
        $data = $r->validate(
            ['bands' => 'required|array',
             'bands.*.min' => 'nullable|numeric|min:0|max:100',
             'bands.*.rating' => 'required|in:' . implode(',', $scale),
             'short' => 'required|array',
             'short.*.usual' => 'required|in:' . implode(',', $short),
             'short.*.superior' => 'required|in:' . implode(',', $short),
             'overlays' => 'required|array',
             'overlays.*' => 'required',
             'default_weights' => 'required|array', 'default_weights.*' => 'required|numeric|min:0|max:100']);
        $bands = collect($data['bands'])->filter(fn ($b) => $b['min'] !== null && $b['min'] !== '')->mapWithKeys(fn ($b) => [(string) (float) $b['min'] => $b['rating']])->sortKeysDesc()->all();
        if (abs(array_sum($data['default_weights']) - 100) > 0.01) return back()->withErrors(['Default pillar weights must add up to 100.'])->withInput();
        $overlays = collect($data['overlays'])->map(fn ($v, $k) => in_array($k, ['inc_cap', 'post_default_cap'], true) ? (in_array($v, $scale, true) ? $v : 'BB+') : (float) $v)->all();
        $put = fn ($k, $v) => self::putSetting($k, $v);
        $put('score_bands', $bands);
        $put('short_term_map', collect($data['short'])->map(fn ($s) => [$s['usual'], $s['superior']])->all());
        $put('overlays', $overlays);
        $put('default_weights', array_map('floatval', $data['default_weights']));
        return $this->saved($m, 'Rating bands and rules saved.');
    }

    /** Regulatory floors, instrument notching, capital conservation buffer and insurer hybrid haircut. */
    public function limits(Request $r, Methodology $m)
    {
        $scale = RatingRationaleParser::LONG_SCALE;
        $data = $r->validate(['regmin' => 'required|array', 'regmin.*' => 'required|numeric|min:0|max:500', 'notch' => 'required|array', 'notch.*' => 'required',
            'ccb' => 'required|array', 'ccb.*.min' => 'nullable|numeric', 'ccb.*.max' => 'nullable|numeric', 'ccb.*.retain' => 'nullable|numeric|min:0|max:100',
            'haircut' => 'required|array', 'haircut.*' => 'required|numeric|min:0|max:100']);
        $notch = collect($data['notch'])->map(fn ($v, $k) => str_ends_with($k, '_from') ? (in_array($v, $scale, true) ? $v : 'AA-') : (int) $v)->all();
        $ccb = collect($data['ccb'])->filter(fn ($b) => is_numeric($b['min'] ?? null) && is_numeric($b['max'] ?? null) && is_numeric($b['retain'] ?? null))
            ->map(fn ($b) => [(float) $b['min'], (float) $b['max'], (float) $b['retain']])->sortBy(0)->values()->all();
        $haircut = collect($data['haircut'])->mapWithKeys(fn ($v, $k) => [(int) $k => (float) $v])->sortKeysDesc()->all();
        self::putSetting('regulatory_minimums', array_map('floatval', $data['regmin']));
        self::putSetting('instrument_notching', $notch);
        self::putSetting('ccb_table', $ccb);
        self::putSetting('insurance_hybrid_haircut', $haircut);
        // Fund credit quality: default rates, short-term equivalents and rules; stored fund scores are recomputed.
        if ($r->has('fund_pd')) {
            $f = $r->validate(['fund_pd' => 'array', 'fund_pd.*' => 'numeric|min:0|max:100', 'fund_short' => 'array', 'fund_short.*' => 'in:' . implode(',', $scale), 'fund_rules' => 'array']);
            self::putSetting('fund_pd_annual', array_map('floatval', $f['fund_pd']));
            self::putSetting('fund_short_equivalent', $f['fund_short'] ?? []);
            self::putSetting('fund_rules', collect($f['fund_rules'] ?? [])->map(fn ($v, $k) => $k === 'unrated_as' ? (in_array($v, $scale, true) ? $v : 'BBB-') : (float) $v)->all());
            if ($r->has('icra')) {
                $i = $r->validate(['icra.category_maturity' => 'array', 'icra.category_maturity.*' => 'nullable|numeric|min:0|max:40', 'icra.short_lowest_long' => 'array', 'icra.short_lowest_long.*' => 'in:' . implode(',', $scale),
                    'icra.unrated_as' => 'in:' . implode(',', $scale), 'icra.cdmdf_dislocation_rating' => 'in:' . implode(',', $scale), 'icra.cdmdf_dislocation' => 'boolean',
                    'icra.invit_notch' => 'integer|min:0|max:6', 'icra.invit_years' => 'numeric|min:0|max:40', 'icra.cdmdf_a3_notch' => 'integer|min:0|max:6', 'icra.cdmdf_a3_years' => 'numeric|min:0|max:15',
                    'icra.lookback_months' => 'integer|min:1|max:12', 'icra.upgrade_months' => 'integer|min:1|max:12', 'icra.sharp_breach_notches' => 'integer|min:1|max:10'])['icra'];
                $i['category_maturity'] = collect($i['category_maturity'] ?? [])->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($v) => (float) $v)->all();
                $i['cdmdf_dislocation'] = (bool) ($i['cdmdf_dislocation'] ?? false);
                self::putSetting('icra_fund', $i + config('fund_portfolios.icra'));
            }
            $m->flush();
            \Illuminate\Support\Facades\Artisan::call('mf:portfolios', ['--rescore' => true]);
        }
        return $this->saved($m, 'Regulatory limits and instrument notching saved.');
    }

    /** Another agency's sector criteria: the five-step cut points of each graded factor and whether the grid is shown. */
    public function agency(Request $r, int $id, Methodology $m, AgencyCriteria $criteria)
    {
        $row = DB::table('credit_agency_criteria')->where('id', $id)->first() ?? abort(404);
        $data = $r->validate(['band' => 'nullable|array', 'band.*' => 'array|size:4', 'band.*.*' => 'required|numeric']);
        $factors = json_decode($row->factors, true);
        foreach ($factors as &$f) {
            if (!isset($f['band'], $data['band'][$f['key']])) continue;
            $band = array_map('floatval', array_values($data['band'][$f['key']]));
            $sorted = $band; ($f['dir'] ?? 'up') === 'up' ? rsort($sorted) : sort($sorted);
            if ($sorted !== $band) return back()->withErrors(["Cut points for '{$f['label']}' must run from Strong to Weak (" . (($f['dir'] ?? 'up') === 'up' ? 'highest first' : 'lowest first') . ').'])->withInput();
            $f['band'] = $band;
            $f['strongest_value'] = $band[0];
            $f['weakest_value'] = $band[3];
        }
        unset($f);
        DB::table('credit_agency_criteria')->where('id', $id)->update(['factors' => json_encode($factors, JSON_UNESCAPED_UNICODE), 'is_active' => $r->boolean('is_active'), 'updated_at' => now()->addSecond()]);
        $criteria->flush();
        return $this->saved($m, "{$row->agency} {$row->name} criteria saved.");
    }
    /** Owner edits keep the original created_at, so the seeder can tell them apart from untouched defaults. */
    private static function putSetting(string $key, $value): void
    {
        $exists = DB::table('credit_methodology_settings')->where('key', $key)->exists();
        $exists ? DB::table('credit_methodology_settings')->where('key', $key)->update(['value' => json_encode($value), 'updated_at' => now()->addSecond()])
            : DB::table('credit_methodology_settings')->insert(['key' => $key, 'value' => json_encode($value), 'created_at' => now()->subSecond(), 'updated_at' => now()]);
    }
    /** Recomputes a batch now (the nightly job finishes the rest). */
    public function recompute(Request $r)
    {
        Artisan::call('insights:assess', ['--limit' => 200]);
        return back()->with('status', trim(Artisan::output()) ?: 'Recomputed.');
    }
    public function reset(Methodology $m)
    {
        $m->seed(true);
        DB::table('company_credit_assessments')->update(['computed_at' => null]);
        return back()->with('status', 'Methodology restored to the defaults; assessments will be recomputed.');
    }
    private function saved(Methodology $m, string $msg)
    {
        $m->flush();
        DB::table('company_credit_assessments')->update(['computed_at' => null]);   // stale: recomputed on view or by the nightly job
        return back()->with('status', $msg . ' Stored assessments will be recomputed.');
    }
}
