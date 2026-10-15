<?php

namespace App\Services\Trading;

/**
 * Charges on a mutual fund order. Stamp duty (0.005% by default) applies to purchases and is taken out of the
 * invested amount; transaction charge and platform fee carry GST. All rates are editable in trading settings.
 */
class FeeCalculator
{
    public function __construct(private TradingSettings $settings) {}

    /** @return array{gross:float, stamp_duty:float, transaction_charge:float, platform_fee:float, gst:float, total_fees:float, net_amount:float} */
    public function quote(string $side, float $amount): array
    {
        $buy = $side === 'buy';
        $txn = $buy && $amount >= $this->settings->number('fee.transaction_charge_min_amount') ? $this->settings->number('fee.transaction_charge') : 0.0;
        $platform = $this->settings->number('fee.platform_fee');
        $gst = round(($txn + $platform) * $this->settings->number('fee.gst_pct') / 100, 2);
        $afterCharges = max(0, $amount - $txn - $platform - $gst);
        // Stamp duty is levied on the amount actually invested: net = after / (1 + rate).
        $rate = $buy ? $this->settings->number('fee.stamp_duty_pct') / 100 : 0.0;
        $net = round($afterCharges / (1 + $rate), 2);
        $stamp = round($afterCharges - $net, 2);
        $total = round($txn + $platform + $gst + $stamp, 2);
        return ['gross' => round($amount, 2), 'stamp_duty' => $stamp, 'transaction_charge' => round($txn, 2), 'platform_fee' => round($platform, 2),
            'gst' => $gst, 'total_fees' => $total, 'net_amount' => round($amount - $total, 2)];
    }
}
