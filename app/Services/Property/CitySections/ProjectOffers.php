<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;

// "Builder offers": projects whose builder has a running offer.
class ProjectOffers extends CitySection
{
    public function data(CityContext $c, array $options): ?array
    {
        $items = $c->projects()->whereHas('liveOffers')->with(['images', 'liveOffers'])->latest('updated_at')->limit(10)->get();
        return $items->isEmpty() ? null : ['items' => $items];
    }
}
