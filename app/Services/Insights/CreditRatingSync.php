<?php
namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CompanyFinancialStatement;
use App\Models\CreditRating;
use App\Models\CreditRatingSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fetches rating rationales from the agencies' public sites and stores them as structured rating actions:
 *  - ICRA publishes every rationale as a PDF under a sequential id; new ids are read daily (and older ones backfilled).
 *  - CARE answers per-company lookups (current instrument ratings, lenders, press releases by year).
 * Agency-reported annual figures also fill financial years a company has no statement for.
 */
class CreditRatingSync
{
    public const ICRA = 'https://www.icra.in';
    public const CARE = 'https://www.careratings.com';
    private const AGENT = 'Mozilla/5.0 (compatible; CareerPortalInsights/1.0; company credit research)';
    private const PAUSE_MS = 400;

    public function __construct(private PdfText $pdf, private RatingRationaleParser $parser) {}

    // ---- ICRA ----

    /** Highest rationale id on ICRA's home page (its "Rating Rationales" list shows the newest releases). */
    public function icraLatestId(): ?int
    {
        $html = $this->get(self::ICRA . '/')?->body() ?? '';
        preg_match_all('/ShowRationaleReport\?[Ii]d=(\d+)/', $html, $m);
        return $m[1] ? max(array_map('intval', $m[1])) : null;
    }

    /** New rationales since the last stored id (up to $limit), then $backfill older ids below the oldest stored one. */
    public function syncIcra(int $limit = 250, int $backfill = 0, ?callable $log = null): array
    {
        $stats = ['new' => 0, 'backfilled' => 0, 'empty' => 0, 'failed' => 0];
        $latest = $this->icraLatestId();
        if (!$latest) { $log && $log('ICRA latest rationale id not found.'); return $stats; }
        // Failed downloads/parses are retried on the next run.
        $ids = CreditRating::where('agency', 'ICRA')->where('parse_status', '!=', 'failed')->pluck('source_key')->map(fn ($k) => (int) $k);
        $known = $ids->flip();
        // The newest $limit ids: new releases plus any gaps (earlier failures) inside that window.
        for ($id = max(1, $latest - $limit + 1); $id <= $latest; $id++) {
            if (isset($known[$id])) continue;
            $result = $this->icraOne($id);
            $stats[$result === 'parsed' ? 'new' : $result]++;
        }
        $oldest = CreditRating::where('agency', 'ICRA')->min(DB::raw('CAST(source_key AS UNSIGNED)')) ?: $latest;
        for ($id = (int) $oldest - 1, $done = 0; $done < $backfill && $id > 0; $id--, $done++) {
            if (isset($known[$id])) continue;
            $result = $this->icraOne($id);
            $stats[$result === 'parsed' ? 'backfilled' : $result]++;
        }
        $log && $log("ICRA: latest id $latest; " . json_encode($stats));
        return $stats;
    }

    public function icraOne(int $id): string
    {
        $url = self::ICRA . '/Rating/GetRationalReportFilePdf?Id=' . $id;
        $response = $this->get($url, 90);
        usleep(self::PAUSE_MS * 1000);
        if (!$response) return 'failed';
        return $this->ingest('ICRA', (string) $id, $url, $response->body(), self::ICRA . '/Rationale/ShowRationaleReport?Id=' . $id);
    }

    // ---- CARE ----

    /** CARE company lookup: current ratings, lenders and every press release not stored yet. */
    public function syncCare(Company $company, ?callable $log = null): ?CreditRatingSource
    {
        $source = CreditRatingSource::firstOrNew(['company_id' => $company->id, 'agency' => 'CARE']);
        try {
            if (!$source->agency_company_id) {
                $query = trim(preg_replace('/\b(private|pvt|limited|ltd)\b\.?/i', '', $company->name)) ?: $company->name;
                $found = $this->get(self::CARE . '/header/searchlist', 40, ['cinput' => mb_substr($query, 0, 60)])?->json('data') ?? [];
                $key = Company::nameKey($company->name);
                $match = collect($found)->first(fn ($r) => Company::nameKey($r['CompanyName'] ?? '') === $key);
                if ($match) { $source->agency_company_id = $match['CompanyID']; $source->agency_name = trim($match['CompanyName']); }
            }
            if ($source->agency_company_id) {
                $id = $source->agency_company_id;
                $current = $this->get(self::CARE . '/getSearchprintrating', 40, ['companyName' => $id])?->json('data.0.CompanyInstrument') ?? [];
                $source->current = collect($current)->map(fn ($r) => [
                    'instrument' => trim((string) ($r['Instrument'] ?? '')), 'rating' => trim((string) ($r['Rating'] ?? '')),
                    // CARE reports rated amounts in Rs. million.
                    'amount_crore' => is_numeric($r['RatingAmount'] ?? null) ? round((float) $r['RatingAmount'] / 10, 2) : null,
                ])->filter(fn ($r) => $r['instrument'] !== '')->values()->all();
                $source->lenders = collect($this->get(self::CARE . '/getSearchBankListed', 40, ['companyName' => $id])?->json('data.0.getBankListed') ?? [])
                    ->map(fn ($r) => array_filter(array_map(fn ($v) => is_scalar($v) ? trim((string) $v) : null, (array) $r)))->take(40)->values()->all();
                $this->carePressReleases($company, $id);
            }
            $source->checked_at = now();
            $source->save();
        } catch (Throwable $e) {
            report($e);
            $log && $log('CARE lookup failed for ' . $company->name . ': ' . $e->getMessage());
            return null;
        }
        return $source;
    }

