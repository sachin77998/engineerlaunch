<?php

namespace App\Console\Commands;

use App\Services\Insights\UsMarketData;
use App\Services\SectorDirectory;
use Illuminate\Console\Command;

class ImportUsCompanies extends Command
{
    protected $signature = 'insights:import-us {--prices : Only refresh last price, change, volume and market cap} {--years=6 : Calendar years of SEC annual results to load}';
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
        SectorDirectory::flush();
        return self::SUCCESS;
    }
}
