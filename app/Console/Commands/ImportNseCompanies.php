<?php

namespace App\Console\Commands;

use App\Jobs\ImportNseCompany;
use App\Services\Insights\NseData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportNseCompanies extends Command
{
    protected $signature = 'insights:import-nse {--symbol=* : Only these NSE symbols} {--now : Import inline instead of queueing} {--force : Re-import companies synced in the last week} {--status : Show progress only} {--prices : Only refresh closing prices (for P/E)}';
    protected $description = 'Import every NSE-listed company: CIN, audited annual results (XBRL since FY 2016-17), shareholding pattern and closing price';

    public function handle(NseData $nse): int
    {
        if ($this->option('status')) {
            $total = DB::table('listed_companies')->count();
            $done = DB::table('listed_companies')->whereNotNull('synced_at')->whereNull('sync_error')->count();
            $this->info("Listed companies: {$total}; imported: {$done}; with CIN: " . DB::table('listed_companies')->whereNotNull('cin')->count()
                . '; with results: ' . DB::table('listed_companies')->where('years_imported', '>', 0)->count() . '; errors: ' . DB::table('listed_companies')->whereNotNull('sync_error')->count());
            return self::SUCCESS;
        }

        if ($this->option('prices')) {
            $prices = $nse->closingPrices();
            $updated = 0;
            foreach (DB::table('listed_companies')->whereNotNull('company_id')->get(['id', 'symbol', 'company_id']) as $listed) {
                if (!isset($prices[$listed->symbol])) continue;
                DB::table('listed_companies')->where('id', $listed->id)->update(['last_price' => $prices[$listed->symbol], 'updated_at' => now()]);
                $latest = \App\Models\CompanyFinancialStatement::where('company_id', $listed->company_id)->max('financial_year');
                if ($latest) \App\Models\CompanyFinancialStatement::where('company_id', $listed->company_id)->where('financial_year', $latest)->update(['market_price' => $prices[$listed->symbol]]);
                $updated++;
            }
            $this->info("Closing prices updated for {$updated} companies.");
            return self::SUCCESS;
        }

        $list = $nse->equityList();
        $prices = $nse->closingPrices();
        $now = now();
        foreach (array_chunk($list, 500) as $chunk) {
            DB::table('listed_companies')->upsert(array_map(fn ($r) => $r + ['last_price' => $prices[$r['symbol']] ?? null, 'created_at' => $now, 'updated_at' => $now], $chunk),
                ['symbol'], ['isin', 'name', 'series', 'listed_on', 'face_value', 'last_price', 'updated_at']);
        }
        $this->info(count($list) . ' NSE equities listed; ' . count($prices) . ' closing prices.');

        $query = DB::table('listed_companies')->orderBy('symbol');
        if ($symbols = array_map('strtoupper', $this->option('symbol'))) $query->whereIn('symbol', $symbols);
        elseif (!$this->option('force')) $query->where(fn ($q) => $q->whereNull('synced_at')->orWhere('synced_at', '<', now()->subWeek())->orWhereNotNull('sync_error'));

        $count = 0;
        foreach ($query->pluck('symbol') as $symbol) {
            if ($this->option('now')) {
                $listed = DB::table('listed_companies')->where('symbol', $symbol)->first();
                try {
                    $years = $nse->import($listed);
                    $this->line("{$symbol}: {$years} years");
                } catch (\Throwable $e) {
                    $this->warn("{$symbol}: " . $e->getMessage());
                }
            } else {
                ImportNseCompany::dispatch($symbol)->onConnection('database')->onQueue('registry');
            }
            $count++;
        }
        $this->info($this->option('now') ? "{$count} companies imported." : "{$count} companies queued on the registry queue.");
        return self::SUCCESS;
    }
}
