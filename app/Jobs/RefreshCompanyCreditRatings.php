<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\Insights\CreditRatingSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Looks a company up at CARE (current ratings, lenders, new press releases); queued when its page is opened and the last check is old. */
class RefreshCompanyCreditRatings implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 600;
    public int $tries = 2;
    public int $uniqueFor = 21600;

    public function __construct(public int $companyId) {}

    public function uniqueId(): string
    {
        return (string) $this->companyId;
    }

    public function handle(CreditRatingSync $sync): void
    {
        if ($company = Company::find($this->companyId)) $sync->syncCare($company);
    }
}
