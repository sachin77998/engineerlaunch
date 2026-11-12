<?php

namespace App\Services\Property;

use App\Models\Property\Project;
use App\Models\Property\Property;
use App\Models\Property\PropertyCity;
use App\Models\Property\PropertyLocality;
use Illuminate\Database\Eloquent\Builder;

// What a city page section needs to know: the city, the chosen zone (e.g. "Bangalore East") and who is browsing.
class CityContext
{
    public ?array $zoneLocalities;

    public function __construct(public PropertyCity $city, public ?string $zone, public array $visitorIds)
    {
        $this->zoneLocalities = $zone && $city->exists
            ? PropertyLocality::where(['city_id' => $city->id, 'zone' => $zone])->pluck('name')->all() : null;
    }

    public function slug(): string { return $this->city->slug; }

    public function place(): string { return $this->zone ?: $this->city->name; }

    /** Live listings in the city (and zone). */
    public function listings(): Builder
    {
        return Property::live()->where('city_slug', $this->city->slug)->when($this->zoneLocalities !== null, fn ($q) => $q->whereIn('locality', $this->zoneLocalities ?: ['']));
    }

    public function projects(): Builder
    {
        return Project::live()->where('city_slug', $this->city->slug)->when($this->zoneLocalities !== null, fn ($q) => $q->whereIn('locality', $this->zoneLocalities ?: ['']));
    }

    /** A search URL in this city with extra filters, e.g. search('buy', ['bhk' => ['2']]). */
    public function search(string $mode, array $query = []): string
    {
        if ($this->zoneLocalities) $query['loc'] = array_slice($this->zoneLocalities, 0, 30);
        return route('properties.search', [$mode, $this->city->slug]) . ($query ? '?' . http_build_query($query) : '');
    }

    public function cacheKey(): string { return md5((string) $this->zone); }
}