    private function carePressReleases(Company $company, string $careId): void
    {
        $years = collect($this->get(self::CARE . '/pryear', 40, ['companyName' => $careId])?->json('data') ?? [])
            ->map(fn ($r) => (int) ($r['PublishedDate'] ?? $r['Year'] ?? 0))->filter()->unique()->sortDesc()->values();
        // The year list can lag; always include the current and previous two years.
        $years = $years->merge([(int) date('Y'), (int) date('Y') - 1, (int) date('Y') - 2])->unique()->sortDesc()->take(8);
        $latestStored = CreditRating::where('company_id', $company->id)->where('agency', 'CARE')->max('published_on');
        foreach ($years as $year) {
            if ($latestStored && $year < (int) substr($latestStored, 0, 4)) break;
            $docs = $this->get(self::CARE . '/getSearchprdocument', 40, ['companyName' => $careId, 'YearID' => $year])?->json('data.0.PRDocument') ?? [];
            foreach ($docs as $doc) {
                $key = (string) ($doc['CompRRPRID'] ?? '');
                $file = (string) ($doc['FileURL'] ?? '');
                if ($key === '' || $file === '' || CreditRating::where('agency', 'CARE')->where('source_key', $key)->where('parse_status', '!=', 'failed')->exists()) continue;
                $url = self::CARE . '/upload/CompanyFiles/PR/' . rawurlencode($file);
                $response = $this->get($url, 90);
                usleep(self::PAUSE_MS * 1000);
                if ($response) $this->ingest('CARE', $key, $url, $response->body(), $url, $company);
            }
            usleep(self::PAUSE_MS * 1000);
        }
    }

    /** Re-reads stored rationales from their source PDFs (after parser improvements). */
    public function reparse(?int $companyId = null, ?callable $log = null, int $batch = 0, bool $missingOnly = false): int
    {
        $count = 0;
        // Batched mode reads only reports parsed before funding events existed, newest first, a few hundred per night.
        CreditRating::whereIn('parse_status', ['parsed', 'unreadable'])->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($missingOnly, fn ($q) => $q->where('parse_status', 'parsed')->whereNull('funding_events'))
            ->orderByDesc('published_on')->orderBy('id')->limit($batch > 0 ? $batch : PHP_INT_MAX)->get()->chunk(100)->each(function ($rows) use (&$count) {
                foreach ($rows as $r) {
                    $url = $r->agency === 'ICRA' ? self::ICRA . '/Rating/GetRationalReportFilePdf?Id=' . $r->source_key : $r->source_url;
                    if (!$url || !($response = $this->get($url, 90))) continue;
                    $this->ingest($r->agency, $r->source_key, $url, $response->body(), $r->source_url, $r->company);
                    $count++;
                    usleep(self::PAUSE_MS * 1000);
                }
            });
        $log && $log("Re-read $count rationales.");
        return $count;
    }

    // ---- shared ----

    /** Parses one rationale PDF and stores it; returns parsed / empty / failed. */
    public function ingest(string $agency, string $key, string $url, string $body, ?string $link = null, ?Company $company = null): string
    {
        $base = ['source_url' => $link ?? $url];
        try {
            $text = str_starts_with(ltrim($body), '%PDF') ? $this->pdf->extract($body) : '';
            if (mb_strlen(trim($text)) < 300) {
                CreditRating::updateOrCreate(['agency' => $agency, 'source_key' => $key], $base + ['entity_name' => '', 'name_key' => '', 'parse_status' => 'empty']);
                return 'empty';
            }
            $data = $this->parser->parse($text);
            $entity = $data['entity_name'] ?: $company?->name;
            if (!$entity) {
                CreditRating::updateOrCreate(['agency' => $agency, 'source_key' => $key], $base + ['entity_name' => '', 'name_key' => '', 'parse_status' => 'unreadable']);
                return 'empty';
            }
            $nameKey = Company::nameKey($entity);
            $company ??= $this->match($nameKey);
            $rating = CreditRating::updateOrCreate(['agency' => $agency, 'source_key' => $key], $base + [
                'company_id' => $company?->id, 'entity_name' => mb_substr($entity, 0, 255), 'name_key' => $nameKey,
                'title' => $data['title'], 'published_on' => $data['published_on'], 'action' => $data['action'],
                'rating_long' => $data['rating_long'], 'rating_short' => $data['rating_short'], 'outlook' => $data['outlook'],
                'previous_rating_long' => $data['previous_rating_long'], 'score' => $data['score'], 'total_rated_crore' => $data['total_rated_crore'],
                'liquidity' => $data['liquidity'], 'withdrawn' => $data['withdrawn'], 'non_cooperating' => $data['non_cooperating'],
                'instruments' => $data['instruments'], 'debt_instruments' => $data['debt_instruments'], 'financials' => $data['financials'],
                'strengths' => $data['strengths'], 'challenges' => $data['challenges'], 'sensitivities' => $data['sensitivities'],
                'about' => $data['about'], 'rationale' => $data['rationale'] ?? null, 'liquidity_text' => $data['liquidity_text'] ?? null,
                'funding_events' => $data['funding_events'] ?? null, 'parse_status' => 'parsed',
            ]);
            if ($company) $this->fillFinancials($company, $rating);
            return 'parsed';
        } catch (Throwable $e) {
            report($e);
            CreditRating::updateOrCreate(['agency' => $agency, 'source_key' => $key], $base + ['entity_name' => '', 'name_key' => '', 'parse_status' => 'failed']);
            return 'failed';
        }
    }

