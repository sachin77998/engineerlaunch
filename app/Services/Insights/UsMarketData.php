<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\UsListedCompany;
use App\Services\Discovery\CompanyClassifier;
use App\Services\SectorCatalogImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * US-listed companies for Insights: every NYSE / Nasdaq / NYSE American stock with its last price and market cap
 * (Nasdaq's public stock screener), its SEC CIK, and annual results from SEC EDGAR XBRL "frames" — one request
 * returns one figure for every filer for one year, so a few dozen requests cover all companies.
 */
class UsMarketData
{
    private const SCREENER = 'https://api.nasdaq.com/api/screener/stocks?tableonly=true&download=true';
    private const SEC_TICKERS = 'https://www.sec.gov/files/company_tickers_exchange.json';
    private const FRAMES = 'https://data.sec.gov/api/xbrl/frames/%s/%s/%s/%s.json';
    private const PAUSE_MS = 150; // SEC allows 10 requests a second

    // Field => [concepts in order of preference (us-gaap, then IFRS for foreign filers), unit, instant?]
    public const FIELDS = [
        'revenue' => [['us-gaap/Revenues', 'us-gaap/RevenueFromContractWithCustomerExcludingAssessedTax', 'us-gaap/RevenueFromContractWithCustomerIncludingAssessedTax', 'us-gaap/SalesRevenueNet', 'ifrs-full/Revenue'], 'USD', false],
        'operating_income' => [['us-gaap/OperatingIncomeLoss', 'ifrs-full/ProfitLossFromOperatingActivities'], 'USD', false],
        'net_income' => [['us-gaap/NetIncomeLoss', 'us-gaap/ProfitLoss', 'ifrs-full/ProfitLoss'], 'USD', false],
        'operating_cash_flow' => [['us-gaap/NetCashProvidedByUsedInOperatingActivities', 'ifrs-full/CashFlowsFromUsedInOperatingActivities'], 'USD', false],
        'eps_diluted' => [['us-gaap/EarningsPerShareDiluted', 'us-gaap/EarningsPerShareBasic', 'ifrs-full/DilutedEarningsLossPerShare'], 'USD-per-shares', false],
        'total_assets' => [['us-gaap/Assets', 'ifrs-full/Assets'], 'USD', true],
        'total_liabilities' => [['us-gaap/Liabilities', 'ifrs-full/Liabilities'], 'USD', true],
        'equity' => [['us-gaap/StockholdersEquity', 'us-gaap/StockholdersEquityIncludingPortionAttributableToNoncontrollingInterest', 'ifrs-full/Equity'], 'USD', true],
        'cash' => [['us-gaap/CashAndCashEquivalentsAtCarryingValue', 'us-gaap/CashCashEquivalentsRestrictedCashAndRestrictedCashEquivalents', 'ifrs-full/CashAndCashEquivalents'], 'USD', true],
        'long_term_debt' => [['us-gaap/LongTermDebtNoncurrent', 'us-gaap/LongTermDebt', 'ifrs-full/NoncurrentPortionOfNoncurrentBorrowings'], 'USD', true],
    ];

    // Nasdaq screener sector => catalog sector and sub-category, when the name and industry say nothing more specific.
    private const SECTORS = [
        'Technology' => ['IT Services & Software', 'Product & Internet Companies'],
        'Health Care' => ['Pharmaceuticals & Healthcare', 'Biotechnology & Vaccines'],
        'Finance' => ['Banking & Financial Services', 'Fintech, Broking & Market Infrastructure'],
        'Real Estate' => ['Real Estate & Construction', 'Real Estate & Infrastructure'],
        'Energy' => ['Oil, Gas & Energy', 'Oil Refining & Marketing'],
        'Utilities' => ['Oil, Gas & Energy', 'Power Generation & Utilities'],
        'Basic Materials' => ['Steel, Metals & Pipes', 'Stainless Steel & Non-Ferrous Metals'],
        'Consumer Staples' => ['Food & Beverages', 'Packaged Foods & Confectionery'],
        'Consumer Discretionary' => ['FMCG - Personal & Home Care', 'Supermarkets & Retail Chains'],
        'Industrials' => ['Heavy Engineering & Capital Goods', 'Machine Tools & Industrial Machinery'],
        'Telecommunications' => ['Media, Telecom & Entertainment', 'Telecom'],
    ];

    public function __construct(private CompanyClassifier $classifier, private SectorCatalogImporter $catalog) {}

    /** Stocks from the screener, without warrants, units, rights and preferred shares. */
    public function screener(): array
    {
        $rows = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36', 'Accept' => 'application/json'])
            ->timeout(60)->retry(2, 3000)->get(self::SCREENER)->throw()->json('data.rows') ?? [];
        if (!$rows) throw new RuntimeException('The Nasdaq screener returned no stocks');
        return array_values(array_filter($rows, fn ($r) => !empty($r['symbol']) && !str_contains($r['symbol'], '^')
            && !preg_match('/\b(warrants?|units?|rights?|preferred|notes? due|debentures?)\b/i', (string) $r['name'])));
    }

    /** Ticker => [cik, exchange] from the SEC. */
    public function secTickers(): array
    {
        $json = $this->sec(self::SEC_TICKERS);
        $map = [];
        foreach ($json['data'] ?? [] as [$cik, $name, $ticker, $exchange]) $map[strtoupper((string) $ticker)] = [(int) $cik, $exchange];
        return $map;
    }

    /** Refreshes listings and quotes; links every listing to a company page (created, and filed under a sector, if new). */
    public function syncListings(bool $pricesOnly = false): array
    {
        $rows = $this->screener();
        $sec = $pricesOnly ? [] : $this->secTickers();
        $today = now('America/New_York')->toDateString();
        $now = now();
        $records = [];
        foreach ($rows as $r) {
            $ticker = str_replace('/', '-', strtoupper(trim($r['symbol']))); // class shares: BRK/B -> BRK-B, as the SEC writes them
            $quote = ['last_price' => $this->number($r['lastsale'] ?? null), 'change_pct' => $this->number($r['pctchange'] ?? null),
                'volume' => ($v = $this->number($r['volume'] ?? null)) === null ? null : (int) $v, 'market_cap' => $this->number($r['marketCap'] ?? null) ?: null,
                'price_date' => $today, 'updated_at' => $now];
            if ($pricesOnly) { $records[] = ['ticker' => $ticker, 'name' => $this->cleanName($r['name']), 'created_at' => $now] + $quote; continue; }
            [$cik, $exchange] = $sec[str_replace('.', '-', $ticker)] ?? [null, null];
            $records[] = ['ticker' => $ticker, 'cik' => $cik, 'name' => $this->cleanName($r['name']), 'exchange' => $exchange,
                'sector' => $r['sector'] ?: null, 'industry' => $r['industry'] ?: null, 'country' => $r['country'] ?: null,
                'ipo_year' => ctype_digit((string) ($r['ipoyear'] ?? '')) ? (int) $r['ipoyear'] : null, 'created_at' => $now] + $quote;
        }
        $update = $pricesOnly ? ['last_price', 'change_pct', 'volume', 'market_cap', 'price_date', 'updated_at']
            : ['cik', 'name', 'exchange', 'sector', 'industry', 'country', 'ipo_year', 'last_price', 'change_pct', 'volume', 'market_cap', 'price_date', 'updated_at'];
        foreach (array_chunk($records, 500) as $chunk) DB::table('us_listed_companies')->upsert($chunk, ['ticker'], $update);
        $linked = $pricesOnly ? 0 : $this->linkCompanies();
        return ['listings' => count($records), 'linked' => $linked];
    }

    /** One company page per issuer (share classes of one CIK share it). */
    public function linkCompanies(): int
    {
        $linked = 0;
        UsListedCompany::whereNull('company_id')->orderByDesc('market_cap')->get()->each(function (UsListedCompany $listing) use (&$linked) {
            $companyId = $listing->cik ? UsListedCompany::where('cik', $listing->cik)->whereNotNull('company_id')->value('company_id') : null;
            // A name match counts only within the same country: "Coca-Cola India" is not The Coca-Cola Company.
            $company = $companyId ? Company::find($companyId) : Company::where('name_key', Company::nameKey($listing->name))->get()
                ->first(fn (Company $c) => (in_array(self::country((string) $c->country), ['', 'global'], true) || self::country($c->country) === self::country($listing->country ?: 'United States'))
                    && (stripos($c->name, 'india') === false || stripos($listing->name, 'india') !== false));
            if ($company && !$company->categories()->where('company_categories.taxonomy', SectorCatalogImporter::TAXONOMY)->whereNotNull('parent_id')
                    ->whereNotIn('company_categories.name', ['Discovered Companies'])->exists()) {
                [$sector, $subsector] = $this->classify($listing);
                $this->catalog->attach($company, $sector, $subsector);
                if (!$company->industry || $company->industry === CompanyClassifier::FALLBACK[0]) $company->forceFill(['industry' => $sector, 'sector' => $subsector])->save();
            }
            if (!$company) {
                [$sector, $subsector] = $this->classify($listing);
                $base = Str::limit(Str::slug($listing->name), 200, '') ?: Str::slug($listing->ticker);
                $slug = $base;
                for ($n = 2; Company::where('slug', $slug)->exists(); $n++) $slug = $base . '-' . $n;
                $name = Company::where('name', $listing->name)->exists() ? $listing->name . ' (' . $listing->ticker . ')' : $listing->name;
                $company = Company::create(['name' => $name, 'slug' => $slug, 'country' => $listing->country ?: 'United States', 'industry' => $sector,
                    'sector' => $subsector, 'is_active' => true, 'sync_enabled' => false]);
                $this->catalog->attach($company, $sector, $subsector);
            }
            $listing->forceFill(['company_id' => $company->id])->save();
            $linked++;
        });
        return $linked;
    }

    /** @return array{0:string,1:string} */
    public function classify(UsListedCompany $listing): array
    {
        $match = $this->classifier->classify($listing->name, (string) $listing->industry);
        if ($this->classifier->isFallback($match)) $match = self::SECTORS[$listing->sector] ?? $match;
        // Bank rules are written for India; a US-listed bank belongs with the US banks.
        if ($match[0] === 'Banking & Financial Services' && in_array($match[1], ['Indian Banks', 'Public Sector & Government Banks'], true) && ($listing->country ?: 'United States') === 'United States')
            $match[1] = 'United States Banks';
        if ($match[1] === 'Indian IT Services' && self::country($listing->country ?: 'United States') !== 'india') $match[1] = 'Product & Internet Companies';
        return $match;
    }

    private static function country(string $country): string
    {
        $c = strtolower(trim($country));
        return in_array($c, ['usa', 'us', 'u.s.', 'united states of america'], true) ? 'united states' : $c;
    }

    /** Annual results for every listed CIK for the given calendar years, from SEC XBRL frames. @return int rows saved */
    public function importFinancials(array $years): int
    {
        $ciks = array_flip(UsListedCompany::whereNotNull('cik')->distinct()->pluck('cik')->all());
        $saved = 0;
        foreach ($years as $year) {
            $rows = [];
            foreach (self::FIELDS as $field => [$concepts, $unit, $instant]) {
                foreach ($concepts as $concept) {
                    [$taxonomy, $tag] = explode('/', $concept);
                    try {
                        $data = $this->sec(sprintf(self::FRAMES, $taxonomy, $tag, $unit, 'CY' . $year . ($instant ? 'Q4I' : '')))['data'] ?? [];
                    } catch (RuntimeException $e) {
                        continue; // 404: nobody reported this concept for that year
                    }
                    foreach ($data as $fact) {
                        $cik = (int) $fact['cik'];
                        if (!isset($ciks[$cik]) || isset($rows[$cik][$field])) continue;
                        $rows[$cik][$field] = $fact['val'];
                        if ($instant) $rows[$cik]['balance_sheet_date'] ??= $fact['end'];
                        else $rows[$cik]['period_end'] ??= $fact['end'];
                    }
                }
            }
            $now = now();
            $records = [];
            foreach ($rows as $cik => $values) {
                $records[] = array_merge(['cik' => $cik, 'fiscal_year' => $year, 'created_at' => $now, 'updated_at' => $now],
                    array_fill_keys(array_keys(self::FIELDS), null), ['period_end' => null, 'balance_sheet_date' => null], $values);
            }
            foreach (array_chunk($records, 500) as $chunk) {
                DB::table('us_company_financials')->upsert($chunk, ['cik', 'fiscal_year'], [...array_keys(self::FIELDS), 'period_end', 'balance_sheet_date', 'updated_at']);
            }
            $saved += count($records);
        }
        return $saved;
    }

    /** "$1.25T", "$342.1B", "$12.4M" */
    public static function money(?float $value): string
    {
        if ($value === null) return '—';
        $sign = $value < 0 ? '−' : '';
        $abs = abs($value);
        foreach ([1e12 => 'T', 1e9 => 'B', 1e6 => 'M', 1e3 => 'K'] as $size => $suffix) {
            if ($abs >= $size) return $sign . '$' . number_format($abs / $size, $abs / $size >= 100 ? 0 : 1) . $suffix;
        }
        return $sign . '$' . number_format($abs, 0);
    }

    private function sec(string $url): array
    {
        usleep(self::PAUSE_MS * 1000);
        $response = Http::withHeaders(['User-Agent' => config('services.sec.user_agent'), 'Accept-Encoding' => 'gzip, deflate'])
            ->timeout(120)->retry(2, 2000, fn ($e) => !($e instanceof \Illuminate\Http\Client\RequestException && $e->response->status() === 404), throw: false)->get($url);
        if (!$response->successful()) throw new RuntimeException('SEC returned HTTP ' . $response->status() . ' for ' . $url);
        return $response->json() ?? [];
    }

    private function number(mixed $value): ?float
    {
        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return $clean === '' || $clean === '-' || !is_numeric($clean) ? null : (float) $clean;
    }

    private function cleanName(string $name): string
    {
        $name = preg_split('/\s+(?:Class [A-Z]\b|Common Stock|Common Shares|Ordinary Shares|American Depositary|Depositary Shares|Sponsored ADR|ADR\b|Shares of Beneficial|Subordinate Voting)/i', $name)[0];
        $name = trim(rtrim(trim($name), ','));
        return preg_replace('/^(.+?)\s*\(The\)$/i', 'The $1', $name) ?: $name;
    }
}
