<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SearchSuggestionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required','string','max:80'], 'type' => ['nullable','in:keyword,location']]);
        $term = trim($data['q']);
        if (mb_strlen($term) < 1) return response()->json(['data' => []]);

        $type = $data['type'] ?? 'keyword';
        $key = 'search-suggestions:'.sha1(mb_strtolower($type.'|'.$term));
        $suggestions = Cache::remember($key, now()->addMinutes(10), function () use ($term, $type) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $prefix = addcslashes($term, '%_\\').'%';
            if ($type === 'location') {
                return DB::table('jobs')->whereNull('deleted_at')->where('is_active', true)
                    ->where(fn ($q) => $q->where('location','like',$like)->orWhere('state','like',$like)->orWhere('country','like',$like))
                    ->selectRaw("COALESCE(NULLIF(location,''), NULLIF(state,''), country) as value")
                    ->whereNotNull(DB::raw("COALESCE(NULLIF(location,''), NULLIF(state,''), country)"))
                    ->distinct()->orderByRaw('CASE WHEN location LIKE ? THEN 0 ELSE 1 END', [$prefix])->limit(10)->pluck('value')
                    ->map(fn ($value) => ['value'=>$value,'type'=>'Location'])->values()->all();
            }

            $companies = DB::table('companies')->where('is_active', true)->where('name','like',$like)
                ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$prefix])->limit(6)->pluck('name')->map(fn ($v)=>['value'=>$v,'type'=>'Company']);
            $technologies = DB::table('technologies')->where('name','like',$like)
                ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$prefix])->limit(6)->pluck('name')->map(fn ($v)=>['value'=>$v,'type'=>'Skill']);
            $titles = DB::table('jobs')->whereNull('deleted_at')->where('is_active', true)->where('title','like',$like)
                ->distinct()->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [$prefix])->limit(6)->pluck('title')->map(fn ($v)=>['value'=>$v,'type'=>'Job title']);
            return $companies->concat($technologies)->concat($titles)->unique(fn ($item)=>mb_strtolower($item['value']))->take(12)->values()->all();
        });

        return response()->json(['data' => $suggestions]);
    }
}
