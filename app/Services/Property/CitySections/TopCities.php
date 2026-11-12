<?php

namespace App\Services\Property\CitySections;

use App\Models\Property\Property;
use App\Models\Property\PropertyCity;
use App\Services\Property\CityContext;

// "Popular cities": cities by live listings, with their photo.
class TopCities extends CitySection
{
    public int $minutes = 60;

    public function data(CityContext $c, array $options): ?array
    {
        $counts = Property::live()->whereNotNull('city_slug')->groupBy('city_slug')->selectRaw('city_slug, count(*) as n')->pluck('n', 'city_slug');
        $items = PropertyCity::where('is_active', true)->where('country', $c->city->country ?: 'India')->where('slug', '!=', $c->slug())->get()
            ->map(fn ($city) => ['city' => $city, 'count' => (int) ($counts[$city->slug] ?? 0)])
            ->sortBy(fn ($r) => [-$r['count'], $r['city']->sort])->take($options['limit'] ?? 12)->values();
        return $items->isEmpty() ? null : ['items' => $items];
    }
}
