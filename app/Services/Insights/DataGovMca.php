<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CompanyRegistryDetail;
use App\Services\Discovery\CompanyClassifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * MCA "Company Master Data" from the data.gov.in API (DATAGOV_API_KEY): about 40 lakh registered companies with
 * CIN, name, status, class, category, capital, state, ROC, activity, address and registration date.
 * Filters are exact matches, so names are tried in their usual legal forms.
 */
class DataGovMca
{
    public const STATES = ['Andaman and Nicobar Islands', 'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chandigarh', 'Chhattisgarh',
        'Dadra and Nagar Haveli', 'Daman and Diu', 'Delhi', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jammu and Kashmir', 'Jharkhand', 'Karnataka',
        'Kerala', 'Ladakh', 'Lakshadweep', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Puducherry', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal'];

    public function configured(): bool
    {
        return filled(config('services.datagov.key'));
    }

    /** One page of records. @return array{total:int, records:array<int, array>} */
    public function page(array $filters = [], int $offset = 0, int $limit = 1000, array $ranges = []): array
    {
        if (!$this->configured()) throw new RuntimeException('Set DATAGOV_API_KEY (data.gov.in → My Account) to use MCA master data.');
        $query = ['api-key' => config('services.datagov.key'), 'format' => 'json', 'limit' => $limit, 'offset' => $offset];
        foreach ($filters as $field => $value) $query["filters[{$field}]"] = $value;
        // Ranges are [from, to): from inclusive, to exclusive.
        foreach ($ranges as $field => [$from, $to]) { $query["range[{$field}][gte]"] = $from; $query["range[{$field}][lt]"] = $to; }
        if ($ranges) $query['sort[corporate_identification_number]'] = 'asc';
        $resource = (array) config('services.datagov.company_resources');
        $response = Http::timeout(120)->retry(3, 5000, throw: false)->get('https://api.data.gov.in/resource/' . reset($resource), $query);
        $json = $response->json();
        if (!$response->successful() || !is_array($json)) throw new RuntimeException('data.gov.in returned HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 200));
        if (isset($json['error'])) throw new RuntimeException('data.gov.in: ' . $json['error']);
        return ['total' => (int) ($json['total'] ?? 0), 'records' => array_map([$this, 'normalise'], $json['records'] ?? [])];
    }

    /** Exact CIN lookup. */
    public function byCin(string $cin): ?array
    {
        return $this->page(['corporate_identification_number' => strtoupper($cin)], 0, 5)['records'][0] ?? null;
    }

    /** Exact-name lookup across the usual legal-name forms ("abc pvt ltd" → "ABC PRIVATE LIMITED"). */
    public function byName(string $name): array
    {
        $base = strtoupper(trim(preg_replace('/\s+/', ' ', $name)));
        $base = preg_replace(['/\bPVT\b\.?/', '/\bLTD\b\.?/', '/\bCO\b\.?/'], ['PRIVATE', 'LIMITED', 'COMPANY'], $base);
        $core = trim(preg_replace('/\s+(PRIVATE\s+LIMITED|LIMITED|LLP)$/', '', $base));
        $variants = array_unique([$base, $core . ' PRIVATE LIMITED', $core . ' LIMITED', $core . ' (INDIA) PRIVATE LIMITED', $core . ' INDIA PRIVATE LIMITED', $core . ' LLP']);
        $found = [];
        foreach ($variants as $variant) {
            foreach ($this->page(['company_name' => $variant], 0, 10)['records'] as $record) $found[$record['cin']] = $record;
        }
        return array_values($found);
    }

    /** Upserts records into mca_companies; returns how many were written. */
    public function store(array $records): int
    {
        $now = now();
        $rows = [];
        foreach ($records as $r) {
            if (!$r['cin'] || !$r['name']) continue;
            $rows[$r['cin']] = $r + ['created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            DB::table('mca_companies')->upsert($chunk, ['cin'], ['name', 'status', 'company_class', 'category', 'sub_category', 'authorised_capital', 'paid_up_capital', 'state', 'roc', 'activity', 'address', 'registered_on', 'updated_at']);
        }
        return count($rows);
    }

    /** The company page record for a CIN: existing company, or created from the register (stored row or live API). */
    public function companyFor(string $cin): ?Company
    {
        $cin = strtoupper($cin);
        $company = Company::where('registry_cin', $cin)->first() ?? CompanyRegistryDetail::where('cin', $cin)->first()?->company;
        $row = (array) (DB::table('mca_companies')->where('cin', $cin)->first() ?? []);
        if (!$row && $this->configured()) {
            try { if ($row = $this->byCin($cin)) $this->store([$row]); } catch (Throwable $e) { report($e); }
        }
        if (!$row) return $company;
        $company ??= Company::where('name_key', Company::nameKey($row['name']))->first();
        if (!$company) {
            [$sector, $subsector] = app(CompanyClassifier::class)->classify($row['name'], (string) $row['activity']);
            $base = Str::limit(Str::slug($row['name']), 200, '') ?: 'company';
            $slug = $base;
            for ($i = 2; Company::where('slug', $slug)->exists(); $i++) $slug = $base . '-' . $i;
            $company = Company::create(['name' => Str::title(Str::lower($row['name'])), 'slug' => $slug, 'country' => 'India', 'industry' => $sector, 'sector' => $subsector,
                'is_active' => true, 'sync_enabled' => false, 'registry_cin' => $cin, 'registry_status' => $row['status'], 'registered_state' => $row['state']]);
            app(\App\Services\SectorCatalogImporter::class)->attach($company, $sector, $subsector);
        }
        $registry = CompanyRegistryDetail::firstOrNew(['company_id' => $company->id]);
        if (!$registry->cin && !CompanyRegistryDetail::where('cin', $cin)->where('company_id', '!=', $company->id)->exists()) $registry->cin = $cin;
        foreach (['company_status' => 'status', 'company_class' => 'company_class', 'company_category' => 'category', 'company_subcategory' => 'sub_category',
                  'authorised_capital' => 'authorised_capital', 'paid_up_capital' => 'paid_up_capital', 'roc' => 'roc', 'registered_address' => 'address',
                  'incorporation_date' => 'registered_on'] as $field => $key) {
            if (($row[$key] ?? null) !== null && blank($registry->{$field})) $registry->{$field} = $row[$key];
        }
        $registry->listing_status ??= $cin[0] === 'L' ? 'Listed' : 'Unlisted';
        $registry->source = $registry->source ?: 'MCA Company Master Data (data.gov.in)';
        $registry->save();
        $company->forceFill(array_filter(['registry_cin' => $company->registry_cin ?: $cin, 'registry_status' => $company->registry_status ?: $row['status'],
            'registered_state' => $company->registered_state ?: $row['state']]))->save();
        return $company;
    }

    private function normalise(array $r): array
    {
        $v = fn ($key) => isset($r[$key]) && !in_array(strtoupper(trim((string) $r[$key])), ['', 'NA', 'N/A', 'NULL', '-'], true) ? trim((string) $r[$key]) : null;
        $money = fn ($key) => ($x = $v($key)) !== null && is_numeric($x) ? (float) $x : null;
        $date = null;
        if ($d = $v('date_of_registration')) {
            try {
                $parsed = Carbon::parse($d);
                // The register stores two-digit years, so 1944 comes back as 2044.
                if ($parsed->isFuture()) $parsed->subYears(100);
                $date = $parsed->toDateString();
            } catch (Throwable) {}
        }
        return [
            'cin' => strtoupper((string) $v('corporate_identification_number')),
            'name' => Str::limit((string) $v('company_name'), 250, ''),
            'status' => Str::limit((string) $v('company_status'), 60, '') ?: null,
            'company_class' => Str::limit((string) $v('company_class'), 40, '') ?: null,
            'category' => Str::limit((string) $v('company_category'), 80, '') ?: null,
            'sub_category' => Str::limit((string) $v('sub_category'), 80, '') ?: null,
            'authorised_capital' => $money('authorized_capital'),
            'paid_up_capital' => $money('paidup_capital'),
            'state' => Str::limit((string) $v('registered_state'), 60, '') ?: null,
            'roc' => Str::limit((string) $v('registrar_of_companies'), 60, '') ?: null,
            'activity' => Str::limit((string) $v('principal_business_activity'), 250, '') ?: null,
            'address' => $v('registered_office_address'),
            'registered_on' => $date,
        ];
    }
}
