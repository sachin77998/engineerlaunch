<?php

namespace App\Services\Money;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Machine-learning fund ranking and timing signals.
 *
 * Data: weekly NAVs (10 years, AMFI via mfapi.in) for Direct-Growth open-ended funds.
 * Features (measured against the category median on the same week): 6-month, 1-year and 3-year return,
 * 1-year volatility, 1-year max drawdown, distance from the 40-week average, 14-week RSI.
 * Target: the fund's return over the next 52 weeks minus its category median.
 * Model: ridge regression on standardised features, validated on the latest two years held out of training
 * (rank correlation and top-quintile hit rate are stored with the model).
 */
class FundModel
{
    public const FEATURES = ['r6m', 'r1y', 'r3y', 'vol', 'mdd', 'ma_gap', 'rsi'];
    private const MODEL_PATH = 'money/fund-model.json';

    public function universe()
    {
        $c = 'mf_schemes.category';
        return DB::table('mf_schemes')->select('mf_schemes.*')->where('mf_schemes.is_active', true)->where('mf_schemes.plan', 'Direct')->where('mf_schemes.option', 'Growth')
            ->where('mf_schemes.scheme_type', 'Open Ended')->where('mf_schemes.asset_class', '!=', 'ETF')->whereNotNull('mf_schemes.return_1y')
            ->where(fn ($q) => $q->where($c, 'like', 'Equity%')->orWhere($c, 'like', 'Hybrid%')->orWhere($c, 'like', 'Debt%')->orWhere($c, 'like', '%ELSS%')->orWhere($c, 'like', '%Index%')->orWhere($c, 'like', '%Fund of Funds%'));
    }

    /** Downloads NAV history for the funds whose weekly data is oldest; returns how many were refreshed. */
    public function fetchBatch(int $limit, ?callable $log = null): int
    {
        $codes = $this->universe()->leftJoin('mf_model_scores as s', 's.scheme_code', '=', 'mf_schemes.scheme_code')
            ->where(fn ($q) => $q->whereNull('s.history_fetched_at')->orWhere('s.history_fetched_at', '<', now()->subDays(6)))
            ->orderByRaw('s.history_fetched_at IS NOT NULL')->orderBy('s.history_fetched_at')->limit($limit)->pluck('mf_schemes.category', 'mf_schemes.scheme_code');
        $done = 0;
        foreach ($codes as $code => $category) {
            try {
                $rows = Http::timeout(20)->retry(2, 1000, throw: false)->get('https://api.mfapi.in/mf/' . $code)->json('data') ?? [];
            } catch (Throwable) { $rows = []; }
            $weekly = [];
            $cut = now()->subYears(10)->toDateString();
            foreach ($rows as $r) {
                if (!isset($r['date'], $r['nav']) || !is_numeric($r['nav']) || $r['nav'] <= 0) continue;
                [$d, $m, $y] = explode('-', $r['date']);
                $date = "$y-$m-$d";
                if ($date < $cut) continue;
                $week = date('o-W', strtotime($date));
                if (!isset($weekly[$week]) || $weekly[$week][0] < $date) $weekly[$week] = [$date, (float) $r['nav']];
            }
            foreach (array_chunk(array_values($weekly), 500) as $chunk) {
                DB::table('mf_nav_weekly')->upsert(array_map(fn ($w) => ['scheme_code' => $code, 'week_date' => $w[0], 'nav' => $w[1]], $chunk), ['scheme_code', 'week_date'], ['nav']);
            }
            DB::table('mf_model_scores')->upsert([['scheme_code' => $code, 'category' => (string) $category, 'signal' => 'pending', 'as_of' => now()->toDateString(), 'history_fetched_at' => now(), 'created_at' => now(), 'updated_at' => now()]],
                ['scheme_code'], ['category', 'history_fetched_at', 'updated_at']);
            $done++;
            if ($log && $done % 25 === 0) $log("fetched $done / " . count($codes));
            usleep(150000);
        }
        return $done;
    }

