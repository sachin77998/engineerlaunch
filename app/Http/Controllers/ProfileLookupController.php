<?php
namespace App\Http\Controllers;
use App\Models\Company;use App\Services\GeographyService;use App\Services\InstitutionLookupService;use Illuminate\Http\JsonResponse;use Illuminate\Http\Request;use Illuminate\Support\Facades\Cache;
class ProfileLookupController extends Controller
{
    public function states(Request $request, GeographyService $geography): JsonResponse
    {
        $data = $request->validate(['country' => 'required|string|max:100']);
        return response()->json($geography->states($data['country']));
    }

    public function cities(Request $request, GeographyService $geography): JsonResponse
    {
        $data = $request->validate(['country' => 'required|string|max:100', 'state' => 'required|string|max:150']);
        return response()->json($geography->cities($data['country'], $data['state']));
    }

    public function companies(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q'));
        if (mb_strlen($query) < 2) return response()->json([]);

        $results = Cache::remember('company-search:'.md5(mb_strtolower($query)), 3600, function () use ($query) {
            $database = Company::query()->where('name', 'like', '%'.$query.'%')->pluck('name');
            return $database->merge(config('resume.companies', []))
                ->filter(fn ($name) => is_string($name) && str_contains(mb_strtolower($name), mb_strtolower($query)))
                ->unique(fn ($name) => mb_strtolower($name))->sort()->take(30)->values();
        });

        return response()->json($results);
    }

    public function institutions(Request $request, InstitutionLookupService $institutions): JsonResponse
    {
        $data = $request->validate(['q' => 'required|string|min:2|max:100', 'country' => 'nullable|string|max:100']);
        return response()->json($institutions->search($data['q'], $data['country'] ?? 'India'));
    }
}
