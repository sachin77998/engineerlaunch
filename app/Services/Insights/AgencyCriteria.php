<?php

namespace App\Services\Insights;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Other rating agencies' sector methodologies (credit_agency_criteria, seeded from config/icra_methodology.php). Grades a
 * company's figures on the agency's five-step factor scale; qualitative factors are listed with the agency's description.
 */
class AgencyCriteria
{
    public const GRADES = [5 => 'Strong', 4 => 'Comfortable', 3 => 'Adequate', 2 => 'Moderate', 1 => 'Weak'];
    // Liquidity is graded on its own scale in the annexures (Superior … Poor).
    public const LIQUIDITY_GRADES = [5 => 'Superior', 4 => 'Strong', 3 => 'Adequate', 2 => 'Stretched', 1 => 'Poor'];

    public static function label(string $group, int $grade): string
    {
        return ($group === 'liquidity' ? self::LIQUIDITY_GRADES : self::GRADES)[$grade];
    }
    private const CACHE = 'agency-criteria:v2';

    /** Five-step cut points [strongest, c1, c2, weakest]; the middle two are spaced evenly (linear or on a ratio scale). */
    public static function band(float $strongest, float $weakest, string $spacing = 'linear'): array
    {
        if ($spacing === 'log' && $strongest > 0 && $weakest > 0) {
            $r = $weakest / $strongest;
            return [$strongest, round($strongest * $r ** (1 / 3), 2), round($strongest * $r ** (2 / 3), 2), $weakest];
        }
        return [$strongest, round($strongest + ($weakest - $strongest) / 3, 2), round($strongest + ($weakest - $strongest) * 2 / 3, 2), $weakest];
    }

    public static function grade(float $v, array $band, string $dir): int
    {
        foreach ($band as $i => $limit) if ($dir === 'up' ? $v >= $limit : $v <= $limit) return 5 - $i;
        return 1;
    }

