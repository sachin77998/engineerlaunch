<?php

namespace App\Http\Controllers;

use App\Services\SectorDirectory;
use Illuminate\Http\Request;

class SectorDirectoryController extends Controller
{
    public function __construct(private SectorDirectory $directory) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $tree = $this->directory->tree();
        $results = array_filter($filters) ? $this->directory->search($filters) : null;
        $openings = $results ? $this->directory->openings($results->pluck('id'), $filters) : collect();
        if ($request->wantsJson()) return response()->json(['sectors' => $tree, 'results' => $results, 'openings' => $openings]);
        return view('sectors.index', ['tree' => $tree, 'sector' => null, 'groups' => collect(), 'results' => $results,
            'openings' => $openings, 'filters' => $filters, 'locations' => $this->directory->locations(), 'hubs' => $this->directory->hubs()]);
    }

    public function show(Request $request, string $slug)
    {
        $sector = $this->directory->find($slug);
        abort_unless($sector, 404);
        $filters = $this->filters($request);
        $groups = $this->directory->sector($sector, $filters);
        $openings = $this->directory->openings($groups->flatMap(fn ($group) => $group->companies->pluck('id'))->unique()->values(), $filters);
        if ($request->wantsJson()) return response()->json(['sector' => $sector, 'groups' => $groups, 'openings' => $openings]);
        return view('sectors.index', ['tree' => $this->directory->tree(), 'sector' => $sector, 'groups' => $groups, 'results' => null,
            'openings' => $openings, 'filters' => $filters, 'locations' => $this->directory->locations(), 'hubs' => collect(),
            'flow' => in_array($sector->slug, config('plant_flow.sectors', []), true) ? config('plant_flow.stages') : []]);
    }

    private function filters(Request $request): array
    {
        $rules = array_fill_keys(SectorDirectory::FILTERS, 'nullable|string|max:120');
        return array_map(fn ($value) => is_string($value) ? trim($value) : $value, array_filter($request->validate($rules), fn ($value) => filled($value)));
    }
}
