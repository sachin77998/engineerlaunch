<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;
use Illuminate\Support\Str;

// One block of the city page. Registered in config/property_city.php; a new block is a new subclass plus one config line.
// data() returns the view data, or null to hide the block. Blocks with $minutes > 0 are cached per city and zone
// (dropped as soon as a listing or project in the city changes); $minutes = 0 means it depends on the visitor and is never cached.
abstract class CitySection
{
    public int $minutes = 10;

    abstract public function data(CityContext $c, array $options): ?array;

    public function view(): string
    {
        return 'properties.city.' . Str::kebab(class_basename(static::class));
    }
}
