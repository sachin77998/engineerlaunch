<?php

namespace App\Http\Controllers;

use App\Jobs\RunMaintenanceStep;
use App\Models\Company;
use App\Models\CompanyFinancialStatement;
use App\Models\CompanyRegistryDetail;
use App\Services\Insights\CompanyInsights;
use App\Services\Insights\FilingImporter;
use App\Services\Insights\InstaFinancials;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "Insights into Company": search by name, CIN, GSTIN or PAN; registry details, financials and ratios from MCA filings. */
class CompanyInsightsController extends Controller
{
    public const FORMS = ['AOC-4', 'AOC-4 XBRL', 'AOC-4 CFS', 'MGT-7', 'MGT-7A'];
    public const FIELDS = [
        'revenue' => 'Revenue from operations', 'other_income' => 'Other income', 'total_expenses' => 'Total expenses', 'employee_cost' => 'Employee cost',
        'finance_cost' => 'Finance cost', 'depreciation' => 'Depreciation', 'profit_before_tax' => 'Profit before tax', 'tax_expense' => 'Tax expense',
        'profit_after_tax' => 'Profit after tax', 'share_capital' => 'Share capital', 'reserves' => 'Reserves / other equity', 'net_worth' => 'Net worth',
        'long_term_borrowings' => 'Long-term borrowings', 'short_term_borrowings' => 'Short-term borrowings', 'trade_payables' => 'Trade payables',
        'current_liabilities' => 'Current liabilities', 'fixed_assets' => 'Fixed assets (PPE)', 'trade_receivables' => 'Trade receivables',
        'inventories' => 'Inventories', 'cash_and_bank' => 'Cash and bank', 'current_assets' => 'Current assets', 'total_assets' => 'Total assets',
        'eps' => 'Basic EPS (₹)', 'market_price' => 'Share price at year end (₹, listed only)',
    ];

    public function __construct(private CompanyInsights $insights) {}

    public function index(Request $request, InstaFinancials $insta)
    {
        $query = trim((string) $request->query('q', ''));
        $identified = $query !== '' ? $this->insights->identify($query) : null;
        $results = $query !== '' ? $this->insights->search($query) : collect();
        // Not in the database yet: look it up in the public registries (GLEIF, MCA master data) and add it.
        $searchedOnline = false;
        if ($query !== '' && $results->isEmpty() && in_array($identified['type'], ['cin', 'name'], true) && mb_strlen($query) >= 3) {
            try {
                $searchedOnline = true;
                $results = app(\App\Services\Insights\CompanyLookup::class)->discover($query)->each(fn ($c) => $c->load('registryDetail'));
            } catch (\Throwable $e) {
                report($e);
            }
        }
        if ($identified && $identified['type'] !== 'name' && $results->count() === 1) {
            return redirect()->route('insights.show', $results->first());
        }
        $decoded = match ($identified['type'] ?? null) {
            'cin' => $this->insights->decodeCin($identified['value']),
            'gstin' => $this->insights->decodeGstin($identified['value']),
            default => null,
        };
        $stats = [
            'companies' => CompanyRegistryDetail::count(),
            'with_financials' => CompanyFinancialStatement::distinct('company_id')->count('company_id'),
        ];
        $recent = Company::whereHas('financialStatements')->with('registryDetail')->latest('updated_at')->limit(9)->get();
        // Nothing local: ask InstaFinancials (only when its key is configured).
        $external = [];
        $externalError = null;
        if ($query !== '' && $results->isEmpty() && ($identified['type'] ?? '') === 'name' && $insta->configured()) {
            try {
                $external = array_slice(InstaFinancials::searchResults(cache()->remember('insta-search:' . md5(mb_strtolower($query)), now()->addDay(), fn () => $insta->searchCin($query))), 0, 20);
            } catch (\Throwable $e) {
                $externalError = $e->getMessage();
            }
        }
        $instaReady = $insta->configured();
        return view('insights.index', compact('query', 'identified', 'results', 'decoded', 'stats', 'recent', 'external', 'externalError', 'instaReady', 'searchedOnline'));
    }

