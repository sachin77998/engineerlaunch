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
