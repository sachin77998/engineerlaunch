<?php

namespace App\Services\Market;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Vehicle sales analytics (latest month, 3/6/12-month totals, month-on-month and year-on-year growth, retail vs
 * wholesale) and commodity prices. Figures come from imported CSVs (SIAM/FADA/company releases) and, for gold and
 * silver, from GoldAPI when GOLDAPI_KEY is set. Nothing is estimated: missing months simply show as blank.
 */
class MarketData
{
    public const VEHICLE_COLUMNS = ['segment', 'brand', 'model', 'month', 'wholesale_units', 'retail_units', 'export_units', 'source', 'source_url'];
    public const PRICE_COLUMNS = ['commodity', 'variant', 'unit', 'price', 'city', 'priced_on', 'source'];

    /** Per-model analytics for a segment (and optionally one brand). */
    public function vehicleTable(string $segment, ?string $brand = null): Collection
    {
        $latest = DB::table('vehicle_sales')->where('segment', $segment)->when($brand, fn ($q) => $q->where('brand', $brand))->max('month');
        if (!$latest) return collect();
        $latest = Carbon::parse($latest)->startOfMonth();
        $rows = DB::table('vehicle_sales')->where('segment', $segment)->when($brand, fn ($q) => $q->where('brand', $brand))
            ->where('month', '>=', $latest->copy()->subMonths(23))->get()->groupBy(fn ($r) => $r->brand . '|' . $r->model);

        return $rows->map(function (Collection $months, string $key) use ($latest) {
            [$brand, $model] = explode('|', $key, 2);
            $byMonth = $months->keyBy(fn ($r) => Carbon::parse($r->month)->format('Y-m'));
            $units = fn (Carbon $m, string $col = 'wholesale_units') => $byMonth[$m->format('Y-m')]->{$col} ?? null;
            $sum = function (int $count, string $col) use ($latest, $units) {
                $total = null;
                for ($i = 0; $i < $count; $i++) { $v = $units($latest->copy()->subMonths($i), $col); if ($v !== null) $total = ($total ?? 0) + $v; }
                return $total;
            };
            $current = $units($latest);
            $previous = $units($latest->copy()->subMonth());
            $lastYear = $units($latest->copy()->subYear());
            $last12 = $sum(12, 'wholesale_units');
            $prior12 = null;
            for ($i = 12; $i < 24; $i++) { $v = $units($latest->copy()->subMonths($i)); if ($v !== null) $prior12 = ($prior12 ?? 0) + $v; }
            return (object) [
                'brand' => $brand, 'model' => $model, 'month' => $latest->format('M Y'),
                'wholesale' => $current, 'retail' => $units($latest, 'retail_units'), 'exports' => $units($latest, 'export_units'),
                'three' => $sum(3, 'wholesale_units'), 'six' => $sum(6, 'wholesale_units'), 'twelve' => $last12,
                'mom' => $this->growth($current, $previous), 'yoy' => $this->growth($current, $lastYear), 'yoy12' => $this->growth($last12, $prior12),
                'retail_vs_wholesale' => ($current && $units($latest, 'retail_units') !== null) ? round($units($latest, 'retail_units') / $current * 100, 1) : null,
                'source' => $byMonth[$latest->format('Y-m')]->source ?? null,
            ];
        })->sortByDesc('wholesale')->values();
    }

    /** Brand totals for the latest month of a segment. */
    public function brandTotals(string $segment): Collection
    {
        return $this->vehicleTable($segment)->groupBy('brand')->map(fn ($models, $brand) => (object) [
            'brand' => $brand, 'models' => $models->count(), 'wholesale' => $models->sum('wholesale'), 'retail' => $models->sum('retail'),
            'twelve' => $models->sum('twelve'),
        ])->sortByDesc('wholesale')->values();
    }

    public function segments(): Collection
    {
        return DB::table('vehicle_sales')->select('segment')->distinct()->orderBy('segment')->pluck('segment');
    }

    /** Latest price per commodity/variant/unit, with the change from the previous recorded day. */
    public function latestPrices(): Collection
    {
        return DB::table('commodity_prices')->orderByDesc('priced_on')->get()
            ->groupBy(fn ($p) => $p->commodity . '|' . $p->variant . '|' . $p->unit . '|' . $p->city)
            ->map(function (Collection $history) {
                $latest = $history->first();
                $previous = $history->skip(1)->first();
                $latest->change = $previous ? round($latest->price - $previous->price, 2) : null;
                return $latest;
            })->sortBy(['commodity', 'variant'])->values();
    }

