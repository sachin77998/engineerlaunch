<?php

namespace App\Http\Controllers;

use App\Services\SectorDirectory;
use Illuminate\Http\Request;

/**
 * Companies by Sector, three levels of cards: category (e.g. Automobile & Ancillaries) -> sub-category (Gears, Axles,
 * Forging…) -> companies. Search and location filters keep listing matching companies directly.
 */
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
        return view('sectors.index', ['tree' => $tree, 'results' => $results, 'openings' => $openings,
            'hubs' => $this->directory->hubs(), 'cards' => $results === null ? $this->directory->safeGroups() : collect()] + $this->base($filters));
    }

    /** A category card opened: its sub-categories as cards. */
    public function group(Request $request, string $group)
    {
        try { $card = $this->directory->group($group); } catch (\Throwable $e) { report($e); return redirect()->route('sectors.index'); }
        abort_unless($card, 404);
        $companyIds = \Illuminate\Support\Facades\DB::table('company_category_company')->whereIn('company_category_id', $card->subcategories->pluck('id'))->distinct()->limit(500)->pluck('company_id');
        $plantSectors = $this->directory->tree()->whereIn('slug', config('plant_flow.sectors', []))->pluck('id');
        $flow = $card->subcategories->contains(fn ($child) => $plantSectors->contains($child->parent_id)) ? config('plant_flow.stages') : [];
        return view('sectors.index', ['tree' => $this->directory->tree(), 'card' => $card, 'flow' => $flow, 'liveRoles' => $this->directory->liveRoles($card->subcategories->pluck('id')),
            'openings' => $this->directory->openings($companyIds, [])] + $this->base([]));
    }

    public function show(Request $request, string $slug)
    {
        $sector = $this->directory->find($slug);
        abort_unless($sector, 404);
        $filters = $this->filters($request);
        $flow = in_array($sector->slug, config('plant_flow.sectors', []), true) ? config('plant_flow.stages') : [];
        // Filtered: list matching companies grouped by sub-category. Otherwise: sub-category cards.
        $groups = $filters || $request->has('all') || $request->wantsJson() ? $this->directory->sector($sector, $filters) : collect();
        $companyIds = $groups->isNotEmpty()
            ? $groups->flatMap(fn ($group) => $group->companies->pluck('id'))->unique()->values()
            : \Illuminate\Support\Facades\DB::table('company_category_company')->whereIn('company_category_id', $sector->children->pluck('id'))->distinct()->limit(500)->pluck('company_id');
        $openings = $this->directory->openings($companyIds, $filters);
        if ($request->wantsJson()) return response()->json(['sector' => $sector, 'groups' => $groups, 'openings' => $openings]);
        return view('sectors.index', ['tree' => $this->directory->tree(), 'sector' => $sector, 'groups' => $groups, 'liveRoles' => $this->directory->liveRoles($sector->children->pluck('id')),
            'openings' => $openings, 'flow' => $flow, 'card' => $groups->isEmpty() && !$filters ? $this->directory->sectorCard($sector) : null] + $this->base($filters));
    }

    /** A sub-category opened: its companies as cards with logos, paginated. */
    public function subcategory(Request $request, string $slug, string $sub)
    {
        [$sector, $child] = $this->directory->subcategory($slug, $sub) ?? abort(404);
        $filters = $this->filters($request);
        $companies = $this->directory->subcategoryCompanies($child, $filters);
        return view('sectors.index', ['tree' => $this->directory->tree(), 'sector' => $sector, 'subcategory' => $child, 'liveRoles' => $this->directory->liveRoles(collect([$child->id])),
            'companies' => $companies, 'openings' => $this->directory->openings($companies->getCollection()->pluck('id'), $filters)] + $this->base($filters));
    }

    private function base(array $filters): array
    {
        return ['sector' => null, 'groups' => collect(), 'results' => null, 'filters' => $filters, 'locations' => $this->directory->locations(),
            'hubs' => collect(), 'card' => null, 'cards' => collect(), 'subcategory' => null, 'companies' => null, 'flow' => [], 'liveRoles' => []];
    }

    private function filters(Request $request): array
    {
        $rules = array_fill_keys(SectorDirectory::FILTERS, 'nullable|string|max:120');
        return array_map(fn ($value) => is_string($value) ? trim($value) : $value, array_filter($request->validate($rules), fn ($value) => filled($value)));
    }
}