    /** Features for every fund on every 13th week (training) or only the latest week (scoring). */
    public function panel(bool $latestOnly = false): array
    {
        $cats = $this->universe()->pluck('mf_schemes.category', 'mf_schemes.scheme_code');
        $raw = [];
        foreach ($cats->keys()->chunk(200) as $chunk) {
            DB::table('mf_nav_weekly')->whereIn('scheme_code', $chunk->all())->orderBy('scheme_code')->orderBy('week_date')
                ->get(['scheme_code', 'week_date', 'nav'])->groupBy('scheme_code')->each(function ($rows, $code) use (&$raw) { $raw[$code] = $rows->map(fn ($r) => [substr($r->week_date, 0, 10), (float) $r->nav])->all(); });
        }
        $rows = [];
        foreach ($raw as $code => $series) {
            $n = count($series);
            if ($n < 160) continue;
            $points = [];
            if ($latestOnly) $points = [$n - 1];
            else for ($t = 156; $t < $n; $t += 13) $points[] = $t;
            foreach ($points as $t) {
                $f = $this->features($series, $t);
                if (!$f) continue;
                $label = $t + 52 < $n ? $series[$t + 52][1] / $series[$t][1] - 1 : null;
                // Week key aligns funds whose NAV dates differ by a day or two.
                $rows[] = ['code' => (int) $code, 'cat' => $this->group($cats[$code]), 'category' => $cats[$code], 'week' => date('o-W', strtotime($series[$t][0])), 'date' => $series[$t][0], 'f' => $f, 'y' => $label];
            }
        }
        // Make every feature and the target relative to the category median for that week.
        $groups = [];
        foreach ($rows as $i => $r) $groups[$r['cat'] . '|' . $r['week']][] = $i;
        foreach ($groups as $idx) {
            if (count($idx) < 3) { foreach ($idx as $i) $rows[$i]['drop'] = true; continue; }
            foreach (self::FEATURES as $k) {
                $med = $this->median(array_map(fn ($i) => $rows[$i]['f'][$k], $idx));
                foreach ($idx as $i) $rows[$i]['x'][$k] = $rows[$i]['f'][$k] - $med;
            }
            $ys = array_filter(array_map(fn ($i) => $rows[$i]['y'], $idx), fn ($v) => $v !== null);
            $medY = count($ys) >= 3 ? $this->median($ys) : null;
            foreach ($idx as $i) $rows[$i]['ex'] = $medY !== null && $rows[$i]['y'] !== null ? $rows[$i]['y'] - $medY : null;
        }
        return array_values(array_filter($rows, fn ($r) => empty($r['drop'])));
    }

    public function train(?callable $log = null): array
    {
        $rows = array_values(array_filter($this->panel(), fn ($r) => $r['ex'] !== null));
        if (count($rows) < 300) return ['ok' => false, 'message' => 'Not enough NAV history yet (' . count($rows) . ' samples). Run more fetch batches.'];
        $dates = array_unique(array_column($rows, 'date')); sort($dates);
        $cut = date('Y-m-d', strtotime(end($dates) . ' -2 years'));
        $train = array_values(array_filter($rows, fn ($r) => $r['date'] < $cut));
        $test = array_values(array_filter($rows, fn ($r) => $r['date'] >= $cut));
        if (count($train) < 200) { $train = $rows; $test = []; }
        $mean = []; $std = [];
        foreach (self::FEATURES as $k) {
            $vals = array_map(fn ($r) => $r['x'][$k], $train);
            $mean[$k] = array_sum($vals) / count($vals);
            $std[$k] = max(1e-6, sqrt(array_sum(array_map(fn ($v) => ($v - $mean[$k]) ** 2, $vals)) / count($vals)));
        }
        $weights = $this->ridge($train, $mean, $std, 5.0);
        $model = ['weights' => $weights, 'mean' => $mean, 'std' => $std, 'trained_at' => now()->toIso8601String(), 'samples' => count($train), 'test_samples' => count($test),
            'funds' => count(array_unique(array_column($rows, 'code')))];
        if ($test) {
            $pred = array_map(fn ($r) => $this->predict($model, $r['x']), $test);
            $model['test_rank_ic'] = round($this->spearman($pred, array_column($test, 'ex')), 3);
            // Share of top-quintile predictions that beat their category over the next year.
            $byDate = [];
            foreach ($test as $i => $r) $byDate[$r['cat'] . $r['date']][] = [$pred[$i], $r['ex']];
            $hits = 0; $tops = 0;
            foreach ($byDate as $g) { if (count($g) < 5) continue; usort($g, fn ($a, $b) => $b[0] <=> $a[0]); foreach (array_slice($g, 0, max(1, intdiv(count($g), 5))) as $x) { $tops++; $hits += $x[1] > 0 ? 1 : 0; } }
            $model['test_top_quintile_hit_rate'] = $tops ? round($hits / $tops * 100, 1) : null;
        }
        Storage::disk('local')->put(self::MODEL_PATH, json_encode($model, JSON_PRETTY_PRINT));
        return ['ok' => true] + $model;
    }

