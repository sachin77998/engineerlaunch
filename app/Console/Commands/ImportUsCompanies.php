<?php

namespace App\Console\Commands;

use App\Services\Insights\UsMarketData;
use App\Services\SectorDirectory;
use Illuminate\Console\Command;

class ImportUsCompanies extends Command
{
    protected $signature = 'insights:import-us {--prices : Only refresh last price, change, volume and market cap} {--years=6 : Calendar years of SEC annual results to load} {--statements=400 : Company pages to fill with full SEC statements per run}';
    protected $description = 'Import every US-listed company (NYSE, Nasdaq, NYSE American): quote and market cap, SEC CIK and annual results from SEC EDGAR';

    public function handle(UsMarketData $us): int
    {
        try {
            $result = $us->syncListings($this->option('prices'));
        } catch (\Throwable $e) {
            report($e);
            $this->error('US listings could not be loaded: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->info("{$result['listings']} US stocks updated; {$result['linked']} linked to company pages.");
        if ($this->option('prices')) return self::SUCCESS;

        $last = (int) now()->subYear()->format('Y');
        $years = range($last - max(1, (int) $this->option('years')) + 1, $last);
        $rows = $us->importFinancials($years);
        $this->info("SEC annual results saved: {$rows} company-years (" . implode(', ', $years) . ').');
        $extra = $us->importMissingFromCompanyFacts($last);
        $this->info("Results added from company facts (foreign filers, odd fiscal years): {$extra} companies.");
        // Full statements for company pages (key metrics, charts, assessment): largest companies without them first.
        $done = 0;
        $ids = \Illuminate\Support\Facades\DB::table('us_listed_companies as l')->whereNotNull('l.cik')->whereNotNull('l.company_id')
            ->whereNotExists(fn ($q) => $q->from('company_financial_statements as s')->whereColumn('s.company_id', 'l.company_id')->where('s.source', 'like', 'SEC %')->where('s.updated_at', '>', now()->subDays(30)))
            ->orderByDesc('l.market_cap')->limit((int) $this->option('statements'))->pluck('l.company_id')->unique();
        $sec = app(\App\Services\Insights\SecStatements::class);
        foreach ($ids as $id) {
            try { if ($sec->import(\App\Models\Company::find($id))) $done++; } catch (\Throwable $e) { report($e); }
            usleep(150000);   // SEC fair-access limit is 10 requests a second
        }
        $this->info("Statements written for {$done} company pages.");
        SectorDirectory::flush();
        return self::SUCCESS;
    }
}
