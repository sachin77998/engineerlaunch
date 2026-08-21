<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\JobScraper;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class SyncCompanyJobs implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;
    public array $backoff = [60, 300, 900];

    public function __construct(public int $companyId) {}

    public function handle(JobScraper $scraper): void
    {
        if ($this->batch()?->cancelled()) return;
        $company = Company::active()->where('sync_enabled', true)->find($this->companyId);
        if (!$company) return;
        $result = $scraper->scrapeCompany($company);
        if (!$result['success']) throw new RuntimeException(implode('; ', $result['errors']));
    }
}
