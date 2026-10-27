<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\UsListedCompany;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Annual statements of a US-listed company from its SEC company-facts file (10-K / 20-F / 40-F XBRL), written into
 * company_financial_statements so the page's key metrics, charts, ratios, credit assessment and vendor checks work as
 * they do for Indian filers. Money is converted to rupees at the ECB reference rate of each fiscal year-end; the source
 * column records the rate. Each year is labelled with the Indian financial year containing the middle of its period.
 */
class SecStatements
{
    private const FACTS = 'https://data.sec.gov/api/xbrl/companyfacts/CIK%010d.json';
    private const FX = 'https://api.frankfurter.dev/v1/%s?from=%s&to=INR';
    private const FORMS = ['10-K', '10-K/A', '20-F', '20-F/A', '40-F', '40-F/A'];

    // Field => tags tried in order; a list inside the list is summed (all parts must exist).
    private const FLOW = [
        'revenue' => ['Revenues', 'RevenueFromContractWithCustomerExcludingAssessedTax', 'RevenueFromContractWithCustomerIncludingAssessedTax', 'SalesRevenueNet', 'Revenue', 'RevenueFromContractsWithCustomers'],
        'operating_income' => ['OperatingIncomeLoss', 'ProfitLossFromOperatingActivities'],
        'total_expenses' => ['CostsAndExpenses', 'OperatingCostsAndExpenses'],
        'other_income' => ['OtherNonoperatingIncomeExpense', 'NonoperatingIncomeExpense', 'OtherIncome'],
        'depreciation' => ['DepreciationDepletionAndAmortization', 'DepreciationAndAmortization', 'DepreciationAmortizationAndAccretionNet', ['Depreciation', 'AmortizationOfIntangibleAssets'], 'Depreciation', 'DepreciationAndAmortisationExpense'],
        'finance_cost' => ['InterestExpense', 'InterestExpenseNonoperating', 'InterestExpenseDebt', 'InterestAndDebtExpense', 'FinanceCosts'],
        'profit_before_tax' => ['IncomeLossFromContinuingOperationsBeforeIncomeTaxesExtraordinaryItemsNoncontrollingInterest', 'IncomeLossFromContinuingOperationsBeforeIncomeTaxesMinorityInterestAndIncomeLossFromEquityMethodInvestments',
            ['IncomeLossFromContinuingOperationsBeforeIncomeTaxesDomestic', 'IncomeLossFromContinuingOperationsBeforeIncomeTaxesForeign'], 'ProfitLossBeforeTax'],
        'tax_expense' => ['IncomeTaxExpenseBenefit', 'IncomeTaxExpenseContinuingOperations'],
        'profit_after_tax' => ['NetIncomeLoss', 'ProfitLoss', 'ProfitLossAttributableToOwnersOfParent'],
        'employee_cost' => ['LaborAndRelatedExpense', 'EmployeeBenefitsExpense'],
        'eps' => ['EarningsPerShareDiluted', 'EarningsPerShareBasic', 'DilutedEarningsLossPerShare'],
        'shares_outstanding' => ['WeightedAverageNumberOfDilutedSharesOutstanding', 'WeightedAverageNumberOfSharesOutstandingBasic'],
    ];
    private const INSTANT = [
        'net_worth' => ['StockholdersEquity', 'StockholdersEquityIncludingPortionAttributableToNoncontrollingInterest', 'EquityAttributableToOwnersOfParent', 'Equity'],
        'share_capital' => ['CommonStocksIncludingAdditionalPaidInCapital', 'CommonStockValue', 'IssuedCapital'],
        'long_term_borrowings' => ['LongTermDebtNoncurrent', 'LongTermNotesAndLoans', 'LongTermNotesPayable', 'LongTermDebtAndCapitalLeaseObligations', 'NoncurrentPortionOfNoncurrentBorrowings'],
        'short_term_borrowings' => ['DebtCurrent', ['LongTermDebtCurrent', 'ShortTermBorrowings'], 'LongTermDebtCurrent', 'ShortTermBorrowings', 'NotesPayableCurrent', 'CurrentBorrowings'],
        'total_debt' => ['DebtLongtermAndShorttermCombinedAmount', 'LongTermDebt', 'Borrowings'],
        'trade_payables' => ['AccountsPayableCurrent', 'TradeAndOtherCurrentPayables'],
        'current_liabilities' => ['LiabilitiesCurrent', 'CurrentLiabilities'],
        'trade_receivables' => ['AccountsReceivableNetCurrent', 'TradeAndOtherCurrentReceivables'],
        'inventories' => ['InventoryNet', 'Inventories'],
        'cash_and_bank' => ['CashAndCashEquivalentsAtCarryingValue', 'CashCashEquivalentsRestrictedCashAndRestrictedCashEquivalents', 'CashAndCashEquivalents'],
        'current_assets' => ['AssetsCurrent', 'CurrentAssets'],
        'fixed_assets' => ['PropertyPlantAndEquipmentNet', 'PropertyPlantAndEquipment'],
        'total_assets' => ['Assets'],
    ];

