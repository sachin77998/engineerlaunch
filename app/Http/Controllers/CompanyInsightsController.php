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
    public const FORMS = ['AOC-4', 'AOC-4 XBRL', 'AOC-4 CFS', 'MGT-7', 'MGT-7A', 'RATING REPORT'];
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
        $mca = app(\App\Services\Insights\DataGovMca::class);
        // CIN not on a company page yet: create it from the MCA register (stored copy or live data.gov.in).
        if ($results->isEmpty() && ($identified['type'] ?? '') === 'cin') {
            try { if ($company = $mca->companyFor($identified['value'])) $results = collect([$company->load('registryDetail')]); } catch (\Throwable $e) { report($e); }
        }
        // Names: matches in the MCA register (40 lakh companies), plus an exact live lookup when the register has none.
        $register = collect();
        if (($identified['type'] ?? '') === 'name' && mb_strlen($query) >= 3) {
            $register = $this->registerSearch($query);
            if ($register->isEmpty() && $mca->configured()) {
                try {
                    $live = $mca->byName($query);
                    $mca->store($live);
                    $register = collect($live)->map(fn ($r) => (object) $r);
                } catch (\Throwable $e) { report($e); }
            }
            $onPages = $results->pluck('registry_cin')->filter()->all();
            $register = $register->reject(fn ($r) => in_array($r->cin, $onPages, true))->values();
        }
        // Not in the database yet: look it up in the public registries (GLEIF, MCA master data) and add it.
        $searchedOnline = false;
        if ($query !== '' && $results->isEmpty() && $register->isEmpty() && in_array($identified['type'], ['cin', 'name'], true) && mb_strlen($query) >= 3) {
            try {
                $searchedOnline = true;
                $results = app(\App\Services\Insights\CompanyLookup::class)->discover($query)->each(fn ($c) => $c->load('registryDetail'));
            } catch (\Throwable $e) {
                report($e);
            }
        }
        // Still nothing for a CIN: ask the paid MCA data providers (Sandbox, Surepass, InstaFinancials) when configured.
        $providers = app(\App\Services\Insights\DataProviders::class);
        if ($results->isEmpty() && ($identified['type'] ?? '') === 'cin' && $providers->enabled() && config('services.insights.auto')) {
            try {
                if ($company = app(\App\Services\Insights\ProviderImporter::class)->fetchCin($identified['value'])) $results = collect([$company->load('registryDetail')]);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        // Director (DIN) not on record yet: list the companies the providers report for that director.
        $directorCompanies = [];
        if ($results->isEmpty() && ($identified['type'] ?? '') === 'din' && $providers->enabled()) {
            try {
                foreach ($providers->din($identified['value']) as $json) {
                    if (!isset($json['_error'])) $directorCompanies = array_merge($directorCompanies, InstaFinancials::searchResults($json));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
        if ($identified && !in_array($identified['type'], ['name', 'din'], true) && $results->count() === 1) {
            return redirect()->route('insights.show', $results->first());
        }
        $decoded = match ($identified['type'] ?? null) {
            'cin' => $this->insights->decodeCin($identified['value']),
            'gstin' => $this->insights->decodeGstin($identified['value']),
            default => null,
        };
        $registerCount = cache()->remember('insights:register-count', now()->addMinutes(10), fn () => \Illuminate\Support\Facades\DB::table('mca_companies')->count());
        $stats = cache()->remember('insights:stats', now()->addMinutes(10), fn () => [
            'companies' => max($registerCount, Company::whereNotNull('registry_cin')->count()),
            'with_financials' => CompanyFinancialStatement::distinct('company_id')->count('company_id'),
            'directors' => \App\Models\CompanyOfficer::count(),
            'charges' => \App\Models\CompanyCharge::count(),
        ]);
        // No search: browse the company database (registered companies with a CIN), filterable like a directory.
        $directory = null;
        $filters = $request->only(['status', 'state', 'industry', 'listing']);
        if ($query === '' && $registerCount > 0) {
            // The full MCA register: filters on indexed columns, no total count over lakhs of rows (simple pagination).
            $directory = \Illuminate\Support\Facades\DB::table('mca_companies')
                ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
                ->when($filters['state'] ?? null, fn ($q, $v) => $q->where('state', $v))
                ->when($filters['industry'] ?? null, fn ($q, $v) => $q->where('company_class', $v))
                ->when($filters['listing'] ?? null, fn ($q, $v) => $q->where('cin', 'like', ($v === 'listed' ? 'L' : 'U') . '%'))
                ->orderByDesc('paid_up_capital')->simplePaginate(25)->withQueryString();
            $facets = cache()->remember('insights:register-facets', now()->addDay(), fn () => [
                'status' => \Illuminate\Support\Facades\DB::table('mca_companies')->whereNotNull('status')->distinct()->orderBy('status')->pluck('status'),
                'state' => collect(\App\Services\Insights\DataGovMca::STATES),
                'industry' => \Illuminate\Support\Facades\DB::table('mca_companies')->whereNotNull('company_class')->distinct()->orderBy('company_class')->pluck('company_class'),
            ]);
            $directoryIsRegister = true;
        } elseif ($query === '') {
            $directory = Company::query()->whereNotNull('registry_cin')->where('is_active', true)
                ->with('registryDetail')->withCount('financialStatements')
                ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('registry_status', $v))
                ->when($filters['state'] ?? null, fn ($q, $v) => $q->where('registered_state', $v))
                ->when($filters['industry'] ?? null, fn ($q, $v) => $q->where('industry', $v))
                ->when($filters['listing'] ?? null, fn ($q, $v) => $q->where('registry_cin', 'like', ($v === 'listed' ? 'L' : 'U') . '%'))
                ->orderByDesc('financial_statements_count')->orderBy('name')
                ->paginate(25)->withQueryString();
            $facets = cache()->remember('insights:facets', now()->addHour(), fn () => [
                'status' => Company::whereNotNull('registry_cin')->whereNotNull('registry_status')->distinct()->orderBy('registry_status')->pluck('registry_status'),
                'state' => Company::whereNotNull('registry_cin')->whereNotNull('registered_state')->distinct()->orderBy('registered_state')->pluck('registered_state'),
                'industry' => Company::whereNotNull('registry_cin')->whereNotNull('industry')->distinct()->orderBy('industry')->pluck('industry'),
            ]);
        }
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
        $instaReady = $instaReady || $providers->enabled();
        return view('insights.index', compact('query', 'identified', 'results', 'decoded', 'stats', 'recent', 'external', 'externalError', 'instaReady', 'searchedOnline', 'directorCompanies', 'directory', 'filters', 'register', 'registerCount') + ['facets' => $facets ?? [], 'directoryIsRegister' => $directoryIsRegister ?? false]);
    }

    /** Company names in the MCA register: full-text words first, then a name prefix. */
    private function registerSearch(string $query): \Illuminate\Support\Collection
    {
        $words = collect(preg_split('/[^a-z0-9]+/i', mb_strtolower($query)))
            ->reject(fn ($w) => strlen($w) < 3 || in_array($w, ['pvt', 'private', 'ltd', 'limited', 'the', 'and', 'company', 'india'], true));
        if ($words->isEmpty()) return collect();
        $boolean = $words->map(fn ($w) => '+' . $w . '*')->implode(' ');
        try {
            $rows = \Illuminate\Support\Facades\DB::table('mca_companies')->whereRaw('MATCH(name) AGAINST (? IN BOOLEAN MODE)', [$boolean])
                ->orderByRaw("status = 'Active' DESC")->orderByDesc('paid_up_capital')->limit(30)->get();
        } catch (\Throwable $e) {
            report($e);
            $rows = collect();
        }
        return $rows->isNotEmpty() ? $rows : \Illuminate\Support\Facades\DB::table('mca_companies')->where('name', 'like', strtoupper($query) . '%')->limit(30)->get();
    }

    /** Opens a company by CIN: the company page is created from the MCA register on first visit. */
    public function byCin(string $cin)
    {
        $cin = strtoupper($cin);
        abort_unless(preg_match(CompanyInsights::CIN, $cin), 404);
        $company = app(\App\Services\Insights\DataGovMca::class)->companyFor($cin);
        abort_unless($company, 404, 'This CIN is not in the MCA register.');
        return redirect()->route('insights.show', $company);
    }

    /** Owner: fetch a company's MCA master data, directors and charges from the configured providers (paid calls) and save them. */
    public function importInsta(Request $request, \App\Services\Insights\ProviderImporter $importer)
    {
        $cin = strtoupper(trim((string) $request->input('cin')));
        abort_unless(preg_match(CompanyInsights::CIN, $cin), 422, 'A valid CIN is required.');
        try {
            $company = $importer->fetchCin($cin);
        } catch (\Throwable $e) {
            return back()->withErrors(['cin' => $e->getMessage()]);
        }
        if (!$company) return back()->withErrors(['cin' => 'No provider returned data for ' . $cin . '. Check the API keys in .env.']);
        return redirect()->route('insights.show', $company)->with('success', 'Master data, directors and charges fetched.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::limit(\Illuminate\Support\Str::slug($name), 200, '') ?: 'company';
        $slug = $base;
        for ($i = 2; Company::where('slug', $slug)->exists(); $i++) $slug = $base . '-' . $i;
        return $slug;
    }

    public function show(Company $company, Request $request)
    {
        $vendorInputs = $request->validate(['vendor_revenue_change'=>'nullable|numeric|between:-100,100']);
        // Registry details and public figures are refreshed in the background, at most once a month.
        $enrichedAt = $company->registryDetail?->enriched_at;
        if (!$enrichedAt || $enrichedAt->lt(now()->subDays(30))) {
            try { \App\Jobs\EnrichCompanyInsights::dispatch($company->id)->onConnection('database')->onQueue('catalog'); } catch (\Throwable $e) { report($e); }
        }
        // Fill entity details missing from older records (activity, PIN, city) from the MCA register, once a day at most.
        $knownCin = $company->registryDetail?->cin ?? $company->registry_cin;
        if ($knownCin && preg_match(CompanyInsights::CIN, $knownCin) && (!$company->registryDetail?->principal_activity || !$company->registryDetail?->city)
            && cache()->add('insights:register-fill:' . $company->id, 1, now()->addDay())) {
            try { app(\App\Services\Insights\DataGovMca::class)->companyFor($knownCin); $company->unsetRelation('registryDetail'); } catch (\Throwable $e) { report($e); }
        }
        $company->load(['registryDetail', 'financialStatements', 'officers', 'filings', 'shareholdings', 'businessProducts', 'relatedParties', 'charges', 'auditors']);
        $cin = $company->registryDetail?->cin ?? $company->registry_cin;
        $standalone = $company->financialStatements->where('is_consolidated', false);
        $consolidated = $company->financialStatements->where('is_consolidated', true);
        // Group structure from discovery ("Part of Tata Group."): subsidiaries of this company and its own parent.
        $subsidiaries = Company::where('description', 'Part of ' . $company->name . '.')->where('is_active', true)->orderBy('name')->limit(60)->get(['id', 'name', 'slug', 'industry', 'logo_url', 'website']);
        $parent = preg_match('/^Part of (.+)\.$/', (string) $company->description, $m) ? Company::where('name', $m[1])->first(['id', 'name', 'slug']) : null;
        $ratios = $this->insights->ratios($standalone->isNotEmpty() ? $standalone : $consolidated);
        $premium = (bool) auth()->user()?->hasActivePremium();
        // Premium viewers of a company without financials: fetch them from the providers in the background (billed per call).
        if ($premium && $cin && $company->financialStatements->isEmpty() && config('services.insights.auto') && app(\App\Services\Insights\DataProviders::class)->enabled()) {
            try { \App\Jobs\FetchCompanyFromProviders::dispatch($company->id, $cin)->onConnection('database')->onQueue('catalog'); } catch (\Throwable $e) { report($e); }
        }
        return view('insights.show', [
            'premium' => $premium, 'peers' => $premium ? $this->insights->peers($company) : collect(),
            'company' => $company, 'cin' => $cin, 'subsidiaries' => $subsidiaries, 'parent' => $parent, 'parentName' => $m[1] ?? null,
            'health' => $this->insights->health($ratios, $company->registryDetail, $company->charges),
            'dashboard' => app(\App\Services\Insights\InsightsDashboard::class)->build($company),
            'vendorAssessment' => app(\App\Services\Insights\VendorAssessment::class)->assess($company, (float)($vendorInputs['vendor_revenue_change'] ?? -10)),
            'compliance' => $this->insights->compliance($company->registryDetail),
            'decoded' => $cin && preg_match(CompanyInsights::CIN, $cin) ? $this->insights->decodeCin($cin) : null,
            'ratios' => $this->insights->ratios($standalone->isNotEmpty() ? $standalone : $consolidated),
            'consolidatedRatios' => $standalone->isNotEmpty() && $consolidated->isNotEmpty() ? $this->insights->ratios($consolidated) : collect(),
            'forms' => self::FORMS, 'fields' => self::FIELDS,
            'register' => $cin ? \Illuminate\Support\Facades\DB::table('mca_companies')->where('cin', $cin)->first() : null,
            'providerStatus' => auth()->user()?->isOwner() ? app(\App\Services\Insights\DataProviders::class)->status() : [],
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
