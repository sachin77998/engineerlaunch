<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;

class BootstrapProductionJobs extends Command
{
    protected $signature = 'jobs:bootstrap-production {--force : Refresh feeds even when jobs already exist}';

    protected $description = 'Populate an empty production job database from configured official feeds';

    public function handle(): int
    {
        if (!$this->option('force') && Job::query()->exists()) {
            $this->info('Jobs already exist; production bootstrap skipped.');

            return self::SUCCESS;
        }

        $this->info($this->option('force')
            ? 'Refreshing every configured official career feed.'
            : 'The jobs table is empty; starting the initial official-feed sync.');

        return $this->call('jobs:scrape');
    }
}
