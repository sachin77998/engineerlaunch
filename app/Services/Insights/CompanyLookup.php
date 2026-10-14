<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CompanyFinancialStatement;
use App\Models\CompanyRegistryDetail;
use App\Services\Discovery\CompanyClassifier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Finds companies that are not in the database yet and fills in their details automatically, from free sources:
 * - GLEIF (legal entity identifiers): exact CIN or name → legal name, CIN, LEI, status, registered address.
 * - data.gov.in MCA Company Master Data API (free key in DATAGOV_API_KEY): CIN/name → status, class, category,
 *   capital, ROC, incorporation date, address for every registered company.
 * - Wikidata: employees, website, revenue, profit and total assets by year for larger companies.
 * Financial statements of smaller companies are only in their MCA filings (AOC-4 / XBRL), which are uploaded,
 * or come from InstaFinancials when its key is set.
 */
class CompanyLookup
{
    private const GLEIF = 'https://api.gleif.org/api/v1/lei-records';

    public function __construct(private CompanyClassifier $classifier) {}

    /** Companies found online for a CIN or name, created locally with their registry details. */
    public function discover(string $query): Collection
    {
        $id = app(CompanyInsights::class)->identify($query);
        $found = collect();
        $rows = $this->gleif($id['type'] === 'cin' ? ['filter[entity.registeredAs]' => $id['value']] : ['filter[fulltext]' => $query, 'filter[entity.legalAddress.country]' => 'IN', 'page[size]' => 10]);
        if (!$rows && $id['type'] === 'name') {
            // GLEIF full-text often misses multi-word phrases: search the most distinctive word, keep names containing every word.
            $words = collect(preg_split('/[^a-z0-9&]+/', mb_strtolower($query)))->reject(fn ($w) => strlen($w) < 3 || in_array($w, ['private', 'pvt', 'limited', 'ltd', 'india', 'the', 'and', 'company'], true))->values();
            if ($words->isNotEmpty()) {
                $rows = collect($this->gleif(['filter[fulltext]' => $words->sortByDesc(fn ($w) => strlen($w))->first(), 'filter[entity.legalAddress.country]' => 'IN', 'page[size]' => 50]))
                    ->filter(fn ($row) => $words->every(fn ($w) => str_contains(mb_strtolower($row['name']), $w)))->take(10)->values()->all();
            }
        }
        foreach ($rows as $row) $found->push($this->save($row));
        try {
            foreach ($this->dataGov($id['type'] === 'cin' ? $id['value'] : null, $id['type'] === 'name' ? $query : null) as $row) $found->push($this->save($row));
        } catch (Throwable $e) { report($e); }
        return $found->filter()->unique('id')->values();
    }

    /** Fills a company's registry details and public figures from the online sources (runs at most monthly). */
    public function enrich(Company $company, bool $force = false): void
    {
        $registry = CompanyRegistryDetail::firstOrNew(['company_id' => $company->id]);
        if (!$force && $registry->enriched_at && $registry->enriched_at->gt(now()->subDays(30))) return;
        $cin = $registry->cin ?? $company->registry_cin;
        try {
            foreach ($this->gleif($cin ? ['filter[entity.registeredAs]' => $cin] : ['filter[entity.legalName]' => mb_strtoupper($company->name), 'filter[entity.legalAddress.country]' => 'IN']) as $row) { $this->fill($company, $registry, $row); break; }
        } catch (Throwable $e) { report($e); }
        try {
            foreach ($this->dataGov($cin, $cin ? null : $company->name) as $row) { $this->fill($company, $registry, $row); break; }
        } catch (Throwable $e) { report($e); }
        try { $this->wikidata($company, $registry); } catch (Throwable $e) { report($e); }
        // Paid MCA providers (Sandbox, Surepass…) give the official master data, directors and charges when configured.
        $cin = $registry->cin ?? $company->registry_cin;
        if ($cin && config('services.insights.auto') && app(DataProviders::class)->enabled()) {
            try { app(ProviderImporter::class)->fetchCin($cin, $company); $registry->refresh(); } catch (Throwable $e) { report($e); }
        }
        $registry->enriched_at = now();
        $registry->source = $registry->source ?: 'Public registries';
        $registry->save();
        if ($company->isDirty()) $company->save();
    }

