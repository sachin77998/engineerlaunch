<?php

namespace App\Services\Insights;

use App\Services\Discovery\RegistryWriter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Imports the MCA "Company Master Data" CSVs published state-wise on data.gov.in (open government data):
 * CIN, name, status, class, category, registration date, capital, NIC activity, address, ROC, email and the
 * latest annual return / financial statement years. New companies are added; existing ones get registry details.
 */
class MasterDataImporter
{
    // data.gov.in has used several header spellings over the years.
    private const COLUMNS = [
        // data.gov.in CSVs and MCA's monthly "companies registered during the month" Excel files.
        'cin' => ['corporate_identification_number', 'cin', 'corporate_identity_number', 'company_cin', 'cin_fcrn'],
        'name' => ['company_name', 'company_llp_name', 'name_of_the_company', 'name'],
        'status' => ['company_status', 'company_status_for_efiling', 'status'],
        'class' => ['company_class', 'class_of_company', 'class'],
        'category' => ['company_category', 'category'],
        'subcategory' => ['company_sub_category', 'company_subcategory', 'sub_category'],
        'registered' => ['date_of_registration', 'company_registration_date', 'date_of_incorporation', 'date_of_incorporation_registration', 'date_of_incorporation_or_registration', 'doi'],
        'state' => ['registered_state', 'company_state', 'state'],
        'authorised' => ['authorized_cap', 'authorised_cap', 'authorized_capital', 'authorised_capital', 'authorized_capital_inr', 'authorised_capital_inr', 'authorized_capital_rs', 'authorised_capital_rs', 'authorized_capital_in_rs', 'authorised_capital_in_rs'],
        'paid_up' => ['paidup_capital', 'paid_up_capital', 'paidup_cap', 'paid_up_capital_inr', 'paid_up_capital_rs', 'paid_up_capital_in_rs'],
        'nic' => ['industrial_class', 'nic_code', 'activity_code', 'company_industrial_classification', 'principal_business_activity_code'],
        'activity' => ['principal_business_activity_as_per_cin', 'principal_business_activity', 'activity_description', 'description_of_main_division'],
        'address' => ['registered_office_address', 'company_address', 'registered_address', 'address'],
        'roc' => ['registrar_of_companies', 'roc', 'roc_code', 'company_roc', 'company_roc_code', 'roc_name'],
        'email' => ['email_addr', 'email', 'email_id'],
        'annual_return' => ['latest_year_annual_return', 'latest_annual_return'],
        'financials' => ['latest_year_financial_statement', 'latest_financial_statement'],
    ];

    public function __construct(private RegistryWriter $writer) {}

    /**
     * CSV or Excel (.xlsx). The header is found anywhere in the file (MCA files start with title rows, and each
     * worksheet — companies, LLPs — has its own header), so every sheet is read with its own column positions.
     * @return array{rows:int,created:int,detailed:int}
     */
    public function import(string $path, ?string $originalName = null): array
    {
        $isExcel = str_ends_with(strtolower($originalName ?? $path), '.xlsx') || $this->isZip($path);
        $rows = $isExcel ? app(XlsxReader::class)->rows($path) : $this->csvRows($path);
        $index = [];
        $totals = ['rows' => 0, 'created' => 0, 'detailed' => 0];
        $batch = [];
        foreach ($rows as $row) {
            if ($found = $this->header($row)) { $index = $found; continue; }
            if (!isset($index['cin'], $index['name'])) continue;
            $get = fn ($field) => isset($index[$field]) ? trim((string) ($row[$index[$field]] ?? '')) : '';
            $cin = strtoupper($get('cin'));
            if (!preg_match(CompanyInsights::CIN, $cin) || $get('name') === '') continue;   // LLPINs and blank rows are skipped
            $batch[] = ['cin' => $cin] + array_map($get, array_combine(array_keys(self::COLUMNS), array_keys(self::COLUMNS)));
            if (count($batch) === 500) { $this->flush($batch, $totals); $batch = []; }
        }
        if ($batch) $this->flush($batch, $totals);
        if (!$totals['rows'] && !$index) throw new RuntimeException('This does not look like MCA company data: CIN and company name columns are required.');
        return $totals;
    }

