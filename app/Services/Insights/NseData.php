<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CompanyFinancialStatement;
use App\Models\CompanyShareholding;
use App\Services\Discovery\CompanyClassifier;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Listed-company data published by NSE: the equity list, audited annual results (XBRL, FY 2017-18 onwards, including
 * SEBI "Integrated Filing" from 2025), the shareholding pattern and the share price. Requests are spaced out politely.
 */
class NseData
{
    private const BASE = 'https://www.nseindia.com';
    private const EQUITY_LIST = 'https://nsearchives.nseindia.com/content/equities/EQUITY_L.csv';
    private const PAUSE_MS = 350;

    private ?CookieJar $jar = null;
    private bool $primed = false;

    /** Every equity listed on NSE: symbol, name, series, listing date, ISIN, face value. */
    public function equityList(): array
    {
        $csv = $this->get(self::EQUITY_LIST, [], false);
        $rows = [];
        foreach (preg_split('/\r?\n/', trim($csv)) as $i => $line) {
            if ($i === 0 || trim($line) === '') continue;
            $c = array_map('trim', str_getcsv($line));
            if (count($c) < 8) continue;
            $listed = null;
            try { $listed = Carbon::createFromFormat('d-M-Y', $c[3])->toDateString(); } catch (Throwable) {}
            $rows[] = ['symbol' => $c[0], 'name' => $c[1], 'series' => $c[2], 'listed_on' => $listed, 'isin' => $c[6], 'face_value' => is_numeric($c[7]) ? (float) $c[7] : null];
        }
        return $rows;
    }

    /**
     * Audited annual results with XBRL for a symbol, newest first: [year_end, consolidated, xbrl].
     * Older years come from the financial-results list, 2025 onwards from Integrated Filing.
     */
    public function annualFilings(string $symbol): array
    {
        $found = [];
        $add = function (?string $end, bool $consolidated, ?string $xbrl, ?string $broadcast, bool $audited) use (&$found) {
            if (!$end || !$xbrl || !str_ends_with(strtolower($xbrl), '.xml')) return;
            try { $date = Carbon::parse($end); } catch (Throwable) { return; }
            $key = $date->toDateString() . ($consolidated ? '-c' : '-s');
            // Audited beats unaudited; among equals a later (revised) filing replaces the earlier one.
            $rank = [(int) $audited, $broadcast ? (int) strtotime($broadcast) : 0];
            if (!isset($found[$key]) || $rank >= $found[$key]['rank']) $found[$key] = ['year_end' => $date, 'consolidated' => $consolidated, 'xbrl' => $xbrl, 'rank' => $rank];
        };
        foreach ((array) $this->api('/api/corporates-financial-results', ['index' => 'equities', 'period' => 'Annual', 'symbol' => $symbol]) as $r) {
            if (!is_array($r)) continue;
            $add($r['toDate'] ?? null, stripos((string) ($r['consolidated'] ?? ''), 'non') === false, $r['xbrl'] ?? null, $r['broadCastDate'] ?? null, stripos((string) ($r['audited'] ?? ''), 'un') !== 0);
        }
        $integrated = $this->api('/api/integrated-filing-results', ['index' => 'equities', 'type' => 'Integrated Filing- Financials', 'symbol' => $symbol]);
        foreach ((array) ($integrated['data'] ?? []) as $r) {
            // The March quarter filing carries the full-year statement (some companies label it unaudited).
            if (!is_array($r) || !preg_match('/^31-MAR-/i', (string) ($r['qe_Date'] ?? ''))) continue;
            $add($r['qe_Date'], strcasecmp((string) ($r['consolidated'] ?? ''), 'Consolidated') === 0, $r['xbrl'] ?? null, $r['broadcast_Date'] ?? null, stripos((string) ($r['audited'] ?? ''), 'un') !== 0);
        }
        usort($found, fn ($a, $b) => $b['year_end'] <=> $a['year_end']);
        return array_values($found);
    }