    /** @return int years written */
    public function import(Company $company, int $years = 6): int
    {
        $listing = UsListedCompany::where('company_id', $company->id)->whereNotNull('cik')->orderByDesc('market_cap')->first();
        if (!$listing) return 0;
        $res = Http::withHeaders(['User-Agent' => config('services.sec.user_agent'), 'Accept-Encoding' => 'gzip, deflate'])->timeout(120)->retry(2, 2000, throw: false)
            ->get(sprintf(self::FACTS, $listing->cik));
        if (!$res->successful()) return 0;
        $facts = $res->json('facts') ?? [];
        $tags = ($facts['us-gaap'] ?? []) + ($facts['ifrs-full'] ?? []);
        if (!$tags) return 0;

        [$currency, $ends] = $this->fiscalYearEnds($tags);
        if (!$ends) return 0;
        $ends = array_slice($ends, -$years);
        $rows = [];
        foreach ($ends as $end) {
            $v = [];
            foreach (self::FLOW as $field => $choices) $v[$field] = $this->pick($tags, $choices, $currency, $end, true, $field);
            foreach (self::INSTANT as $field => $choices) $v[$field] = $this->pick($tags, $choices, $currency, $end, false, $field);
            if ($v['revenue'] === null && $v['total_assets'] === null) continue;
            $rate = $this->rate($currency, $end);
            if (!$rate) continue;
            $rows[] = $this->row($company, $end, $v, $rate, $currency, $listing);
        }
        if (!$rows) return 0;
        DB::transaction(function () use ($company, $rows) {
            // Rows from other sources (MCA filings, providers) are kept; SEC rows are replaced as a set.
            DB::table('company_financial_statements')->where('company_id', $company->id)->where('source', 'like', 'SEC %')->delete();
            $taken = DB::table('company_financial_statements')->where('company_id', $company->id)->where('is_consolidated', true)->pluck('financial_year')->all();
            foreach ($rows as $r) if (!in_array($r['financial_year'], $taken, true)) DB::table('company_financial_statements')->insert($r);
        });
        Cache::forget('insights:sec-statements:' . $company->id);
        return count($rows);
    }

    /** Currency of the filings and the fiscal year-end dates of annual reports (oldest first). */
    private function fiscalYearEnds(array $tags): array
    {
        foreach (['Revenues', 'RevenueFromContractWithCustomerExcludingAssessedTax', 'NetIncomeLoss', 'ProfitLoss', 'Revenue', 'Assets'] as $tag) {
            foreach ($tags[$tag]['units'] ?? [] as $unit => $items) {
                if (!preg_match('~^[A-Z]{3}$~', $unit)) continue;
                $ends = collect($items)->filter(fn ($x) => in_array($x['form'] ?? '', self::FORMS, true) && ($x['fp'] ?? '') === 'FY'
                    && (!isset($x['start']) || abs(Carbon::parse($x['start'])->diffInDays(Carbon::parse($x['end']))) >= 340))->pluck('end')->unique()->sort()->values();
                if ($ends->isNotEmpty()) return [$unit, $ends->all()];
            }
        }
        return [null, []];
    }

    private function pick(array $tags, array $choices, string $currency, string $end, bool $flow, string $field)
    {
        foreach ($choices as $choice) {
            if (is_array($choice)) {
                $parts = array_map(fn ($t) => $this->value($tags, $t, $currency, $end, $flow, $field), $choice);
                if (!in_array(null, $parts, true)) return array_sum($parts);
                continue;
            }
            if (($v = $this->value($tags, $choice, $currency, $end, $flow, $field)) !== null) return $v;
        }
        return null;
    }

