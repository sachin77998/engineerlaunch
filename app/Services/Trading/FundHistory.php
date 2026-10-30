<?php

namespace App\Services\Trading;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Full daily NAV history of a scheme (AMFI data via mfapi.in), cached for 12 hours and kept in mf_nav_history,
 * plus risk ratios computed from it.
 */
class FundHistory
{
    /** @return array<int, array{0:string,1:float}> [Y-m-d, nav], oldest first */
    public function points(int $schemeCode): array
    {
        $key = 'fund-history:' . $schemeCode;
        // A page view never waits for mfapi (up to 12s): the stored copy is shown and mfapi read after the response.
        if (!Cache::has('stale:' . $key) && !app()->runningInConsole()) {
            app()->terminating(fn () => rescue(fn () => \App\Support\Stale::build($key, now()->addHours(12), fn () => $this->fetch($schemeCode)), null, false));
            return DB::table('mf_nav_history')->where('scheme_code', $schemeCode)->orderBy('nav_date')->pluck('nav', 'nav_date')
                ->map(fn ($v, $d) => [$d, (float) $v])->values()->all();
        }
        return \App\Support\Stale::remember($key, now()->addHours(12), fn () => $this->fetch($schemeCode));
    }

    /** mfapi.in history, stored in mf_nav_history; the stored copy when mfapi is unreachable. */
    private function fetch(int $schemeCode): array
    {
        try {
            $rows = Http::timeout(12)->retry(1, 500, throw: false)->get('https://api.mfapi.in/mf/' . $schemeCode)->json('data') ?? [];
        } catch (Throwable) {
            $rows = [];
        }
        $points = [];
        foreach ($rows as $r) {
            if (!isset($r['date'], $r['nav']) || !is_numeric($r['nav'])) continue;
            [$d, $m, $y] = explode('-', $r['date']);
            $points["$y-$m-$d"] = (float) $r['nav'];
        }
        if ($points) {
            ksort($points);
            // Keep a copy, so the page still has history when the source is unreachable.
            rescue(function () use ($schemeCode, $points) {
                foreach (array_chunk(array_map(fn ($d, $v) => ['scheme_code' => $schemeCode, 'nav_date' => $d, 'nav' => $v], array_keys($points), $points), 1000) as $chunk) {
                    DB::table('mf_nav_history')->upsert($chunk, ['scheme_code', 'nav_date'], ['nav']);
                }
            }, null, false);
        } else {
            $points = DB::table('mf_nav_history')->where('scheme_code', $schemeCode)->orderBy('nav_date')->pluck('nav', 'nav_date')->map(fn ($v) => (float) $v)->all();
        }
        return array_map(fn ($d, $v) => [$d, $v], array_keys($points), $points);
    }

    /** Volatility, Sharpe, Sortino and maximum drawdown over the last 3 years (or all history if shorter). */
    public function ratios(array $points, float $riskFree = 6.5): array
    {
        $cut = date('Y-m-d', strtotime('-3 years'));
        $navs = array_column(array_values(array_filter($points, fn ($p) => $p[0] >= $cut)), 1);
        if (count($navs) < 60) return [];
        $returns = [];
        for ($i = 1; $i < count($navs); $i++) if ($navs[$i - 1] > 0) $returns[] = $navs[$i] / $navs[$i - 1] - 1;
        $n = count($returns);
        $mean = array_sum($returns) / $n;
        $sd = sqrt(array_sum(array_map(fn ($r) => ($r - $mean) ** 2, $returns)) / max(1, $n - 1));
        $down = array_filter($returns, fn ($r) => $r < 0);
        $dd = $down ? sqrt(array_sum(array_map(fn ($r) => $r ** 2, $down)) / count($down)) : null;
        $annual = ($mean * 252) * 100;
        $vol = $sd * sqrt(252) * 100;
        $peak = $navs[0]; $maxDd = 0;
        foreach ($navs as $v) { $peak = max($peak, $v); $maxDd = min($maxDd, $v / $peak - 1); }
        return ['volatility' => $vol, 'sharpe' => $vol > 0 ? ($annual - $riskFree) / $vol : null,
            'sortino' => $dd ? ($annual - $riskFree) / ($dd * sqrt(252) * 100) : null, 'max_drawdown' => $maxDd * 100, 'period' => count($navs) >= 700 ? '3Y' : 'available history'];
    }
}
