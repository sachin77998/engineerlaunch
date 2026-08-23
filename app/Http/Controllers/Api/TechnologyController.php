<?php

namespace App\Http\Controllers\Api;

use App\Models\Technology;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Support\DiscoveryCache;
use Illuminate\Support\Facades\Cache;

class TechnologyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Technology::query();
        if ($request->has('category')) {$query->category($request->get('category'));}
        if ($request->has('q')) { $query->search($request->get('q'));}
        $query->withCount('jobs');
        $perPage = $request->get('per_page', 50);
        $technologies = Cache::remember(DiscoveryCache::key('technologies.index', $request->query()),DiscoveryCache::ttl(),fn () => $query->orderBy('name')->paginate($perPage));

        return response()->json(['success' => true,'data' => $technologies->items(),
            'pagination' => ['total' => $technologies->total(),'per_page' => $technologies->perPage(),'current_page' => $technologies->currentPage(),'last_page' => $technologies->lastPage(),],
        ]);
    }
    public function byCategory($category, Request $request): JsonResponse
    {
        $technologies = Technology::category($category)->withCount('jobs')->orderBy('name')->paginate($request->get('per_page', 50));
        return response()->json(['success' => true,'data' => $technologies->items(),
            'pagination' => ['total' => $technologies->total(),'per_page' => $technologies->perPage(),'current_page' => $technologies->currentPage(),'last_page' => $technologies->lastPage(),],]);
    }
    public function categories(): JsonResponse
    {
        $categories = Technology::whereNotNull('category')->distinct()->pluck('category')->sort()->values();
        return response()->json(['success' => true,'data' => $categories,]);
    }
    public function trending(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $technologies = Technology::withCount('jobs')->orderBy('jobs_count', 'desc')->limit($limit)->get();
        return response()->json(['success' => true,'data' => $technologies,]);
    }

    public function show($id, Request $request): JsonResponse
    {
        $technology = Technology::with('jobs')->find($id);
        if (!$technology) {return response()->json(['success' => false,'message' => 'Technology not found',], 404);}
        $perPage = $request->get('per_page', 15);
        $jobs = $technology->jobs()->active()->paginate($perPage);
        return response()->json(['success' => true,
            'data' => [
                'technology' => $technology,'jobs' => $jobs->items(),
                'pagination' => ['total' => $jobs->total(),'per_page' => $jobs->perPage(),'current_page' => $jobs->currentPage(),'last_page' => $jobs->lastPage(),],
            ],
        ]);
    }
}
