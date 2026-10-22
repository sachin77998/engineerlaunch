<?php

namespace App\Services\Money;

class Format
{
    // Indian digit grouping: 1250000 → 12,50,000.
    public static function inr(float $v): string
    {
        $neg = $v < 0; $n = (string) (int) round(abs($v));
        if (strlen($n) > 3) $n = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($n, 0, -3)) . ',' . substr($n, -3);
        return ($neg ? '-' : '') . $n;
    }

    // Short form for headlines: ₹1.2 Cr, ₹45.3 L, ₹8,500.
    public static function short(float $v): string
    {
        $a = abs($v);
        return '₹' . ($a >= 1e7 ? round($v / 1e7, 2) . ' Cr' : ($a >= 1e5 ? round($v / 1e5, 2) . ' L' : self::inr($v)));
    }
}
