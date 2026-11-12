<?php

namespace App\Http\Controllers;

use App\Models\Property\Builder;
use App\Models\Property\Project;
use App\Models\Property\ProjectImage;
use App\Models\Property\ProjectOffer;
use App\Models\Property\PropertyCity;
use App\Models\Property\PropertyLocality;
use App\Models\Property\PropertyOption;
use App\Services\Property\PropertyCache;
use App\Services\Property\PropertyOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// Admin for the property catalogue: option lists (one table each), cities / zones / localities, builders, and projects with photos and offers.
class PropertyCatalogAdminController extends Controller
{
    public function __construct(private PropertyOptions $options) {}

    // ---- Option lists ----

    public function options(Request $request)
    {
        $groups = $this->options->groups();
        $group = array_key_exists($request->query('group'), $groups) ? $request->query('group') : array_key_first($groups);
        $rows = PropertyOption::in($group)->orderBy('sort')->orderBy('id')->get();
        return view('properties.admin.options', compact('groups', 'group', 'rows'));
    }

    public function saveOptions(Request $request, string $group)
    {
        abort_unless(array_key_exists($group, $this->options->groups()), 404);
        $data = $request->validate([
            'rows' => 'nullable|array', 'rows.*.label' => 'required|string|max:160', 'rows.*.extra' => 'nullable|json', 'rows.*.sort' => 'nullable|integer|min:0|max:65000', 'rows.*.is_active' => 'nullable|boolean',
            'new.key' => 'nullable|required_with:new.label|string|max:60|regex:/^[a-z0-9_]+$/', 'new.label' => 'nullable|required_with:new.key|string|max:160', 'new.extra' => 'nullable|json',
        ], ['new.key.regex' => 'The key may only use lower-case letters, digits and underscores.']);
        $table = PropertyOption::in($group);
        foreach ($data['rows'] ?? [] as $id => $row) {
            PropertyOption::in($group)->whereKey($id)->update(['label' => $row['label'], 'extra' => $this->extra($row['extra'] ?? null), 'sort' => (int) ($row['sort'] ?? 0),
                'is_active' => !empty($row['is_active']), 'updated_at' => now()]);
        }
        if (!empty($data['new']['key'])) {
            abort_if(PropertyOption::in($group)->where('key', $data['new']['key'])->exists(), 422, 'That key already exists.');
            $table->insert(['key' => $data['new']['key'], 'label' => $data['new']['label'], 'extra' => $this->extra($data['new']['extra'] ?? null),
                'sort' => (int) PropertyOption::in($group)->max('sort') + 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->options->forget();
        return back()->with('success', 'Saved.');
    }

    private function extra(?string $json): ?string
    {
        if ($json === null || trim($json) === '') return null;
        $v = json_decode($json, true);
        return json_encode(array_values((array) $v), JSON_UNESCAPED_UNICODE);
    }

    // ---- Cities, zones and localities ----

    public function cities(Request $request)
    {
        $cities = PropertyCity::withCount('localities')->orderBy('country')->orderBy('sort')->orderBy('name')->get();
        $city = $request->query('city') ? PropertyCity::with(['localities' => fn ($q) => $q->orderBy('zone')->orderBy('name')])->find($request->query('city')) : null;
        return view('properties.admin.cities', compact('cities', 'city'));
    }

    public function saveCity(Request $request, ?PropertyCity $city = null)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80', 'state' => 'nullable|string|max:80', 'country' => 'required|string|max:60',
            'zones' => 'nullable|string|max:1000', 'sort' => 'nullable|integer|min:0|max:65000', 'is_active' => 'nullable|boolean',
            'latitude' => 'nullable|numeric|between:-90,90', 'longitude' => 'nullable|numeric|between:-180,180',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096', 'image_url' => 'nullable|url|max:500',
        ]);
        $city ??= new PropertyCity(['slug' => Str::slug($data['name'])]);
        abort_if(!$city->exists && PropertyCity::where('slug', $city->slug)->exists(), 422, 'That city already exists.');
        $city->fill(collect($data)->except(['image', 'zones'])->all() + [
            'zones' => collect(explode(',', $data['zones'] ?? ''))->map(fn ($z) => trim($z))->filter()->unique()->values()->all() ?: null,
            'is_active' => $request->boolean('is_active'), 'sort' => (int) ($data['sort'] ?? 0)]);
        if ($request->hasFile('image')) {
            if ($city->image_path) Storage::disk('public')->delete($city->image_path);
            $city->image_path = $request->file('image')->store('property-cities', 'public');
        }
        $city->save();
        $this->options->forget();
        PropertyCache::bump('_all'); // name and photo show on every city's "popular cities"
        return redirect()->route('admin.property-cities.index', ['city' => $city->id])->with('success', 'City saved.');
    }

