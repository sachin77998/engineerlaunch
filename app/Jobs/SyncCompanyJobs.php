<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\BatchAuditService;
use App\Services\JobScraper;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class SyncCompanyJobs implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;
    public array $backoff = [60, 300, 900];

    public function __construct(public int $companyId, public ?int $batchRunId = null) {}

    public function handle(JobScraper $scraper, BatchAuditService $audit): void
    {
        if ($this->batch()?->cancelled()) return;
        $company = Company::active()->where('sync_enabled', true)->find($this->companyId);
        if (!$company) {
            $exception = new RuntimeException('The company is missing, inactive, or synchronization is disabled.');
            if ($this->batchRunId) $audit->failItem($this->batchRunId, $this->companyId, $exception, 'load_company');
            throw $exception;
        }

        $item = $this->batchRunId ? $audit->startItem($this->batchRunId, $this->companyId) : null;
        try {
            $result = $scraper->scrapeCompany($company);
            if (!empty($result['continuation']) && $this->batch()) {
                if ($item) $item->update(['status'=>'pending',
                    'records_found'=>$item->records_found+($result['jobs_found'] ?? 0),
                    'records_created'=>$item->records_created+($result['jobs_added'] ?? 0),
                    'records_updated'=>$item->records_updated+($result['jobs_updated'] ?? 0),
                    'context'=>['warnings'=>array_slice(array_merge($item->context['warnings'] ?? [],$result['errors'] ?? []),0,10)]]);
                $this->batch()->add([new self($this->companyId,$this->batchRunId)]);
                if ($item) $audit->refreshRun($item->batch_run_id);
                return;
            }
            if ($item) {
                if (!empty($item->context['warnings'])) {
                    $result['success']=false;
                    $result['errors']=array_merge($result['errors'],$item->context['warnings']);
                }
                $result['jobs_found'] += $item->records_found;
                $result['jobs_added'] += $item->records_created;
                $result['jobs_updated'] += $item->records_updated;
            }
            if (!$result['success']) throw new RuntimeException(implode('; ', $result['errors']), 0, $result['exception'] ?? null);
            if ($item) $audit->completeItem($item, $result);
        } catch (Throwable $exception) {
            if ($this->batchRunId) $audit->failItem($this->batchRunId, $this->companyId, $exception, 'scrape_and_persist');
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($this->batchRunId) {
            app(BatchAuditService::class)->failItem($this->batchRunId, $this->companyId, $exception, 'queue_failed');
        }
    }
}
