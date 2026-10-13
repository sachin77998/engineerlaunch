<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\Insights\CompanyLookup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Fills a company's insight page from public registries in the background (queued when the page is opened). */
class EnrichCompanyInsights implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 180;
    public int $tries = 2;
    public int $uniqueFor = 3600;

    public function __construct(public int $companyId, public bool $force = false) {}

    public function uniqueId(): string
    {
        return (string) $this->companyId;
    }

    public function handle(CompanyLookup $lookup): void
    {
        if ($company = Company::find($this->companyId)) $lookup->enrich($company, $this->force);
    }
}
