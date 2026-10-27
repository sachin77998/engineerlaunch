<?php

namespace App\Jobs;

use App\Models\Company;
use App\Services\Insights\SecStatements;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Annual statements of a US-listed company from SEC EDGAR, queued when its page is viewed without them. */
class ImportSecStatements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public int $companyId) {}

    public function handle(SecStatements $sec): void
    {
        if ($company = Company::find($this->companyId)) {
            $sec->import($company);
            // The stored credit assessment and vendor checks are rebuilt from the new statements on the next view.
            \Illuminate\Support\Facades\DB::table('company_credit_assessments')->where('company_id', $company->id)->update(['computed_at' => null]);
        }
    }
}
