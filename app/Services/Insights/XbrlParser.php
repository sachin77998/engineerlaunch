<?php

namespace App\Services\Insights;

use Carbon\Carbon;
use DOMDocument;
use RuntimeException;

/**
 * Reads the XBRL instance attached to an AOC-4 XBRL filing (e.g. "Instance_Company_2024-25.xml"). Facts are
 * matched by element name, so both the Schedule III (AS, in-gaap) and Ind AS taxonomies work. Each instance holds
 * the current and the previous year, so one file gives two years of figures. Amounts in XBRL are in rupees.
 */
class XbrlParser
{
    // statement column => XBRL element names, most specific first.
    private const FIELDS = [
        'revenue' => ['RevenueFromOperations'],
        'other_income' => ['OtherIncome'],
        'total_income' => ['Income', 'TotalRevenue', 'Revenue'],
        'total_expenses' => ['Expenses', 'TotalExpenses'],
        'employee_cost' => ['EmployeeBenefitExpense'],
        'finance_cost' => ['FinanceCosts'],
        'depreciation' => ['DepreciationDepletionAndAmortisationExpense', 'DepreciationAndAmortisationExpense'],
        'profit_before_tax' => ['ProfitBeforeTax', 'ProfitLossBeforeTax', 'ProfitBeforeExtraordinaryItemsAndTax'],
        'tax_expense' => ['TaxExpense', 'IncomeTaxExpenseContinuingOperations'],
        'profit_after_tax' => ['ProfitLossForPeriod', 'ProfitLoss', 'ProfitLossForPeriodFromContinuingOperations'],
        'share_capital' => ['ShareCapital', 'EquityShareCapital'],
        'reserves' => ['ReservesAndSurplus', 'OtherEquity'],
        'net_worth' => ['ShareholdersFunds', 'Equity', 'EquityAttributableToOwnersOfParent'],
        'long_term_borrowings' => ['LongTermBorrowings', 'BorrowingsNoncurrent', 'NoncurrentBorrowings'],
        'short_term_borrowings' => ['ShortTermBorrowings', 'BorrowingsCurrent', 'CurrentBorrowings'],
        'trade_payables' => ['TradePayables', 'TradePayablesCurrent'],
        'current_liabilities' => ['CurrentLiabilities'],
        'trade_receivables' => ['TradeReceivables', 'TradeReceivablesCurrent'],
        'inventories' => ['Inventories'],
        'cash_and_bank' => ['CashAndBankBalances', 'CashAndCashEquivalents'],
        'current_assets' => ['CurrentAssets'],
        'fixed_assets' => ['TangibleAssets', 'PropertyPlantAndEquipment'],
        'total_assets' => ['Assets'],
        'eps' => ['BasicEarningsLossPerShareFromContinuingOperations', 'BasicEarningsPerShareAfterExtraordinaryItems', 'BasicEarningsLossPerShare', 'BasicEarningsPerShare'],
    ];

    public static function looksLikeXbrl(string $text): bool
    {
        $head = substr(ltrim($text), 0, 4000);
        return str_starts_with($head, '<') && (stripos($head, 'xbrl') !== false);
    }

    public function parse(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded) throw new RuntimeException('The XBRL file could not be read as XML.');

        // Contexts: id => [end date, has dimensions]. Totals use contexts without dimensions.
        $contexts = [];
        foreach ($dom->getElementsByTagNameNS('*', 'context') as $context) {
            $end = null;
            foreach (['endDate', 'instant'] as $tag) {
                $node = $context->getElementsByTagNameNS('*', $tag)->item(0);
                if ($node) { $end = trim($node->textContent); break; }
            }
            $dimensional = $context->getElementsByTagNameNS('*', 'segment')->length > 0 || $context->getElementsByTagNameNS('*', 'scenario')->length > 0;
            $contexts[$context->getAttribute('id')] = ['end' => $end, 'dimensional' => $dimensional];
        }

        // facts[name][contextId] = value; textFacts[contextId][name] = value (for dimensional rows such as directors).
        $facts = [];
        $byContext = [];
        foreach ($dom->getElementsByTagName('*') as $element) {
            $contextId = $element->getAttribute('contextRef');
            if ($contextId === '' || !isset($contexts[$contextId])) continue;
            $value = trim($element->textContent);
            if ($value === '') continue;
            $facts[$element->localName][$contextId] = $value;
            $byContext[$contextId][$element->localName] = $value;
        }

        $data = ['cin' => null, 'company_name' => null, 'years' => [], 'officers' => [], 'auditors' => [], 'activities' => [], 'related_parties' => []];
        $data['cin'] = $this->text($facts, ['CorporateIdentityNumber', 'CINOfCompany']);
        $data['company_name'] = $this->text($facts, ['NameOfCompany', 'CompanyName']);

