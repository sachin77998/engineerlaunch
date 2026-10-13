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
        'cin' => ['corporate_identification_number', 'cin', 'corporate_identity_number'],
        'name' => ['company_name', 'company_llp_name', 'name'],
        'status' => ['company_status', 'company_status_for_efiling', 'status'],
        'class' => ['company_class', 'class_of_company', 'class'],
        'category' => ['company_category', 'category'],
        'subcategory' => ['company_sub_category', 'company_subcategory', 'sub_category'],
        'registered' => ['date_of_registration', 'company_registration_date', 'date_of_incorporation'],
        'state' => ['registered_state', 'company_state', 'state'],
        'authorised' => ['authorized_cap', 'authorised_cap', 'authorized_capital', 'authorised_capital', 'authorized_capital_inr', 'authorised_capital_inr'],
        'paid_up' => ['paidup_capital', 'paid_up_capital', 'paidup_cap', 'paid_up_capital_inr'],
        'nic' => ['industrial_class', 'nic_code', 'activity_code', 'company_industrial_classification'],
        'activity' => ['principal_business_activity_as_per_cin', 'principal_business_activity', 'activity_description'],
        'address' => ['registered_office_address', 'company_address', 'registered_address', 'address'],
        'roc' => ['registrar_of_companies', 'roc', 'roc_code', 'company_roc', 'company_roc_code'],
        'email' => ['email_addr', 'email', 'email_id'],
        'annual_return' => ['latest_year_annual_return', 'latest_annual_return'],
        'financials' => ['latest_year_financial_statement', 'latest_financial_statement'],
    ];

    public function __construct(private RegistryWriter $writer) {}

    /** @return array{rows:int,created:int,detailed:int} */
    public function import(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) throw new RuntimeException('Cannot read the CSV file.');
        // The header is not always the first row (MCA exports can start with a title line).
        $index = [];
        for ($line = 0; $line < 20 && !isset($index['cin'], $index['name']) && ($cells = fgetcsv($handle)) !== false; $line++) {
            $header = array_map(fn ($h) => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)), '_')), $cells);
            $index = [];
            foreach (self::COLUMNS as $field => $aliases) {
                foreach ($aliases as $alias) if (($i = array_search($alias, $header, true)) !== false) { $index[$field] = $i; break; }
            }
        }
        if (!isset($index['cin'], $index['name'])) throw new RuntimeException('This does not look like MCA company master data: CIN and company name columns are required.');

        $totals = ['rows' => 0, 'created' => 0, 'detailed' => 0];
        $batch = [];
        while (($row = fgetcsv($handle)) !== false) {
            $get = fn ($field) => isset($index[$field]) ? trim((string) ($row[$index[$field]] ?? '')) : '';
            $cin = strtoupper($get('cin'));
            if (!preg_match(CompanyInsights::CIN, $cin) || $get('name') === '') continue;
            $batch[] = ['cin' => $cin] + array_map($get, array_combine(array_keys(self::COLUMNS), array_keys(self::COLUMNS)));
            if (count($batch) === 500) { $this->flush($batch, $totals); $batch = []; }
        }
        if ($batch) $this->flush($batch, $totals);
        fclose($handle);
        return $totals;
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
