<?php

namespace App\Support;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Cache::remember that never makes a visitor wait for a rebuild: once a value is older than $ttl the old copy is still
 * served and the value is rebuilt after the response has been sent (or at the end of a console command). Only the very
 * first build of a key runs inline; `cache:warm` does those after a deploy.
 */
final class Stale
{
    /** Copies are kept this long past their freshness so an idle page still has something to show. */
    private const KEEP_SECONDS = 7 * 86400;

    private static array $refreshing = [];

    public static function remember(string $key, DateTimeInterface|DateInterval|int $ttl, Closure $build): mixed
    {
        $entry = rescue(fn () => Cache::get('stale:' . $key), null, false);
        if (is_array($entry) && array_key_exists('value', $entry)) {
            if (($entry['fresh_until'] ?? 0) < time()) self::refreshLater($key, $ttl, $build);
            return $entry['value'];
        }
        return self::build($key, $ttl, $build);
    }

    /** Rebuilds now (the warm command and scheduled refreshes use this). */
    public static function build(string $key, DateTimeInterface|DateInterval|int $ttl, Closure $build): mixed
    {
        $value = $build();
        Cache::put('stale:' . $key, ['value' => $value, 'fresh_until' => time() + self::seconds($ttl)], self::KEEP_SECONDS);
        return $value;
    }

    public static function forget(string $key): void
    {
        Cache::forget('stale:' . $key);
    }

    private static function refreshLater(string $key, DateTimeInterface|DateInterval|int $ttl, Closure $build): void
    {
        if (isset(self::$refreshing[$key])) return;
        self::$refreshing[$key] = true;
        // One rebuild at a time per key, across all visitors.
        $lock = rescue(fn () => Cache::lock('stale-lock:' . $key, 900), null, false);
        if ($lock && !$lock->get()) return;
        $rebuild = function () use ($key, $ttl, $build, $lock) {
            @set_time_limit(900);
            try { self::build($key, $ttl, $build); } catch (\Throwable $e) { report($e); } finally { $lock?->release(); }
        };
        // Commands and queue workers have no visitor waiting (and a worker may never reach the terminate step).
        if (app()->runningInConsole()) $rebuild();
        else app()->terminating($rebuild);
    }

    private static function seconds(DateTimeInterface|DateInterval|int $ttl): int
    {
        if ($ttl instanceof DateTimeInterface) return max(1, $ttl->getTimestamp() - time());
        if ($ttl instanceof DateInterval) return max(1, (new \DateTimeImmutable())->add($ttl)->getTimestamp() - time());
        return max(1, $ttl);
    }
}