        // Year-wise figures from non-dimensional contexts, grouped by financial year end.
        foreach (self::FIELDS as $field => $names) {
            foreach ($names as $name) {
                if (empty($facts[$name])) continue;
                $found = false;
                foreach ($facts[$name] as $contextId => $value) {
                    $context = $contexts[$contextId];
                    if ($context['dimensional'] || !$context['end'] || !is_numeric($value)) continue;
                    $year = FilingParser::financialYear(Carbon::parse($context['end']));
                    $data['years'][$year]['period_end'] ??= Carbon::parse($context['end'])->toDateString();
                    $data['years'][$year][$field] ??= (float) $value;
                    $found = true;
                }
                if ($found) break;
            }
        }
        foreach ($data['years'] as $year => &$figures) {
            if (!isset($figures['net_worth']) && (isset($figures['share_capital']) || isset($figures['reserves']))) $figures['net_worth'] = ($figures['share_capital'] ?? 0) + ($figures['reserves'] ?? 0);
            if (isset($figures['long_term_borrowings']) || isset($figures['short_term_borrowings'])) $figures['total_debt'] = ($figures['long_term_borrowings'] ?? 0) + ($figures['short_term_borrowings'] ?? 0);
            if (isset($figures['profit_before_tax'], $figures['finance_cost'], $figures['depreciation'])) $figures['ebitda'] = $figures['profit_before_tax'] + $figures['finance_cost'] + $figures['depreciation'];
        }
        unset($figures);
        krsort($data['years']);

        // Rows held in dimensional contexts: directors, auditors, products, related parties.
        foreach ($byContext as $row) {
            $get = fn (array $names) => $this->first($row, $names);
            if ($din = $get(['DirectorIdentificationNumberOfDirector', 'DINOfDirector'])) {
                $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([$get(['FirstNameOfDirector']), $get(['MiddleNameOfDirector']), $get(['LastNameOfDirector'])]))));
                if ($name !== '') $data['officers'][$din . $name] = ['din' => preg_match('/^\d{8}$/', $din) ? $din : null, 'name' => ucwords(strtolower($name)),
                    'designation' => ucwords(strtolower((string) $get(['DesignationOfDirector']))) ?: 'Director', 'appointed_on' => null, 'ceased_on' => null, 'shares_held' => null, 'pan' => null];
            }
            if ($firm = $get(['NameOfAuditFirm', 'NameOfAuditor', 'NameOfAuditorOrAuditFirm'])) {
                $data['auditors'][$firm] = ['name' => trim($firm), 'firm_registration_number' => $get(['FirmsRegistrationNumberOfAuditFirm', 'FirmRegistrationNumberOfAuditFirm', 'RegistrationNumberOfAuditFirm']),
                    'membership_number' => $get(['MembershipNumberOfAuditor', 'MembershipNumberOfPartnerOfAuditFirm']), 'pan' => $get(['IncomeTaxPANOfAuditorOrAuditorsFirm', 'PANOfAuditor']), 'city' => $get(['CityOfAuditor', 'CityOfAuditFirm'])];
            }
            if ($code = $get(['ProductOrServiceCategoryITC4DigitCode', 'ProductOrServiceCategoryITC8DigitCode'])) {
                $turnover = $get(['TurnoverOfProductOrServiceCategory']);
                $data['activities'][$code] = ['code' => $code, 'description' => (string) $get(['DescriptionOfProductOrServiceCategory']), 'main_activity' => 'ITC ' . $code, 'turnover' => is_numeric($turnover) ? (float) $turnover : null];
            }
            if ($party = $get(['NameOfRelatedParty'])) {
                $amount = $get(['AmountOfTransactionsWithRelatedParty', 'RelatedPartyTransactionAmount']);
                $data['related_parties'][] = ['party_name' => $party, 'relationship' => $get(['DescriptionOfNatureOfRelatedPartyRelationship', 'NatureOfRelatedPartyRelationship']),
                    'transaction_description' => $get(['DescriptionOfNatureOfTransactionWithRelatedParty', 'NatureOfTransactionWithRelatedParty']), 'amount' => is_numeric($amount) ? (float) $amount : null];
            }
        }
        $data['officers'] = array_values($data['officers']);
        $data['auditors'] = array_values($data['auditors']);
        $latestRevenue = reset($data['years'])['revenue'] ?? null;
        $data['activities'] = array_values(array_map(fn ($a) => $a + ['turnover_percentage' => $a['turnover'] !== null && $latestRevenue ? round(min(100, $a['turnover'] / $latestRevenue * 100), 2) : null], $data['activities']));
        $data['related_parties'] = array_slice($data['related_parties'], 0, 100);
        return $data;
    }

    private function text(array $facts, array $names): ?string
    {
        foreach ($names as $name) if (!empty($facts[$name])) return (string) reset($facts[$name]);
        return null;
    }

    private function first(array $row, array $names): ?string
    {
        foreach ($names as $name) if (isset($row[$name]) && $row[$name] !== '') return $row[$name];
        return null;
    }
}
