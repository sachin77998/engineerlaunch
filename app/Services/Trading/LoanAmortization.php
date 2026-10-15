<?php

namespace App\Services\Trading;

use App\Models\Trading\Loan;
use App\Models\Trading\LoanSchedule;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Loan amortisation schedules.
 * - Equal instalments (EMI) monthly, quarterly, half-yearly or yearly: payment = r·P / (n·(1 − (1 + r/n)^(−n·years))).
 * - Interest monthly or quarterly with principal at maturity; interest monthly with principal quarterly;
 *   principal and interest every calendar quarter; a single bullet payment at maturity.
 * - Interest for each period is charged on the actual days (Actual/365, Actual/360 or 30/360).
 * - Moratorium: "principal_only" (interest is paid, principal deferred) or "principal_and_interest" (interest is
 *   added to the balance); the instalment is recalculated for the remaining periods afterwards.
 * - The final instalment absorbs rounding so the closing balance is exactly zero.
 */
class LoanAmortization
{
    public const FREQUENCIES = [
        'monthly' => 'Monthly EMI', 'quarterly' => 'Quarterly instalments', 'half_yearly' => 'Half-yearly instalments', 'yearly' => 'Yearly instalments',
        'interest_monthly_principal_maturity' => 'Interest monthly, principal at maturity',
        'interest_quarterly_principal_maturity' => 'Interest every calendar quarter, principal at maturity',
        'interest_monthly_principal_quarterly' => 'Interest monthly, principal quarterly',
        'principal_and_interest_quarterly' => 'Principal and interest every calendar quarter',
        'at_maturity' => 'Single payment at maturity (bullet)',
    ];
    public const DAY_COUNTS = ['actual_365' => 'Actual/365', 'actual_360' => 'Actual/360', '30_360' => '30/360'];
    private const MONTHS = ['monthly' => 1, 'quarterly' => 3, 'half_yearly' => 6, 'yearly' => 12];

    /** Builds the schedule (without saving). */
    public function schedule(array $t): array
    {
        $principal = (float) $t['principal'];
        $rate = (float) $t['interest_rate'] / 100;
        $issue = Carbon::parse($t['issue_date'])->startOfDay();
        $maturity = Carbon::parse($t['maturity_date'])->startOfDay();
        if ($maturity->lte($issue)) throw ValidationException::withMessages(['maturity_date' => 'Maturity must be after the issue date.']);
        $frequency = $t['payment_frequency'];
        $basis = $t['day_count'] ?? 'actual_365';
        $moratoriumMonths = (int) ($t['moratorium_months'] ?? 0);
        $moratoriumType = $moratoriumMonths > 0 ? ($t['moratorium_type'] ?? 'principal_only') : null;
        $moratoriumEnd = $issue->copy()->addMonthsNoOverflow($moratoriumMonths);

        $dates = $this->dueDates($frequency, $issue, $maturity, isset($t['first_due_date']) ? Carbon::parse($t['first_due_date']) : null);
        $principalDates = $this->principalDates($frequency, $dates, $maturity);
        $rows = [];
        $balance = $principal;
        $previous = $issue->copy();
        $emiPeriods = in_array($frequency, array_keys(self::MONTHS), true) || $frequency === 'principal_and_interest_quarterly';
        $perYear = $frequency === 'principal_and_interest_quarterly' ? 4 : (12 / (self::MONTHS[$frequency] ?? 12));
        $payment = null;
        $count = count($dates);
        $principalLeft = count($principalDates);

        foreach ($dates as $i => $due) {
            $no = $i + 1;
            $days = $this->days($previous, $due, $basis);
            $interest = round($balance * $rate * $days / ($basis === 'actual_360' || $basis === '30_360' ? 360 : 365), 2);
            $inMoratorium = $moratoriumType && $due->lte($moratoriumEnd) && $no < $count;
            $opening = $balance;
            $principalPart = 0.0;
            $scheduled = 0.0;
            if ($inMoratorium) {
                if ($moratoriumType === 'principal_and_interest') { $balance = round($balance + $interest, 2); $total = 0.0; }
                else { $total = $interest; }
                $scheduled = $total;
                $payment = null;   // recalculate the instalment once the moratorium ends
            } elseif ($emiPeriods) {
                $remaining = $count - $i;
                if ($payment === null) $payment = $this->instalment($balance, $rate, $perYear, $remaining);
                $scheduled = $payment;
                $principalPart = $no === $count ? $balance : min($balance, round($payment - $interest, 2));
                $total = round($principalPart + $interest, 2);
                $balance = round($balance - $principalPart, 2);
            } else {
                // Interest-only periods; principal on its own dates (maturity, or each quarter).
                $isPrincipalDate = isset($principalDates[$due->toDateString()]);
                if ($isPrincipalDate) {
                    $principalPart = $no === $count ? $balance : round($balance / max(1, $principalLeft), 2);
                    $principalLeft--;
                }
                $total = round($interest + $principalPart, 2);
                $scheduled = $total;
                $balance = round($balance - $principalPart, 2);
            }
            $rows[] = ['installment_no' => $no, 'due_date' => $due->toDateString(), 'days' => $days, 'opening_balance' => round($opening, 2),
                'scheduled_payment' => round($scheduled, 2), 'principal' => round($principalPart, 2), 'interest' => $interest, 'total_payment' => round($total, 2),
                'closing_balance' => max(0, round($balance, 2)), 'daily_interest' => $days > 0 ? round($interest / $days, 4) : null, 'is_moratorium' => $inMoratorium];
            $previous = $due->copy();
        }
        $totals = ['principal' => round(array_sum(array_column($rows, 'principal')), 2), 'interest' => round(array_sum(array_column($rows, 'interest')), 2),
            'payment' => round(array_sum(array_column($rows, 'total_payment')), 2)];
        $firstRegular = collect($rows)->firstWhere('is_moratorium', false);
        return ['rows' => $rows, 'totals' => $totals, 'emi' => $emiPeriods ? ($firstRegular['scheduled_payment'] ?? null) : null, 'installments' => count($rows)];
    }

