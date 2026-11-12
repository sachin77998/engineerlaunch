<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;

// "Browse by bedrooms": residential sale listings per bedroom count.
class BhkChoice extends CitySection
{
    public function data(CityContext $c, array $options): ?array
    {
        $counts = $c->listings()->where('listing', 'sale')->where('segment', 'residential')->whereNotNull('bhk')->groupBy('bhk')->selectRaw('bhk, count(*) as n')->pluck('n', 'bhk');
        $tiles = collect(config('properties.bhk'))->map(fn ($label, $key) => ['label' => $label, 'count' => (int) ($counts[$key] ?? 0), 'url' => $c->search('buy', ['bhk' => [$key]])])
            ->filter(fn ($t) => $t['count'] > 0)->values();
        return $tiles->isEmpty() ? null : ['tiles' => $tiles];
    }
}