    /** Company by normalised name; when several share it, the one with financials or a registry record. */
    public function match(string $nameKey): ?Company
    {
        if ($nameKey === '' || strlen($nameKey) < 3) return null;
        return Company::where('name_key', $nameKey)->withCount('financialStatements')->orderByDesc('financial_statements_count')->orderBy('id')->first();
    }

    /** Links stored rationales to companies added since (same normalised name). */
    public function relink(): int
    {
        $linked = 0;
        CreditRating::whereNull('company_id')->where('parse_status', 'parsed')->where('name_key', '!=', '')->select('name_key')->distinct()
            ->orderBy('name_key')->chunk(500, function ($keys) use (&$linked) {
                foreach ($keys as $row) {
                    if (!$company = $this->match($row->name_key)) continue;
                    $linked += CreditRating::whereNull('company_id')->where('name_key', $row->name_key)->update(['company_id' => $company->id]);
                    CreditRating::where('company_id', $company->id)->where('parse_status', 'parsed')->get()->each(fn ($r) => $this->fillFinancials($company, $r));
                }
            });
        return $linked;
    }

    /**
     * Agency-reported annual figures for years without a statement (Rs. crore → rupees). Debt and finance cost are derived:
     * debt = (total debt / OPBDITA) × OPBDITA, finance cost = OPBDITA ÷ interest coverage, OPBDITA = margin × operating income.
     */
    public function fillFinancials(Company $company, CreditRating $rating): void
    {
        $fin = $rating->financials;
        if (!$fin || empty($fin['periods'])) return;
        $have = CompanyFinancialStatement::where('company_id', $company->id)->pluck('financial_year')->flip();
        $metric = fn ($name, $i) => collect($fin['rows'])->firstWhere('metric', $name)['values'][$i] ?? null;
        $seen = [];
        foreach ($fin['periods'] as $i => $period) {
            $fy = $period['fy'] ?? null;
            if (!$fy || ($period['partial'] ?? false) || isset($have[$fy]) || isset($seen[$fy])) continue;
            $seen[$fy] = true; // the first column set (standalone) wins when a table repeats years for consolidated figures
            $revenue = $metric('revenue', $i) ?? $metric('total_income', $i);
            if ($revenue === null && $metric('pat', $i) === null) continue;
            $ebitda = $metric('ebitda', $i) ?? (($m = $metric('ebitda_margin', $i)) !== null && $revenue !== null ? $revenue * $m / 100 : null);
            $debt = ($x = $metric('debt_to_ebitda', $i)) !== null && $ebitda !== null && $ebitda > 0 ? $x * $ebitda : null;
            $interest = ($c = $metric('interest_coverage', $i)) !== null && $c > 0 && $ebitda !== null && $ebitda > 0 ? $ebitda / $c : null;
            $crore = fn ($v) => $v === null ? null : round($v * 1e7, 2);
            CompanyFinancialStatement::create([
                'company_id' => $company->id, 'financial_year' => $fy, 'period_end' => (substr($fy, 0, 4) + 1) . '-03-31', 'is_consolidated' => false,
                'revenue' => $crore($revenue), 'total_income' => $crore($metric('total_income', $i)), 'ebitda' => $crore($ebitda),
                'profit_after_tax' => $crore($metric('pat', $i)), 'total_assets' => $crore($metric('total_assets', $i)),
                'total_debt' => $crore($debt), 'finance_cost' => $crore($interest), 'source' => $rating->agency . ' rationale',
            ]);
            $have[$fy] = true;
        }
    }

    private function get(string $url, int $timeout = 40, array $query = []): ?\Illuminate\Http\Client\Response
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::AGENT, 'Accept' => '*/*', 'X-Requested-With' => 'XMLHttpRequest'])
                ->timeout($timeout)->retry(2, 2000, throw: false)->get($url, $query ?: null); // an empty query array would drop the URL's own query string
            return $response->successful() ? $response : null;
        } catch (Throwable $e) {
            Log::warning('Credit rating fetch failed: ' . $url . ' ' . $e->getMessage());
            return null;
        }
    }
}
