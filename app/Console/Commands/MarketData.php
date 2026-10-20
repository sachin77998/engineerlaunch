<?php

namespace App\Console\Commands;

use App\Services\Market\MarketData as Market;
use Illuminate\Console\Command;

class MarketData extends Command
{
    protected $signature = 'market:data {action : fetch-metals, metal-history or import} {type? : vehicles or prices (import)} {file? : CSV path (import)}';
    protected $description = 'Fetch gold/silver prices (GoldAPI) or import vehicle sales / commodity price CSVs';

    public function handle(Market $market): int
    {
        if ($this->argument('action') === 'metal-history') {
            $this->info('Saved ' . $market->fetchMetalHistory() . ' daily gold/silver/copper prices.');
            return self::SUCCESS;
        }
        if ($this->argument('action') === 'fetch-metals') {
            if (!config('services.goldapi.key')) { $this->warn('GOLDAPI_KEY is not set; skipping gold/silver fetch.'); return self::SUCCESS; }
            $this->info('Saved ' . $market->fetchMetals() . ' gold/silver prices.');
            return self::SUCCESS;
        }
        $count = $market->import((string) $this->argument('type'), (string) $this->argument('file'));
        $this->info("Imported {$count} rows.");
        return self::SUCCESS;
    }
}