    public function save(array $terms, int $userId, ?Loan $loan = null): Loan
    {
        $result = $this->schedule($terms);
        return DB::transaction(function () use ($terms, $userId, $loan, $result) {
            $loan ??= new Loan(['loan_no' => 'LN' . now('Asia/Kolkata')->format('ymd') . strtoupper(\Illuminate\Support\Str::random(6)), 'user_id' => $userId]);
            $loan->fill(array_intersect_key($terms, array_flip(['borrower_name', 'account_number', 'loan_type', 'principal', 'interest_rate', 'issue_date', 'first_due_date',
                'maturity_date', 'payment_frequency', 'day_count', 'moratorium_months', 'moratorium_type'])))
                ->forceFill(['emi' => $result['emi'], 'total_interest' => $result['totals']['interest'], 'total_payment' => $result['totals']['payment'], 'installments' => $result['installments']])->save();
            LoanSchedule::where('loan_id', $loan->id)->delete();
            foreach (array_chunk($result['rows'], 200) as $chunk) {
                LoanSchedule::insert(array_map(fn ($r) => $r + ['loan_id' => $loan->id, 'status' => Carbon::parse($r['due_date'])->isPast() ? 'overdue' : 'due'], $chunk));
            }
            return $loan;
        });
    }

    public function instalment(float $balance, float $rate, float $perYear, int $periods): float
    {
        if ($periods <= 0) return $balance;
        if ($rate <= 0) return round($balance / $periods, 2);
        $r = $rate / $perYear;
        return round($balance * $r / (1 - pow(1 + $r, -$periods)), 2);
    }

    /** @return Carbon[] */
    private function dueDates(string $frequency, Carbon $issue, Carbon $maturity, ?Carbon $first): array
    {
        if ($frequency === 'at_maturity') return [$maturity->copy()];
        $calendarQuarter = in_array($frequency, ['interest_quarterly_principal_maturity', 'principal_and_interest_quarterly'], true);
        $step = $calendarQuarter ? 3 : (self::MONTHS[$frequency] ?? 1);
        $dates = [];
        $due = $first?->copy()->startOfDay() ?? ($calendarQuarter ? $issue->copy()->lastOfQuarter()->startOfDay() : $issue->copy()->addMonthsNoOverflow($step));
        if ($calendarQuarter && $due->lte($issue)) $due = $issue->copy()->addMonthsNoOverflow(3)->lastOfQuarter()->startOfDay();
        while ($due->lt($maturity) && count($dates) < 1200) {
            $dates[] = $due->copy();
            $due = $calendarQuarter ? $due->copy()->addMonthsNoOverflow(3)->lastOfQuarter()->startOfDay() : $due->copy()->addMonthsNoOverflow($step);
        }
        $dates[] = $maturity->copy();
        return $dates;
    }

    /** Dates on which principal falls due for interest-only structures (as a date => true set). */
    private function principalDates(string $frequency, array $dates, Carbon $maturity): array
    {
        if ($frequency === 'interest_monthly_principal_quarterly') {
            return collect($dates)->filter(fn ($d, $i) => ($i + 1) % 3 === 0 || $d->equalTo($maturity))->mapWithKeys(fn ($d) => [$d->toDateString() => true])->all();
        }
        return [$maturity->toDateString() => true];
    }

    private function days(CarbonInterface $from, CarbonInterface $to, string $basis): int
    {
        if ($basis !== '30_360') return (int) $from->diffInDays($to);
        $d1 = min(30, $from->day);
        $d2 = $to->day === 31 && $d1 === 30 ? 30 : $to->day;
        return ($to->year - $from->year) * 360 + ($to->month - $from->month) * 30 + ($d2 - $d1);
    }
}
