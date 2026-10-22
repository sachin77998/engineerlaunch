<?php

namespace App\Services\Trading;

use App\Models\Trading\MarginRate;
use Illuminate\Validation\ValidationException;

/** Mutual fund return (lumpsum and SIP), EMI, loan amortisation schedule and equity margin calculators. */
class Calculators
{
    public const TYPES = ['lumpsum', 'sip', 'emi', 'amortization', 'margin', 'fd', 'rd'];

    public function run(string $type, array $in): array
    {
        return match ($type) {
            'lumpsum' => $this->lumpsum((float) $in['amount'], (float) $in['rate'], (float) $in['years']),
            'sip' => $this->sip((float) $in['monthly'], (float) $in['rate'], (float) $in['years'], (float) ($in['step_up'] ?? 0)),
            'emi' => $this->emi((float) $in['principal'], (float) $in['rate'], (int) $in['months']),
            'amortization' => $this->amortization((float) $in['principal'], (float) $in['rate'], (int) $in['months'], (float) ($in['prepayment'] ?? 0), (int) ($in['prepay_month'] ?? 0)),
            'fd' => $this->fd((float) $in['amount'], (float) $in['rate'], (float) $in['years'], (int) ($in['compounding'] ?? 4)),
            'rd' => $this->rd((float) $in['monthly'], (float) $in['rate'], (float) $in['years']),
            'margin' => $this->margin($in['symbol'] ?? null, (float) $in['price'], (int) $in['quantity'], $in['product'] ?? 'delivery', isset($in['margin_pct']) ? (float) $in['margin_pct'] : null),
            default => throw ValidationException::withMessages(['type' => 'Unknown calculator.']),
        };
    }

    public function lumpsum(float $amount, float $rate, float $years): array
    {
        $value = $amount * pow(1 + $rate / 100, $years);
        $yearly = [];
        for ($y = 1; $y <= (int) ceil($years); $y++) $yearly[] = ['year' => $y, 'value' => round($amount * pow(1 + $rate / 100, min($y, $years)), 2)];
        return ['invested' => round($amount, 2), 'value' => round($value, 2), 'gain' => round($value - $amount, 2), 'absolute_return_pct' => $amount > 0 ? round(($value / $amount - 1) * 100, 2) : 0, 'yearly' => $yearly];
    }

    /** SIP at the start of each month; optional yearly step-up (%). */
    public function sip(float $monthly, float $rate, float $years, float $stepUp = 0): array
    {
        $r = pow(1 + $rate / 100, 1 / 12) - 1;   // effective monthly rate from the annual return
        $value = $invested = 0;
        $instalment = $monthly;
        $yearly = [];
        $months = (int) round($years * 12);
        for ($m = 1; $m <= $months; $m++) {
            $value = ($value + $instalment) * (1 + $r);
            $invested += $instalment;
            if ($m % 12 === 0 || $m === $months) {
                $yearly[] = ['year' => (int) ceil($m / 12), 'invested' => round($invested, 2), 'value' => round($value, 2)];
                if ($m % 12 === 0) $instalment *= 1 + $stepUp / 100;
            }
        }
        return ['invested' => round($invested, 2), 'value' => round($value, 2), 'gain' => round($value - $invested, 2), 'months' => $months, 'yearly' => $yearly];
    }

    /** Fixed deposit compounded n times a year (banks compound quarterly); also the monthly interest of a payout FD. */
    public function fd(float $amount, float $rate, float $years, int $n = 4): array
    {
        $n = in_array($n, [1, 2, 4, 12], true) ? $n : 4;
        $value = $amount * pow(1 + $rate / 100 / $n, $n * $years);
        $yearly = [];
        for ($y = 1; $y <= (int) ceil($years); $y++) $yearly[] = ['year' => $y, 'invested' => round($amount, 2), 'value' => round($amount * pow(1 + $rate / 100 / $n, $n * min($y, $years)), 2)];
        return ['invested' => round($amount, 2), 'value' => round($value, 2), 'gain' => round($value - $amount, 2), 'monthly_payout' => round($amount * $rate / 100 / 12, 2),
            'effective_rate' => round((pow(1 + $rate / 100 / $n, $n) - 1) * 100, 2), 'yearly' => $yearly];
    }