    /** Full-year profit and loss and year-end balance sheet from a results XBRL file (amounts in rupees). */
    public function parseResults(string $xml): array
    {
        $doc = new DOMDocument();
        if (!@$doc->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT)) throw new RuntimeException('Unreadable XBRL');
        $xp = new DOMXPath($doc);
        // Contexts without dimensions (whole-company figures).
        $durations = $instants = [];
        foreach ($xp->query('//*[local-name()="context"]') as $ctx) {
            if ($xp->query('.//*[local-name()="explicitMember" or local-name()="typedMember"]', $ctx)->length) continue;
            $id = $ctx->getAttribute('id');
            $instant = $xp->query('.//*[local-name()="instant"]', $ctx)->item(0);
            if ($instant) $instants[$id] = trim($instant->textContent); else $durations[] = $id;
        }
        $facts = [];
        foreach ($xp->query('//*[@contextRef]') as $el) {
            $facts[$el->localName][$el->getAttribute('contextRef')] = trim($el->textContent);
        }
        $num = fn ($v) => $v !== null && is_numeric(str_replace(',', '', $v)) ? (float) str_replace(',', '', $v) : null;
        // NSE names the full-year context "FourD" and the year-end balance sheet "OneI" (older files use them without
        // defining them); otherwise the full year is the duration with the largest revenue (a year exceeds a quarter).
        $used = collect($facts)->flatMap(fn ($byContext) => array_keys($byContext))->unique()->all();
        $year = in_array('FourD', $used, true) ? 'FourD' : collect($durations)->sortByDesc(fn ($c) => abs($num($facts['RevenueFromOperations'][$c] ?? $facts['Income'][$c] ?? null) ?? 0))->first();
        $end = $facts['DateOfEndOfReportingPeriod'][$year] ?? null;
        $instant = in_array('OneI', $used, true) ? 'OneI' : (collect($instants)->filter(fn ($d) => $d === $end)->keys()->first() ?? collect($instants)->sortDesc()->keys()->first());
        $d = fn (string ...$tags) => $this->first($facts, $tags, $year, $num);
        $i = fn (string ...$tags) => $this->first($facts, $tags, $instant, $num);
        $sum = function (?float ...$v): ?float {
            $v = array_filter($v, fn ($x) => $x !== null);
            return $v ? array_sum($v) : null;
        };

