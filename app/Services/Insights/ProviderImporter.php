<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CompanyCharge;
use App\Models\CompanyFiling;
use App\Models\CompanyFinancialStatement;
use App\Models\CompanyOfficer;
use App\Models\CompanyRegistryDetail;
use App\Services\Discovery\CompanyClassifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Saves company data returned by the paid MCA data providers (Sandbox, Surepass, InstaFinancials, RapidAPI) into the
 * insight tables: registry details, directors, charges and, when present, year-wise financials. Provider responses
 * differ in shape, so values are found by normalised key names anywhere in the JSON.
 */
class ProviderImporter
{
    public function __construct(private DataProviders $providers) {}

    /** Fetch a CIN from every enabled provider and save it; returns the company (created if new) or null. */
    public function fetchCin(string $cin, ?Company $company = null): ?Company
    {
        $responses = array_filter($this->providers->company($cin), fn ($json) => is_array($json) && !isset($json['_error']));
        if (!$responses) return null;
        foreach ($responses as $provider => $json) {
            $company = $this->save($cin, $json, $provider, $company);
        }
        return $company;
    }

    public function save(string $cin, array $json, string $provider, ?Company $company = null): ?Company
    {
        $find = fn (array $names) => InstaFinancials::find($json, $names);
        $name = (string) ($find(['companyname', 'companyllpname', 'nameofcompany', 'legalname', 'name']) ?: '');
        $company ??= Company::where('registry_cin', $cin)->first()
            ?? CompanyRegistryDetail::where('cin', $cin)->first()?->company
            ?? ($name !== '' ? Company::where('name_key', Company::nameKey($name))->first() : null);
        if (!$company) {
            if ($name === '') return null;
            [$sector, $subsector] = app(CompanyClassifier::class)->classify($name, (string) $find(['mainactivity', 'principalbusinessactivity', 'industry']));
            $base = Str::limit(Str::slug($name), 200, '') ?: 'company';
            $slug = $base;
            for ($i = 2; Company::where('slug', $slug)->exists(); $i++) $slug = $base . '-' . $i;
            $company = Company::create(['name' => Str::title(Str::lower($name)), 'slug' => $slug, 'country' => 'India', 'industry' => $sector, 'sector' => $subsector,
                'is_active' => true, 'sync_enabled' => false, 'registry_cin' => $cin]);
            app(\App\Services\SectorCatalogImporter::class)->attach($company, $sector, $subsector);
        }

        DB::transaction(function () use ($company, $cin, $json, $provider, $find) {
            $date = function ($value) {
                if (!$value || !is_scalar($value)) return null;
                foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd M Y', 'd-M-Y'] as $f) { try { return Carbon::createFromFormat($f, trim((string) $value))->toDateString(); } catch (Throwable) {} }
                try { return Carbon::parse((string) $value)->toDateString(); } catch (Throwable) { return null; }
            };
            $number = fn ($value) => is_scalar($value) && is_numeric($clean = preg_replace('/[^\d.\-]/', '', (string) $value)) && $clean !== '' ? (float) $clean : null;

            $registry = CompanyRegistryDetail::firstOrNew(['company_id' => $company->id]);
            if (!$registry->cin && !CompanyRegistryDetail::where('cin', $cin)->where('company_id', '!=', $company->id)->exists()) $registry->cin = $cin;
            $values = array_filter([
                'company_status' => $find(['companystatusforefiling', 'companystatus', 'status']),
                'company_class' => $find(['classofcompany', 'companyclass']),
                'company_category' => $find(['companycategory', 'category']),
                'company_subcategory' => $find(['companysubcategory', 'subcategory']),
                'roc' => $find(['roccode', 'roc', 'registrarofcompanies']),
                'incorporation_date' => $date($find(['dateofincorporation', 'incorporationdate', 'dateofregistration'])),
                'authorised_capital' => $number($find(['authorisedcapitalrs', 'authorizedcapitalrs', 'authorisedcapital', 'authorizedcapital'])),
                'paid_up_capital' => $number($find(['paidupcapitalrs', 'paidupcapital', 'paidupcapitalinr'])),
                'email' => ($e = $find(['emailid', 'email', 'emailaddress'])) && filter_var(strtolower((string) $e), FILTER_VALIDATE_EMAIL) ? strtolower((string) $e) : null,
                'registered_address' => $find(['registeredaddress', 'registeredofficeaddress', 'addressotherthanro', 'address']),
                'last_agm_date' => $date($find(['dateoflastagm', 'lastagmdate'])),
                'last_balance_sheet_date' => $date($find(['dateofbalancesheet', 'balancesheetdate'])),
                'listing_status' => ($l = $find(['whetherlistedornot', 'listingstatus'])) ? (preg_match('/^(yes|listed)/i', (string) $l) ? 'Listed' : 'Unlisted') : null,
                'pan' => ($p = $find(['pan', 'companypan'])) && preg_match(CompanyInsights::PAN, strtoupper((string) $p)) ? strtoupper((string) $p) : null,
                'nic_code' => ($n = $find(['niccode', 'industrialclass', 'mainactivitycode'])) ? substr(preg_replace('/\D/', '', (string) $n), 0, 10) ?: null : null,
            ], fn ($v) => $v !== null && $v !== '' && !is_array($v));
            // Provider data is current: it replaces older values.
            foreach ($values as $field => $value) $registry->{$field} = is_string($value) ? Str::limit(trim($value), $field === 'registered_address' ? 2000 : 250, '') : $value;
            $registry->listing_status ??= $cin[0] === 'L' ? 'Listed' : 'Unlisted';
            $registry->source = ucfirst($provider);
            $registry->enriched_at = now();
            $registry->save();
            if (!empty($values['company_status'])) $company->registry_status = Str::limit((string) $values['company_status'], 60, '');
            if (!$company->registry_cin) $company->registry_cin = $cin;
            if ($company->isDirty()) $company->save();

            foreach (InstaFinancials::people($json) as $person) {
                $ceased = $date($person['ceased_on'] ?? null);
                CompanyOfficer::updateOrCreate(['company_id' => $company->id, 'name' => $person['name'], 'designation' => $person['designation']],
                    array_filter(['din' => $person['din'], 'appointed_on' => $date($person['appointed_on']), 'ceased_on' => $ceased]) + ['source' => ucfirst($provider)]);
            }
            foreach (InstaFinancials::charges($json) as $charge) {
                CompanyCharge::updateOrCreate(['company_id' => $company->id, 'charge_id' => $charge['charge_id']],
                    ['created_on' => $date($charge['created_on']), 'modified_on' => $date($charge['modified_on']), 'satisfied_on' => $date($charge['satisfied_on'])]
                    + array_intersect_key($charge, array_flip(['lender', 'amount', 'status', 'assets_charged'])) + ['source' => ucfirst($provider)]);
            }
            foreach ($this->financials($json) as $year => $figures) {
                $statement = CompanyFinancialStatement::firstOrNew(['company_id' => $company->id, 'financial_year' => $year, 'is_consolidated' => false]);
                foreach ($figures as $field => $value) if ($value !== null && $statement->{$field} === null) $statement->{$field} = $value;
                $statement->source ??= ucfirst($provider);
                $statement->save();
            }
            CompanyFiling::create(['company_id' => $company->id, 'form' => Str::limit(ucfirst($provider) . ' data', 20, ''), 'status' => 'parsed',
                'parsed_fields' => ['cin' => $cin, 'all_fields' => InstaFinancials::flatten($json)]]);
        });
        return $company;
    }

