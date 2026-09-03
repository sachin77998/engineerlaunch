<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Services\JobClassificationService;
use Illuminate\Console\Command;

class ClassifyJobs extends Command
{
    protected $signature = 'jobs:classify {--force : Reclassify every job using the current rules}';

    protected $description = 'Backfill normalized department and engineering-discipline fields without re-ingesting jobs';

    public function handle(JobClassificationService $classifier): int
    {
        $query = Job::query()->with('company');
        if (! $this->option('force')) {
            $query->where(function ($jobs) {
                $jobs->whereNull('classified_at')
                    ->orWhere('classification_version', '<', JobClassificationService::VERSION);
            });
        }

        $total = (clone $query)->count();
        $bar = $this->output->createProgressBar($total);

        $query->chunkById(500, function ($jobs) use ($classifier, $bar) {
            foreach ($jobs as $job) {
                $job->updateQuietly($classifier->classify($job->getAttributes(), $job->company));
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Classified {$total} jobs without calling external career feeds.");

        return self::SUCCESS;
    }
}
