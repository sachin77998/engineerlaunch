<?php

namespace App\Services\Trading;

use App\Models\Trading\MfScheme;
use App\Models\Trading\TradingHoliday;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/** Business days for orders: weekends and exchange holidays are closed; cut-off decides which day's NAV applies. */
class MarketCalendar
{
    public const TZ = 'Asia/Kolkata';

    public function __construct(private TradingSettings $settings) {}

    public function now(): Carbon
    {
        return Carbon::now(self::TZ);
    }

    /** @return array<string, string> date => description */
    public function holidays(): array
    {
        return Cache::remember('trading:holidays', 3600, fn () => TradingHoliday::where('segment', 'CM')
            ->pluck('description', 'holiday_on')->mapWithKeys(fn ($d, $date) => [Carbon::parse($date)->toDateString() => $d])->all());
    }

    public function isBusinessDay(CarbonInterface $day): bool
    {
        return !$day->isWeekend() && !isset($this->holidays()[$day->toDateString()]);
    }

    /** Why a day is closed, or null when open. */
    public function closedReason(CarbonInterface $day): ?string
    {
        if ($day->isSaturday() || $day->isSunday()) return 'Markets are closed on ' . $day->format('l') . 's.';
        if ($name = $this->holidays()[$day->toDateString()] ?? null) return 'Markets are closed today for ' . $name . '.';
        return null;
    }

    public function nextBusinessDay(CarbonInterface $day): Carbon
    {
        $next = Carbon::parse($day->toDateString(), self::TZ)->addDay();
        while (!$this->isBusinessDay($next)) $next->addDay();
        return $next;
    }

    /** NAV date for an order placed now: today before the cut-off, else the next business day. */
    public function applicableNavDate(MfScheme $scheme, ?Carbon $at = null): Carbon
    {
        $at ??= $this->now();
        $liquid = preg_match('/liquid|overnight/i', (string) $scheme->category);
        [$h, $m] = array_map('intval', explode(':', (string) $this->settings->get($liquid ? 'order.cutoff_time_liquid' : 'order.cutoff_time')));
        $cutoff = $at->copy()->setTime($h, $m);
        $day = Carbon::parse($at->toDateString(), self::TZ);
        return $this->isBusinessDay($day) && $at->lt($cutoff) ? $day : $this->nextBusinessDay($day);
    }
}
