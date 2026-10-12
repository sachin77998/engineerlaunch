<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Keeps the self-researching imports running without anyone pressing buttons: after each deploy and once a day,
 * every import that has not run within its interval is queued (the every-minute queue worker processes it).
 * Last runs are kept in storage/app so cache clears during deploys do not re-trigger everything.
 */
class BootstrapDiscovery extends Command
{
    protected $signature = 'discovery:bootstrap {--force : Queue every import now}';
    protected $description = 'Queue IT parks, registry trade search and technology discovery when they are due';

    private const TASKS = [
        'parks' => ['companies:discover', ['source' => 'parks'], 30],
        'gleif-trades' => ['companies:discover', ['source' => 'gleif', '--gleif-trades' => true], 30],
        'technologies' => ['technologies:sync', [], 30],
    ];

    public function handle(): int
    {
        $file = 'discovery-bootstrap.json';
        $state = Storage::exists($file) ? (json_decode(Storage::get($file), true) ?: []) : [];
        foreach (self::TASKS as $key => [$command, $arguments, $days]) {
            $last = $state[$key] ?? 0;
            if (!$this->option('force') && $last > now()->subDays($days)->timestamp) { $this->line("{$key}: ran " . date('Y-m-d', $last) . ', not due'); continue; }
            try {
                Artisan::call($command, $arguments);
                $state[$key] = time();
                Storage::put($file, json_encode($state));
                $this->info("{$key}: " . trim(Artisan::output()));
            } catch (Throwable $e) {
                $this->warn("{$key}: " . $e->getMessage());
            }
        }
        return self::SUCCESS;
    }
}