    public function model(): ?array
    {
        return Storage::disk('local')->exists(self::MODEL_PATH) ? json_decode(Storage::disk('local')->get(self::MODEL_PATH), true) : null;
    }

    /** Scores the latest week for every fund and writes the buy / hold / exit signal. */
    public function score(): int
    {
        $model = $this->model();
        $rows = $this->panel(true);
        $byCat = [];
        foreach ($rows as $i => $r) { $rows[$i]['pred'] = $model ? $this->predict($model, $r['x']) : $this->heuristic($r['x']); $byCat[$r['cat']][] = $i; }
        foreach ($byCat as $idx) {
            usort($idx, fn ($a, $b) => $rows[$a]['pred'] <=> $rows[$b]['pred']);
            $n = count($idx);
            foreach ($idx as $rank => $i) $rows[$i]['pct'] = $n > 1 ? (int) round($rank / ($n - 1) * 100) : 50;
        }
        foreach ($rows as $r) {
            [$signal, $reasons] = $this->signal($r);
            DB::table('mf_model_scores')->upsert([['scheme_code' => $r['code'], 'category' => $r['category'], 'predicted_excess' => round($r['pred'] * 100, 4), 'score' => $r['pct'], 'signal' => $signal,
                'reasons' => json_encode($reasons), 'features' => json_encode(array_map(fn ($v) => round($v, 4), $r['f'])), 'as_of' => $r['date'], 'created_at' => now(), 'updated_at' => now()]],
                ['scheme_code'], ['category', 'predicted_excess', 'score', 'signal', 'reasons', 'features', 'as_of', 'updated_at']);
        }
        return count($rows);
    }

    // Timing rules on top of the model rank: add on dips in strong funds, trim when overheated, exit persistent laggards.
    public function signal(array $r): array
    {
        $f = $r['f']; $x = $r['x']; $pct = $r['pct'] ?? 50; $reasons = [];
        if ($pct < 25 && $x['r1y'] < 0 && $x['r3y'] < 0) {
            $reasons[] = 'Model rank ' . $pct . '/100 in its category';
            $reasons[] = 'Behind the category median over 1 year (' . $this->pp($x['r1y']) . ') and 3 years (' . $this->pp($x['r3y']) . ' a year)';
            return ['exit', $reasons];
        }
        if ($f['rsi'] > 75 && $f['ma_gap'] > 0.15) {
            $reasons[] = '14-week RSI ' . round($f['rsi']) . ' (overbought)';
            $reasons[] = 'NAV ' . round($f['ma_gap'] * 100) . '% above its 40-week average';
            return ['book_profit', $reasons];
        }
        if ($pct >= 60 && ($f['ma_gap'] < 0 || $f['mdd'] <= -0.08)) {
            $reasons[] = 'Model rank ' . $pct . '/100 in its category';
            $reasons[] = $f['ma_gap'] < 0 ? 'NAV ' . abs(round($f['ma_gap'] * 100, 1)) . '% below its 40-week average' : 'Down ' . abs(round($f['mdd'] * 100)) . '% from its 1-year high';
            return ['buy', $reasons];
        }
        $reasons[] = 'Model rank ' . $pct . '/100 in its category';
        return [$pct >= 40 ? 'hold' : 'watch', $reasons];
    }

    public function features(array $s, int $t): ?array
    {
        if ($t < 156) return null;
        $nav = fn ($i) => $s[$i][1];
        $rets = [];
        for ($i = $t - 51; $i <= $t; $i++) $rets[] = $nav($i) / $nav($i - 1) - 1;
        $m = array_sum($rets) / count($rets);
        $vol = sqrt(array_sum(array_map(fn ($r) => ($r - $m) ** 2, $rets)) / (count($rets) - 1)) * sqrt(52);
        $peak = 0; $mdd = 0;
        for ($i = $t - 52; $i <= $t; $i++) { $peak = max($peak, $nav($i)); $mdd = min($mdd, $nav($i) / $peak - 1); }
        $ma = array_sum(array_map(fn ($i) => $nav($i), range($t - 39, $t))) / 40;
        $gain = 0; $loss = 0;
        for ($i = $t - 13; $i <= $t; $i++) { $d = $nav($i) - $nav($i - 1); $d > 0 ? $gain += $d : $loss -= $d; }
        $rsi = $loss == 0 ? 100 : 100 - 100 / (1 + $gain / $loss);
        return ['r6m' => $nav($t) / $nav($t - 26) - 1, 'r1y' => $nav($t) / $nav($t - 52) - 1, 'r3y' => ($nav($t) / $nav($t - 156)) ** (1 / 3) - 1,
            'vol' => $vol, 'mdd' => $mdd, 'ma_gap' => $nav($t) / $ma - 1, 'rsi' => $rsi];
    }

