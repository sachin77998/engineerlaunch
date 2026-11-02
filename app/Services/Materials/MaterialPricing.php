<?php

namespace App\Services\Materials;

use App\Models\Material;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** Unit rates for the build-cost calculator and estimators, from live store prices where the store sells the item. */
class MaterialPricing
{
    private const PERCENTILES = ['basic' => 0.25, 'medium' => 0.5, 'premium' => 0.85];

    /** Every calculator line with its three rates, where they came from and a product to buy at each tier. */
    public function calculatorLines(): array
    {
        return Cache::remember('materials:calc-lines', now()->addHours(3), function () {
            $lines = [];
            foreach (config('materials.calculator') as $key => [$label, $qty, $unit, $opt]) {
                $live = isset($opt['live']) ? $this->tiers($this->offers($opt['live'])) : null;
                $lines[$key] = ['key' => $key, 'label' => $label, 'qty' => $qty, 'unit' => $unit, 'local' => !empty($opt['local']), 'min' => $opt['min'] ?? 0,
                    'live' => (bool) $live, 'rates' => $live ? array_map(fn ($t) => $t['rate'], $live) : array_combine(array_keys(self::PERCENTILES), $opt['rates']),
                    'picks' => $live ? array_map(fn ($t) => $t['pick'], $live) : null, 'count' => $live['medium']['count'] ?? 0,
                    'category' => $opt['live']['collections'][0] ?? null, 'per' => $opt['live']['per'] ?? null];
            }
            return $lines;
        });
    }

    /**
     * Products matching a line, one offer per pack (variant): [rate per unit, product, variant, pack price, pack size].
     * per = pack prices the pack itself (cement bags); kg / L / m divide by the pack size.
     */
    public function offers(array $spec): Collection
    {
        return Material::listed()->where('in_stock', true)
            ->where(fn ($q) => collect($spec['collections'])->each(fn ($c) => $q->orWhereJsonContains('collections', $c)))
            ->get()
            ->filter(fn (Material $m) => Str::contains(Str::lower($m->product_type . ' ' . $m->title), (array) $spec['type'])
                && (empty($spec['title']) || Str::contains(Str::lower($m->product_type . ' ' . $m->title), $spec['title'])))
            ->flatMap(fn (Material $m) => collect($m->variants)->filter(fn ($v) => $v['available'] && (empty($spec['variant']) || Str::contains((string) $v['title'], $spec['variant'])))
                ->map(function ($v) use ($m, $spec) {
                    if ($spec['per'] === 'pack') return ['rate' => $v['price'], 'material' => $m, 'variant' => $v];
                    if ($v['unit'] !== $spec['per'] || !$v['size']) return null;
                    return ['rate' => round($v['price'] / $v['size'], 2), 'material' => $m, 'variant' => $v];
                })->filter())
            ->sortBy('rate')->values();
    }

    /** Pack options for an estimator: products of a kind with every pack size and price (for the cheapest-pack mix). */
    public function packs(array $spec): array
    {
        return $this->offers($spec)->groupBy(fn ($o) => $o['material']->id)->map(function ($offers) {
            $m = $offers->first()['material'];
            return ['handle' => $m->handle, 'title' => $m->title, 'brand' => $m->brand, 'url' => route('materials.show', $m),
                'packs' => $offers->map(fn ($o) => ['size' => $o['variant']['size'] ?? 1, 'price' => $o['variant']['price'], 'label' => $o['variant']['title'] ?: (($o['variant']['size'] ?? 1) + 0) . ' ' . $o['variant']['unit'],
                    'index' => max(0, (int) collect($m->variants)->search(fn ($v) => $v['title'] === $o['variant']['title']))])->sortBy('size')->values()->all(),
                'rate' => $offers->min('rate')];
        })->sortBy('rate')->values()->all();
    }

    private function tiers(Collection $offers): ?array
    {
        if ($offers->isEmpty()) return null;
        $rates = $offers->pluck('rate')->values();
        $out = [];
        foreach (self::PERCENTILES as $tier => $p) {
            $rate = $rates[(int) floor($p * ($rates->count() - 1))];
            $o = $offers->sortBy(fn ($o) => abs($o['rate'] - $rate))->first();
            $out[$tier] = ['rate' => $rate, 'count' => $offers->count(), 'pick' => ['handle' => $o['material']->handle, 'title' => $o['material']->title, 'image' => $o['material']->image_url,
                'variant' => $o['variant']['title'], 'variant_index' => max(0, (int) collect($o['material']->variants)->search(fn ($v) => $v['title'] === $o['variant']['title'])), 'pack_price' => $o['variant']['price'], 'pack_size' => $o['variant']['size'] ?? null]];
        }
        return $out;
    }

    /** Factor for local lines (labour, sand, aggregate, bricks) in a city. */
    public function cityFactor(?string $city): float
    {
        foreach (config('materials.city_tiers') as [$factor, $cities]) {
            if ($city && in_array(Str::lower($city), array_map('strtolower', $cities), true)) return $factor;
        }
        return config('materials.city_tiers.other.0');
    }
}