    /** The latest-filed annual value of a tag for the period ending on $end. */
    private function value(array $tags, string $tag, string $currency, string $end, bool $flow, string $field): ?float
    {
        $unit = match ($field) { 'eps' => $currency . '/shares', 'shares_outstanding' => 'shares', default => $currency };
        $items = $tags[$tag]['units'][$unit] ?? null;
        if (!$items) return null;
        $match = collect($items)->filter(function ($x) use ($end, $flow) {
            if (($x['end'] ?? null) !== $end || !in_array($x['form'] ?? '', self::FORMS, true)) return false;
            if (!$flow) return !isset($x['start']);
            return isset($x['start']) && abs(Carbon::parse($x['start'])->diffInDays(Carbon::parse($end))) >= 340;
        })->sortByDesc('filed')->first();
        return $match ? (float) $match['val'] : null;
    }

    /** ECB reference rate (via Frankfurter) on the year-end date, or the last published day before it. */
    private function rate(string $currency, string $end): ?float
    {
        if ($currency === 'INR') return 1.0;
        return Cache::remember("fx:{$currency}INR:$end", now()->addDays(60), function () use ($currency, $end) {
            try {
                return Http::timeout(20)->retry(2, 1000, throw: false)->get(sprintf(self::FX, $end, $currency))->json('rates.INR') ?: null;
            } catch (Throwable $e) {
                report($e);
                return null;
            }
        });
    }

    private function row(Company $company, string $end, array $v, float $rate, string $currency, UsListedCompany $listing): array
    {
        $inr = fn ($x) => $x === null ? null : round($x * $rate, 2);
        $endDate = Carbon::parse($end);
        $mid = $endDate->copy()->subMonths(6);
        $fyStart = $mid->month >= 4 ? $mid->year : $mid->year - 1;
        $ebitda = $v['operating_income'] !== null && $v['depreciation'] !== null ? $v['operating_income'] + $v['depreciation'] : null;
        $debt = $v['total_debt'] ?? ($v['long_term_borrowings'] !== null || $v['short_term_borrowings'] !== null ? (float) $v['long_term_borrowings'] + (float) $v['short_term_borrowings'] : null);
        $isLatest = $endDate->gt(now()->subMonths(15));
        return [
            'company_id' => $company->id, 'financial_year' => $fyStart . '-' . substr((string) ($fyStart + 1), -2), 'period_end' => $end, 'is_consolidated' => true,
            'revenue' => $inr($v['revenue']), 'other_income' => $inr($v['other_income']),
            'total_income' => $v['revenue'] !== null ? $inr($v['revenue'] + (float) $v['other_income']) : null,
            'total_expenses' => $inr($v['total_expenses'] ?? ($v['revenue'] !== null && $v['operating_income'] !== null ? $v['revenue'] - $v['operating_income'] : null)),
            'cost_of_materials_consumed' => null, 'purchases_of_stock_in_trade' => null, 'other_expenses' => null,
            'ebitda' => $inr($ebitda), 'depreciation' => $inr($v['depreciation']), 'finance_cost' => $inr($v['finance_cost']),
            'profit_before_tax' => $inr($v['profit_before_tax'] ?? ($v['profit_after_tax'] !== null && $v['tax_expense'] !== null ? $v['profit_after_tax'] + $v['tax_expense'] : null)),
            'tax_expense' => $inr($v['tax_expense']), 'profit_after_tax' => $inr($v['profit_after_tax']),
            'share_capital' => $inr($v['share_capital']), 'reserves' => $v['net_worth'] !== null && $v['share_capital'] !== null ? $inr($v['net_worth'] - $v['share_capital']) : null,
            'net_worth' => $inr($v['net_worth']), 'long_term_borrowings' => $inr($v['long_term_borrowings']), 'short_term_borrowings' => $inr($v['short_term_borrowings']),
            'total_debt' => $inr($debt), 'trade_payables' => $inr($v['trade_payables']), 'current_liabilities' => $inr($v['current_liabilities']),
            'trade_receivables' => $inr($v['trade_receivables']), 'inventories' => $inr($v['inventories']), 'cash_and_bank' => $inr($v['cash_and_bank']),
            'current_assets' => $inr($v['current_assets']), 'fixed_assets' => $inr($v['fixed_assets']), 'total_assets' => $inr($v['total_assets']),
            'employee_cost' => $inr($v['employee_cost']),
            'market_price' => $isLatest && $listing->last_price && $currency === 'USD' ? round($listing->last_price * $rate, 2) : null,
            'eps' => $v['eps'] !== null ? round($v['eps'] * $rate, 2) : null, 'shares_outstanding' => $v['shares_outstanding'],
            'source' => mb_substr("SEC annual report ({$currency}; ₹" . number_format($rate, 2) . " per {$currency} on {$endDate->format('d M Y')})", 0, 190),
            'filing_id' => null, 'created_at' => now(), 'updated_at' => now(),
        ];
    }
}
