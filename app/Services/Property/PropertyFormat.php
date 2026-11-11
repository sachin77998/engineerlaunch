<?php

namespace App\Services\Property;

// Prices in the listing's currency and areas in any unit. INR uses Indian grouping (1,25,000) and lakh / crore
// for large amounts; other currencies use thousands separators and K / M.
class PropertyFormat
{
    public static function symbol(?string $currency): string
    {
        foreach (config('properties.countries') as $c) if ($c['currency'] === $currency) return $c['symbol'];
        return ($currency ?: 'INR') . ' ';
    }

    /** Every digit: "₹35,000", "AED 4,500". */
    public static function full($amount, ?string $currency = 'INR'): ?string
    {
        if ($amount === null || $amount === '') return null;
        $n = (float) $amount;
        return self::symbol($currency) . ($currency === 'INR' ? self::indian($n) : number_format($n, fmod($n, 1) ? 2 : 0));
    }

    /** Short form for big amounts: "₹1.25 Cr", "₹48 L", "$1.2M"; small amounts keep every digit. */
    public static function short($amount, ?string $currency = 'INR'): ?string
    {
        if ($amount === null || $amount === '') return null;
        $n = (float) $amount;
        $trim = fn (float $v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        if ($currency === 'INR') {
            if ($n >= 1e7) return '₹' . $trim($n / 1e7) . ' Cr';
            if ($n >= 1e5) return '₹' . $trim($n / 1e5) . ' Lac';
            return self::full($n, 'INR');
        }
        if ($n >= 1e6) return self::symbol($currency) . $trim($n / 1e6) . 'M';
        return self::full($n, $currency);
    }

    public static function indian(float $n): string
    {
        $whole = (string) (int) round($n);
        if (strlen($whole) <= 3) return $whole;
        $last = substr($whole, -3);
        $rest = substr($whole, 0, -3);
        return preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',' . $last;
    }

    public static function toSqft(?float $value, string $unit): ?float
    {
        if ($value === null) return null;
        return round($value * (config("properties.area_units.$unit.1") ?? 1), 2);
    }

    public static function fromSqft(?float $sqft, string $unit): ?float
    {
        if ($sqft === null) return null;
        return round($sqft / (config("properties.area_units.$unit.1") ?? 1), 2);
    }

    public static function area(?float $value, string $unit): ?string
    {
        if ($value === null) return null;
        return rtrim(rtrim(number_format($value, 2), '0'), '.') . ' ' . (config("properties.area_units.$unit.0") ?? $unit);
    }
}
