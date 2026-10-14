<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\Insights\ProviderImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

/** Fetches a company from the paid MCA data providers in the background; at most once a week per company (calls are billed). */
class FetchCompanyFromProviders implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 300;
    public int $tries = 1;
    public int $uniqueFor = 3600;

    public function __construct(public int $companyId, public string $cin) {}

    public function uniqueId(): string
    {
        return $this->cin;
    }

    public function handle(ProviderImporter $importer): void
    {
        if (!Cache::add('provider-fetch:' . $this->cin, true, now()->addWeek())) return;
        if ($company = Company::find($this->companyId)) $importer->fetchCin($this->cin, $company);
    }
}
