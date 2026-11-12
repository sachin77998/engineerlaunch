<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;

// Hero carousel: handpicked projects in the city that have a cover photo (builder logo, RERA number, configurations, starting price).
class FeaturedBanner extends CitySection
{
    public function data(CityContext $c, array $options): ?array
    {
        $items = $c->projects()->where('is_featured', true)->whereHas('images')->with(['images', 'builder'])->latest('updated_at')->limit($options['limit'] ?? 5)->get();
        return $items->isEmpty() ? null : ['items' => $items];
    }
}
