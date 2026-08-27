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
    public function index(Request $request): JsonResponse
    {
        $query = Company::active();
        if (!$request->boolean('include_empty')) {$query->whereHas('activeJobs');}
        if ($request->has('country')) {$query->country($request->get('country'));}
        if ($request->has('sector')) {$query->sector($request->get('sector'));}
        if ($request->has('industry')) {$query->where('industry', $request->get('industry'));}
        if ($request->has('q')) {$query->where('name', 'LIKE', "%{$request->get('q')}%");}
        $query->withCount('activeJobs');
        $perPage = min(50, max(1, (int) $request->get('per_page', 50)));
        $companies = Cache::remember(
            DiscoveryCache::key('companies.index', $request->query()),
            DiscoveryCache::ttl(),
            fn () => $query->orderBy('name')->paginate($perPage)
        );

        return response()->json(['success' => true,'data' => $companies->items(),
            'pagination' => ['total' => $companies->total(),'per_page' => $companies->perPage(),'current_page' => $companies->currentPage(),'last_page' => $companies->lastPage(),],
        ]);
    }
    public function show($id): JsonResponse
    {
        $company = Company::active()->withCount('activeJobs')->find($id);
        if (!$company) {return response()->json(['success' => false,'message' => 'Company not found',], 404);}
        $recentJobs = $company->activeJobs()->latest('posted_at')->limit(5)->get();
        return response()->json(['success' => true,'data' => ['company' => $company,'recent_jobs' => $recentJobs,],]);
    }
    public function byCountry($country, Request $request): JsonResponse
    {
        $companies = Company::active()->whereHas('activeJobs')->country($country)->withCount('activeJobs')->orderBy('name')->paginate(min(50, max(1, (int) $request->get('per_page', 50))));
        return response()->json(['success' => true,'data' => $companies->items(),
            'pagination' => ['total' => $companies->total(),'per_page' => $companies->perPage(),'current_page' => $companies->currentPage(),'last_page' => $companies->lastPage(),],
        ]);
    }
    public function topHiring(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $companies = Company::active()->whereHas('activeJobs')->withCount('activeJobs')->orderBy('active_jobs_count', 'desc')->limit($limit)->get();
        return response()->json(['success' => true,'data' => $companies,]);
    }
    public function countries(): JsonResponse
    {
        $countries = Company::active()->distinct()->pluck('country')->sort()->values();
        return response()->json(['success' => true,'data' => $countries,]);
    }   
    public function sectors(): JsonResponse
    {
        $sectors = Company::active()->whereNotNull('sector')->distinct()->pluck('sector')->sort()->values();
        return response()->json(['success' => true,'data' => $sectors,]);
    }
}
