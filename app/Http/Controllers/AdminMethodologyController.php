<?php

namespace App\Http\Controllers;

use App\Services\Insights\Methodology;
use App\Services\Insights\RatingRationaleParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

// Owner page for the credit methodology tables: ratio definitions and bands, sector profiles, score bands,
// short-term mapping and overlay rules. Saving marks stored assessments stale so the nightly run recomputes them.
class AdminMethodologyController extends Controller
{
    public const CHECKS = ['capex_vs_networth', 'negative_cfo_proxy', 'group_support', 'commodity_margin_volatility', 'forex_exposure', 'inventory_build', 'receivable_stretch', 'seasonality', 'keyman', 'related_party_heavy', 'ice_obsolescence'];
    private const PILLARS = ['business', 'financial', 'liquidity', 'management'];

    public function index(Methodology $m)
    {
        $m->sectors();   // seeds the tables on first visit
        return view('admin.methodology', [
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
        DB::table('credit_methodology_sectors')->where('id', $id)->update([
            'name' => $data['name'], 'criteria' => $data['criteria'], 'match_industry' => $data['match_industry'] ?: null, 'match_name' => $data['match_name'] ?: null,
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
        $data = $r->validate(['bands' => 'required|array', 'bands.*.min' => 'nullable|numeric|min:0|max:100', 'bands.*.rating' => 'required|in:' . implode(',', $scale),
            'short' => 'required|array', 'short.*.usual' => 'required|in:' . implode(',', $short), 'short.*.superior' => 'required|in:' . implode(',', $short),
            'overlays' => 'required|array', 'overlays.*' => 'required', 'default_weights' => 'required|array', 'default_weights.*' => 'required|numeric|min:0|max:100']);
        $bands = collect($data['bands'])->filter(fn ($b) => $b['min'] !== null && $b['min'] !== '')->mapWithKeys(fn ($b) => [(string) (float) $b['min'] => $b['rating']])->sortKeysDesc()->all();
        if (abs(array_sum($data['default_weights']) - 100) > 0.01) return back()->withErrors(['Default pillar weights must add up to 100.'])->withInput();
        $overlays = collect($data['overlays'])->map(fn ($v, $k) => in_array($k, ['inc_cap', 'post_default_cap'], true) ? (in_array($v, $scale, true) ? $v : 'BB+') : (float) $v)->all();
        $put = fn ($k, $v) => DB::table('credit_methodology_settings')->updateOrInsert(['key' => $k], ['value' => json_encode($v), 'updated_at' => now(), 'created_at' => now()]);
        $put('score_bands', $bands);
        $put('short_term_map', collect($data['short'])->map(fn ($s) => [$s['usual'], $s['superior']])->all());
        $put('overlays', $overlays);
        $put('default_weights', array_map('floatval', $data['default_weights']));
        return $this->saved($m, 'Rating bands and rules saved.');
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
