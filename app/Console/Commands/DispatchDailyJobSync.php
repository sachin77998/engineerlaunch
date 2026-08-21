<?php

namespace App\Console\Commands;

use App\Jobs\SyncCompanyJobs;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class DispatchDailyJobSync extends Command
{
    protected $signature = 'jobs:dispatch-daily';
    protected $description = 'Dispatch a resilient daily batch for every verified company feed';

    public function handle(): int
    {
        $jobs = Company::active()->where('sync_enabled', true)->pluck('id')
            ->map(fn ($id) => new SyncCompanyJobs((int) $id))->all();
        if ($jobs === []) {
            $this->warn('No verified feeds are enabled.');
            return self::SUCCESS;
        }
        $batch = Bus::batch($jobs)->name('Daily official career feed sync '.now()->toDateString())
            ->allowFailures()->onQueue('ingestion')->dispatch();
        $this->info("Dispatched batch {$batch->id} with ".count($jobs).' companies.');
        return self::SUCCESS;
    }
}