    public function saveLocalities(Request $request, PropertyCity $city)
    {
        $data = $request->validate(['rows' => 'nullable|array', 'rows.*.zone' => 'nullable|string|max:80', 'rows.*.is_active' => 'nullable|boolean',
            'new' => 'nullable|string|max:5000', 'new_zone' => 'nullable|string|max:80']);
        foreach ($data['rows'] ?? [] as $id => $row) {
            PropertyLocality::where('city_id', $city->id)->whereKey($id)->update(['zone' => $row['zone'] ?: null, 'is_active' => !empty($row['is_active'])]);
        }
        foreach (preg_split('/[\r\n]+/', (string) ($data['new'] ?? '')) as $name) {
            $name = trim($name);
            if ($name === '' || !$slug = Str::slug($name)) continue;
            PropertyLocality::firstOrCreate(['city_id' => $city->id, 'slug' => $slug], ['name' => $name, 'zone' => $data['new_zone'] ?? null, 'is_active' => true]);
        }
        $this->options->forget();
        PropertyCache::bump($city->slug);
        return back()->with('success', 'Localities saved.');
    }

    // ---- Builders ----

    public function builders()
    {
        return view('properties.admin.builders', ['builders' => Builder::withCount('projects')->orderBy('name')->get()]);
    }

    public function saveBuilder(Request $request, ?Builder $builder = null)
    {
        $data = $request->validate(['name' => 'required|string|max:160', 'website' => 'nullable|url|max:191',
            'phone' => ['nullable', 'regex:/^\+?[0-9 ()-]{7,20}$/'], 'email' => 'nullable|email|max:191', 'established_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'description' => 'nullable|string|max:5000', 'is_active' => 'nullable|boolean', 'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048']);
        $builder ??= new Builder(['slug' => $this->uniqueSlug(Builder::class, $data['name'])]);
        $builder->fill(collect($data)->except('logo')->all() + ['is_active' => $request->boolean('is_active', !$builder->exists)]);
        if ($request->hasFile('logo')) {
            if ($builder->logo_path) Storage::disk('public')->delete($builder->logo_path);
            $builder->logo_path = $request->file('logo')->store('builders', 'public');
        }
        $builder->save();
        $builder->projects()->pluck('city_slug')->unique()->each(fn ($c) => PropertyCache::bump($c));
        return back()->with('success', 'Builder saved.');
    }

    // ---- Projects ----

    public function projects(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $projects = Project::with('builder:id,name')->withCount(['images', 'properties', 'liveOffers'])
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('city', 'like', "%$q%")->orWhere('locality', 'like', "%$q%")))
            ->latest('updated_at')->paginate(30)->withQueryString();
        return view('properties.admin.projects', compact('projects', 'q'));
    }

