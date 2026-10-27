<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** State-wise business approvals (licences, NOCs, connections) with their document checklists, policies and acts. */
class StateApprovalController extends Controller
{
    private const FILTERS = ['size' => 'unit_sizes', 'location' => 'locations', 'risk' => 'risk_categories', 'investor' => 'investor_types'];

    public function index()
    {
        $states = DB::table('industrial_states as st')->leftJoin('state_approval_sources as s', 's.state_id', '=', 'st.id')
            ->where('st.is_active', true)->orderBy('st.name')
            ->select('st.id', 'st.name', 'st.slug', 's.portal_name', 's.portal_url', 's.services_count', 's.last_fetched_at')->get();
        $policyCounts = DB::table('state_policies')->where('is_active', true)->groupBy('state_id')->selectRaw('state_id, COUNT(DISTINCT title) as n')->pluck('n', 'state_id');

        return view('approvals.index', ['states' => $states, 'policyCounts' => $policyCounts, 'national' => config('state_approvals.national')]);
    }

    public function state(Request $request, string $stateSlug)
    {
        $state = DB::table('industrial_states')->where('slug', $stateSlug)->where('is_active', true)->first() ?? abort(404);
        $source = DB::table('state_approval_sources')->where('state_id', $state->id)->first();
        $f = $request->validate(['q' => 'nullable|string|max:120', 'department' => 'nullable|string|max:255', 'stage' => 'nullable|string|max:120',
            'size' => 'nullable|string|max:60', 'location' => 'nullable|string|max:60', 'risk' => 'nullable|string|max:60', 'investor' => 'nullable|string|max:60',
            'tab' => 'nullable|in:services,policies']);

        $base = DB::table('state_approval_services')->where('state_id', $state->id)->where('is_active', true);
        $all = (clone $base)->get(['department', 'stages', 'unit_sizes', 'locations', 'risk_categories', 'investor_types']);
        $options = ['department' => $all->pluck('department')->filter()->unique()->sort()->values()];
        foreach (['stage' => 'stages'] + self::FILTERS as $key => $col) {
            $options[$key] = $all->flatMap(fn ($r) => json_decode((string) $r->$col, true) ?: [])->unique()->values();
        }

        $query = (clone $base)
            // Every word must match name or department; plural / -y endings are trimmed so "factory" finds "Factories".
            ->when($f['q'] ?? null, function ($q, $s) {
                foreach (preg_split('~\s+~', trim($s)) as $word) {
                    $stem = mb_strlen($word) > 4 ? preg_replace('~(ies|es|s|y)$~i', '', $word) : $word;
                    $q->where(fn ($w) => $w->where('name', 'like', "%$stem%")->orWhere('department', 'like', "%$stem%"));
                }
            })
            ->when($f['department'] ?? null, fn ($q, $d) => $q->where('department', $d))
            ->when($f['stage'] ?? null, fn ($q, $s) => $q->whereJsonContains('stages', $s));
        foreach (self::FILTERS as $key => $col) {
            // A service with no published applicability applies to everyone.
            if (!empty($f[$key])) $query->where(fn ($w) => $w->whereJsonContains($col, $f[$key])->orWhereNull($col)->orWhere($col, '[]'));
        }
        $stageCounts = (clone $query)->get(['stages'])->flatMap(fn ($r) => json_decode((string) $r->stages, true) ?: ['Other'])->countBy()->sortDesc();
        $services = $query->orderBy('department')->orderBy('name')->paginate(40)->withQueryString();
        $docCounts = DB::table('state_approval_documents')->whereIn('service_id', $services->pluck('id'))->whereIn('kind', ['document', 'option', 'noc'])
            ->groupBy('service_id')->selectRaw('service_id, SUM(source = \'checklist\') as checklist, SUM(source = \'portal\') as portal, SUM(source = \'manual\') as manual')->get()->keyBy('service_id');

        $policies = DB::table('state_policies')->where('state_id', $state->id)->where('is_active', true)->orderBy('kind')->orderBy('title')->get()
            ->groupBy(fn ($p) => $p->kind . '|' . $p->title)->map(fn ($g) => (object) ['kind' => $g[0]->kind, 'title' => $g[0]->title, 'department' => $g[0]->department, 'links' => $g])->groupBy('kind');

        return view('approvals.state', compact('state', 'source', 'f', 'options', 'services', 'docCounts', 'stageCounts', 'policies') + ['national' => config('state_approvals.national')]);
    }

    public function service(string $stateSlug, string $externalId)
    {
        $state = DB::table('industrial_states')->where('slug', $stateSlug)->first() ?? abort(404);
        $service = DB::table('state_approval_services')->where('state_id', $state->id)->where('external_id', $externalId)->where('is_active', true)->first() ?? abort(404);
        $docs = DB::table('state_approval_documents')->where('service_id', $service->id)->orderBy('position')->get()->groupBy('source');
        $source = DB::table('state_approval_sources')->where('state_id', $state->id)->first();
        $related = DB::table('state_approval_services')->where('state_id', $state->id)->where('is_active', true)->where('department', $service->department)
            ->where('id', '!=', $service->id)->orderBy('name')->limit(12)->get(['external_id', 'name']);

        return view('approvals.service', ['state' => $state, 'service' => $service, 'checklist' => $docs['checklist'] ?? collect(), 'portalDocs' => $docs['portal'] ?? collect(), 'manual' => $docs['manual'] ?? collect(), 'source' => $source, 'related' => $related]);
    }
}
