<?php

namespace App\Console\Commands;

use App\Services\JobSearchIndex;
use Illuminate\Console\Command;

class SyncJobSearchIndex extends Command
{
    protected $signature = 'jobs:search-index {--full : Re-copy every job instead of the ones updated since the last run}';
    protected $description = 'Copy new, changed and closed jobs into the job search table';

    public function handle(JobSearchIndex $index): int
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);
        $started = microtime(true);
        $last = 0;
        [$copied, $removed] = $index->sync((bool) $this->option('full'), function ($copied) use (&$last) {
            if ($copied - $last >= 20000) { $this->line("  {$copied} copied"); $last = $copied; }
        });
        $this->info(sprintf('%d jobs copied, %d removed in %.0fs.', $copied, $removed, microtime(true) - $started));
        return self::SUCCESS;
    }
}