    public function editProject(?Project $project = null)
    {
        $project ??= new Project(['status' => 'under_construction', 'segment' => 'residential', 'is_active' => true]);
        $project->load(['images', 'offers', 'units', 'highlights', 'videos', 'faqs', 'charges', 'leads' => fn ($q) => $q->with(['user:id,name,email', 'unit:id,label,area_sqft'])->limit(200)]);
        return view('properties.admin.project', ['p' => $project, 'builders' => Builder::orderBy('name')->get(['id', 'name']),
            'cities' => PropertyCity::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug'])]);
    }

    public function saveProject(Request $request, ?Project $project = null)
    {
        $o = config('properties');
        $data = $request->validate([
            'name' => 'required|string|max:160', 'builder_id' => 'nullable|exists:builders,id', 'segment' => 'required|in:residential,commercial',
            'city_id' => 'required|exists:property_cities,id', 'locality' => 'nullable|string|max:150', 'address' => 'nullable|string|max:300',
            'latitude' => 'nullable|numeric|between:-90,90', 'longitude' => 'nullable|numeric|between:-180,180',
            'status' => ['required', Rule::in(array_keys($o['project_statuses']))], 'rera_number' => 'nullable|string|max:80', 'rera_website' => 'nullable|url|max:191',
            'launched_on' => 'nullable|date', 'possession_on' => 'nullable|date', 'price_min' => 'nullable|numeric|min:0', 'price_max' => 'nullable|numeric|gte:price_min',
            'configurations' => 'nullable|string|max:500', 'amenities' => 'nullable|array', 'amenities.*' => Rule::in(array_keys($o['amenities'])),
            'total_units' => 'nullable|integer|min:1', 'towers' => 'nullable|integer|min:1|max:500', 'land_acres' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:10000', 'is_featured' => 'nullable|boolean', 'is_active' => 'nullable|boolean',
            'visit_days' => 'nullable|array', 'visit_days.*' => 'integer|between:1,7', 'visit_start' => 'nullable|date_format:H:i',
            'visit_end' => 'nullable|date_format:H:i|after:visit_start', 'visit_capacity' => 'nullable|integer|min:1|max:50',
            'contact_phone' => ['nullable', 'regex:/^\+?[0-9 ()-]{7,20}$/'], 'contact_email' => 'nullable|email|max:191', 'no_brokerage' => 'nullable|boolean',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', 'brochure' => 'nullable|file|mimes:pdf|max:20480', 'remove_brochure' => 'nullable|boolean',
        ]);
        $files = ['logo' => $request->file('logo'), 'brochure' => $request->file('brochure')];
        $data = collect($data)->except(['logo', 'brochure', 'remove_brochure'])->all() + ['no_brokerage' => $request->boolean('no_brokerage')];
        $data['visit_days'] = isset($data['visit_days']) ? array_values(array_unique(array_map('intval', $data['visit_days']))) : null;
        $city = PropertyCity::find($data['city_id']);
        $project ??= new Project(['slug' => $this->uniqueSlug(Project::class, $data['name'] . ' ' . $city->name)]);
        $project->fill(collect($data)->except(['city_id', 'configurations'])->all() + [
            'city' => $city->name, 'city_slug' => $city->slug, 'amenities' => $data['amenities'] ?? [],
            'configurations' => collect(explode(',', $data['configurations'] ?? ''))->map(fn ($c) => trim($c))->filter()->values()->all() ?: null,
            'is_featured' => $request->boolean('is_featured'), 'is_active' => $request->boolean('is_active'),
        ]);
        $project->save();
        if ($files['logo']) {
            if ($project->logo_path) Storage::disk('public')->delete($project->logo_path);
            $project->logo_path = $files['logo']->store('projects/' . $project->id, 'public');
        }
        if ($files['brochure'] || $request->boolean('remove_brochure')) {
            if ($project->brochure_path) Storage::disk('public')->delete($project->brochure_path);
            $project->brochure_path = $files['brochure']?->store('projects/' . $project->id . '/brochure', 'public');
        }
        if ($project->isDirty()) $project->save();
        return redirect()->route('admin.projects.edit', $project)->with('success', 'Project saved.');
    }

    /** Units (configurations) with floor plan images. */
    public function saveUnit(Request $request, Project $project, ?\App\Models\Property\ProjectUnit $unit = null)
    {
        if ($unit) abort_unless($unit->project_id === $project->id, 404);
        if ($request->boolean('delete') && $unit) {
            if ($unit->floor_plan_path) Storage::disk('public')->delete($unit->floor_plan_path);
            $unit->delete();
            return back()->with('success', 'Unit removed.');
        }
        $data = $request->validate([
            'label' => 'required|string|max:80', 'bhk' => ['nullable', Rule::in(array_keys(config('properties.bhk')))],
            'area_type' => ['required', Rule::in(array_keys(config('properties.area_types')))], 'area_sqft' => 'required|numeric|min:1|max:10000000',
            'price' => 'nullable|numeric|min:0', 'bathrooms' => 'nullable|integer|min:0|max:20', 'balconies' => 'nullable|integer|min:0|max:20',
            'possession_on' => 'nullable|date', 'sort' => 'nullable|integer|min:0|max:65000', 'floor_plan' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);
        $unit ??= new \App\Models\Property\ProjectUnit(['project_id' => $project->id]);
        $unit->fill(collect($data)->except('floor_plan')->all() + ['sort' => (int) ($data['sort'] ?? 0)]);
        if ($request->hasFile('floor_plan')) {
            if ($unit->floor_plan_path) Storage::disk('public')->delete($unit->floor_plan_path);
            $unit->floor_plan_path = $request->file('floor_plan')->store('projects/' . $project->id . '/plans', 'public');
        }
        $unit->save();
        PropertyCache::bump($project->city_slug);
        return back()->with('success', 'Unit saved.');
    }

    /** Highlights, videos, FAQs and charges share one editor: add, update or delete a row. */
    public function saveChild(Request $request, Project $project, string $child, ?int $id = null)
    {
        $map = [
            'highlights' => [\App\Models\Property\ProjectHighlight::class, ['text' => 'required|string|max:255']],
            'videos' => [\App\Models\Property\ProjectVideo::class, ['url' => ['required', 'url', 'max:300', 'regex:~(youtube\.com|youtu\.be|vimeo\.com)/~'], 'title' => 'nullable|string|max:160']],
            'faqs' => [\App\Models\Property\ProjectFaq::class, ['question' => 'required|string|max:255', 'answer' => 'required|string|max:5000']],
            'charges' => [\App\Models\Property\ProjectCharge::class, ['name' => 'required|string|max:120', 'amount' => 'nullable|numeric|min:0', 'note' => 'nullable|string|max:160']],
        ];
        abort_unless(isset($map[$child]), 404);
        [$model, $rules] = $map[$child];
        $row = $id ? $model::where('project_id', $project->id)->findOrFail($id) : new $model(['project_id' => $project->id]);
        if ($request->boolean('delete') && $row->exists) {
            $row->delete();
        } else {
            $data = $request->validate($rules + ['sort' => 'nullable|integer|min:0|max:65000'], ['url.regex' => 'Use a YouTube or Vimeo link.']);
            $row->fill($data + ['sort' => (int) ($data['sort'] ?? ($row->sort ?? $model::where('project_id', $project->id)->max('sort') + 1))])->save();
        }
        PropertyCache::bump($project->city_slug);
        return back()->with('success', 'Saved.');
    }

    public function leadStatus(Request $request, Project $project, \App\Models\Property\ProjectLead $lead)
    {
        abort_unless($lead->project_id === $project->id, 404);
        $lead->update($request->validate(['status' => 'required|in:new,contacted,closed']));
        return back()->with('success', 'Lead updated.');
    }

    public function uploadImages(Request $request, Project $project)
    {
        $data = $request->validate(['images' => 'required|array|max:30', 'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:8192',
            'kind' => ['required', Rule::in(array_keys(config('properties.image_kinds')))], 'caption' => 'nullable|string|max:160']);
        $sort = (int) $project->images()->max('sort');
        $hasCover = $project->images()->where('is_cover', true)->exists();
        foreach ($data['images'] as $file) {
            $project->images()->create(['path' => $file->store('projects/' . $project->id, 'public'), 'kind' => $data['kind'], 'caption' => $data['caption'] ?? null,
                'sort' => ++$sort, 'is_cover' => !$hasCover]);
            $hasCover = true;
        }
        $project->touch();
        PropertyCache::bump($project->city_slug);
        return back()->with('success', count($data['images']) . ' photo(s) added.');
    }

    public function imageAction(Request $request, Project $project, ProjectImage $image)
    {
        abort_unless($image->project_id === $project->id, 404);
        $data = $request->validate(['action' => 'required|in:cover,delete,update', 'kind' => ['nullable', Rule::in(array_keys(config('properties.image_kinds')))], 'caption' => 'nullable|string|max:160']);
        DB::transaction(function () use ($data, $project, $image) {
            match ($data['action']) {
                'cover' => [$project->images()->update(['is_cover' => false]), $image->update(['is_cover' => true])],
                'update' => $image->update(['kind' => $data['kind'] ?? $image->kind, 'caption' => $data['caption'] ?? null]),
                'delete' => [Storage::disk('public')->delete($image->path), $image->delete()],
            };
            if ($data['action'] === 'delete' && $image->is_cover) $project->images()->orderBy('sort')->first()?->update(['is_cover' => true]);
        });
        PropertyCache::bump($project->city_slug);
        return back()->with('success', 'Photo updated.');
    }

    public function saveOffer(Request $request, Project $project, ?ProjectOffer $offer = null)
    {
        if ($offer) abort_unless($offer->project_id === $project->id, 404);
        if ($request->boolean('delete') && $offer) {
            $offer->delete();
        } else {
            $data = $request->validate(['title' => 'required|string|max:160', 'details' => 'nullable|string|max:500', 'valid_until' => 'nullable|date|after_or_equal:today', 'is_active' => 'nullable|boolean']);
            ($offer ?? new ProjectOffer(['project_id' => $project->id]))->fill($data + ['is_active' => $request->boolean('is_active', true)])->save();
        }
        PropertyCache::bump($project->city_slug);
        return back()->with('success', 'Offer saved.');
    }

    private function uniqueSlug(string $model, string $text): string
    {
        $base = Str::limit(Str::slug($text), 170, '') ?: 'item';
        $slug = $base;
        for ($i = 2; $model::where('slug', $slug)->exists(); $i++) $slug = $base . '-' . $i;
        return $slug;
    }
}
