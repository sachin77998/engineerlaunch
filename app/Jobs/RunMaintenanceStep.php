<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Runs one Production Setup step (an artisan command) on the background queue, so long steps such as seeders
 * finish in one go instead of being cut off by the web server's time limit. Progress is kept in the cache and
 * shown on /admin/maintenance.
 */
class RunMaintenanceStep implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;
    public int $tries = 1;

    public function __construct(public string $key, public string $label, public string $command, public array $arguments = [])
    {
        $this->onConnection('database');
        $this->onQueue('maintenance');
    }

    public static function statusKey(string $key): string
    {
        return 'maintenance-step:' . $key;
    }

    public static function markQueued(string $key, string $label): void
    {
        Cache::put(self::statusKey($key), ['state' => 'queued', 'label' => $label, 'queued_at' => now()->toDateTimeString(), 'output' => ''], now()->addDays(3));
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) return;
        @ini_set('memory_limit', '1024M');
        @set_time_limit($this->timeout);
        $started = microtime(true);
        Cache::put(self::statusKey($this->key), ['state' => 'running', 'label' => $this->label, 'started_at' => now()->toDateTimeString(), 'output' => ''], now()->addDays(3));
        try {
            $exit = Artisan::call($this->command, $this->arguments);
            $output = Artisan::output();
        } catch (Throwable $e) {
            report($e);
            $exit = 1;
            $output = get_class($e) . ': ' . $e->getMessage();
        }
        Cache::put(self::statusKey($this->key), [
            'state' => $exit === 0 ? 'done' : 'failed', 'label' => $this->label, 'exit' => $exit,
            'finished_at' => now()->toDateTimeString(), 'seconds' => round(microtime(true) - $started, 1),
            'output' => mb_substr(trim($output), -6000),
        ], now()->addDays(3));
    }
}
