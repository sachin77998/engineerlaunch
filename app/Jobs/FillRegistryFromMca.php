<?php

namespace App\Jobs;

use App\Services\Insights\DataGovMca;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Fills a company's missing registry details (activity, city, PIN) from the MCA register on data.gov.in. */
class FillRegistryFromMca implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(public string $cin) {}

    public function handle(DataGovMca $mca): void
    {
        $mca->companyFor($this->cin);
    }
}
