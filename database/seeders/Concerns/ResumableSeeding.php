<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Cache;

/**
 * Lets long seeders run in slices when started from the browser (/admin/maintenance), where shared hosting
 * kills requests after ~30 s. From the command line (deploy script) they still run to completion in one go.
 * A paused seeder prints "[continue]"; the setup page then runs the same step again until it finishes.
 */
trait ResumableSeeding
{
    private float $sliceStartedAt;

    protected function startSlice(): int
    {
        $this->sliceStartedAt = microtime(true);
        @ini_set('memory_limit', '512M');
        return (int) Cache::get($this->progressKey(), 0);
    }

    protected function sliceExhausted(): bool
    {
        return PHP_SAPI !== 'cli' && microtime(true) - $this->sliceStartedAt > (float) (getenv('SEED_SLICE_SECONDS') ?: 18);
    }

    protected function rememberProgress(int $lastId): void
    {
        Cache::put($this->progressKey(), $lastId, now()->addDay());
    }

    protected function finishSlices(bool $paused, string $detail = ''): void
    {
        if ($paused) {
            $this->command?->line('[continue] ' . class_basename(static::class) . ' paused to stay within the server time limit' . ($detail ? " ({$detail})" : '') . '; it resumes on the next run.');
            return;
        }
        Cache::forget($this->progressKey());
    }

    private function progressKey(): string
    {
        return 'seed-progress:' . static::class;
    }
}