    /** @return array<int, array> normalised rows */
    private function gleif(array $filter): array
    {
        $response = Http::withHeaders(['Accept' => 'application/vnd.api+json', 'User-Agent' => config('discovery.user_agent', 'EngineerLaunch/1.0')])->timeout(20)->get(self::GLEIF, $filter);
        if (!$response->successful()) return [];
        return collect($response->json('data', []))->map(function ($record) {
            $entity = $record['attributes']['entity'] ?? [];
            $address = $entity['legalAddress'] ?? [];
            $cin = strtoupper((string) ($entity['registeredAs'] ?? ''));
            return [
                'name' => $entity['legalName']['name'] ?? null,
                'cin' => preg_match(CompanyInsights::CIN, $cin) ? $cin : null,
                'lei' => $record['attributes']['lei'] ?? null,
                'status' => ($entity['status'] ?? null) === 'ACTIVE' ? 'Active' : (ucfirst(strtolower((string) ($entity['status'] ?? ''))) ?: null),
                'address' => trim(implode(', ', array_filter(array_merge($address['addressLines'] ?? [], [$address['city'] ?? null, $address['region'] ?? null, $address['postalCode'] ?? null])))),
                'city' => isset($address['city']) ? ucwords(strtolower($address['city'])) : null,
                'pin_code' => $address['postalCode'] ?? null,
                'source' => 'GLEIF',
            ];
        })->filter(fn ($row) => $row['name'] && $row['cin'])->values()->all();   // Indian companies only (they carry a CIN)
    }

    /** MCA Company Master Data from data.gov.in (exact CIN, or exact registered name). */
    private function dataGov(?string $cin, ?string $name): array
    {
        $mca = app(DataGovMca::class);
        if (!$mca->configured() || (!$cin && !$name)) return [];
        // Stored register first, then the live API (names tried in their usual legal forms).
        $stored = $cin ? (array) (\Illuminate\Support\Facades\DB::table('mca_companies')->where('cin', $cin)->first() ?? []) : [];
        if ($stored) {
            $records = [$stored];
        } else {
            $records = $cin ? array_filter([$mca->byCin($cin)]) : $mca->byName($name);
            $mca->store($records);
        }
        $rows = [];
        foreach ($records as $r) {
            if (!preg_match(CompanyInsights::CIN, (string) $r['cin'])) continue;
            $address = $r['address'] ?? null;
            $rows[] = [
                'name' => $r['name'], 'cin' => $r['cin'], 'lei' => null, 'status' => $r['status'], 'class' => $r['company_class'],
                'category' => $r['category'], 'subcategory' => $r['sub_category'], 'roc' => $r['roc'], 'incorporated' => $r['registered_on'],
                'authorised' => $r['authorised_capital'], 'paid_up' => $r['paid_up_capital'], 'nic' => null, 'email' => null,
                'address' => $address, 'pin_code' => $address && preg_match('/\b([1-9]\d{5})\b/', $address, $m) ? $m[1] : null, 'city' => null,
                'state' => $r['state'], 'source' => 'MCA Company Master Data (data.gov.in)',
            ];
        }
        return $rows;
    }

    /** Creates or updates a company from a normalised registry row. */
    private function save(array $row): ?Company
    {
        if (!$row['name']) return null;
        $company = ($row['cin'] ? Company::where('registry_cin', $row['cin'])->first() ?? CompanyRegistryDetail::where('cin', $row['cin'])->first()?->company : null)
            ?? Company::where('name_key', Company::nameKey($row['name']))->first();
        if (!$company) {
            [$sector, $subsector] = $this->classifier->classify($row['name'], (string) ($row['nic'] ?? ''));
            $base = Str::limit(Str::slug($row['name']), 200, '') ?: 'company';
            $slug = $base;
            for ($i = 2; Company::where('slug', $slug)->exists(); $i++) $slug = $base . '-' . $i;
            $company = Company::create(['name' => Str::title(Str::lower($row['name'])), 'slug' => $slug, 'country' => 'India', 'industry' => $sector, 'sector' => $subsector,
                'is_active' => true, 'sync_enabled' => false, 'registry_cin' => $row['cin'], 'registered_state' => $row['state'] ?? null]);
            app(\App\Services\SectorCatalogImporter::class)->attach($company, $sector, $subsector);
        }
        $registry = CompanyRegistryDetail::firstOrNew(['company_id' => $company->id]);
        $this->fill($company, $registry, $row);
        $registry->source = $registry->source ?: $row['source'];
        $registry->save();
        if ($company->isDirty()) $company->save();
        return $company;
    }

