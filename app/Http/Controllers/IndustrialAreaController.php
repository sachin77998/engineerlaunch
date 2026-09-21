<?php

namespace App\Http\Controllers;

use App\Models\IndustrialArea;
use App\Models\IndustrialCompany;
use App\Models\IndustrialDepartment;
use App\Models\IndustrialJob;
use App\Models\IndustrialJobRole;
use App\Models\IndustrialState;
use App\Services\IndustrialIntelligence;
use App\Services\IndustrialSearch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IndustrialAreaController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->data($request);
        return view('industrial.index', $data);
    }
     public function ajax(Request $request)
    {
        $data = $this->data($request);
        return response()->json(['html' => view('industrial.results', $data)->render(),'options' => $data['options'],'taxonomyHtml' => view('industrial.taxonomy', $data)->render(),'hero' => $data['heroUrl'],'heroText' => $data['heroText'],'heroCaption' => $data['heroCaption'],'filters' => $data['filters'],'heading' => $data['contextCompany']->name ?? $data['contextArea']->name ?? 'Industrial India',]);
    }
    private function data(Request $request): array
    {
        foreach (['state_id' => 'state', 'city' => 'location', 'area_id' => 'area', 'department_id' => 'department',] as $alias => $name) {
            if (! $request->has($name) && $request->has($alias)) {$request->merge([$name => $request->input($alias),]);}
        }
        $filters = $request->validate([
            'experience' => ['nullable', Rule::in(['fresher', '0-2', '2-5', '5-10',]),],
            'qualification' => ['nullable', Rule::in(['10th', '12th', 'ITI', 'Diploma', 'B.Tech', 'Graduate',]),],
            'salary' => ['nullable',Rule::in(['10000-20000', '20000-30000', '30000-50000', '50000+',]),],
            'sector' => 'nullable|integer|min:1',
            'subsector' => 'nullable|integer|min:1',
            'process' => 'nullable|integer|min:1',
            'skill' => 'nullable|string|max:120',
            'search' => 'nullable|string|max:120',
            'state' => 'nullable|integer|min:1',
            'location' => 'nullable|string|max:255',
            'area' => 'nullable|integer|min:1',
            'company' => 'nullable|integer|min:1',
            'department' => 'nullable|integer|min:1',
            'role' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1',
            'companies_page' => 'nullable|integer|min:1',
            'jobs_page' => 'nullable|integer|min:1',
        ]);
        $taxonomyService = app(\App\Services\IndustrialTaxonomy::class);
        $taxonomy = $taxonomyService->prepare($filters);
        $states = IndustrialState::query()->where('is_active', true)->orderBy('name')->get(['id', 'name',]);
        if (!empty($filters['state']) && ! $states->contains('id', $filters['state'])) {
            $this->invalid('state');
        }
        $baseAreas = IndustrialArea::visible();
        if (! empty($filters['state'])) {
            $baseAreas->where('state_id', $filters['state']);
        }
        $locations = IndustrialArea::locationNames($baseAreas);
        if (! empty($filters['location'])) {
            if (empty($filters['state']) || ! $locations->contains($filters['location'])) {
                $this->invalid('location');
            }
            $baseAreas->atLocation($filters['location']);
        }
        if (! empty($filters['area'])) {if (!(clone $baseAreas)->whereKey($filters['area'])->exists()) {$this->invalid('area');}
        }
        $areaOptions = ! empty($filters['state']) ? (clone $baseAreas)->orderBy('name')->get(['id', 'name',]) : collect();
        if (! empty($filters['area'])) {
            $baseAreas->whereKey($filters['area']);
        }
        $companiesQuery = IndustrialCompany::visible()->whereIn('industrial_area_id', (clone $baseAreas)->select('id'));
        $taxonomyService->companies($companiesQuery, $taxonomy);
        if (! empty($filters['company']) && (empty($filters['area']) || ! (clone $companiesQuery)->whereKey($filters['company'])->exists())) {
            $this->invalid('company');
        }
        $companyOptions = ! empty($filters['area']) ? (clone $companiesQuery)->orderBy('name')->get(['id', 'name',]) : collect();
         $jobsQuery = IndustrialJob::live()->whereIn('industrial_area_id', (clone $baseAreas)->select('id'));
        if ($taxonomy['sector']) {
            $jobsQuery->whereHas('company', fn($q) => $taxonomyService->companies($q, $taxonomy));
        }
        if (! empty($filters['company'])) {
            $companiesQuery->whereKey($filters['company']);
            $jobsQuery->where('industrial_company_id', $filters['company']);
        }
        $departmentsQuery = IndustrialDepartment::query()->where('is_active', true);
        if (! empty($filters['company'])) {
            $departmentsQuery->whereIn('id', (clone $jobsQuery)->select('department_id'));
        }
        $departments = $departmentsQuery->orderBy('name')->get(['id', 'name',]);
        if (! empty($filters['department'])) {
            if (!$departments->contains('id', $filters['department'])) {
                $this->invalid('department');
            }
            $jobsQuery->where('department_id', $filters['department']);
        }
        $rolesQuery = IndustrialJobRole::query()->where('is_active', true)->whereHas('department', fn($q) => $q->where('is_active', true));
        if (! empty($filters['department'])) {
            $rolesQuery->where('department_id', $filters['department']);
        }
        if (! empty($filters['company'])) {
            $rolesQuery->whereIn('id', (clone $jobsQuery)->select('job_role_id'));
        }
        if (! empty($filters['role'])) {
            if (empty($filters['department']) || ! (clone $rolesQuery)->whereKey($filters['role'])->exists()) {
                $this->invalid('role');
            }
            $jobsQuery->where('job_role_id',$filters['role']);
        }
        $roles = ! empty($filters['department']) ? $rolesQuery->orderBy('name')->get(['id', 'name',]) : collect();
        //   SEARCH
        $search = app(IndustrialSearch::class);
        $tokens = $search->tokens($filters['search'] ?? '');
        $search->areas($baseAreas, $tokens);
        $search->companies($companiesQuery, $tokens);
        $search->jobs($jobsQuery, $tokens);
        //  EXPERIENCE FILTER

        if (! empty($filters['experience'])) {
            if ($filters['experience'] === 'fresher') {
                [$min, $max] = [0, 0];
            } else {
                [$min, $max] = array_map('intval', explode('-', $filters['experience']));
            }
            $jobsQuery->whereNotNull('experience_min')->where('experience_min', '<=', $max)->where(fn($q) => $q->whereNull('experience_max')->orWhere('experience_max', '>=', $min));
        }
        //  QUALIFICATION FILTER
        if (! empty($filters['qualification'])) {
            $jobsQuery->where('qualification', 'like', '%' . $filters['qualification'] . '%');
        }
        //   SKILL FILTER
        if (! empty($filters['skill'])) {
            if ($jobsQuery->getConnection()->getDriverName() === 'sqlite') {
                $jobsQuery->whereRaw(
                    'EXISTS (SELECT 1 FROM json_each(industrial_jobs.skills) WHERE json_each.value = ?)',
                    [$filters['skill'],]
                );
            } else {
                $jobsQuery->whereJsonContains('skills', $filters['skill']);
            }
        }
        //   SALARY FILTER
        if (! empty($filters['salary'])) {
            if ($filters['salary'] === '50000+') {
                [$min, $max] = [50000, null];
            } else {
                [$min, $max] = array_map('intval', explode('-', $filters['salary']));
            }
            $jobsQuery->whereIn('salary_period', ['monthly', 'annual',])->whereNotNull('salary_min');
            if ($max !== null) {
                $jobsQuery->whereRaw(
                    "salary_min / CASE
                        WHEN salary_period = 'annual' THEN 12 ELSE 1 END <= ?",
                    [$max,]
                );
            }
            $jobsQuery->whereRaw("COALESCE(salary_max, salary_min) /CASE WHEN salary_period = 'annual' THEN 12 ELSE 1 END >= ?", [$min,]);
        }
        //   SECTOR → AREA RELATION
        if ($taxonomy['sector']) {
            $baseAreas->where(
                function ($q) use ($taxonomy, $taxonomyService) {
                    $q->whereHas(
                        'companies',
                        function ($c) use ($taxonomy, $taxonomyService) {
                            $c->visible();
                            $taxonomyService->companies($c, $taxonomy);
                        }
                    );
                    if (! $taxonomy['sub'] && ! $taxonomy['process']) {
                        $q->orWhereHas('sectorCatalog', fn($s) => $s->whereKey($taxonomy['sector']->id));
                    }
                }
            );
        }
        //   INDUSTRIAL AREAS RESULT
        $areas = $baseAreas->with('state')->withCount(['companies' => fn($q) => $q->visible(), 'jobs as live_jobs_count' => fn($q) => $q->live(),])
            ->orderByDesc('is_featured')->orderBy('name')->orderBy('id')->paginate(12)->withQueryString();
        //   COMPANIES RESULT

        $companies = $companiesQuery->with('area.state', 'sources', 'sectors')->withCount(['jobs as live_jobs_count' => fn($q) => $q->live(),])
            ->orderBy('name')->orderBy('id')->paginate(12, ['*'], 'companies_page')->withQueryString();
        //   LIVE JOBS RESULT
        $careerMatches = $taxonomy['careerMatch']['matches'];
        if ($careerMatches) {
            $cases = [];
            $bindings = [];
            foreach ($careerMatches as $roleId => $match) {
                $cases[] = 'WHEN ? THEN ?';
                $bindings[] = $roleId;
                $bindings[] = $match['score'];
            }
            $jobsQuery->orderByRaw('CASE job_role_id ' . implode(' ', $cases) . ' ELSE 0 END DESC', $bindings);
        }
        $jobs = $jobsQuery->with(['area.state', 'company', 'department', 'role',])->latest('id')->paginate(12, ['*'], 'jobs_page')->withQueryString();
        //  FEATURED INDUSTRIAL HUBS
        $hubs = IndustrialArea::visible()->where('is_featured', true)->with('state')->orderBy('state_id')
            ->orderBy('city')->limit(200)->get()->groupBy('state.name');
        //  FILTER OPTIONS        
        $options = [
            'state' => $states,

            'location' => $locations
                ->map(
                    fn($name) => [
                        'id' => $name,
                        'name' => $name,
                    ]
                )
                ->values(),

            'area' => $areaOptions,

            'company' => $companyOptions,

            'department' => $departments,

            'role' => $roles,
        ];

        $options += [
            'sector' => $taxonomy['sectors'],
            'subsector' => $taxonomy['subOptions'],
            'process' => $taxonomy['processes'],
        ];

        /*
        |--------------------------------------------------------------------------
        | VIEW DATA
        |--------------------------------------------------------------------------
        */
        $data = compact(
            'taxonomy',
            'filters',
            'options',
            'areas',
            'companies',
            'jobs',
            'hubs'
        );

        /*
        |--------------------------------------------------------------------------
        | CURRENT AREA / COMPANY CONTEXT
        |--------------------------------------------------------------------------
        */
        $data['contextArea'] = ! empty($filters['area'])
            ? IndustrialArea::visible()
            ->with('state')
            ->find($filters['area'])
            : null;

        $data['contextCompany'] = ! empty($filters['company'])
            ? IndustrialCompany::visible()
            ->find($filters['company'])
            : null;

        /*
        |--------------------------------------------------------------------------
        | INDUSTRIAL INTELLIGENCE
        |--------------------------------------------------------------------------
        */
        if ($data['contextArea']) {
            $data += app(
                IndustrialIntelligence::class
            )->summary(
                $data['contextArea'],
                $data['contextCompany']?->id
            );

            $data += app(
                IndustrialIntelligence::class
            )->nearby(
                $data['contextArea']
            );
        }

        $data['careerMatches'] = $careerMatches;
        $data += app(\App\Services\IndustrialPresentation::class)->build($filters, $taxonomy, $data['contextArea'], $data['contextCompany']);
        return $data;
    }

    /**
     * Invalid hierarchy selection.
     */
    private function invalid(string $field): void
    {
        throw ValidationException::withMessages([
            $field =>
            'Choose an available '
                . $field
                . ' within the selected hierarchy.',
        ]);
    }

    /**
     * Individual live job opening.
     */
    public function opening(int $job)
    {
        $job = IndustrialJob::live()
            ->with([
                'area.state',
                'company',
                'department',
                'role',
            ])
            ->findOrFail($job);

        return view(
            'industrial.job',
            compact('job')
        );
    }

    /**
     * Get cities / districts for a state.
     *
     * India
     *   → Punjab
     *       → Ludhiana
     *       → Jalandhar
     *       → Rajpura
     */
    public function cities(Request $request)
    {
        $data = $request->validate([
            'state_id' => 'required|integer|min:1',
        ]);

        $state = IndustrialState::query()
            ->where('is_active', true)
            ->findOrFail(
                $data['state_id']
            );

        return response()->json(
            IndustrialArea::locationNames(
                $state->areas()->visible()
            )
        );
    }

    /**
     * Get industrial areas for state + city/district.
     */
    public function areas(Request $request)
    {
        $data = $request->validate([
            'state_id' => 'required|integer|min:1',
            'city' => 'nullable|string|max:255',
        ]);

        $state = IndustrialState::query()
            ->where('is_active', true)
            ->findOrFail(
                $data['state_id']
            );

        return response()->json(
            $state
                ->areas()
                ->visible()
                ->when(
                    ! empty($data['city']),
                    fn($q) => $q->atLocation(
                        $data['city']
                    )
                )
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'slug',
                    'city',
                    'district',
                ])
        );
    }

    /**
     * Get companies / plants inside an industrial area.
     *
     * Area
     *   → Companies / Plants
     */
    public function companies(int $area)
    {
        $area = IndustrialArea::visible()
            ->findOrFail($area);

        return response()->json(
            $area
                ->companies()
                ->visible()
                ->withCount([
                    'jobs as live_jobs_count' =>
                    fn($q) => $q->live(),
                ])
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Resolve an area using state + area slug.
     */
    private function resolveArea(
        string $stateSlug,
        string $areaSlug
    ): IndustrialArea {
        return IndustrialArea::visible()
            ->where(
                'slug',
                $areaSlug
            )
            ->whereHas(
                'state',
                fn($q) => $q->where(
                    'slug',
                    $stateSlug
                )
            )
            ->with('state')
            ->firstOrFail();
    }

    /**
     * State → Area page.
     */
    public function show(
        Request $request,
        string $stateSlug,
        string $areaSlug
    ) {
        $area = $this->resolveArea(
            $stateSlug,
            $areaSlug
        );

        $request->merge([
            'state' => $area->state_id,

            'location' =>
            $area->city
                ?: $area->district,

            'area' => $area->id,
        ]);

        $data = $this->data($request);

        return view(
            'industrial.index',
            $data
        );
    }

    /**
     * State → Area → Company page.
     */
    public function company(
        Request $request,
        string $stateSlug,
        string $areaSlug,
        string $companySlug
    ) {
        $area = $this->resolveArea(
            $stateSlug,
            $areaSlug
        );

        $company = $area
            ->companies()
            ->visible()
            ->where(
                'slug',
                $companySlug
            )
            ->firstOrFail();

        $request->merge([
            'state' => $area->state_id,

            'location' =>
            $area->city
                ?: $area->district,

            'area' => $area->id,

            'company' => $company->id,
        ]);

        $data = $this->data($request);

        return view(
            'industrial.index',
            $data
        );
    }

    /**
     * Backward-compatible area URL.
     */
    public function legacyArea(
        Request $request,
        string $areaSlug
    ) {
        $query = IndustrialArea::visible()
            ->where(
                'slug',
                $areaSlug
            )
            ->with('state');

        if ($request->filled('state')) {
            $query->whereHas(
                'state',
                fn($q) => $q->where(
                    'slug',
                    $request->query('state')
                )
            );
        }

        $matches = $query
            ->limit(2)
            ->get();

        abort_unless(
            $matches->count() === 1,
            404,
            'Use a state-scoped area URL.'
        );

        $area = $matches->first();

        return redirect()->route(
            'industrial.show',
            [
                $area->state->slug,
                $area->slug,
            ]
        );
    }

    /**
     * Backward-compatible company URL.
     */
    public function legacyCompany(
        Request $request,
        string $companySlug
    ) {
        $matches = IndustrialCompany::visible()
            ->where(
                'slug',
                $companySlug
            )
            ->with('area.state')
            ->limit(2)
            ->get();

        abort_unless(
            $matches->count() === 1,
            404,
            'Use an area-scoped company URL.'
        );

        $company = $matches->first();

        return redirect()->route(
            'industrial.company',
            [
                $company->area->state->slug,
                $company->area->slug,
                $company->slug,
            ]
        );
    }
}
