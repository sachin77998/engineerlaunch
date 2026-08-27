<?php

namespace App\Console\Commands;

use App\Jobs\SyncCompanyJobs;
use App\Models\BatchRun;
use App\Models\BatchRunItem;
use App\Models\Company;
use App\Services\BatchAuditService;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Throwable;

class DispatchDailyJobSync extends Command
{
    protected $signature = 'jobs:dispatch-daily {--triggered-by=console : scheduler, console, owner, or api} {--user-id= : User who initiated the run}';
    protected $description = 'Dispatch a resilient daily batch for every verified company feed';

    public function handle(): int
    {
        $companies = Company::active()->where('sync_enabled', true)->get(['id', 'name']);
        $userId = $this->option('user-id') ? (int) $this->option('user-id') : null;
        $run = BatchRun::create([
            'batch_type' => 'official_job_ingestion',
            'name' => 'Daily official career feed sync '.now()->toDateString(),
            'status' => 'dispatching',
            'triggered_by' => (string) $this->option('triggered-by'),
            'command' => 'jobs:dispatch-daily',
            'cron_expression' => '0 2 * * *',
            'scheduled_time' => '02:00 '.config('app.timezone'),
            'host' => gethostname() ?: null,
            'process_id' => getmypid() ?: null,
            'total_items' => $companies->count(),
            'pending_items' => $companies->count(),
            'started_at' => now(),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        foreach ($companies as $company) {
            BatchRunItem::create([
                'batch_run_id' => $run->id,
                'company_id' => $company->id,
                'item_key' => (string) $company->id,
                'item_name' => $company->name,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        if ($companies->isEmpty()) {
            $run->update(['status' => 'skipped', 'finished_at' => now(), 'failure_stage' => 'dispatch', 'failure_reason' => 'No verified feeds are enabled.']);
            $this->warn('No verified feeds are enabled.');
            return self::SUCCESS;
        }

        $runId = $run->id;
        $jobs = $companies->map(fn ($company) => new SyncCompanyJobs((int) $company->id, $runId))->all();
        try {
            $batch = Bus::batch($jobs)
                ->name($run->name)
                ->then(fn (Batch $batch) => app(BatchAuditService::class)->refreshRun($runId, true))
                ->catch(function (Batch $batch, Throwable $exception) use ($runId) {
                    BatchRun::whereKey($runId)->update(['status' => 'partially_failed', 'failure_stage' => 'queue_batch', 'failure_reason' => $exception->getMessage()]);
                })
                ->finally(fn (Batch $batch) => app(BatchAuditService::class)->refreshRun($runId, true))
                ->allowFailures()->onQueue('ingestion')->dispatch();
        } catch (Throwable $exception) {
            $run->update(['status' => 'failed', 'failure_stage' => 'dispatch', 'failure_reason' => $exception->getMessage(), 'finished_at' => now()]);
            report($exception);
            $this->error('Batch dispatch failed: '.$exception->getMessage());
            return self::FAILURE;
        }

        $run->update(['queue_batch_id' => $batch->id, 'status' => 'queued']);
        $this->info("Dispatched batch {$batch->id} with ".count($jobs).' companies.');
        $this->info("Audit report batch_runs.id={$run->id}");
        return self::SUCCESS;
    }
}