    /** Recurring deposit: each monthly instalment compounds quarterly until maturity (Indian bank method). */
    public function rd(float $monthly, float $rate, float $years): array
    {
        $months = (int) round($years * 12);
        $valueAt = function (int $upto) use ($monthly, $rate) {
            $v = 0;
            for ($k = 1; $k <= $upto; $k++) $v += $monthly * pow(1 + $rate / 400, ($upto - $k + 1) / 3);
            return $v;
        };
        $yearly = [];
        for ($m = 12; $m < $months; $m += 12) $yearly[] = ['year' => $m / 12, 'invested' => round($monthly * $m, 2), 'value' => round($valueAt($m), 2)];
        $value = $valueAt($months);
        $yearly[] = ['year' => (int) ceil($months / 12), 'invested' => round($monthly * $months, 2), 'value' => round($value, 2)];
        return ['invested' => round($monthly * $months, 2), 'value' => round($value, 2), 'gain' => round($value - $monthly * $months, 2), 'months' => $months, 'yearly' => $yearly];
    }

    public function emi(float $principal, float $rate, int $months): array
    {
        if ($months < 1) throw ValidationException::withMessages(['months' => 'Tenure must be at least one month.']);
        $r = $rate / 12 / 100;
        $emi = $r > 0 ? $principal * $r * pow(1 + $r, $months) / (pow(1 + $r, $months) - 1) : $principal / $months;
        $total = $emi * $months;
        return ['emi' => round($emi, 2), 'total_payment' => round($total, 2), 'total_interest' => round($total - $principal, 2), 'principal' => round($principal, 2), 'months' => $months];
    }

    /** Month-by-month schedule; an optional one-time prepayment shortens the tenure (EMI unchanged). */
    public function amortization(float $principal, float $rate, int $months, float $prepayment = 0, int $prepayMonth = 0): array
    {
        $emi = $this->emi($principal, $rate, $months)['emi'];
        $r = $rate / 12 / 100;
        $balance = $principal;
        $rows = [];
        $totalInterest = 0;
        for ($m = 1; $m <= $months && $balance > 0.005; $m++) {
            $interest = round($balance * $r, 2);
            $principalPart = min($balance, round($emi - $interest, 2));
            $extra = $m === $prepayMonth ? min($prepayment, $balance - $principalPart) : 0;
            $balance = round($balance - $principalPart - $extra, 2);
            $totalInterest += $interest;
            $rows[] = ['month' => $m, 'emi' => round($principalPart + $interest, 2), 'principal' => $principalPart, 'interest' => $interest, 'prepayment' => round($extra, 2), 'balance' => max(0, $balance)];
        }
        return ['emi' => $emi, 'months_taken' => count($rows), 'months_saved' => $months - count($rows), 'total_interest' => round($totalInterest, 2),
            'total_payment' => round($principal + $totalInterest, 2), 'schedule' => $rows];
    }

    /**
     * Equity margin: delivery needs the full value; intraday needs the exchange's applicable margin (VaR + ELM, NSE file),
     * never less than 20% under SEBI's peak-margin rules.
     */
    public function margin(?string $symbol, float $price, int $quantity, string $product, ?float $marginPct): array
    {
        $value = $price * $quantity;
        $rate = null;
        if ($symbol) $rate = MarginRate::where('symbol', strtoupper($symbol))->whereIn('series', ['EQ', 'BE'])->first();
        $applicable = $marginPct ?? ($rate?->applicable_margin ?? 20.0);
        $pct = $product === 'delivery' ? 100.0 : max(20.0, $applicable);
        $required = $value * $pct / 100;
        return ['trade_value' => round($value, 2), 'margin_pct' => round($pct, 2), 'margin_required' => round($required, 2), 'leverage' => round(100 / $pct, 2),
            'var_margin' => $rate?->var_margin, 'elm' => $rate?->elm, 'exchange_margin' => $rate?->applicable_margin, 'as_of' => $rate?->as_of?->toDateString(), 'product' => $product];
    }
}
