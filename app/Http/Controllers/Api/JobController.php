<?php

namespace App\Http\Controllers\Api;

use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Technology;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Support\DiscoveryCache;
use Illuminate\Support\Facades\Cache;

class JobController extends Controller
{
    /**
     * Get role categories for job discovery filters.
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => JobCategory::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Get all jobs with filters
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:150'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'job_type' => ['nullable', 'string', 'max:50'],
            'experience_level' => ['nullable', 'string', 'max:50'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'posting_source' => ['nullable', 'in:official_company,recruitment_agency,job_board'],
            'work_mode' => ['nullable', 'in:office,hybrid,remote,temporary_remote'],
            'role_family' => ['nullable', 'string', 'max:80'],
            'posted_within_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'technology_id' => ['nullable', 'integer', 'exists:technologies,id'],
            'category_id' => ['nullable', 'integer', 'exists:job_categories,id'],
            'q' => ['nullable', 'string', 'max:150'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'gte:salary_min'],
            'sort_by' => ['nullable', 'in:posted_at,salary,views'],
            'sort_order' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Job::active()->with('company', 'categories', 'technologies');

        // Filter by country
        if ($request->has('country')) {
            $query->country($request->get('country'));
        }

        // Filter by location
        if ($request->has('location')) {
            $query->location($request->get('location'));
        }

        // Filter by company
        if ($request->has('company_id')) {
            $query->company($request->get('company_id'));
        }

        // Filter by job type
        if ($request->has('job_type')) {
            $query->jobType($request->get('job_type'));
        }

        // Filter by experience level
        if ($request->has('experience_level')) {
            $query->experienceLevel($request->get('experience_level'));
        }

        if ($request->filled('experience_years')) {
            $years = (int) $request->get('experience_years');
            $query->where(function ($experience) use ($years) {
                $experience->whereNull('experience_min')->orWhere('experience_min', '<=', $years);
            })->where(function ($experience) use ($years) {
                $experience->whereNull('experience_max')->orWhere('experience_max', '>=', $years);
            });
        }

        foreach (['posting_source', 'work_mode', 'role_family'] as $filter) {
            if ($request->filled($filter)) $query->where($filter, $request->get($filter));
        }

        if ($request->filled('posted_within_days')) {
            $query->where('posted_at', '>=', now()->subDays((int) $request->get('posted_within_days')));
        }

        // Filter by technology
        if ($request->has('technology_id')) {
            $query->withTechnology($request->get('technology_id'));
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->withCategory($request->get('category_id'));
        }

        // Search by keyword
        if ($request->has('q')) {
            $query->search($request->get('q'));
        }

        // Filter by salary range
        if ($request->filled('salary_min')) $query->where('salary_max', '>=', $request->get('salary_min'));
        if ($request->filled('salary_max')) $query->where('salary_min', '<=', $request->get('salary_max'));

        // Sorting
        $sortBy = $validated['sort_by'] ?? 'posted_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        
        if ($sortBy === 'posted_at') {
            $query->orderBy('posted_at', $sortOrder);
        } elseif ($sortBy === 'salary') {
            $query->orderBy('salary_max', $sortOrder);
        } elseif ($sortBy === 'views') {
            $query->orderBy('views', $sortOrder);
        }

        // Pagination
        $perPage = $validated['per_page'] ?? 15;
        $jobs = Cache::remember(
            DiscoveryCache::key('jobs.index', $request->query()),
            DiscoveryCache::TTL_SECONDS,
            fn () => $query->paginate($perPage)
        );

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'pagination' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'from' => $jobs->firstItem(),
                'to' => $jobs->lastItem(),
            ],
        ]);
    }

    /**
     * Get single job details
     */
    public function show($id): JsonResponse
    {
        $job = Job::active()->with('company', 'categories', 'technologies')->find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found',
            ], 404);
        }

        // Increment view count
        $job->incrementViews();

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }

    /**
     * Get jobs by company
     */
    public function byCompany($companyId, Request $request): JsonResponse
    {
        $query = Job::active()
            ->where('company_id', $companyId)
            ->with('company', 'categories', 'technologies');

        // Apply other filters if needed
        if ($request->has('q')) {
            $query->search($request->get('q'));
        }

        $perPage = $request->get('per_page', 15);
        $jobs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'pagination' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    /**
     * Get recently posted jobs
     */
    public function recentJobs(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);
        $perPage = $request->get('per_page', 20);

        $jobs = Job::active()
            ->where('posted_at', '>=', now()->subDays($days))
            ->with('company', 'categories', 'technologies')
            ->newest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'pagination' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    /**
     * Get trending jobs by views
     */
    public function trending(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);

        $jobs = Job::active()
            ->with('company', 'categories', 'technologies')
            ->orderBy('views', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'pagination' => [
                'total' => $jobs->total(),
                'per_page' => $jobs->perPage(),
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    /**
     * Get job statistics
     */
    public function stats(): JsonResponse
    {
        $data = Cache::remember(DiscoveryCache::key('jobs.stats'), DiscoveryCache::TTL_SECONDS, fn () => [
            'total_jobs' => Job::active()->count(),
            'total_companies' => \App\Models\Company::active()->whereHas('activeJobs')->count(),
            'total_technologies' => Technology::count(),
            'jobs_by_country' => Job::active()->groupBy('country')->selectRaw('country, count(*) as count')->get(),
            'jobs_by_experience_level' => Job::active()->groupBy('experience_level')->selectRaw('experience_level, count(*) as count')->get(),
            'jobs_by_job_type' => Job::active()->groupBy('job_type')->selectRaw('job_type, count(*) as count')->get(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
