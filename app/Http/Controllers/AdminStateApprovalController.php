<?php

namespace App\Http\Controllers;

use App\Services\Approvals\StateApprovalImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Owner: each state's portal source, fetch runs, and per-service checklist edits (an edited service is locked from re-fetch). */
class AdminStateApprovalController extends Controller
{
    public function index(StateApprovalImporter $importer)
    {
        $importer->seedSources();
        $rows = DB::table('industrial_states as st')->leftJoin('state_approval_sources as s', 's.state_id', '=', 'st.id')->orderBy('st.name')
            ->select('st.id as state_id', 'st.name', 'st.slug', 'st.code', 's.id', 's.portal_name', 's.portal_url', 's.driver', 's.services_url', 's.policies_url', 's.acts_url', 's.is_active', 's.services_count', 's.last_status', 's.last_fetched_at')->get();

        return view('admin.approvals.index', ['rows' => $rows, 'drivers' => array_keys(StateApprovalImporter::DRIVERS)]);
    }

    public function saveSource(Request $request, int $state)
    {
        DB::table('industrial_states')->where('id', $state)->exists() || abort(404);
        $data = $request->validate(['portal_name' => 'nullable|string|max:255', 'portal_url' => 'nullable|url|max:500', 'driver' => ['nullable', Rule::in(array_keys(StateApprovalImporter::DRIVERS))],
            'services_url' => 'nullable|url|max:500', 'policies_url' => 'nullable|url|max:500', 'acts_url' => 'nullable|url|max:500', 'is_active' => 'nullable|boolean']);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        DB::table('state_approval_sources')->updateOrInsert(['state_id' => $state], $data + ['updated_at' => now(), 'created_at' => now()]);

        return back()->with('status', 'Portal settings saved.');
    }

    public function fetch(int $state)
    {
        $code = DB::table('industrial_states')->where('id', $state)->value('code') ?? abort(404);
        Artisan::queue('approvals:fetch', ['--state' => [$code]])->onConnection('database')->onQueue('ingestion');

        return back()->with('status', 'Fetch queued. Services, checklists and policies update within a few minutes.');
    }

    public function services(Request $request, int $state)
    {
        $st = DB::table('industrial_states')->where('id', $state)->first() ?? abort(404);
        $q = $request->validate(['q' => 'nullable|string|max:120'])['q'] ?? null;
        $services = DB::table('state_approval_services')->where('state_id', $state)
            ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('department', 'like', "%$q%")->orWhere('external_id', $q)))
            ->orderBy('department')->orderBy('name')->paginate(50)->withQueryString();

        return view('admin.approvals.services', ['state' => $st, 'services' => $services, 'q' => $q]);
    }

    public function edit(int $service)
    {
        $s = DB::table('state_approval_services')->where('id', $service)->first() ?? abort(404);

        return view('admin.approvals.edit', ['service' => $s, 'state' => DB::table('industrial_states')->find($s->state_id),
            'docs' => DB::table('state_approval_documents')->where('service_id', $service)->where('source', 'checklist')->orderBy('position')->get()]);
    }

    public function update(Request $request, int $service)
    {
        $s = DB::table('state_approval_services')->where('id', $service)->first() ?? abort(404);
        if ($request->has('unlock')) {
            DB::table('state_approval_services')->where('id', $service)->update(['locked' => false, 'checklist_hash' => null, 'updated_at' => now()]);
            return back()->with('status', 'Unlocked. The next fetch replaces this checklist with the portal version.');
        }
        $data = $request->validate(['is_active' => 'nullable|boolean', 'docs' => 'array|max:300', 'docs.*.name' => 'nullable|string|max:2000',
            'docs.*.condition' => 'nullable|string|max:500', 'docs.*.group_label' => 'nullable|string|max:500', 'docs.*.kind' => ['nullable', Rule::in(['document', 'option', 'noc', 'note'])],
            'docs.*.is_required' => ['nullable', Rule::in(['1', '0', ''])]]);
        DB::transaction(function () use ($data, $service) {
            DB::table('state_approval_services')->where('id', $service)->update(['is_active' => (bool) ($data['is_active'] ?? false), 'locked' => true, 'updated_at' => now()]);
            DB::table('state_approval_documents')->where('service_id', $service)->where('source', 'checklist')->delete();
            $pos = 0;
            foreach ($data['docs'] ?? [] as $d) {
                if (trim((string) ($d['name'] ?? '')) === '') continue;
                DB::table('state_approval_documents')->insert(['service_id' => $service, 'source' => 'checklist', 'position' => ++$pos, 'kind' => $d['kind'] ?? 'document', 'name' => trim($d['name']),
                    'condition' => ($d['condition'] ?? '') !== '' ? $d['condition'] : null, 'group_label' => ($d['group_label'] ?? '') !== '' ? $d['group_label'] : null,
                    'is_required' => ($d['is_required'] ?? '') === '' ? null : $d['is_required'] === '1', 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        return back()->with('status', 'Checklist saved and locked from automatic updates.');
    }
}
