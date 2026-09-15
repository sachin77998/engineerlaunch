<?php

namespace App\Http\Controllers;

use App\Models\IndustrialArea;
use App\Models\IndustrialState;
use App\Services\IndustrialAdminCatalog;
use App\Services\IndustrialCsvImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminIndustrialAreaController extends Controller
{
    public function index(Request $request, IndustrialAdminCatalog $catalog)
    {
        $filters = $request->validate(['type' => ['nullable', Rule::in(array_keys($catalog::LABELS))], 'search' => 'nullable|string|max:120', 'status' => ['nullable', Rule::in(['all', 'active', 'inactive', 'unverified', 'government_source', 'company_source', 'admin_verified', 'community_verified'])], 'city' => 'nullable|string|max:255', 'state_id' => 'nullable|integer|min:1']);
        $type = $filters['type'] ?? 'industrial_areas';
        $model = $catalog->model($type);
        $query = $model::query();
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($type, $filters) {
                $q->where($type === 'industrial_jobs' ? 'job_title' : 'name', 'like', '%'.$filters['search'].'%')->orWhere('source_name', 'like', '%'.$filters['search'].'%');
                if ($type === 'industrial_areas') {
                    $q->orWhere('city', 'like', '%'.$filters['search'].'%')->orWhere('district', 'like', '%'.$filters['search'].'%');
                }
            });
        }
        $status = $filters['status'] ?? 'all';
        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('is_active', $status === 'active');
        } elseif ($status !== 'all') {
            $query->where('verification_status', $status);
        }
        if ($type === 'industrial_areas') {
            $query->with('state');
            if (! empty($filters['city'])) {
                $query->where('city', $filters['city']);
            }
            if (! empty($filters['state_id'])) {
                $query->where('state_id', $filters['state_id']);
            }
        }
        if (in_array($type, ['industrial_companies', 'industrial_jobs'], true)) {
            $query->with('area.state');
        }
        $items = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return view('admin.industrial.index', ['items' => $items, 'type' => $type, 'labels' => $catalog::LABELS, 'filters' => $filters]);
    }

    public function form(Request $request, IndustrialAdminCatalog $catalog, string $type, ?int $record = null)
    {
        $model = $catalog->model($type);
        $item = $record ? $model::findOrFail($record) : new $model(['is_active' => true, 'verification_status' => 'unverified', 'salary_period' => 'monthly']);
        $values = array_merge($catalog->values($item), $request->session()->getOldInput());

        return view('admin.industrial.form', ['item' => $item, 'type' => $type, 'labels' => $catalog::LABELS, 'fields' => $catalog->fields($type), 'values' => $values, 'options' => $catalog->options($values)]);
    }

    public function save(Request $request, IndustrialAdminCatalog $catalog, IndustrialCsvImporter $importer, string $type, ?int $record = null)
    {
        $model = $catalog->model($type);
        if ($record !== null) {
            $model::findOrFail($record);
        }
        $data = $request->only(array_keys($catalog->fields($type)));
        try {
            $item = $importer->saveRecord($type, $data, $record);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['record' => $e->getMessage()]);
        }

        return redirect()->route('admin.industrial.edit', [$type, $item->id])->with('status', 'Record saved. Public visibility follows its active and verification settings.');
    }

    public function import(Request $request, IndustrialCsvImporter $importer)
    {
        $data = $request->validate(['type' => ['required', Rule::in(IndustrialCsvImporter::TYPES)], 'csv' => 'required|file|max:10240', 'dry_run' => 'required|boolean']);
        try {
            $count = $importer->import($data['type'], $request->file('csv')->getRealPath(), (bool) $data['dry_run']);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['csv' => $e->getMessage()]);
        }

        return redirect()->route('admin.industrial.index', ['type' => $data['type']])->with('status', $data['dry_run'] ? 'Validated '.$count.' rows. No changes saved.' : 'Imported '.$count.' rows.');
    }

    public function lookups(Request $request, IndustrialAdminCatalog $catalog)
    {
        $values = $request->validate(['state_slug' => 'nullable|string|max:255', 'area_slug' => 'nullable|string|max:255', 'department_slug' => 'nullable|string|max:255']);

        return response()->json(array_intersect_key($catalog->options($values), array_flip(['area_slug', 'company_slug', 'role_slug'])));
    }

    public function cities(Request $request, IndustrialAdminCatalog $catalog)
    {
        $data = $request->validate(['state_id' => 'nullable|integer|min:1']);
        $items = IndustrialArea::query()->when(! empty($data['state_id']), fn ($q) => $q->where('state_id', $data['state_id']))->selectRaw('state_id, city, district, COUNT(*) as areas_count')->groupBy('state_id', 'city', 'district')->with('state')->orderBy('city')->paginate(25)->withQueryString();

        return view('admin.industrial.cities', ['items' => $items, 'states' => IndustrialState::orderBy('name')->get(), 'labels' => $catalog::LABELS]);
    }

    public function updateCity(Request $request)
    {
        $data = $request->validate(['state_id' => 'required|integer|exists:industrial_states,id', 'old_city' => 'nullable|string|max:255', 'old_district' => 'nullable|string|max:255', 'city' => 'nullable|string|max:255|required_without:district', 'district' => 'nullable|string|max:255|required_without:city']);
        $count = DB::transaction(fn () => IndustrialArea::where('state_id', $data['state_id'])->where('city', $data['old_city'] ?? null)->where('district', $data['old_district'] ?? null)->update(['city' => $data['city'] ?? null, 'district' => $data['district'] ?? null, 'updated_at' => now()]));

        return back()->with('status', 'Updated location labels for '.$count.' area records.');
    }

    public function sources(Request $request, IndustrialAdminCatalog $catalog)
    {
        $union = null;
        foreach ($catalog::MODELS as $type => $model) {
            $query = $model::query()->selectRaw('? as record_type, id, '.($type === 'industrial_jobs' ? 'job_title' : 'name').' as name, source_name, source_url, verification_status, last_verified_at', [$type]);
            if ($union === null) {
                $union = $query;
            } else {
                $union->unionAll($query);
            }
        }
        $status = $request->validate(['status' => ['nullable', Rule::in(['unverified', 'government_source', 'company_source', 'admin_verified', 'community_verified'])]])['status'] ?? null;
        $items = DB::query()->fromSub($union, 'sources')->when($status, fn ($q) => $q->where('verification_status', $status))->orderBy('record_type')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('admin.industrial.sources', ['items' => $items, 'labels' => $catalog::LABELS]);
    }
}