    /** Adds configured criteria and refreshes rows the owner has never edited (updated_at = created_at). Returns rows written. */
    public function seed(bool $reset = false): int
    {
        if (!Schema::hasTable('credit_agency_criteria')) return 0;
        $n = 0; $now = now();
        if ($reset) DB::table('credit_agency_criteria')->delete();
        foreach (config('icra_methodology', []) as $key => $c) {
            $factors = array_map(fn ($f) => isset($f['strongest_value'], $f['weakest_value'])
                ? $f + ['band' => self::band((float) $f['strongest_value'], (float) $f['weakest_value'], $f['spacing'] ?? 'linear')] : $f, $c['factors']);
            $values = ['agency' => $c['agency'], 'sector_key' => $c['sector_key'], 'applies_if' => $c['applies_if'] ?? null, 'name' => $c['name'], 'criteria' => $c['criteria'], 'published_on' => $c['published_on'] ?? null,
                'factors' => json_encode($factors, JSON_UNESCAPED_UNICODE), 'adjustments' => json_encode($c['adjustments'] ?? [], JSON_UNESCAPED_UNICODE),
                'notes' => json_encode($c['notes'] ?? [], JSON_UNESCAPED_UNICODE), 'is_active' => true];
            $row = DB::table('credit_agency_criteria')->where('key', $key)->first();
            if ($row && (string) $row->updated_at !== (string) $row->created_at) continue;   // edited by the owner
            if ($row && collect($values)->every(fn ($v, $k) => (string) (is_bool($v) ? (int) $v : $v) === (string) $row->$k)) continue;
            DB::table('credit_agency_criteria')->updateOrInsert(['key' => $key], $values + ['created_at' => $row->created_at ?? $now, 'updated_at' => $row->created_at ?? $now]);
            $n++;
        }
        $this->flush();
        return $n;
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE);
    }

    /** @return array<int, array> active criteria, decoded */
    public function all(): array
    {
        if (!Schema::hasTable('credit_agency_criteria')) return [];
        return Cache::rememberForever(self::CACHE, function () {
            if (!DB::table('credit_agency_criteria')->exists()) $this->seed();
            return DB::table('credit_agency_criteria')->where('is_active', true)->orderBy('agency')->orderBy('name')->get()->map(fn ($r) => [
                'id' => $r->id, 'key' => $r->key, 'agency' => $r->agency, 'sector_key' => $r->sector_key, 'applies_if' => $r->applies_if ?? null, 'name' => $r->name, 'criteria' => $r->criteria,
                'published_on' => $r->published_on, 'factors' => json_decode($r->factors, true) ?: [], 'adjustments' => json_decode((string) $r->adjustments, true) ?: [],
                'notes' => json_decode((string) $r->notes, true) ?: [],
            ])->all();
        });
    }

    /** @param string $about the company's industry and name, matched against a narrower paper's applies_if pattern */
    public function forSector(string $sectorKey, string $about = ''): array
    {
        return array_values(array_filter($this->all(), fn ($c) => $c['sector_key'] === $sectorKey
            && (empty($c['applies_if']) || @preg_match($c['applies_if'], $about) === 1)));
    }

    /**
     * @param array $metrics figure key => value (null when not available), plus 'liquidity' (level) and 'parent_support' (bool|null)
     * @param array $sources figure key => where the value came from
     */
    public function evaluate(array $c, array $metrics, array $sources = []): array
    {
        $factors = [];
        foreach ($c['factors'] as $f) {
            $v = isset($f['metric']) ? ($metrics[$f['metric']] ?? null) : null;
            // A fallback measure (return on net worth when RoEV is not disclosed) is only graded when the primary one is missing.
            $replaced = isset($f['fallback_for']) && ($metrics[$f['fallback_for']] ?? null) !== null;
            $grade = $v !== null && !$replaced && isset($f['band']) ? self::grade((float) $v, $f['band'], $f['dir'] ?? 'up') : null;
            $factors[] = ['key' => $f['key'], 'group' => $f['group'], 'sub' => $f['sub'] ?? null, 'label' => $f['label'], 'unit' => $f['unit'] ?? null,
                'value' => $v, 'grade' => $grade, 'grade_label' => $grade ? self::label($f['group'], $grade) : null, 'qualitative' => !isset($f['metric']), 'band' => $f['band'] ?? null, 'dir' => $f['dir'] ?? null,
                'strongest' => $f['strongest'] ?? null, 'weakest' => $f['weakest'] ?? null,
                'source' => $replaced ? 'Not graded: RoEV is available' : (isset($f['metric']) ? ($sources[$f['metric']] ?? null) : null)];
        }
        $groups = [];
        foreach (array_values(array_unique(array_column($factors, 'group'))) as $g) {
            $grades = collect($factors)->where('group', $g)->pluck('grade')->filter()->sort()->values();
            $grade = $grades->isEmpty() ? null : (int) $grades[intdiv($grades->count() - 1, 2)];
            $groups[$g] = ['grade' => $grade, 'label' => $grade ? self::label($g, $grade) : null, 'graded' => $grades->count(), 'of' => collect($factors)->where('group', $g)->count()];
        }
        $adjustments = array_map(fn ($a) => ['key' => $a['key'], 'label' => $a['label']] + $this->effect($a['metric'] ?? null, $metrics), $c['adjustments']);
        return ['key' => $c['key'], 'agency' => $c['agency'], 'name' => $c['name'], 'criteria' => $c['criteria'], 'published_on' => $c['published_on'],
            'factors' => $factors, 'groups' => $groups, 'adjustments' => $adjustments, 'notes' => $c['notes']];
    }

    /** Enhance / Support-Neutral / Hinder from the figures on file; null when it needs the analyst's judgement. */
    private function effect(?string $metric, array $m): array
    {
        return match ($metric) {
            'liquidity' => match (true) {
                ($m['liquidity'] ?? null) === null => ['effect' => null, 'why' => null],
                (bool) preg_match('/strong|superior/i', $m['liquidity']) => ['effect' => 'Enhance', 'why' => 'Liquidity assessed as ' . $m['liquidity']],
                (bool) preg_match('/adequate/i', $m['liquidity']) => ['effect' => 'Support / Neutral', 'why' => 'Liquidity assessed as ' . $m['liquidity']],
                default => ['effect' => 'Hinder', 'why' => 'Liquidity assessed as ' . $m['liquidity']],
            },
            // Jewellery retail: liquidity level, with how much of the (readily saleable) inventory is funded by current liabilities.
            'liquidity_inventory' => ($m['liquidity'] ?? null) === null && ($m['cl_inventory'] ?? null) === null ? ['effect' => null, 'why' => null]
                : ['effect' => ($m['liquidity'] ?? null) === null ? null : ((bool) preg_match('/strong|superior/i', $m['liquidity']) ? 'Enhance' : ((bool) preg_match('/adequate/i', $m['liquidity']) ? 'Support / Neutral' : 'Hinder')),
                    'why' => implode('; ', array_filter([($m['liquidity'] ?? null) ? 'Liquidity assessed as ' . $m['liquidity'] : null,
                        ($m['cl_inventory'] ?? null) !== null ? '3-year average current liabilities / inventory ' . $m['cl_inventory'] . 'x' : null,
                        ($m['tol_inventory'] ?? null) !== null ? 'total outside liabilities / inventory ' . $m['tol_inventory'] . 'x' : null]))],
            'rpt_revenue' => ($v = $m['rpt_revenue'] ?? null) === null ? ['effect' => null, 'why' => null]
                : ($v > 30 ? ['effect' => 'Hinder', 'why' => "Related-party transactions are {$v}% of revenue"] : ['effect' => 'Support / Neutral', 'why' => "Related-party transactions are {$v}% of revenue"]),
            // Insurer hybrids: coupons need IRDAI approval below 1.50x solvency; a call without prior approval needs 1.80x afterwards.
            'hybrid_servicing' => ($sv = $m['solvency_ratio'] ?? null) === null ? ['effect' => null, 'why' => null]
                : ($sv >= 1.8 ? ['effect' => 'Support / Neutral', 'why' => "Solvency {$sv}x: sub-debt coupons and a call need no prior IRDAI approval (call threshold 1.80x)"]
                    : ($sv >= 1.5 ? ['effect' => 'Hinder', 'why' => "Solvency {$sv}x: above the 1.50x minimum, but a call would need prior IRDAI approval (below 1.80x)"]
                        : ['effect' => 'Hinder', 'why' => "Solvency {$sv}x is below the 1.50x minimum: coupon servicing needs prior IRDAI approval"])),
            'parent_support' => ($m['parent_support'] ?? null) ? ['effect' => 'Enhance', 'why' => $m['parent_support_why'] ?? 'Demonstrated funding support from the group'] : ['effect' => null, 'why' => null],
            default => ['effect' => null, 'why' => null],
        };
    }
}
