<?php

namespace App\Console\Commands;

use App\Services\Trading\MarketDataSync;
use App\Services\Trading\OrderService;
use Illuminate\Console\Command;
use Throwable;

class TradingSync extends Command
{
    protected $signature = 'trading:sync {--navs} {--returns} {--holidays} {--margins} {--allot : Allot submitted orders whose NAV is published}';
    protected $description = 'Do Trading batch: mutual fund NAVs (AMFI), returns, NSE holidays and margins, then order allotment (no option = everything)';

    public function handle(MarketDataSync $sync, OrderService $orders): int
    {
        $all = !collect(['navs', 'returns', 'holidays', 'margins', 'allot'])->contains(fn ($o) => $this->option($o));
        $steps = [
            'holidays' => fn () => $sync->syncHolidays() . ' holidays',
            'navs' => fn () => vsprintf('%d schemes, %d NAVs', $sync->syncNavs()),
            'returns' => fn () => $sync->syncReturns() . ' schemes with returns',
            'margins' => fn () => $sync->syncMargins() . ' margin rates',
            'allot' => fn () => $orders->allotDue() . ' orders allotted',
        ];
        $failed = false;
        foreach ($steps as $name => $run) {
            if (!$all && !$this->option($name)) continue;
            try {
                $this->info(ucfirst($name) . ': ' . $run());
            } catch (Throwable $e) {
                report($e);
                $this->error(ucfirst($name) . ' failed: ' . $e->getMessage());
                $failed = true;
            }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
