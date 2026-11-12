<?php

namespace App\Services\Property;

use Closure;
use Illuminate\Support\Facades\Cache;

// Versioned cache keys for property pages: bumping a city's version makes every cached section of that city stale at once
// (works on any cache store, unlike tags). Listings and projects changing bump their city through FlushPropertyCache;
// bump('_all') makes every city stale (e.g. after city photos change).
class PropertyCache
{
    public static function remember(string $city, string $key, $ttl, Closure $fn)
    {
        return Cache::remember('property-city:' . $city . ':v' . self::version($city) . '.' . self::version('_all') . ':' . $key, $ttl, $fn);
    }

    public static function version(string $city): int
    {
        return (int) Cache::rememberForever('property-city-version:' . $city, fn () => 1);
    }

    public static function bump(?string $city): void
    {
        if ($city) Cache::forever('property-city-version:' . $city, self::version($city) + 1);
    }
}