        $revenue = $d('RevenueFromOperations', 'InterestEarned', 'TotalRevenueFromOperations', 'NetSalesIncomeFromOperations', 'TotalIncomeFromOperations', 'NetPremiumIncome', 'NetPremiumEarned');
        $otherIncome = $d('OtherIncome');
        $income = $d('Income', 'TotalIncome');
        $long = $i('BorrowingsNoncurrent'); $short = $i('BorrowingsCurrent');
        $row = [
            'period_end' => $end,
            'is_consolidated' => stripos((string) ($facts['NatureOfReportStandaloneConsolidated'][$year] ?? ''), 'consolidated') === 0,
            'revenue' => $revenue ?? ($income !== null && $otherIncome !== null ? $income - $otherIncome : null),
            'other_income' => $otherIncome,
            'total_income' => $income,
            'total_expenses' => $d('Expenses', 'TotalExpenses'),
            'cost_of_materials_consumed' => $d('CostOfMaterialsConsumed'),
            'purchases_of_stock_in_trade' => $d('PurchasesOfStockInTrade'),
            'other_expenses' => $d('OtherExpenses'),
            'employee_cost' => $d('EmployeeBenefitExpense', 'EmployeesCost', 'EmployeesRemunerationAndWelfareExpenses'),
            'finance_cost' => $d('FinanceCosts', 'InterestExpended'),
            'depreciation' => $d('DepreciationDepletionAndAmortisationExpense', 'DepreciationAndAmortisationExpense'),
            'profit_before_tax' => $d('ProfitBeforeTax', 'ProfitLossFromOrdinaryActivitiesBeforeTax', 'ProfitLossBeforeTax'),
            'tax_expense' => $d('TaxExpense'),
            'profit_after_tax' => $d('ProfitLossForPeriod', 'ProfitLossForThePeriod', 'NetProfitLossForThePeriod', 'NetProfitLossForPeriod', 'ProfitLossAfterTaxAndExtraordinaryItems', 'ProfitLossAfterTaxBeforeExtraordinaryItems'),
            'eps' => $d('BasicEarningsLossPerShareFromContinuingAndDiscontinuedOperations', 'BasicEarningsLossPerShareFromContinuingOperations', 'BasicEarningsPerShareAfterExtraordinaryItems', 'BasicAndDilutedEPSAfterExtraordinaryItemsNetOfTaxExpenseForThePeriodNotToBeAnnualized', 'BasicEarningsPerShareAfterExtraordinaryItemsNotAnnualised'),
            'share_capital' => $i('EquityShareCapital', 'ShareCapital', 'PaidUpEquityShareCapital') ?? $d('PaidUpValueOfEquityShareCapital'),
            'reserves' => $i('OtherEquity', 'ReservesExcludingRevaluationReserves', 'ReservesAndSurplus') ?? $d('ReserveExcludingRevaluationReserves', 'ReservesAndSurplusExcludingRevaluationReserve'),
            'net_worth' => $i('EquityAttributableToOwnersOfParent', 'Equity', 'TotalEquity', 'ShareholdersFunds'),
            'long_term_borrowings' => $long,
            'short_term_borrowings' => $short,
            'total_debt' => $sum($long, $short) ?? $i('Borrowings'),
            'trade_payables' => $sum($i('TradePayablesCurrent'), $i('TradePayablesNoncurrent')),
            'current_liabilities' => $i('CurrentLiabilities'),
            'trade_receivables' => $sum($i('TradeReceivablesCurrent'), $i('TradeReceivablesNoncurrent')),
            'inventories' => $i('Inventories'),
            'cash_and_bank' => $sum($i('CashAndCashEquivalents'), $i('BankBalanceOtherThanCashAndCashEquivalents')),
            'current_assets' => $i('CurrentAssets'),
            'fixed_assets' => $i('PropertyPlantAndEquipment', 'FixedAssets'),
            'total_assets' => $i('Assets', 'TotalAssets'),
        ];
        $face = $d('FaceValueOfEquityShareCapital');
        if ($row['share_capital'] && $face > 0) $row['shares_outstanding'] = (int) round($row['share_capital'] / $face);
        if ($row['net_worth'] === null && $row['share_capital'] !== null && $row['reserves'] !== null) $row['net_worth'] = $row['share_capital'] + $row['reserves'];
        if ($row['profit_before_tax'] !== null && $row['finance_cost'] !== null && $row['depreciation'] !== null) {
            $row['ebitda'] = $row['profit_before_tax'] + $row['finance_cost'] + $row['depreciation'];
        }
        return $row;
    }

    /** Latest shareholding pattern per financial year: [fy => [promoter, public, employee_trusts, date]]. */
    public function shareholding(string $symbol): array
    {
        $out = [];
        foreach ((array) $this->api('/api/corporate-share-holdings-master', ['index' => 'equities', 'symbol' => $symbol]) as $r) {
            if (!is_array($r) || !isset($r['date'], $r['pr_and_prgrp'])) continue;
            try { $date = Carbon::parse($r['date']); } catch (Throwable) { continue; }
            $fy = FilingParser::financialYear($date);
            if (isset($out[$fy]) && $out[$fy]['date']->gte($date)) continue;
            $out[$fy] = ['date' => $date, 'promoter' => (float) $r['pr_and_prgrp'], 'public' => is_numeric($r['public_val'] ?? null) ? (float) $r['public_val'] : null,
                'employee_trusts' => is_numeric($r['employeeTrusts'] ?? null) ? (float) $r['employeeTrusts'] : null];
        }
        krsort($out);
        return $out;
    }

    /** Closing prices of every equity from the latest daily bhavcopy in the NSE archive: [symbol => close]. */
    public function closingPrices(): array
    {
        for ($day = now('Asia/Kolkata'), $tries = 0; $tries < 8; $day = $day->subDay(), $tries++) {
            if ($day->isWeekend()) continue;
            try {
                $csv = $this->get('https://nsearchives.nseindia.com/products/content/sec_bhavdata_full_' . $day->format('dmY') . '.csv', [], false);
            } catch (Throwable) {
                continue;
            }
            $prices = [];
            foreach (preg_split('/\r?\n/', trim($csv)) as $i => $line) {
                $c = array_map('trim', explode(',', $line));
                if ($i === 0 || count($c) < 9 || !in_array($c[1], ['EQ', 'BE', 'BZ', 'SM', 'ST'], true) || !is_numeric($c[8])) continue;
                $prices[$c[0]] ??= (float) $c[8];
            }
            if ($prices) return $prices;
        }
        return [];
    }

    /** Imports one listed company: link to its CIN and company page, then results, shareholding and price. Returns years stored. */
    public function import(object $listed): int
    {
        $company = $this->companyFor($listed);
        $this->fileSector($company, $listed);
        $years = 0;
        foreach ($this->annualFilings($listed->symbol) as $filing) {
            if ($filing['year_end']->year < 2017) continue;
            try {
                $row = $this->parseResults($this->get($filing['xbrl'], [], false));
            } catch (Throwable $e) {
                report($e);
                continue;
            }
            if ($row['revenue'] === null && $row['profit_after_tax'] === null) continue;
            $this->storeStatement($company, $filing['year_end'], $filing['consolidated'], $row);
            $years++;
        }
        try {
            foreach (array_slice($this->shareholding($listed->symbol), 0, 6, true) as $fy => $s) {
                foreach (['Promoters' => $s['promoter'], 'Public / other than promoters' => $s['public'], 'Employee trusts' => $s['employee_trusts']] as $category => $pct) {
                    if ($pct === null || ($category === 'Employee trusts' && $pct == 0)) continue;
                    CompanyShareholding::updateOrCreate(['company_id' => $company->id, 'financial_year' => $fy, 'category' => $category], ['percentage' => $pct, 'source' => 'NSE shareholding pattern ' . $s['date']->format('d M Y')]);
                }
            }
        } catch (Throwable $e) { report($e); }
        $price = $listed->last_price ? (float) $listed->last_price : null;
        if ($price) {
            CompanyFinancialStatement::where('company_id', $company->id)->orderByDesc('financial_year')->limit(2)->get()
                ->groupBy('financial_year')->first()?->each(fn ($s) => $s->forceFill(['market_price' => $price])->save());
        }
        DB::table('listed_companies')->where('id', $listed->id)->update(['company_id' => $company->id, 'last_price' => $price, 'years_imported' => $years,
            'synced_at' => now(), 'sync_error' => null, 'updated_at' => now()]);
        $company->registryDetail?->forceFill(['listing_status' => 'Listed (NSE: ' . $listed->symbol . ')'])->save();
        return $years;
    }

    /**
     * Only the company page and its sector for a listed company: no filings are downloaded and the CIN is looked up in the local
     * MCA table only, so every listing gets a page in minutes. Results, shareholding and prices follow with the full import.
     */
    public function link(object $listed): Company
    {
        $this->offline = true;
        try {
            $company = $this->companyFor($listed);
        } finally { $this->offline = false; }
        $this->fileSector($company, $listed);
        DB::table('listed_companies')->where('id', $listed->id)->update(['company_id' => $company->id, 'updated_at' => now()]);
        $company->registryDetail?->forceFill(['listing_status' => 'Listed (NSE: ' . $listed->symbol . ')'])->save();
        return $company;
    }

    private bool $offline = false;

    private function fileSector(Company $company, object $listed): void
    {
        try {
            $sectors = app(ListedSectors::class);
            $cin = DB::table('listed_companies')->where('id', $listed->id)->value('cin') ?: $company->registryDetail?->cin;
            if ($decision = $sectors->classify($listed->name, $sectors->industries()[$listed->symbol] ?? null, $cin)) $sectors->file($company, $decision);
        } catch (Throwable $e) { report($e); }
    }

    /** The company page for a listed company: by CIN from the MCA register when the name matches, else by name. */
    public function companyFor(object $listed): Company
    {
        if ($listed->company_id && ($company = Company::find($listed->company_id))) return $company;
        $cin = $listed->cin ?: $this->findCin($listed->name);
        if ($cin) {
            DB::table('listed_companies')->where('id', $listed->id)->update(['cin' => $cin]);
            if ($company = app(DataGovMca::class)->companyFor($cin)) return $company;
        }
        $company = Company::where('name_key', Company::nameKey($listed->name))->first();
        if ($company) return $company;
        [$sector, $subsector] = app(CompanyClassifier::class)->classify($listed->name, '');
        $base = Str::limit(Str::slug($listed->name), 200, '') ?: Str::slug($listed->symbol);
        $slug = $base;
        for ($n = 2; Company::where('slug', $slug)->exists(); $n++) $slug = $base . '-' . $n;
        $company = Company::create(['name' => $listed->name, 'slug' => $slug, 'country' => 'India', 'industry' => $sector, 'sector' => $subsector, 'is_active' => true, 'sync_enabled' => false]);
        app(\App\Services\SectorCatalogImporter::class)->attach($company, $sector, $subsector);
        return $company;
    }

    /** CIN of a listed company from the MCA register (stored copy, then the live data.gov.in lookup). */
    public function findCin(string $name): ?string
    {
        $legal = strtoupper(trim(preg_replace(['/\bLtd\.?$/i', '/\bPvt\.?\b/i', '/\s+/'], ['LIMITED', 'PRIVATE', ' '], $name)));
        $variants = array_unique([$legal, str_replace('&', 'AND', $legal), str_replace(' AND ', ' & ', $legal), str_replace('.', '', $legal)]);
        $cin = DB::table('mca_companies')->whereIn('name', $variants)->where('cin', 'like', 'L%')->value('cin');
        if ($cin) return $cin;
        $mca = app(DataGovMca::class);
        if ($this->offline || !$mca->configured()) return null;
        try {
            foreach ($variants as $variant) {
                $rows = $mca->page(['company_name' => $variant], 0, 5)['records'];
                $mca->store($rows);
                foreach ($rows as $r) if (str_starts_with($r['cin'], 'L')) return $r['cin'];
                usleep(self::PAUSE_MS * 1000);
            }
        } catch (Throwable $e) { report($e); }
        return null;
    }

    private function storeStatement(Company $company, Carbon $yearEnd, bool $consolidated, array $row): void
    {
        $statement = CompanyFinancialStatement::firstOrNew(['company_id' => $company->id, 'financial_year' => FilingParser::financialYear($yearEnd), 'is_consolidated' => $consolidated]);
        // Figures filed by hand or read from the company's own MCA filings are kept; NSE fills their gaps.
        $keep = in_array($statement->source, ['manual', 'aoc4', 'xbrl'], true);
        foreach ($row as $field => $value) {
            if ($field === 'is_consolidated' || $value === null) continue;
            if (!$keep || $statement->{$field} === null) $statement->{$field} = $value;
        }
        $statement->period_end ??= $yearEnd->toDateString();
        if (!$keep) $statement->source = 'NSE';
        $statement->save();
    }

    private function first(array $facts, array $tags, ?string $context, callable $num): ?float
    {
        if (!$context) return null;
        foreach ($tags as $tag) {
            if (($v = $num($facts[$tag][$context] ?? null)) !== null) return $v;
        }
        return null;
    }

    private function api(string $path, array $query)
    {
        $body = $this->get(self::BASE . $path, $query, true);
        return is_array($body) ? $body : [];
    }

    /** GET with browser-like headers and NSE session cookies (obtained from the home page when the API asks for them). */
    private function get(string $url, array $query, bool $json)
    {
        $this->jar ??= new CookieJar();
        for ($attempt = 0; $attempt < 2; $attempt++) {
            usleep(self::PAUSE_MS * 1000);
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
                'Accept' => $json ? 'application/json, text/plain, */*' : '*/*',
                'Accept-Language' => 'en-IN,en;q=0.9',
                'Referer' => 'https://www.nseindia.com/',
            ])->withOptions(['cookies' => $this->jar])->timeout(60)->retry(2, 3000, throw: false)->get($url, $query);
            if ($response->successful()) return $json ? $response->json() : $response->body();
            if (in_array($response->status(), [401, 403], true) && !$this->primed) {
                $this->primed = true;
                foreach ([self::BASE . '/', self::BASE . '/market-data/live-equity-market'] as $page) {
                    try { Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36', 'Accept' => 'text/html'])
                        ->withOptions(['cookies' => $this->jar])->timeout(30)->get($page); } catch (Throwable) {}
                }
                continue;
            }
            throw new RuntimeException('NSE returned HTTP ' . $response->status() . ' for ' . $url);
        }
        throw new RuntimeException('NSE refused the request for ' . $url);
    }
}
