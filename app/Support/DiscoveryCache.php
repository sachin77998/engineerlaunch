<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class DiscoveryCache
{
    public const TTL_SECONDS = 3600;

    public static function ttl(): int
    {
        return (int) config('search.shared_cache_seconds', self::TTL_SECONDS);
    }

    public static function key(string $scope, array $parameters = []): string
    {
        ksort($parameters);
        return 'discovery:v'.Cache::get('discovery:version', 1).':'.$scope.':'.sha1(json_encode($parameters));
    }

    public static function invalidate(): void
    {
        if (!Cache::has('discovery:version')) Cache::forever('discovery:version', 1);
        Cache::increment('discovery:version');
    }
}
