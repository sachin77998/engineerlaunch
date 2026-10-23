<?php

namespace App\Services\Insights;

use App\Models\CreditRating;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Machine-learning view of a borrower's credit direction.
 *
 * Training data: consecutive rating actions of the same entity (ICRA/CARE). Label = 1 when the next action is a
 * reaffirmation or upgrade with no fall in the rating, 0 when it is a downgrade, a fall in score or a move to
 * "issuer not cooperating". Features come from the earlier action: rating level, outlook, liquidity, non-cooperation,
 * change in rated amount, action direction and agency-reported interest cover, gearing, debt/EBITDA and margin.
 * Model: L2-regularised logistic regression; the latest quarter of pairs is held out to report AUC and accuracy.
 */
class CreditOutlookModel
{
    public const FEATURES = ['score', 'outlook', 'liquidity', 'non_coop', 'rated_change', 'action', 'icr', 'gearing', 'debt_ebitda', 'margin'];
    private const PATH = 'insights/credit-outlook-model.json';
    private const LIQUIDITY = ['Poor' => 0, 'Stretched' => 1, 'Adequate' => 2, 'Strong' => 3, 'Superior' => 4];

    public function train(): array
    {
        $pairs = [];
        CreditRating::where('parse_status', 'parsed')->whereNotNull('published_on')->whereNotNull('score')
            ->orderBy('published_on')->get(['id', 'company_id', 'name_key', 'agency', 'published_on', 'action', 'score', 'outlook', 'liquidity', 'non_cooperating', 'withdrawn', 'total_rated_crore', 'financials'])
            ->groupBy(fn ($r) => $r->agency . '|' . ($r->company_id ?: $r->name_key))
            ->each(function (Collection $list) use (&$pairs) {
                $list = $list->values();
                for ($i = 0; $i < $list->count() - 1; $i++) {
                    $now = $list[$i]; $next = $list[$i + 1];
                    // A withdrawal says nothing about direction (often loans repaid); skip it as a label.
                    if ($next->withdrawn || $now->withdrawn) continue;
                    $bad = $next->action === 'downgraded' || ($next->score !== null && $next->score < $now->score) || ($next->non_cooperating && !$now->non_cooperating);
                    $pairs[] = ['x' => $this->features($now, $list[$i - 1] ?? null), 'y' => $bad ? 0 : 1, 'date' => $now->published_on->toDateString()];
                }
            });
        if (count($pairs) < 40) return ['ok' => false, 'message' => 'Only ' . count($pairs) . ' consecutive rating pairs so far; the model trains once 40 exist (the rating import adds more daily).'];
        usort($pairs, fn ($a, $b) => strcmp($a['date'], $b['date']));
        $cut = (int) floor(count($pairs) * 0.75);
        $train = array_slice($pairs, 0, $cut); $test = array_slice($pairs, $cut);
        [$mean, $std] = $this->scaler($train);
        $w = $this->fit($train, $mean, $std);
        $model = ['weights' => $w, 'mean' => $mean, 'std' => $std, 'pairs' => count($pairs), 'positive_rate' => round(array_sum(array_column($pairs, 'y')) / count($pairs), 3), 'trained_at' => now()->toIso8601String()];
        if ($test) {
            $p = array_map(fn ($r) => $this->prob($model, $r['x']), $test);
            $y = array_column($test, 'y');
            $model['test_auc'] = $this->auc($p, $y);
            $model['test_accuracy'] = round(count(array_filter(array_keys($p), fn ($i) => ($p[$i] >= 0.5 ? 1 : 0) === $y[$i])) / count($p) * 100, 1);
            $model['test_pairs'] = count($test);
        }
        // Each run is stored; the newest becomes the active model.
        DB::transaction(function () use ($model) {
            DB::table('credit_model_runs')->where('model', 'credit_outlook')->update(['is_active' => false]);
            DB::table('credit_model_runs')->insert(['model' => 'credit_outlook', 'pairs' => $model['pairs'], 'test_pairs' => $model['test_pairs'] ?? null, 'test_auc' => $model['test_auc'] ?? null,
                'test_accuracy' => $model['test_accuracy'] ?? null, 'positive_rate' => $model['positive_rate'], 'weights' => json_encode($model['weights']), 'mean' => json_encode($model['mean']),
                'std' => json_encode($model['std']), 'is_active' => true, 'trained_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        });
        $this->cached = null;
        return ['ok' => true] + $model;
    }

    private ?array $cached = null;

    /** Active run from credit_model_runs; the older JSON file is read only if no run is stored yet. */
    public function model(): ?array
    {
        if ($this->cached) return $this->cached;
        $run = rescue(fn () => DB::table('credit_model_runs')->where('model', 'credit_outlook')->where('is_active', true)->latest('trained_at')->first(), null, false);
        if ($run) return $this->cached = ['weights' => json_decode($run->weights, true), 'mean' => json_decode($run->mean, true), 'std' => json_decode($run->std, true), 'pairs' => $run->pairs,
            'test_auc' => $run->test_auc !== null ? (float) $run->test_auc : null, 'test_accuracy' => $run->test_accuracy !== null ? (float) $run->test_accuracy : null, 'test_pairs' => $run->test_pairs,
            'positive_rate' => (float) $run->positive_rate, 'trained_at' => $run->trained_at];
        return Storage::disk('local')->exists(self::PATH) ? json_decode(Storage::disk('local')->get(self::PATH), true) : null;
    }

    /** Probability that the next rating action is stable or better, with the factors pushing it up or down. */
    public function predict(CreditRating $latest, ?CreditRating $previous = null): array
    {
        $x = $this->features($latest, $previous);
        $model = $this->model();
        if ($model) {
            $p = $this->prob($model, $x);
            $drivers = collect(self::FEATURES)->map(fn ($f) => ['feature' => $f, 'impact' => $model['weights'][$f] * (($x[$f] - $model['mean'][$f]) / $model['std'][$f])])
                ->sortByDesc(fn ($d) => abs($d['impact']))->take(4)->values()->all();
        } else {
            // Before enough history exists: transparent scorecard on the same inputs.
            $z = 0.9 + 0.12 * ($x['score'] - 12) + 0.8 * $x['outlook'] + 0.35 * ($x['liquidity'] - 2) - 1.6 * $x['non_coop'] + 0.4 * $x['action'] + 0.05 * min(10, $x['icr']) - 0.08 * min(10, $x['debt_ebitda']);
            $p = 1 / (1 + exp(-$z));
            $drivers = [];
        }
        return ['probability' => round($p, 3), 'features' => $x, 'drivers' => $drivers, 'model' => (bool) $model,
            'label' => $p >= 0.8 ? 'Stable or improving' : ($p >= 0.6 ? 'Mostly stable' : ($p >= 0.4 ? 'At risk of downgrade' : 'Downgrade likely'))];
    }

    public function features(CreditRating $r, ?CreditRating $prev): array
    {
        $fin = $this->latestMetrics($r->financials);
        $outlook = strtolower((string) $r->outlook);
        return [
            'score' => (float) $r->score,
            'outlook' => str_contains($outlook, 'positive') ? 1.0 : (str_contains($outlook, 'negative') ? -1.0 : (str_contains($outlook, 'watch') ? -0.5 : 0.0)),
            'liquidity' => (float) (self::LIQUIDITY[$r->liquidity] ?? 2),
            'non_coop' => $r->non_cooperating ? 1.0 : 0.0,
            'rated_change' => $prev && $prev->total_rated_crore > 0 && $r->total_rated_crore ? max(-1, min(2, $r->total_rated_crore / $prev->total_rated_crore - 1)) : 0.0,
            'action' => $r->action === 'upgraded' ? 1.0 : ($r->action === 'downgraded' ? -1.0 : 0.0),
            'icr' => max(0, min(20, (float) ($fin['interest_coverage'] ?? 3))),
            'gearing' => max(0, min(10, (float) ($fin['gearing'] ?? 1))),
            'debt_ebitda' => max(-5, min(15, (float) ($fin['debt_to_ebitda'] ?? 3))),
            'margin' => max(-50, min(60, (float) ($fin['ebitda_margin'] ?? 10))),
        ];
    }

    /** Latest reported value of each metric in an agency financials table. */
    public function latestMetrics($financials): array
    {
        $out = [];
        foreach ((array) ($financials['rows'] ?? []) as $row) {
            if (empty($row['metric'])) continue;
            $vals = array_values(array_filter((array) ($row['values'] ?? []), fn ($v) => is_numeric($v)));
            if ($vals) $out[$row['metric']] = (float) end($vals);
        }
        return $out;
    }

    private function scaler(array $rows): array
    {
        $mean = []; $std = [];
        foreach (self::FEATURES as $f) {
            $v = array_map(fn ($r) => $r['x'][$f], $rows);
            $mean[$f] = array_sum($v) / count($v);
            $std[$f] = max(1e-6, sqrt(array_sum(array_map(fn ($x) => ($x - $mean[$f]) ** 2, $v)) / count($v)));
        }
        return [$mean, $std];
    }

    // Batch gradient descent with L2 penalty; class weights balance the rare downgrades.
    private function fit(array $rows, array $mean, array $std, float $lambda = 0.05, int $epochs = 600, float $lr = 0.3): array
    {
        $w = array_fill_keys(self::FEATURES, 0.0); $b = 0.0;
        $pos = max(1, array_sum(array_column($rows, 'y'))); $neg = max(1, count($rows) - $pos);
        $wPos = count($rows) / (2 * $pos); $wNeg = count($rows) / (2 * $neg);
        $n = count($rows);
        for ($e = 0; $e < $epochs; $e++) {
            $g = array_fill_keys(self::FEATURES, 0.0); $gb = 0.0;
            foreach ($rows as $r) {
                $z = $b; $xs = [];
                foreach (self::FEATURES as $f) { $xs[$f] = ($r['x'][$f] - $mean[$f]) / $std[$f]; $z += $w[$f] * $xs[$f]; }
                $err = (1 / (1 + exp(-$z)) - $r['y']) * ($r['y'] ? $wPos : $wNeg);
                foreach (self::FEATURES as $f) $g[$f] += $err * $xs[$f];
                $gb += $err;
            }
            foreach (self::FEATURES as $f) $w[$f] -= $lr * ($g[$f] / $n + $lambda * $w[$f]);
            $b -= $lr * $gb / $n;
        }
        return $w + ['bias' => $b];
    }

    private function prob(array $model, array $x): float
    {
        $z = $model['weights']['bias'];
        foreach (self::FEATURES as $f) $z += $model['weights'][$f] * (($x[$f] - $model['mean'][$f]) / $model['std'][$f]);
        return 1 / (1 + exp(-$z));
    }

    private function auc(array $p, array $y): ?float
    {
        $pos = []; $neg = [];
        foreach ($p as $i => $v) $y[$i] ? $pos[] = $v : $neg[] = $v;
        if (!$pos || !$neg) return null;
        $wins = 0;
        foreach ($pos as $a) foreach ($neg as $b) $wins += $a > $b ? 1 : ($a == $b ? 0.5 : 0);
        return round($wins / (count($pos) * count($neg)), 3);
    }
}