    /** Column positions when the row is a header row (has CIN and company name columns), otherwise null. */
    private function header(array $cells): ?array
    {
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)), '_')), $cells);
        $index = [];
        foreach (self::COLUMNS as $field => $aliases) {
            foreach ($aliases as $alias) if (($i = array_search($alias, $header, true)) !== false) { $index[$field] = $i; break; }
        }
        return isset($index['cin'], $index['name']) ? $index : null;
    }

    private function csvRows(string $path): \Generator
    {
        $handle = fopen($path, 'r');
        if (!$handle) throw new RuntimeException('Cannot read the file.');
        while (($row = fgetcsv($handle)) !== false) yield $row;
        fclose($handle);
    }

    private function isZip(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        $magic = $handle ? fread($handle, 4) : '';
        if ($handle) fclose($handle);
        return $magic === "PK\x03\x04";
    }

    private function flush(array $rows, array &$totals): void
    {
        $result = $this->writer->write(array_map(fn ($r) => [
            'name' => $r['name'], 'country' => 'India', 'registration' => $r['cin'], 'state' => ucwords(strtolower($r['state'])) ?: null,
            'city' => null, 'lei' => null,
        ], $rows), 'MCA Company Master Data');
        $totals['rows'] += count($rows);
        $totals['created'] += $result['created'];

        // Company ids: by CIN first, then by normalised name.
        $byCin = DB::table('companies')->whereIn('registry_cin', array_column($rows, 'cin'))->pluck('id', 'registry_cin');
        $keys = array_map(fn ($r) => \App\Models\Company::nameKey($r['name']), $rows);
        $byKey = DB::table('companies')->whereIn('name_key', $keys)->pluck('id', 'name_key');
        $taken = DB::table('company_registry_details')->whereIn('cin', array_column($rows, 'cin'))->pluck('company_id', 'cin');
        $now = now();
        $details = [];
        foreach ($rows as $i => $r) {
            $companyId = $byCin[$r['cin']] ?? $byKey[$keys[$i]] ?? null;
            if (!$companyId || (isset($taken[$r['cin']]) && (int) $taken[$r['cin']] !== (int) $companyId)) continue;
            $details[$companyId] = [
                'company_id' => $companyId, 'cin' => $r['cin'], 'company_status' => $this->clean($r['status']), 'company_class' => $this->clean($r['class']),
                'company_category' => $this->clean($r['category']), 'company_subcategory' => $this->clean($r['subcategory']),
                'listing_status' => $r['cin'][0] === 'L' ? 'Listed' : 'Unlisted', 'roc' => $this->clean($r['roc']),
                'nic_code' => substr(preg_replace('/\D/', '', $r['nic']), 0, 10) ?: substr($r['cin'], 1, 5),
                'incorporation_date' => $this->date($r['registered']), 'authorised_capital' => $this->number($r['authorised']),
                'paid_up_capital' => $this->number($r['paid_up']), 'registered_address' => $this->clean($r['address']),
                'email' => filter_var(strtolower($r['email']), FILTER_VALIDATE_EMAIL) ?: null,
                'last_agm_date' => null, 'last_balance_sheet_date' => $this->yearEnd($r['financials']),
                'source' => 'MCA Company Master Data (data.gov.in)', 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        if ($details) {
            DB::table('company_registry_details')->upsert(array_values($details), ['company_id'],
                ['cin', 'company_status', 'company_class', 'company_category', 'company_subcategory', 'listing_status', 'roc', 'nic_code', 'incorporation_date',
                 'authorised_capital', 'paid_up_capital', 'registered_address', 'email', 'last_balance_sheet_date', 'source', 'updated_at']);
            // Companies found by name get their CIN too, so CIN search finds them.
            foreach ($details as $companyId => $d) DB::table('companies')->where('id', $companyId)->whereNull('registry_cin')->update(['registry_cin' => $d['cin'], 'registry_status' => $d['company_status']]);
            $totals['detailed'] += count($details);
        }
    }

    private function clean(string $value): ?string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value));
        return $value === '' || in_array(strtoupper($value), ['NA', 'N/A', 'NULL', '-'], true) ? null : mb_substr($value, 0, 2000);
    }

    private function number(string $value): ?float
    {
        $value = preg_replace('/[^\d.]/', '', $value);
        return $value === '' ? null : (float) $value;
    }

    private function date(string $value): ?string
    {
        // Excel serial date (days since 1899-12-30), as found in MCA's .xlsx downloads.
        if (preg_match('/^\d{4,5}(\.\d+)?$/', $value) && (int) $value > 1000) return Carbon::create(1899, 12, 30)->addDays((int) $value)->toDateString();
        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'm/d/Y', 'd-M-Y', 'd-M-y'] as $format) {
            try { $date = Carbon::createFromFormat($format, $value); if ($date && $date->year > 1850) return $date->toDateString(); } catch (\Throwable) {}
        }
        return null;
    }

    /** "2023" or "2023-24" → 31 March of the closing year. */
    private function yearEnd(string $value): ?string
    {
        if (preg_match('/(\d{4})(?:\s*-\s*(\d{2,4}))?/', $value, $m)) {
            $year = isset($m[2]) ? (int) (strlen($m[2]) === 2 ? substr($m[1], 0, 2) . $m[2] : $m[2]) : (int) $m[1];
            return $year > 1900 && $year < 2100 ? "{$year}-03-31" : null;
        }
        return null;
    }
}