    private function fill(Company $company, CompanyRegistryDetail $registry, array $row): void
    {
        $number = fn ($v) => $v !== null && is_numeric($clean = preg_replace('/[^\d.]/', '', (string) $v)) && $clean !== '' ? (float) $clean : null;
        $date = function ($v) { if (!$v) return null; foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'm/d/Y'] as $f) { try { return Carbon::createFromFormat($f, $v)->toDateString(); } catch (Throwable) {} } return null; };
        if ($row['cin'] && !$registry->cin && !CompanyRegistryDetail::where('cin', $row['cin'])->where('company_id', '!=', $company->id)->exists()) $registry->cin = $row['cin'];
        if ($row['cin'] && !$company->registry_cin) $company->registry_cin = $row['cin'];
        $values = [
            'lei' => $row['lei'] ?? null, 'company_status' => $row['status'] ?? null, 'company_class' => $row['class'] ?? null, 'company_category' => $row['category'] ?? null,
            'company_subcategory' => $row['subcategory'] ?? null, 'roc' => $row['roc'] ?? null, 'incorporation_date' => $date($row['incorporated'] ?? null),
            'authorised_capital' => $number($row['authorised'] ?? null), 'paid_up_capital' => $number($row['paid_up'] ?? null), 'nic_code' => isset($row['nic']) ? substr(preg_replace('/\D/', '', $row['nic']), 0, 10) ?: null : null,
            'email' => isset($row['email']) && filter_var(strtolower($row['email']), FILTER_VALIDATE_EMAIL) ? strtolower($row['email']) : null,
            'registered_address' => $row['address'] ?? null, 'city' => $row['city'] ?? null, 'pin_code' => $row['pin_code'] ?? null,
        ];
        foreach ($values as $field => $value) if ($value !== null && $value !== '' && blank($registry->{$field})) $registry->{$field} = $value;
        if (!$registry->listing_status && $registry->cin) $registry->listing_status = $registry->cin[0] === 'L' ? 'Listed' : 'Unlisted';
        if (!empty($row['status']) && !$company->registry_status) $company->registry_status = $row['status'];
        if (!$company->headquarters && !empty($row['city'])) $company->headquarters = $row['city'];
    }

    /** Employees, website, inception and yearly revenue / profit / assets (INR only) from Wikidata. */
    private function wikidata(Company $company, CompanyRegistryDetail $registry): void
    {
        $qid = $registry->wikidata_id;
        if (!$qid) {
            $search = Http::withHeaders(['User-Agent' => config('discovery.user_agent', 'EngineerLaunch/1.0')])->timeout(15)
                ->get('https://www.wikidata.org/w/api.php', ['action' => 'wbsearchentities', 'search' => preg_replace('/\b(private|pvt|limited|ltd)\b\.?/i', '', $company->name), 'language' => 'en', 'type' => 'item', 'limit' => 5, 'format' => 'json']);
            $candidates = collect($search->json('search', []))->pluck('id')->all();
            if (!$candidates) return;
            $values = 'wd:' . implode(' wd:', $candidates);
            $pick = $this->sparql("SELECT ?i WHERE { VALUES ?i { {$values} } ?i wdt:P17 wd:Q668. ?i wdt:P31/wdt:P279* wd:Q4830453. } LIMIT 1");
            $qid = isset($pick[0]['i']) ? basename($pick[0]['i']['value']) : null;
            if (!$qid) return;
            $registry->wikidata_id = $qid;
        }
        $rows = $this->sparql("SELECT ?prop ?amount ?unit ?time ?website ?inception WHERE {
            OPTIONAL { wd:{$qid} wdt:P856 ?website } OPTIONAL { wd:{$qid} wdt:P571 ?inception }
            OPTIONAL { VALUES ?prop { 'revenue' 'profit' 'assets' 'employees' 'equity' }
              VALUES (?prop ?p ?psv ?pq) { ('revenue' p:P2139 psv:P2139 pq:P585) ('profit' p:P2295 psv:P2295 pq:P585) ('assets' p:P2403 psv:P2403 pq:P585) ('employees' p:P1128 psv:P1128 pq:P585) ('equity' p:P2138 psv:P2138 pq:P585) }
              wd:{$qid} ?p ?st. ?st ?psv ?v. ?v wikibase:quantityAmount ?amount. OPTIONAL { ?v wikibase:quantityUnit ?unit } OPTIONAL { ?st ?pq ?time } } }");
        $field = ['revenue' => 'revenue', 'profit' => 'profit_after_tax', 'assets' => 'total_assets', 'equity' => 'net_worth'];
        foreach ($rows as $r) {
            if (!$company->website && isset($r['website'])) $company->website = rtrim($r['website']['value'], '/');
            if (!$registry->incorporation_date && isset($r['inception'])) { try { $registry->incorporation_date = Carbon::parse($r['inception']['value'])->toDateString(); } catch (Throwable) {} }
            if (!isset($r['prop'], $r['amount'])) continue;
            $prop = $r['prop']['value'];
            if ($prop === 'employees') { $registry->employees = max((int) $registry->employees, (int) $r['amount']['value']); continue; }
            // Only rupee amounts with a date are stored; other currencies would need an exchange rate.
            if (!isset($r['time']) || basename($r['unit']['value'] ?? '') !== 'Q80031') continue;
            $fy = FilingParser::financialYear(Carbon::parse($r['time']['value']));
            $statement = CompanyFinancialStatement::firstOrNew(['company_id' => $company->id, 'financial_year' => $fy, 'is_consolidated' => false]);
            if ($statement->{$field[$prop]} === null) { $statement->{$field[$prop]} = (float) $r['amount']['value']; $statement->source ??= 'Wikidata'; $statement->save(); }
        }
    }

    private function sparql(string $query): array
    {
        $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent', 'EngineerLaunch/1.0'), 'Accept' => 'application/sparql-results+json'])
            ->timeout(30)->get(config('discovery.wikidata_url', 'https://query.wikidata.org/sparql'), ['query' => $query]);
        return $response->successful() ? $response->json('results.bindings', []) : [];
    }
}
