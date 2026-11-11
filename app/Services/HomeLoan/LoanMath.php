<?php

namespace App\Services\HomeLoan;

// EMI and eligibility arithmetic, mirrored by the calculators' JavaScript on /home-loan.
class LoanMath
{
    public static function emi(float $principal, float $rate, int $years): float
    {
        $n = $years * 12;
        if ($principal <= 0 || $n <= 0) return 0;
        $r = $rate / 1200;
        if ($r <= 0) return round($principal / $n);
        return round($principal * $r * (1 + $r) ** $n / ((1 + $r) ** $n - 1));
    }

    /** Largest loan whose EMI fits in FOIR × income minus existing EMIs. */
    public static function maxLoan(float $income, float $otherEmis, float $rate, int $years): float
    {
        $room = $income * config('home_loans.foir') - $otherEmis;
        $perRupee = self::emi(1000000, $rate, $years) / 1000000;
        return $room > 0 && $perRupee > 0 ? floor($room / $perRupee) : 0;
    }
}
