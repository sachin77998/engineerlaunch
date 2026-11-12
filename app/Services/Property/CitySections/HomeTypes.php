<?php

namespace App\Services\Property\CitySections;

use App\Models\Property\PropertyMedia;
use App\Services\Property\CityContext;

// "Homes by type": live sale listings per home type, each tile showing a real photo of one of them.
class HomeTypes extends CitySection
{
    public function data(CityContext $c, array $options): ?array
    {
        $listing = $options['listing'] ?? 'sale';
        $base = fn () => $c->listings()->where('listing', $listing)->where('segment', 'residential');
        $counts = $base()->selectRaw("case when kind = 'land' then '_land' else coalesce(building_type, kind) end as t, count(*) as n")->groupBy('t')->pluck('n', 't');
        $tiles = collect(config('properties.home_types'))->map(function ($def, $key) use ($counts, $c, $options, $base) {
            $n = $def[2] === null ? (int) ($counts['_land'] ?? 0) : collect($def[2])->sum(fn ($t) => (int) ($counts[$t] ?? 0));
            if (!$n) return null;
            $ids = $base()->when($def[2] === null, fn ($q) => $q->where('kind', 'land'), fn ($q) => $q->where('kind', '!=', 'land')->whereIn('building_type', $def[2]))
                ->whereHas('media', fn ($m) => $m->where('type', 'photo'))->latest('published_at')->limit(1)->pluck('id');
            $photo = $ids->isNotEmpty() ? PropertyMedia::where('property_id', $ids[0])->where('type', 'photo')->orderBy('sort')->orderBy('id')->first()?->url() : null;
            return ['label' => $def[0], 'count' => $n, 'photo' => $photo, 'url' => $c->search($options['mode'] ?? 'buy', ['type' => [$key]])];
        })->filter()->values();
        return $tiles->isEmpty() ? null : ['tiles' => $tiles];
    }
}