    public function predict(array $model, array $x): float
    {
        $y = $model['weights']['bias'];
        foreach (self::FEATURES as $k) $y += $model['weights'][$k] * (($x[$k] - $model['mean'][$k]) / $model['std'][$k]);
        return $y;
    }

    // Before a model exists: favour consistent category-beating returns with lower drawdowns.
    private function heuristic(array $x): float
    {
        return 0.4 * $x['r3y'] + 0.3 * $x['r1y'] + 0.1 * $x['r6m'] - 0.1 * $x['vol'] + 0.1 * $x['mdd'];
    }

    // Closed-form ridge: (XᵀX + λI)⁻¹ Xᵀy with an unpenalised intercept.
    private function ridge(array $rows, array $mean, array $std, float $lambda): array
    {
        $k = count(self::FEATURES) + 1;
        $A = array_fill(0, $k, array_fill(0, $k, 0.0)); $b = array_fill(0, $k, 0.0);
        foreach ($rows as $r) {
            $v = [1.0];
            foreach (self::FEATURES as $f) $v[] = ($r['x'][$f] - $mean[$f]) / $std[$f];
            for ($i = 0; $i < $k; $i++) { $b[$i] += $v[$i] * $r['ex']; for ($j = 0; $j < $k; $j++) $A[$i][$j] += $v[$i] * $v[$j]; }
        }
        for ($i = 1; $i < $k; $i++) $A[$i][$i] += $lambda;
        $w = $this->solve($A, $b);
        $out = ['bias' => $w[0]];
        foreach (self::FEATURES as $i => $f) $out[$f] = $w[$i + 1];
        return $out;
    }

    private function solve(array $A, array $b): array
    {
        $n = count($b);
        for ($c = 0; $c < $n; $c++) {
            $p = $c;
            for ($r = $c + 1; $r < $n; $r++) if (abs($A[$r][$c]) > abs($A[$p][$c])) $p = $r;
            [$A[$c], $A[$p]] = [$A[$p], $A[$c]]; [$b[$c], $b[$p]] = [$b[$p], $b[$c]];
            if (abs($A[$c][$c]) < 1e-12) continue;
            for ($r = 0; $r < $n; $r++) {
                if ($r === $c) continue;
                $f = $A[$r][$c] / $A[$c][$c];
                for ($j = $c; $j < $n; $j++) $A[$r][$j] -= $f * $A[$c][$j];
                $b[$r] -= $f * $b[$c];
            }
        }
        return array_map(fn ($i) => abs($A[$i][$i]) < 1e-12 ? 0.0 : $b[$i] / $A[$i][$i], range(0, $n - 1));
    }

    private function spearman(array $a, array $b): float
    {
        $rank = function ($v) { $s = $v; asort($s); $r = []; $i = 0; foreach (array_keys($s) as $k) $r[$k] = $i++; ksort($r); return array_values($r); };
        $x = $rank($a); $y = $rank($b); $n = count($x);
        if ($n < 3) return 0;
        $mx = array_sum($x) / $n; $my = array_sum($y) / $n; $num = 0; $dx = 0; $dy = 0;
        for ($i = 0; $i < $n; $i++) { $num += ($x[$i] - $mx) * ($y[$i] - $my); $dx += ($x[$i] - $mx) ** 2; $dy += ($y[$i] - $my) ** 2; }
        return $dx && $dy ? $num / sqrt($dx * $dy) : 0;
    }

    // Peer group for comparisons: the SEBI category without the "Equity Scheme - " prefix.
    public function group(string $category): string
    {
        return trim(preg_replace('/^(Equity Scheme|Hybrid Scheme|Debt Scheme|Income\/Debt Oriented Schemes|Growth|Income)\s*-\s*/i', '', $category));
    }

    private function median(array $v): float
    {
        sort($v); $n = count($v);
        return $n ? ($n % 2 ? $v[intdiv($n, 2)] : ($v[$n / 2 - 1] + $v[$n / 2]) / 2) : 0;
    }

    private function pp(float $v): string
    {
        return ($v >= 0 ? '+' : '') . round($v * 100, 1) . '%';
    }
}