    /** Owner: fetch a company's MCA master data, directors and charges from InstaFinancials (paid call) and save them. */
    public function importInsta(Request $request, InstaFinancials $insta)
    {
        $cin = strtoupper(trim((string) $request->input('cin')));
        abort_unless(preg_match(CompanyInsights::CIN, $cin), 422, 'A valid CIN is required.');
        try {
            $json = $insta->basic($cin);
        } catch (\Throwable $e) {
            return back()->withErrors(['cin' => $e->getMessage()]);
        }
        $name = (string) (InstaFinancials::find($json, ['companyname', 'name']) ?: $request->input('name') ?: $cin);
        $company = Company::where('registry_cin', $cin)->first()
            ?? CompanyRegistryDetail::where('cin', $cin)->first()?->company
            ?? Company::where('name_key', Company::nameKey($name))->first();
        if (!$company) {
            $match = app(\App\Services\Discovery\CompanyClassifier::class)->classify($name);
            $company = Company::create(['name' => $name, 'slug' => $this->uniqueSlug($name), 'country' => 'India', 'industry' => $match[0], 'sector' => $match[1],
                'is_active' => true, 'sync_enabled' => false, 'registry_cin' => $cin]);
        }
        $date = function ($value) { try { return $value ? \Carbon\Carbon::parse(str_replace('/', '-', (string) $value))->toDateString() : null; } catch (\Throwable) { return null; } };
        $number = fn ($value) => is_numeric($clean = preg_replace('/[^\d.\-]/', '', (string) $value)) ? (float) $clean : null;
        $registry = CompanyRegistryDetail::firstOrNew(['company_id' => $company->id]);
        $registry->fill(array_filter([
            'cin' => CompanyRegistryDetail::where('cin', $cin)->where('company_id', '!=', $company->id)->exists() ? null : $cin,
            'company_status' => InstaFinancials::find($json, ['companystatus', 'status', 'companystatusforefiling']),
            'company_class' => InstaFinancials::find($json, ['companyclass', 'classofcompany']),
            'company_category' => InstaFinancials::find($json, ['companycategory', 'category']),
            'company_subcategory' => InstaFinancials::find($json, ['companysubcategory', 'subcategory']),
            'roc' => InstaFinancials::find($json, ['roc', 'roccode', 'registrarofcompanies']),
            'incorporation_date' => $date(InstaFinancials::find($json, ['dateofincorporation', 'incorporationdate', 'dateofregistration'])),
            'authorised_capital' => $number(InstaFinancials::find($json, ['authorisedcapital', 'authorizedcapital'])),
            'paid_up_capital' => $number(InstaFinancials::find($json, ['paidupcapital', 'paidupcapitalinr'])),
            'email' => InstaFinancials::find($json, ['email', 'emailid', 'emailaddress']),
            'registered_address' => InstaFinancials::find($json, ['registeredaddress', 'registeredofficeaddress', 'address']),
            'last_agm_date' => $date(InstaFinancials::find($json, ['dateoflastagm', 'lastagmdate'])),
            'last_balance_sheet_date' => $date(InstaFinancials::find($json, ['dateofbalancesheet', 'balancesheetdate'])),
        ], fn ($v) => $v !== null && $v !== ''))->fill(['listing_status' => $cin[0] === 'L' ? 'Listed' : 'Unlisted', 'source' => 'InstaFinancials'])->save();
        foreach (InstaFinancials::people($json) as $person) {
            \App\Models\CompanyOfficer::updateOrCreate(['company_id' => $company->id, 'name' => $person['name'], 'designation' => $person['designation']],
                array_filter(['din' => $person['din'], 'appointed_on' => $date($person['appointed_on'])]) + ['source' => 'InstaFinancials']);
        }
        foreach (InstaFinancials::charges($json) as $charge) {
            \App\Models\CompanyCharge::updateOrCreate(['company_id' => $company->id, 'charge_id' => $charge['charge_id']],
                ['created_on' => $date($charge['created_on']), 'modified_on' => $date($charge['modified_on']), 'satisfied_on' => $date($charge['satisfied_on'])]
                + array_intersect_key($charge, array_flip(['lender', 'amount', 'status', 'assets_charged'])) + ['source' => 'InstaFinancials']);
        }
        \App\Models\CompanyFiling::create(['company_id' => $company->id, 'form' => 'InstaBasic', 'status' => 'parsed', 'uploaded_by' => $request->user()?->id,
            'parsed_fields' => ['cin' => $cin, 'all_fields' => InstaFinancials::flatten($json)]]);
        return redirect()->route('insights.show', $company)->with('success', 'Master data and directors fetched from InstaFinancials.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::limit(\Illuminate\Support\Str::slug($name), 200, '') ?: 'company';
        $slug = $base;
        for ($i = 2; Company::where('slug', $slug)->exists(); $i++) $slug = $base . '-' . $i;
        return $slug;
    }

    public function show(Company $company)
    {
        // Registry details and public figures are refreshed in the background, at most once a month.
        $enrichedAt = $company->registryDetail?->enriched_at;
        if (!$enrichedAt || $enrichedAt->lt(now()->subDays(30))) {
            try { \App\Jobs\EnrichCompanyInsights::dispatch($company->id)->onConnection('database')->onQueue('catalog'); } catch (\Throwable $e) { report($e); }
        }
        $company->load(['registryDetail', 'financialStatements', 'officers', 'filings', 'shareholdings', 'businessProducts', 'relatedParties', 'charges', 'auditors']);
        $cin = $company->registryDetail?->cin ?? $company->registry_cin;
        $standalone = $company->financialStatements->where('is_consolidated', false);
        $consolidated = $company->financialStatements->where('is_consolidated', true);
        // Group structure from discovery ("Part of Tata Group."): subsidiaries of this company and its own parent.
        $subsidiaries = Company::where('description', 'Part of ' . $company->name . '.')->where('is_active', true)->orderBy('name')->limit(60)->get(['id', 'name', 'slug', 'industry', 'logo_url', 'website']);
        $parent = preg_match('/^Part of (.+)\.$/', (string) $company->description, $m) ? Company::where('name', $m[1])->first(['id', 'name', 'slug']) : null;
        $ratios = $this->insights->ratios($standalone->isNotEmpty() ? $standalone : $consolidated);
        return view('insights.show', [
            'company' => $company, 'cin' => $cin, 'subsidiaries' => $subsidiaries, 'parent' => $parent, 'parentName' => $m[1] ?? null,
            'health' => $this->insights->health($ratios, $company->registryDetail, $company->charges),
            'compliance' => $this->insights->compliance($company->registryDetail),
            'decoded' => $cin && preg_match(CompanyInsights::CIN, $cin) ? $this->insights->decodeCin($cin) : null,
            'ratios' => $this->insights->ratios($standalone->isNotEmpty() ? $standalone : $consolidated),
            'consolidatedRatios' => $standalone->isNotEmpty() && $consolidated->isNotEmpty() ? $this->insights->ratios($consolidated) : collect(),
            'forms' => self::FORMS, 'fields' => self::FIELDS,
        ]);
    }

    /** Owner: upload an AOC-4 / MGT-7 (text is extracted in the browser with pdf.js, or a .txt file is sent). */
    public function upload(Request $request, Company $company, FilingImporter $importer)
    {
        $data = $request->validate([
            'form' => ['required', Rule::in(self::FORMS)],
            'text' => 'nullable|string|max:5000000',
            'file' => 'nullable|file|mimes:pdf,txt|max:20480',
        ]);
        $file = $request->file('file');
        $text = $data['text'] ?? '';
        if ($text === '' && $file && strtolower($file->getClientOriginalExtension()) === 'txt') $text = file_get_contents($file->getRealPath());
        if (trim($text) === '') return back()->withErrors(['file' => 'No text was received. Choose the PDF again and wait until "Text ready" appears, or upload the .txt version.']);
        $filing = $importer->import($company, $data['form'], $text, $file, $request->user()?->id);
        return redirect()->route('insights.show', $company)->with($filing->status === 'parsed' ? 'success' : 'warning',
            $filing->form . ' ' . ($filing->financial_year ?? '') . ': ' . ($filing->message ?? 'figures saved.'));
    }

    /** Owner: add or correct a year's figures by hand (amounts in rupees, or lakhs/crores with the unit selector). */
    public function saveStatement(Request $request, Company $company)
    {
        $rules = ['financial_year' => ['required', 'regex:/^\d{4}-\d{2}$/'], 'is_consolidated' => 'nullable|boolean', 'unit' => ['required', Rule::in(['1', '100000', '10000000'])]];
        foreach (array_keys(self::FIELDS) as $field) $rules[$field] = 'nullable|numeric';
        $data = $request->validate($rules);
        $scale = (float) $data['unit'];
        $values = [];
        foreach (array_keys(self::FIELDS) as $field) {
            if (!isset($data[$field])) continue;
            $values[$field] = in_array($field, ['eps', 'market_price'], true) ? $data[$field] : round($data[$field] * $scale, 2);
        }
        if (isset($values['long_term_borrowings']) || isset($values['short_term_borrowings'])) {
            $values['total_debt'] = ($values['long_term_borrowings'] ?? 0) + ($values['short_term_borrowings'] ?? 0);
        }
        CompanyFinancialStatement::updateOrCreate(
            ['company_id' => $company->id, 'financial_year' => $data['financial_year'], 'is_consolidated' => (bool) ($data['is_consolidated'] ?? false)],
            $values + ['source' => 'manual']);
        return redirect()->route('insights.show', $company)->with('success', "Figures for {$data['financial_year']} saved.");
    }

    /** Owner: MCA Company Master Data CSV from data.gov.in, imported in the background. */
    public function masterData(Request $request)
    {
        $request->validate(['csv' => 'required|file|mimes:csv,txt,xlsx,zip|max:204800'], ['csv.mimes' => 'Upload a .csv or .xlsx file (old .xls files: open in Excel and save as .xlsx).']);
        $path = $request->file('csv')->store('insights/master-data');
        $key = 'insights-master-' . now()->format('YmdHis');
        RunMaintenanceStep::markQueued($key, 'Import company master data');
        RunMaintenanceStep::dispatch($key, 'Import company master data', 'insights:import-master', ['file' => $path, '--delete' => true]);
        return back()->with('success', 'Master data file received. It is being imported in the background; companies appear in search as it runs.');
    }
}
