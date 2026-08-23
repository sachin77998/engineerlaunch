<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;

class BootstrapProductionJobs extends Command
{
    protected $signature = 'jobs:bootstrap-production';

    protected $description = 'Populate an empty production job database from configured official feeds';

    public function handle(): int
    {
        if (Job::query()->exists()) {
            $this->info('Jobs already exist; production bootstrap skipped.');

            return self::SUCCESS;
        }

        $this->info('The jobs table is empty; starting the initial official-feed sync.');

        return $this->call('jobs:scrape');
    }
}
