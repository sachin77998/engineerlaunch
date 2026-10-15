<?php

namespace App\Services\Trading;

use App\Models\Trading\TradingSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** Editable trading settings (fees, cut-off times, limits) with defaults; read through a cache. */
class TradingSettings
{
    public const DEFAULTS = [
        'fee.stamp_duty_pct' => ['0.005', 'Stamp duty on purchases (%)', 'percent'],
        'fee.transaction_charge' => ['0', 'Transaction charge per purchase (₹)', 'number'],
        'fee.transaction_charge_min_amount' => ['10000', 'Transaction charge applies from (₹)', 'number'],
        'fee.platform_fee' => ['0', 'Platform fee per order (₹)', 'number'],
        'fee.gst_pct' => ['18', 'GST on charges and platform fee (%)', 'percent'],
        'order.cutoff_time' => ['15:00', 'Cut-off time for same-day NAV (IST)', 'time'],
        'order.cutoff_time_liquid' => ['13:30', 'Cut-off time for liquid and overnight funds (IST)', 'time'],
        'order.min_amount' => ['100', 'Minimum order amount (₹)', 'number'],
        'order.max_amount' => ['1000000', 'Maximum order amount without manual review (₹)', 'number'],
        'order.otp_minutes' => ['5', 'OTP validity (minutes)', 'number'],
        'order.otp_attempts' => ['5', 'Wrong OTP attempts allowed', 'number'],
        'execution.channel' => ['simulated', 'Execution channel (simulated until a registered channel such as BSE StAR MF is connected)', 'string'],
        'export.email' => ['', 'Email for the end-of-day order export', 'string'],
    ];

    public function get(string $key, $default = null)
    {
        return $this->all()[$key] ?? $default ?? (self::DEFAULTS[$key][0] ?? null);
    }

    public function number(string $key): float
    {
        return (float) $this->get($key);
    }

    public function all(): array
    {
        return Cache::remember('trading:settings', 600, function () {
            $values = array_map(fn ($d) => $d[0], self::DEFAULTS);
            if (Schema::hasTable('trading_settings')) {
                foreach (TradingSetting::pluck('value', 'key') as $key => $value) $values[$key] = $value;
            }
            return $values;
        });
    }

    public function set(string $key, ?string $value): void
    {
        $meta = self::DEFAULTS[$key] ?? [null, $key, 'string'];
        TradingSetting::updateOrCreate(['key' => $key], ['value' => $value, 'label' => $meta[1], 'type' => $meta[2]]);
        Cache::forget('trading:settings');
    }
}
