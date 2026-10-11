<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\CompanyFacility;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/** Read side of the sector catalog: cached sector tree, filtered sector pages and matching openings. */
class SectorDirectory
{
    private const VERSION_KEY = 'sector-directory:version';
    public const FILTERS = ['q', 'state', 'city', 'industrial_area', 'role'];

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, (string) microtime(true));
    }

    private function key(string $name): string
    {
        return 'sector-directory:' . Cache::get(self::VERSION_KEY, '0') . ':' . $name;
    }

    /** Sectors with subsectors, company counts and a few company names per subsector. */
    public function tree(): Collection
    {
        return Cache::remember($this->key('tree'), now()->addHours(6), function () {
            return CompanyCategory::where('taxonomy', SectorCatalogImporter::TAXONOMY)->whereNull('parent_id')->where('is_active', true)
                ->withCount(['companies' => fn ($q) => $q->active()])
                ->with(['children' => fn ($q) => $q->where('is_active', true)->withCount(['companies' => fn ($c) => $c->active()])])
                ->orderBy('sort_order')->get()
                ->map(function (CompanyCategory $sector) {
                    $sector->children->each(fn ($child) => $child->setAttribute('preview', $child->companies()->active()->orderBy('name')->limit(8)->pluck('name')->all()));
                    return $sector;
                });
        });
    }

    public function find(string $slug): ?CompanyCategory
    {
        return $this->tree()->firstWhere('slug', $slug);
    }

    /**
     * Category cards (config/sector_groups.php) built from the catalog tree. Each card lists its sub-categories with
     * company and opening counts and a few company logos. Sectors not mapped to a card get a card of their own.
     */
    public function groups(): Collection
    {
        return Cache::remember($this->key('groups'), now()->addHours(6), function () {
            $tree = $this->tree()->keyBy('name');
            $children = $tree->flatMap(fn ($sector) => $sector->children->map(fn ($child) => $child->setRelation('sector', $sector)));
            $openings = $this->openingsByCategory($children->pluck('id'));
            $previews = $this->previewCompanies($children->pluck('id'));
            $used = [];
            $groups = collect(config('sector_groups.groups', []))->map(function ($group, $slug) use ($tree, $openings, $previews, &$used) {
                $subs = collect();
                foreach ($group['members'] as [$sectorName, $only]) {
                    $sector = $tree->get($sectorName);
                    if (!$sector) continue;
                    $used[$sectorName] = true;
                    $subs = $subs->merge($sector->children->filter(fn ($child) => $only === null || in_array($child->name, $only, true)));
                }
                return $this->groupCard($slug, $group, $subs, $openings, $previews);
            });
            foreach ($tree as $name => $sector) {
                if (isset($used[$name]) || $sector->children->isEmpty()) continue;
                $groups[$sector->slug] = $this->groupCard($sector->slug, ['name' => $name, 'icon' => null, 'symbol' => $sector->symbol], $sector->children, $openings, $previews);
            }
            return $groups->filter(fn ($group) => $group->subcategories->isNotEmpty())->values();
        });
    }

    public function group(string $slug): ?object
    {
        return $this->groups()->firstWhere('slug', $slug);
    }

    /** The sub-category with this slug and its parent sector. @return array{0:CompanyCategory,1:CompanyCategory}|null */
    public function subcategory(string $sectorSlug, string $subSlug): ?array
    {
        $sector = $this->find($sectorSlug);
        $child = $sector?->children->firstWhere('slug', $subSlug);
        return $child ? [$sector, $child] : null;
    }

    /** Companies of one sub-category, most openings first, narrowed by the usual filters. */
    public function subcategoryCompanies(CompanyCategory $child, array $filters, int $perPage = 30)
    {
        return $this->companies($filters)->whereHas('categories', fn ($q) => $q->where('company_categories.id', $child->id))
            ->paginate($perPage)->withQueryString();
    }

    public function imageFor(string $name): ?string
    {
        $name = mb_strtolower($name);
        foreach (config('sector_groups.images', []) as $keyword => $path) {
            if (str_contains($name, $keyword) && is_file(public_path($path))) return asset($path);
        }
        return null;
    }

    /** One catalog sector presented like a category card (its sub-categories with counts and logos). */
    public function sectorCard(CompanyCategory $sector): object
    {
        return Cache::remember($this->key('sector-card:' . $sector->id), now()->addHours(6), fn () => $this->groupCard(
            $sector->slug, ['name' => $sector->name, 'symbol' => $sector->symbol], $sector->children,
            $this->openingsByCategory($sector->children->pluck('id')), $this->previewCompanies($sector->children->pluck('id'))));
    }

    private function groupCard(string $slug, array $group, Collection $subs, array $openings, array $previews): object
    {
        $subs = $subs->unique('id')->values()->each(function ($child) use ($openings, $previews) {
            $child->setAttribute('openings_count', $openings[$child->id] ?? 0);
            $child->setAttribute('preview_companies', $previews[$child->id] ?? []);
            $child->setAttribute('image', $this->imageFor($child->name));
        });
        $image = isset($group['image']) && is_file(public_path($group['image'])) ? asset($group['image']) : null;
        return (object) [
            'slug' => $slug, 'name' => $group['name'], 'icon' => $group['icon'] ?? null, 'symbol' => $group['symbol'] ?? null,
            'color' => $group['color'] ?? '#0b2545', 'image' => $image, 'subcategories' => $subs,
            'companies_count' => $this->distinctCompanies($subs->pluck('id')), 'openings_count' => $subs->sum('openings_count'),
            'logos' => collect($subs->flatMap(fn ($child) => $child->preview_companies))->unique('id')->take(5)->values()->all(),
        ];
    }

    /** Active openings per sub-category: jobs counted per company once, then summed over each category's companies. @return array<int,int> */
    private function openingsByCategory(Collection $categoryIds): array
    {
        if ($categoryIds->isEmpty()) return [];
        $perCompany = Cache::remember($this->key('jobs-per-company'), now()->addMinutes(30),
            fn () => Job::active()->whereNotNull('company_id')->selectRaw('company_id, COUNT(*) as n')->groupBy('company_id')->pluck('n', 'company_id')->all());
        $totals = [];
        \Illuminate\Support\Facades\DB::table('company_category_company')->whereIn('company_category_id', $categoryIds)
            ->whereIn('company_id', array_keys($perCompany) ?: [0])->get(['company_category_id', 'company_id'])
            ->each(function ($row) use (&$totals, $perCompany) { $totals[$row->company_category_id] = ($totals[$row->company_category_id] ?? 0) + (int) $perCompany[$row->company_id]; });
        return $totals;
    }

    /** Up to four companies per sub-category (most openings first) for the card logos. @return array<int,array> */
    private function previewCompanies(Collection $categoryIds): array
    {
        if ($categoryIds->isEmpty()) return [];
        $rows = \Illuminate\Support\Facades\DB::table('company_category_company as ccc')->join('companies', 'companies.id', '=', 'ccc.company_id')
            ->where('companies.is_active', true)->whereIn('ccc.company_category_id', $categoryIds)
            ->get(['ccc.company_category_id', 'companies.id', 'companies.name', 'companies.slug', 'companies.website', 'companies.logo_url']);
        $jobs = Job::active()->whereIn('company_id', $rows->pluck('id')->unique())->selectRaw('company_id, COUNT(*) as n')->groupBy('company_id')->pluck('n', 'company_id');
        $previews = [];
        foreach ($rows->groupBy('company_category_id') as $id => $companies) {
            $previews[$id] = $companies->sortBy([fn ($a, $b) => ($jobs[$b->id] ?? 0) <=> ($jobs[$a->id] ?? 0), fn ($a, $b) => strcmp($a->name, $b->name)])
                ->take(4)->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug,
                    'logo' => (new Company(['website' => $c->website]))->forceFill(['logo_url' => $c->logo_url])->logo_url])->values()->all();
        }
        return $previews;
    }

    private function distinctCompanies(Collection $categoryIds): int
    {
        if ($categoryIds->isEmpty()) return 0;
        return (int) \Illuminate\Support\Facades\DB::table('company_category_company as ccc')->join('companies', 'companies.id', '=', 'ccc.company_id')
            ->where('companies.is_active', true)->whereIn('ccc.company_category_id', $categoryIds)->distinct()->count('ccc.company_id');
    }

    /** Distinct plant locations for the left-hand filters. */
    public function locations(): array
    {
        if (!$this->hasFacilities()) return ['states' => [], 'cities' => [], 'areas' => []];
        return Cache::remember($this->key('locations'), now()->addHours(6), fn () => [
            'states' => CompanyFacility::whereNotNull('state')->distinct()->orderBy('state')->pluck('state')->all(),
            'cities' => CompanyFacility::whereNotNull('city')->distinct()->orderBy('city')->pluck('city')->all(),
            'areas' => CompanyFacility::whereNotNull('industrial_area')->distinct()->orderBy('industrial_area')->pluck('industrial_area')->all(),
        ]);
    }

    /** Industrial areas and tech parks with how many catalogued companies have a plant or office there. */
    public function hubs(int $limit = 48): Collection
    {
        if (!$this->hasFacilities()) return collect();
        return Cache::remember($this->key('hubs:' . $limit), now()->addHours(6), fn () => CompanyFacility::query()
            ->whereNotNull('industrial_area')->whereHas('company', fn ($q) => $q->active())
            ->selectRaw('industrial_area, MAX(city) as city, MAX(state) as state, COUNT(DISTINCT company_id) as companies_count')
            ->groupBy('industrial_area')->orderByDesc('companies_count')->limit($limit)->get());
    }

    /** Companies of one sector grouped by subsector, narrowed by name/brand and plant location. */
    public function sector(CompanyCategory $sector, array $filters): Collection
    {
        $companies = $this->companies($filters)
            ->whereHas('categories', fn ($q) => $q->whereIn('company_categories.id', $sector->children->pluck('id')))
            ->with(['categories' => fn ($q) => $q->where('parent_id', $sector->id)->select('company_categories.id')])
            ->get();
        return $sector->children->map(fn ($child) => (object) [
            'category' => $child,
            'companies' => $companies->filter(fn ($company) => $company->categories->contains('id', $child->id))->values(),
        ])->filter(fn ($group) => $group->companies->isNotEmpty())->values();
    }

    /** Cross-sector search, e.g. a brand ("Santoor") or a hub ("Haridwar"). */
    public function search(array $filters): Collection
    {
        return $this->companies($filters)->whereHas('categories', fn ($q) => $q->where('taxonomy', SectorCatalogImporter::TAXONOMY))
            ->with(['categories' => fn ($q) => $q->where('taxonomy', SectorCatalogImporter::TAXONOMY)->select('company_categories.id', 'name', 'slug', 'parent_id')])
            ->limit(150)->get();
    }

    public function openings(Collection $companyIds, array $filters, int $limit = 15): Collection
    {
        if ($companyIds->isEmpty()) return collect();
        return Job::active()->whereIn('company_id', $companyIds)->with('company:id,name,slug')
            ->when($filters['role'] ?? null, function ($q, $role) {
                foreach (preg_split('/\s+/', trim($role)) as $word) $q->where('title', 'like', $this->like($word));
            })
            ->when($filters['city'] ?? null, fn ($q, $city) => $q->where('location', 'like', $this->like($city)))
            ->when($filters['state'] ?? null, fn ($q, $state) => $q->where(fn ($s) => $s->where('state', 'like', $this->like($state))->orWhere('location', 'like', $this->like($state))))
            ->latest('posted_at')->limit($limit)->get(['id', 'company_id', 'title', 'slug', 'location', 'posted_at', 'job_type', 'posting_source', 'source']);
    }

    private function hasColumn(string $column): bool
    {
        return Cache::remember('sector-directory:has-column:' . $column, now()->addMinutes(10), fn () => Schema::hasColumn('companies', $column));
    }

    private function hasFacilities(): bool
    {
        return Cache::remember('sector-directory:has-facilities', now()->addMinutes(10), fn () => Schema::hasTable('company_facilities'));
    }

    private function companies(array $filters): Builder
    {
        $location = array_filter(array_intersect_key($filters, array_flip(['state', 'city', 'industrial_area'])));
        $facilities = $this->hasFacilities();
        if (!$facilities) $location = [];
        return Company::active()->withCount('activeJobs')
            ->when($facilities, fn ($q) => $q->with(['facilities' => fn ($f) => $this->whereLocation($f, $location)->select('id', 'company_id', 'state', 'city', 'industrial_area')]))
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = $this->like(mb_strtolower($term));
                $query->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->when($this->hasColumn('brands'), fn ($w) => $w->orWhereRaw('LOWER(CAST(brands AS CHAR)) LIKE ?', [$like]))
                    ->when($this->hasColumn('products'), fn ($w) => $w->orWhereRaw('LOWER(products) LIKE ?', [$like]))
                    ->when($this->hasFacilities(), fn ($w) => $w->orWhereHas('facilities', fn ($f) => $f->where('city', 'like', $like)->orWhere('industrial_area', 'like', $like))));
            })
            ->when($location, fn ($query) => $query->whereHas('facilities', fn ($f) => $this->whereLocation($f, $location)))
            ->orderByDesc('active_jobs_count')->orderBy('name');
    }

    private function whereLocation($query, array $location)
    {
        foreach ($location as $column => $value) $query->where($column, 'like', $this->like($value));
        return $query;
    }

    private function like(string $value): string
    {
        return '%' . addcslashes(trim($value), '%_\\') . '%';
    }
}
