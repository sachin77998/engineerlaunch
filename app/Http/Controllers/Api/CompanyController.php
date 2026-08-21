<?php

namespace App\Http\Controllers\Api;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Support\DiscoveryCache;
use Illuminate\Support\Facades\Cache;

class CompanyController extends Controller
{
    /**
     * Get all companies
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::active();

        // Public discovery must only show companies backed by synchronized,
        // currently active listings. Administrators can explicitly request the
        // pending source catalogue with include_empty=1.
        if (!$request->boolean('include_empty')) {
            $query->whereHas('activeJobs');
        }

        // Filter by country
        if ($request->has('country')) {
            $query->country($request->get('country'));
        }

        // Filter by sector
        if ($request->has('sector')) {
            $query->sector($request->get('sector'));
        }

        // Filter by industry
        if ($request->has('industry')) {
            $query->where('industry', $request->get('industry'));
        }

        // Search by name
        if ($request->has('q')) {
            $query->where('name', 'LIKE', "%{$request->get('q')}%");
        }

        // Include job counts
        $query->withCount('activeJobs');

        $perPage = $request->get('per_page', 20);
        $companies = Cache::remember(
            DiscoveryCache::key('companies.index', $request->query()),
            DiscoveryCache::TTL_SECONDS,
            fn () => $query->orderBy('name')->paginate($perPage)
        );

        return response()->json([
            'success' => true,
            'data' => $companies->items(),
            'pagination' => [
                'total' => $companies->total(),
                'per_page' => $companies->perPage(),
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
            ],
        ]);
    }

    /**
     * Get single company details
     */
    public function show($id): JsonResponse
    {
        $company = Company::active()
            ->withCount('activeJobs')
            ->find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
            ], 404);
        }

        // Get recent jobs for this company
        $recentJobs = $company->activeJobs()
            ->latest('posted_at')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'company' => $company,
                'recent_jobs' => $recentJobs,
            ],
        ]);
    }

    /**
     * Get companies by country
     */
    public function byCountry($country, Request $request): JsonResponse
    {
        $companies = Company::active()
            ->whereHas('activeJobs')
            ->country($country)
            ->withCount('activeJobs')
            ->orderBy('name')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $companies->items(),
            'pagination' => [
                'total' => $companies->total(),
                'per_page' => $companies->perPage(),
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
            ],
        ]);
    }

    /**
     * Get top hiring companies
     */
    public function topHiring(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        $companies = Company::active()
            ->whereHas('activeJobs')
            ->withCount('activeJobs')
            ->orderBy('active_jobs_count', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $companies,
        ]);
    }

    /**
     * Get all unique countries
     */
    public function countries(): JsonResponse
    {
        $countries = Company::active()
            ->distinct()
            ->pluck('country')
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $countries,
        ]);
    }

    /**
     * Get all unique sectors
     */
    public function sectors(): JsonResponse
    {
        $sectors = Company::active()
            ->whereNotNull('sector')
            ->distinct()
            ->pluck('sector')
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $sectors,
        ]);
    }
}