    /**
     * Year-wise financials in a provider response: any list of rows that carry a year plus revenue/profit figures.
     * Amounts are taken as rupees; a row-level "unit" (crore/lakh) is applied when stated.
     * @return array<string, array<string, float>>
     */
    private function financials(array $json): array
    {
        $map = [
            'revenue' => ['revenuefromoperations', 'totalrevenue', 'revenue', 'netsales', 'turnover', 'sales'],
            'other_income' => ['otherincome'],
            'total_expenses' => ['totalexpenses', 'expenses'],
            'cost_of_materials_consumed' => ['costofmaterialsconsumed', 'rawmaterialconsumed'],
            'purchases_of_stock_in_trade' => ['purchasesofstockintrade', 'purchaseofstockintrade'],
            'other_expenses' => ['otherexpenses'],
            'ebitda' => ['ebitda'],
            'finance_cost' => ['financecost', 'financecosts', 'interest'],
            'depreciation' => ['depreciation', 'depreciationandamortisation'],
            'profit_before_tax' => ['profitbeforetax', 'pbt'],
            'profit_after_tax' => ['profitaftertax', 'netprofit', 'pat', 'profitlossfortheperiod'],
            'net_worth' => ['networth', 'shareholdersfunds', 'totalequity', 'equity'],
            'total_debt' => ['totaldebt', 'borrowings', 'totalborrowings'],
            'total_assets' => ['totalassets', 'assets'],
            'current_assets' => ['currentassets', 'totalcurrentassets'],
            'current_liabilities' => ['currentliabilities', 'totalcurrentliabilities'],
            'cash_and_bank' => ['cashandbank', 'cashandcashequivalents'],
        ];
        $years = [];
        $walk = function ($node) use (&$walk, &$years, $map) {
            if (!is_array($node)) return;
            $year = InstaFinancials::pick($node, ['financialyear', 'year', 'fy', 'yearending', 'periodend']);
            if (is_scalar($year) && preg_match('/(19|20)\d{2}/', (string) $year)) {
                $figures = [];
                foreach ($map as $field => $names) {
                    $value = InstaFinancials::pick($node, $names);
                    if (is_scalar($value) && is_numeric($clean = preg_replace('/[^\d.\-]/', '', (string) $value)) && $clean !== '') $figures[$field] = (float) $clean;
                }
                if (isset($figures['revenue']) || isset($figures['profit_after_tax']) || isset($figures['total_assets'])) {
                    $unit = strtolower((string) (InstaFinancials::pick($node, ['unit', 'units', 'currencyunit']) ?? ''));
                    $scale = str_contains($unit, 'crore') ? 1e7 : (str_contains($unit, 'lakh') || str_contains($unit, 'lac') ? 1e5 : (str_contains($unit, 'million') ? 1e6 : 1));
                    // "2024-25" / "2024-2025" → ends 2025; a date or a bare year ("2025", "31-03-2025") is the closing year.
                    $label = (string) $year;
                    if (preg_match('/^\s*((?:19|20)\d{2})\s*[-\/]\s*(?:\d{2}|\d{4})\s*$/', $label, $r)) {
                        $end = (int) $r[1] + 1;
                    } else {
                        preg_match_all('/(?:19|20)\d{2}/', $label, $all);
                        $end = (int) end($all[0]);
                    }
                    $fy = ($end - 1) . '-' . substr((string) $end, 2);
                    $years[$fy] = array_map(fn ($v) => round($v * $scale, 2), $figures);
                }
            }
            foreach ($node as $child) if (is_array($child)) $walk($child);
        };
        $walk($json);
        return $years;
    }
}
