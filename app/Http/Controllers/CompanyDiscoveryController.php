<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompanyDiscoveryController extends Controller
{
    public function cyberCity(): View
    {
        $companies = Company::active()->whereIn('name', array_column(config('cyber_city.companies', []), 'name'))
            ->withCount('activeJobs')->get()->keyBy('name');
        return view('companies.cyber-city', compact('companies'));
    }

    public function index(Request $request, ?CompanyCategory $category = null): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|array|max:20', 'category.*' => 'integer', 'country' => 'nullable|array|max:20', 'country.*' => 'string|max:80', 'location' => 'nullable|array|max:20', 'location.*' => 'string|max:100']);
        if (preg_match('/(?:dlf\s+)?cyber\s*city/i', $filters['q'] ?? '')) return $this->cyberCity();
        $selected = collect($filters['category'] ?? [])->map(fn($id) => (int)$id);
        $locationMap = config('company_discovery.locations', []);
        $selectedLocations = collect($filters['location'] ?? [])->filter(fn($name) => isset($locationMap[$name]))->values();
        if ($category) $selected->push($category->id);
        $companies = Company::query()->active()->with('categories:id,name,slug,taxonomy,symbol')->withCount('activeJobs')
            ->when($filters['q'] ?? null, fn($q, $term) => $q->where(function ($s) use ($term) {
                $like = addcslashes(trim($term), '%_\\') . '%';
                $s->where('name', 'like', $like)->orWhere('industry', 'like', $like)->orWhere('sector', 'like', $like);
            }))
            ->when($selected->isNotEmpty(), fn($q) => $q->whereHas('categories', fn($c) => $c->whereIn('company_categories.id', $selected)))
            ->when($filters['country'] ?? null, fn($q, $countries) => $q->whereIn('country', $countries))
            // Companies hiring in a location come from the cached (location -> company ids) map: matching every job's
            // location with LIKE for each company took 45s for Pune.
            ->when($selectedLocations->isNotEmpty(), fn ($query) => $query->whereIntegerInRaw('id', $selectedLocations->flatMap(fn ($name) => $this->companiesByLocation($locationMap)[$name] ?? [])->unique()->values()->all() ?: [0]))
            ->orderBy('name')->paginate(50)->withQueryString();
        $taxonomies = \App\Support\Stale::remember('company-discovery:facets:v3', now()->addHour(), function () {
            // One grouped pass over the pivot (0.6s); a counting subquery per category took 5.4s.
            $counts = DB::table('company_category_company as ccc')->join('companies as c', 'c.id', '=', 'ccc.company_id')->where('c.is_active', true)
                ->groupBy('ccc.company_category_id')->pluck(DB::raw('COUNT(*)'), 'ccc.company_category_id');
            return CompanyCategory::where('is_active', true)->orderBy('sort_order')->get()
                ->each(fn ($category) => $category->setAttribute('companies_count', (int) ($counts[$category->id] ?? 0)))->groupBy('taxonomy');
        });
        $featuredOrder = collect(config('company_discovery.featured_collections', []))->flip();
        $featuredCategories = collect($taxonomies->get('collection', collect()))
            ->filter(fn($item) => $featuredOrder->has($item->name))
            ->sortBy(fn($item) => $featuredOrder[$item->name])->values();
        $countries = \App\Support\Stale::remember('company-discovery:countries:v3', now()->addHour(), fn() => Company::active()->whereNotNull('country')->selectRaw('country,count(*) companies_count')->groupBy('country')->orderByDesc('companies_count')->limit(50)->get());
        $locationFacets = collect($this->companiesByLocation($locationMap))->map(fn($ids, $name) => (object)['name' => $name, 'companies_count' => count($ids)])->sortByDesc('companies_count')->values();
        return view('companies.index', compact('companies', 'taxonomies', 'featuredCategories', 'countries', 'locationFacets', 'selectedLocations', 'selected', 'category', 'filters'));
    }

    /** Location name => ids of companies with an open job there, kept for an hour. */
    private function companiesByLocation(array $locationMap): array
    {
        return \App\Support\Stale::remember('company-discovery:location-ids:v1', now()->addHour(), function () use ($locationMap) {
            // Distinct (location, company) pairs, and each distinct location string is matched once:
            // scanning every job row against every alias timed out on production (~30k jobs).
            $companiesByLocation = collect(array_keys($locationMap))->mapWithKeys(fn($name) => [$name => []])->all();
            $matchedNames = [];
            foreach (Job::forceIndex('jobs_open_location_index')->active()->whereNotNull('location')->whereNotNull('company_id')->distinct()->toBase()->get(['location', 'company_id']) as $pair) {
                $value = Str::lower($pair->location);
                $matchedNames[$value] ??= array_keys(array_filter($locationMap, fn ($aliases) => Str::contains($value, $aliases)));
                foreach ($matchedNames[$value] as $name) $companiesByLocation[$name][$pair->company_id] = true;
            }
            return array_map(fn ($ids) => array_keys($ids), $companiesByLocation);
        });
    }

    public function show(Request $request, Company $company): View
    {
        abort_unless($company->is_active, 404);
        $filters = $request->validate(['department' => 'nullable|string|max:100', 'discipline' => 'nullable|string|max:100', 'location' => 'nullable|string|max:120', 'experience' => 'nullable|in:entry,experienced']);
        $base = $company->activeJobs();
        $departments = (clone $base)->selectRaw("COALESCE(NULLIF(department, ''), NULLIF(role_family, ''), NULLIF(category, ''), 'Other') as label, COUNT(*) as jobs_count")->groupBy('label')->orderByDesc('jobs_count')->limit(20)->get();
        $disciplines = (clone $base)->selectRaw("COALESCE(NULLIF(engineering_discipline, ''), 'General / Interdisciplinary') as label, COUNT(*) as jobs_count")->groupBy('label')->orderByDesc('jobs_count')->get();
        $locations = (clone $base)->whereNotNull('location')->where('location', '!=', '')->selectRaw('location as label, COUNT(*) as jobs_count')->groupBy('location')->orderByDesc('jobs_count')->limit(25)->get();
        $jobs = $company->activeJobs()->with(['skills:id,name', 'technologies:id,name'])
            ->when($filters['department'] ?? null, fn($q, $value) => $q->where(function ($departmentQuery) use ($value) {
                $departmentQuery->where('department', $value)->orWhere(function ($legacy) use ($value) {
                    $legacy->whereNull('department')->where('category', $value);
                });
            }))
            ->when($filters['discipline'] ?? null, fn($q, $value) => $q->where('engineering_discipline', $value))
            ->when($filters['location'] ?? null, fn($q, $value) => $q->where('location', $value))
            ->when(($filters['experience'] ?? null) === 'entry', fn($q) => $q->where(fn($x) => $x->whereNull('experience_min')->orWhere('experience_min', '<=', 2)))
            ->when(($filters['experience'] ?? null) === 'experienced', fn($q) => $q->where('experience_min', '>=', 2))
            ->orderByDesc('posted_at')->orderByDesc('id')->paginate(50)->withQueryString();
        $relations = ['categories:id,parent_id,name,slug,taxonomy,symbol'];
        if (\Illuminate\Support\Facades\Schema::hasTable('company_facilities')) $relations['facilities'] = fn ($q) => $q->orderBy('state')->orderBy('city');
        $company->load($relations)->loadCount(['activeJobs', 'publishedReviews'])->loadAvg('publishedReviews', 'rating');
        $companyReviews = $company->publishedReviews()->with('reviewer:id,name')->latest()->limit(10)->get();
        $myCompanyReview = $request->user() ? $company->reviews()->where('user_id', $request->user()->id)->first() : null;
        return view('companies.show', compact('company', 'jobs', 'departments', 'disciplines', 'locations', 'filters', 'companyReviews', 'myCompanyReview'));
    }
}