    /** Imports a CSV with a header row; returns the number of rows saved. */
    public function import(string $type, string $path): int
    {
        $columns = $type === 'vehicles' ? self::VEHICLE_COLUMNS : self::PRICE_COLUMNS;
        $handle = fopen($path, 'r');
        if (!$handle) throw new RuntimeException('Cannot read the CSV file.');
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), fgetcsv($handle) ?: []);
        if (array_diff(array_slice($columns, 0, 4), $header)) {
            throw new RuntimeException('The CSV header must include: ' . implode(', ', $columns));
        }
        $saved = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) continue;
            $data = array_intersect_key(array_combine($header, array_pad($row, count($header), null)), array_flip($columns));
            $data = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
            if ($type === 'vehicles') {
                $data['month'] = Carbon::parse($data['month'] . (strlen($data['month']) === 7 ? '-01' : ''))->startOfMonth()->toDateString();
                foreach (['wholesale_units', 'retail_units', 'export_units'] as $n) $data[$n] = ($data[$n] ?? '') === '' ? null : (int) str_replace(',', '', $data[$n]);
                $data['segment'] = strtolower($data['segment']);
                DB::table('vehicle_sales')->updateOrInsert(
                    ['segment' => $data['segment'], 'brand' => $data['brand'], 'model' => $data['model'], 'month' => $data['month']],
                    array_diff_key($data, array_flip(['segment', 'brand', 'model', 'month'])) + ['updated_at' => now(), 'created_at' => now()]);
            } else {
                $data['priced_on'] = Carbon::parse($data['priced_on'])->toDateString();
                $data['price'] = (float) str_replace(',', '', $data['price']);
                $data['commodity'] = strtolower($data['commodity']);
                $data['variant'] ??= ''; $data['city'] ??= '';
                DB::table('commodity_prices')->updateOrInsert(
                    ['commodity' => $data['commodity'], 'variant' => $data['variant'], 'unit' => $data['unit'], 'city' => $data['city'], 'priced_on' => $data['priced_on']],
                    ['price' => $data['price'], 'source' => $data['source'] ?? 'Manual import', 'currency' => 'INR', 'updated_at' => now(), 'created_at' => now()]);
            }
            $saved++;
        }
        fclose($handle);
        return $saved;
    }

    /** Gold (24K/22K) and silver in INR per gram and per 10 g / kg from GoldAPI (free key). @return int rows saved */
    public function fetchMetals(): int
    {
        $key = config('services.goldapi.key');
        if (!$key) throw new RuntimeException('Set GOLDAPI_KEY (free at goldapi.io) to fetch gold and silver prices.');
        $saved = 0;
        $today = now()->toDateString();
        foreach (['XAU' => 'gold', 'XAG' => 'silver'] as $symbol => $commodity) {
            $res = Http::withHeaders(['x-access-token' => $key])->timeout(20)->get("https://www.goldapi.io/api/{$symbol}/INR");
            if (!$res->successful()) throw new RuntimeException("GoldAPI returned HTTP {$res->status()} for {$symbol}");
            $prices = $commodity === 'gold'
                ? [['24K', 'gram', $res->json('price_gram_24k')], ['24K', '10 gram', $res->json('price_gram_24k') * 10],
                   ['22K', 'gram', $res->json('price_gram_22k')], ['22K', '10 gram', $res->json('price_gram_22k') * 10]]
                : [['999', 'gram', $res->json('price_gram_24k')], ['999', 'kg', $res->json('price_gram_24k') * 1000]];
            foreach ($prices as [$variant, $unit, $price]) {
                if (!$price) continue;
                DB::table('commodity_prices')->updateOrInsert(
                    ['commodity' => $commodity, 'variant' => $variant, 'unit' => $unit, 'city' => '', 'priced_on' => $today],
                    ['price' => round($price, 2), 'currency' => 'INR', 'source' => 'GoldAPI (spot, before GST and making charges)', 'updated_at' => now(), 'created_at' => now()]);
                $saved++;
            }
        }
        return $saved;
    }

    private function growth(?int $current, ?int $base): ?float
    {
        return ($current === null || !$base) ? null : round(($current - $base) / $base * 100, 1);
    }
}
