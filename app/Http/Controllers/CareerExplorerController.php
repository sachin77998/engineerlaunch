<?php

namespace App\Http\Controllers;

use App\Services\CareerOpportunitySearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CareerExplorerController extends Controller
{
    public function index(Request $request, CareerOpportunitySearch $search)
    {
        return view('career-explorer', $search->search($request->query()) + ['options' => $this->options()]);
    }

    public function api(Request $request, CareerOpportunitySearch $search)
    {
        return response()->json($search->search($request->query()));
    }

    /** Cities and industrial areas for one state (dropdowns load them when a state is picked). */
    public function places(Request $request)
    {
        $state = mb_substr(trim((string) $request->query('state')), 0, 100);
        $country = mb_substr(trim((string) $request->query('country')), 0, 100);
        if ($state === '') return response()->json(['cities' => [], 'areas' => []]);
        $data = Cache::remember('career-explorer:places:' . md5($country . '|' . $state), now()->addHours(6), function () use ($state, $country) {
            $facilities = DB::table('company_facilities')->where('state', $state)->when($country !== '', fn ($q) => $q->where('country', $country));
            $cities = (clone $facilities)->whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->limit(800)->pluck('city');
            $areas = (clone $facilities)->whereNotNull('industrial_area')->where('industrial_area', '!=', '')->distinct()->pluck('industrial_area')
                ->merge(DB::table('industrial_areas as a')->join('industrial_states as s', 's.id', '=', 'a.state_id')->where('s.name', $state)->where('a.is_active', true)->pluck('a.name'))
                ->unique()->sort()->values();
            return ['cities' => $cities->values(), 'areas' => $areas];
        });
        return response()->json($data)->header('Cache-Control', 'public, max-age=3600');
    }

    /** Values for the filter dropdowns, from the data the search runs on (cached). */
    private function options(): array
    {
        return Cache::remember('career-explorer:options', now()->addHours(6), function () {
            $facilityCountries = DB::table('company_facilities')->whereNotNull('country')->where('country', '!=', '')->distinct()->pluck('country');
            $jobCountries = DB::table('jobs')->where('is_active', true)->whereNotNull('country')->where('country', '!=', '')->distinct()->pluck('country');
            $countries = $facilityCountries->merge($jobCountries)->map(fn ($c) => trim($c))->filter(fn ($c) => mb_strlen($c) > 2)->unique()->sort()->values();
            if ($countries->contains('India')) $countries = $countries->reject(fn ($c) => $c === 'India')->prepend('India')->values();
            // States by country; region codes of two letters (e.g. "DU") are left out.
            $states = DB::table('company_facilities')->whereNotNull('state')->whereNotNull('country')->select('country', 'state')->distinct()->get()
                ->filter(fn ($r) => mb_strlen(trim($r->state)) > 2)->groupBy('country')->map(fn ($g) => $g->pluck('state')->map(fn ($s) => trim($s))->unique()->sort()->values());
            $industries = DB::table('company_categories')->where('taxonomy', 'sector')->whereNull('parent_id')->orderBy('name')->pluck('name')
                ->merge(config('career_catalog.sectors', []))->unique()->sort()->values();
            $tracks = Schema::hasTable('career_tracks') ? DB::table('career_tracks') : null;
            $roles = Schema::hasTable('career_roles') ? DB::table('career_roles') : null;
            return [
                'countries' => $countries, 'states' => $states, 'industries' => $industries,
                'departments' => $tracks ? $tracks->distinct()->orderBy('department')->pluck('department')->filter()->values() : collect(),
                'job_families' => $roles ? $roles->distinct()->orderBy('job_family')->pluck('job_family')->filter()->values() : collect(),
                'roles' => $roles ? $roles->distinct()->orderBy('name')->pluck('name')->filter()->values() : collect(),
            ];
        });
    }
}
