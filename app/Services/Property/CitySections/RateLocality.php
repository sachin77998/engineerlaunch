<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;

// "Rate where you live": pick a locality and a star rating, then write the review.
class RateLocality extends CitySection
{
    public int $minutes = 60;

    public function data(CityContext $c, array $options): ?array
    {
        $names = collect($c->zoneLocalities ?? config('properties.localities.' . $c->city->name, []))
            ->merge($c->listings()->whereNotNull('locality')->distinct()->limit(200)->pluck('locality'))
            ->unique(fn ($n) => mb_strtolower($n))->sort()->values();
        return $names->isEmpty() ? null : ['localities' => $names];
    }
}
