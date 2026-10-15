<?php

namespace App\Jobs;

use App\Services\Insights\NseData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/** Imports one NSE-listed company: CIN link, audited annual results (XBRL), shareholding pattern and price. */
class ImportNseCompany implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 600;
    public int $tries = 2;
    public array $backoff = [300];

    public function __construct(public string $symbol) {}

    public function handle(NseData $nse): void
    {
        $listed = DB::table('listed_companies')->where('symbol', $this->symbol)->first();
        if (!$listed) return;
        try {
            $nse->import($listed);
        } catch (Throwable $e) {
            DB::table('listed_companies')->where('id', $listed->id)->update(['sync_error' => Str::limit($e->getMessage(), 250), 'synced_at' => now(), 'updated_at' => now()]);
            throw $e;
        }
    }
}
