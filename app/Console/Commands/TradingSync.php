<?php

namespace App\Console\Commands;

use App\Services\Trading\MarketDataSync;
use App\Services\Trading\OrderService;
use Illuminate\Console\Command;
use Throwable;

class TradingSync extends Command
{
    protected $signature = 'trading:sync {--navs} {--returns} {--holidays} {--margins} {--instruments : Stock/ETF master and end-of-day prices} {--catalogue : Fund houses, categories, ratings and collections} {--allot : Allot submitted orders whose NAV is published}';
    protected $description = 'Do Trading batch: mutual fund NAVs (AMFI), returns, NSE holidays and margins, then order allotment (no option = everything)';

    public function handle(MarketDataSync $sync, OrderService $orders): int
    {
        $all = !collect(['navs', 'returns', 'holidays', 'margins', 'instruments', 'catalogue', 'allot'])->contains(fn ($o) => $this->option($o));
        $steps = [
            'holidays' => fn () => $sync->syncHolidays() . ' holidays',
            'navs' => fn () => vsprintf('%d schemes, %d NAVs', $sync->syncNavs()),
            'returns' => fn () => $sync->syncReturns() . ' schemes with returns',
            'margins' => fn () => $sync->syncMargins() . ' margin rates',
            'instruments' => fn () => json_encode(app(\App\Services\Trading\InstrumentSync::class)->sync()),
            'catalogue' => fn () => json_encode(app(\App\Services\Trading\FundCatalogue::class)->rebuild()),
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
