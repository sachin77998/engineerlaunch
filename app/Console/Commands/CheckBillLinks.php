<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

// Re-checks every official link in config/bill_payments.php and lists the ones that stopped responding.
class CheckBillLinks extends Command
{
    protected $signature = 'bills:check-links';
    protected $description = 'Check that every biller and municipal link in the Bills & Payments directory still responds';

    public function handle(): int
    {
        $urls = collect(config('bill_payments.categories'))->flatMap(fn ($c) => array_column($c['billers'], 2))
            ->merge(array_filter(array_column(config('bill_payments.cities'), 3)))
            ->merge(collect(config('bill_payments.state_services'))->flatten(1)->pluck(1))->unique()->values();
        $bad = 0;
        foreach ($urls as $url) {
            try {
                $status = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0 Safari/537.36'])->timeout(30)->get($url)->status();
            } catch (\Throwable $e) { $status = 0; }
            if ($status >= 400 || $status === 0) { $bad++; $this->warn("$status  $url"); }
        }
        $this->info(($urls->count() - $bad) . " of {$urls->count()} links respond.");
        return self::SUCCESS;
    }
}
