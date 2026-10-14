<?php

namespace App\Jobs;

use App\Services\Insights\DataGovMca;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Imports a state's data.gov.in MCA master data. The API (Elasticsearch) returns at most 10,000 records per query,
 * so a state is split by registration-date range until each slice fits, then each slice is paged through.
 */
class ImportMcaCompaniesPage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const PAGE = 2000;
    public const WINDOW = 10000;
    public const FIRST = '1700-01-01';
    public const LAST = '2200-01-01';
    public int $timeout = 600;
    public int $tries = 3;
    public array $backoff = [60, 300];

    public function __construct(public string $state, public int $offset = 0, public string $from = self::FIRST, public string $to = self::LAST) {}

    public function handle(DataGovMca $mca): void
    {
        @ini_set('memory_limit', '512M');
        $whole = $this->from === self::FIRST && $this->to === self::LAST;
        $page = $mca->page(['registered_state' => $this->state], $this->offset, self::PAGE, ['date_of_registration' => [$this->from, $this->to]]);
        if ($whole && $this->offset === 0) {
            Cache::put('mca-import:' . $this->state, ['total' => $page['total'], 'started_at' => now()->toDateTimeString()], now()->addDays(30));
            Cache::put('mca-import:' . $this->state . ':done', 0, now()->addDays(30));
        }
        // Too many for one query: split the date range in half (one day is the smallest slice).
        $days = Carbon::parse($this->from)->diffInDays(Carbon::parse($this->to));
        if ($this->offset === 0 && $page['total'] > self::WINDOW && $days > 1) {
            $mid = Carbon::parse($this->from)->addDays(intdiv($days, 2))->toDateString();
            self::dispatch($this->state, 0, $this->from, $mid)->onConnection('database')->onQueue('registry');
            self::dispatch($this->state, 0, $mid, $this->to)->onConnection('database')->onQueue('registry');
            return;
        }
        $mca->store($page['records']);
        Cache::increment('mca-import:' . $this->state . ':done', count($page['records']));
        Cache::put('mca-import:' . $this->state . ':updated', now()->toDateTimeString(), now()->addDays(30));
        $next = $this->offset + self::PAGE;
        if (count($page['records']) === self::PAGE && $next < min($page['total'], self::WINDOW)) {
            self::dispatch($this->state, $next, $this->from, $this->to)->onConnection('database')->onQueue('registry');
        }
    }
}
