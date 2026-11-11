<?php

namespace App\Http\Controllers;

use App\Models\Property\Property;
use App\Models\Property\PropertyMedia;
use App\Models\Property\PropertyOwner;
use App\Services\Property\PropertyFormat;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// "Post a free property ad": owner contact → what you are posting → one page per step → live listing. Also My properties.
class PropertyPostController extends Controller
{
    public const STEP_LABELS = ['details' => 'Property details', 'location' => 'Location details', 'rental' => 'Rental details', 'sale' => 'Sale details', 'rooms' => 'Rooms & rent',
        'amenities' => 'Amenities', 'gallery' => 'Gallery', 'additional' => 'Additional information', 'schedule' => 'Schedule'];
    private const REQUIRED = ['details', 'location'];   // plus the pricing step of the listing

    public static function steps(Property $p): array
    {
        $pricing = $p->isPg() ? 'rooms' : ($p->listing === 'rent' ? 'rental' : 'sale');
        return array_values(array_filter(['details', 'location', $pricing, $p->isLand() ? null : 'amenities', 'gallery', 'additional', 'schedule']));
    }

    public function start(Request $request)
    {
        $owner = PropertyOwner::firstWhere('user_id', $request->user()->id);
        $drafts = $owner ? $owner->properties()->where('status', 'draft')->latest()->get() : collect();
        return view('properties.post.start', compact('owner', 'drafts'));
    }

    public function saveOwner(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9 ()-]{7,20}$/'], 'whatsapp' => 'nullable|boolean',
            'owner_type' => 'required|in:' . implode(',', array_keys(config('properties.owner_types'))), 'company_name' => 'nullable|required_unless:owner_type,owner|string|max:160',
            'rera_number' => 'nullable|string|max:60']);
        PropertyOwner::updateOrCreate(['user_id' => $request->user()->id], $data + ['whatsapp' => $request->boolean('whatsapp'), 'email' => $request->user()->email]);
        return redirect()->route('properties.post')->with('success', 'Contact details saved.');
    }

    public function create(Request $request)
    {
        $owner = PropertyOwner::where('user_id', $request->user()->id)->firstOrFail();
        $data = $request->validate(['segment' => 'required|in:residential,commercial', 'listing' => 'required|in:rent,sale',
            'kind' => ['required', Rule::in(array_keys(config('properties.kinds.' . $request->input('segment'), [])))]]);
        abort_if($data['segment'] === 'residential' && (($data['kind'] === 'land' && $data['listing'] === 'rent') || (in_array($data['kind'], ['pg', 'flatmate'], true) && $data['listing'] === 'sale')), 422, 'That combination is not offered.');
        $p = Property::create($data + ['owner_id' => $owner->id, 'user_id' => $request->user()->id, 'slug' => 'draft-' . Str::lower(Str::random(16)), 'status' => 'draft', 'steps_done' => []]);
        return redirect()->route('properties.step', [$p->id, 'details']);
    }

    public function step(Request $request, int $id, string $step)
    {
        $p = $this->own($request, $id);
        abort_unless(in_array($step, self::steps($p), true), 404);
        $p->load(['rentTerms', 'saleTerms', 'pgTerms', 'media']);
        return view('properties.post.step', ['p' => $p, 'step' => $step, 'steps' => self::steps($p)]);
    }

    public function save(Request $request, int $id, string $step)
    {
        $p = $this->own($request, $id);
        $steps = self::steps($p);
        abort_unless(in_array($step, $steps, true), 404);
        $this->{'save' . ucfirst($step)}($request, $p);
        $p->steps_done = array_values(array_unique(array_merge($p->steps_done ?? [], [$step])));
        $p->save();
        $next = $steps[array_search($step, $steps, true) + 1] ?? null;
        if ($next && !$request->boolean('finish')) return redirect()->route('properties.step', [$p->id, $next]);

        // Last step (or "Finish posting"): every required step must be filled in before the listing goes live.
        $missing = array_diff(array_merge(self::REQUIRED, [$steps[2]]), $p->steps_done);
        if ($missing) return redirect()->route('properties.step', [$p->id, reset($missing)])->with('warning', 'Please complete ' . strtolower(self::STEP_LABELS[reset($missing)]) . ' to publish.');
        $p->load(['pgTerms']);
        $p->refreshTitleAndSlug();
        if ($p->status === 'draft') $p->fill(['status' => 'active', 'published_at' => now()]);
        $p->save();
        cache()->forget('properties:cities');
        return redirect()->route('properties.mine')->with('success', 'Your listing is live: ' . $p->title)->with('posted', $p->slug);
    }

    public function upload(Request $request, int $id)
    {
        $p = $this->own($request, $id);
        $limits = config('properties.media');
        $request->validate(['photos' => 'nullable|array', 'photos.*' => 'file|mimes:jpg,jpeg,png,webp|max:' . $limits['photo_kb'],
            'videos' => 'nullable|array', 'videos.*' => 'file|mimetypes:video/mp4,video/webm,video/quicktime|max:' . $limits['video_kb']]);
        $counts = $p->media()->selectRaw('type, count(*) as n')->groupBy('type')->pluck('n', 'type');
        $sort = (int) $p->media()->max('sort');
        foreach (['photo' => 'photos', 'video' => 'videos'] as $type => $field) {
            $room = $limits[$field] - (int) ($counts[$type] ?? 0);
            foreach (array_slice($request->file($field, []), 0, max(0, $room)) as $file) {
                PropertyMedia::create(['property_id' => $p->id, 'type' => $type, 'path' => $file->store("properties/{$p->id}", 'public'), 'sort' => ++$sort]);
            }
        }
        return redirect()->route('properties.step', [$p->id, 'gallery'])->with('success', 'Uploaded.');
    }

    public function media(Request $request, int $id, PropertyMedia $media)
    {
        $p = $this->own($request, $id);
        abort_unless($media->property_id === $p->id, 404);
        if ($request->input('action') === 'cover') {
            $p->media()->where('type', $media->type)->increment('sort');
            $media->update(['sort' => 0]);
        } else {
            Storage::disk('public')->delete($media->path);
            $media->delete();
        }
        return back();
    }

    public function mine(Request $request)
    {
        $items = Property::where('user_id', $request->user()->id)->with(['media', 'rentTerms', 'saleTerms', 'pgTerms'])->withCount('leads')->latest()->get();
        $leads = \App\Models\Property\PropertyLead::whereIn('property_id', $items->pluck('id'))->latest()->limit(200)->get()->groupBy('property_id');
        return view('properties.mine', compact('items', 'leads'));
    }

    /** A listing of the signed-in user (the site owner may open any). */
    private function own(Request $request, int $id): Property
    {
        return Property::where('id', $id)->when(!$request->user()->isOwner(), fn ($q) => $q->where('user_id', $request->user()->id))->firstOrFail();
    }

    public function status(Request $request, int $id, string $action)
    {
        $p = $this->own($request, $id);
        abort_unless(in_array($action, ['close', 'reopen', 'delete'], true), 404);
        abort_if($action === 'close' && $p->status !== 'active', 422, 'Only a live listing can be closed.');
        abort_if($action === 'reopen' && $p->status !== 'closed', 422, 'Only a closed listing can be reopened.');
        if ($action === 'close') $p->update(['status' => 'closed', 'closed_at' => now()]);
        if ($action === 'reopen') $p->update(['status' => 'active', 'closed_at' => null, 'published_at' => now()]);
        if ($action === 'delete') $p->delete();
        return redirect()->route('properties.mine')->with('success', ['close' => 'Listing marked as ' . ($p->listing === 'rent' ? 'rented out' : 'sold') . '.', 'reopen' => 'Listing is live again.', 'delete' => 'Listing deleted.'][$action]);
    }

    // ---- one saver per step ----

    private function saveDetails(Request $r, Property $p): void
    {
        $o = config('properties');
        $floors = fn (array $named) => function ($attr, $value, $fail) use ($named) {
            if (!isset($named[$value]) && !(ctype_digit((string) $value) && (int) $value >= 1 && (int) $value <= 99)) $fail('Choose a floor from the list.');
        };
        $area = ['area_value' => ($p->isPg() ? 'nullable' : 'required') . '|numeric|min:1|max:99999999', 'area_unit' => 'required|in:' . implode(',', array_keys($o['area_units'])), 'carpet_area' => 'nullable|numeric|min:1|max:99999999'];
        if ($p->segment === 'commercial') {
            $d = $r->validate($area + [
                'kind' => 'required|in:' . implode(',', array_keys($o['kinds']['commercial'])), 'building_type' => 'required|in:' . implode(',', array_keys($o['building_types']['commercial'])),
                'age' => 'required|in:' . implode(',', array_keys($o['ages'])), 'floor' => ['required', $floors($o['commercial']['floors'])],
                'total_floors' => ['required', $floors($o['commercial']['total_floors'])], 'furnishing' => 'required|in:' . implode(',', array_keys($o['furnishing'])),
                'features' => 'nullable|array', 'features.*' => 'in:' . implode(',', array_keys($o['commercial']['other_features'])),
            ]);
        } elseif ($p->isLand()) {
            $d = $r->validate($area + ['facing' => 'nullable|in:' . implode(',', array_keys($o['facing'])), 'plot_length' => 'nullable|numeric|min:1|max:99999', 'plot_breadth' => 'nullable|numeric|min:1|max:99999',
                'boundary_wall' => 'nullable|boolean', 'open_sides' => 'nullable|integer|between:1,4', 'floors_allowed' => 'nullable|integer|between:1,99']);
            $d['details'] = array_merge($p->details ?? [], Arr::only($d, ['plot_length', 'plot_breadth', 'open_sides', 'floors_allowed']) + ['boundary_wall' => $r->boolean('boundary_wall')]);
        } else {
            $d = $r->validate($area + [
                'building_type' => ($p->isPg() ? 'nullable' : 'required') . '|in:' . implode(',', array_keys($o['building_types']['residential'])),
                'bhk' => ($p->kind === 'pg' ? 'nullable' : 'required') . '|in:' . implode(',', array_keys($o['bhk'])), 'age' => 'nullable|in:' . implode(',', array_keys($o['ages'])),
                'floor' => ['nullable', $floors(['0' => 'Ground'] + $o['commercial']['floors'])], 'total_floors' => 'nullable|integer|between:1,99',
                'facing' => 'nullable|in:' . implode(',', array_keys($o['facing'])), 'furnishing' => ($p->isPg() ? 'nullable' : 'required') . '|in:' . implode(',', array_keys($o['furnishing'])),
                'bedrooms' => 'nullable|integer|between:0,20', 'bathrooms' => 'nullable|integer|between:0,20', 'balconies' => 'nullable|integer|between:0,20',
            ]);
        }
        $d['area_sqft'] = PropertyFormat::toSqft(isset($d['area_value']) ? (float) $d['area_value'] : null, $d['area_unit']);
        $d['carpet_area_sqft'] = PropertyFormat::toSqft(isset($d['carpet_area']) ? (float) $d['carpet_area'] : null, $d['area_unit']);
        $p->fill(Arr::only($d, ['kind', 'building_type', 'bhk', 'age', 'floor', 'total_floors', 'facing', 'furnishing', 'bedrooms', 'bathrooms', 'balconies', 'area_value', 'area_unit',
            'area_sqft', 'carpet_area_sqft', 'features', 'details']));
    }

    private function saveLocation(Request $r, Property $p): void
    {
        $d = $r->validate(['country' => 'required|in:' . implode(',', array_keys(config('properties.countries'))), 'state' => 'nullable|string|max:80', 'city' => 'required|string|max:80',
            'locality' => 'required|string|max:150', 'society' => 'nullable|string|max:150', 'street' => 'required|string|max:200', 'landmark' => 'nullable|string|max:150',
            'pincode' => 'nullable|string|max:12', 'latitude' => 'nullable|numeric|between:-90,90', 'longitude' => 'nullable|numeric|between:-180,180']);
        $p->fill($d + ['city_slug' => Str::slug($d['city']), 'currency' => config("properties.countries.{$d['country']}.currency")]);
    }

    private function saveRental(Request $r, Property $p): void
    {
        $d = $r->validate(['rent' => 'required|numeric|min:1|max:999999999', 'maintenance' => 'nullable|numeric|min:0|max:99999999', 'deposit' => 'required|numeric|min:0|max:9999999999',
            'brokerage' => 'nullable|numeric|min:0|max:99999999', 'lease_years' => 'nullable|in:' . implode(',', array_keys(config('properties.lease_years'))),
            'lockin_years' => 'nullable|in:' . implode(',', array_keys(config('properties.lockin_years'))), 'available_from' => 'required|date|after_or_equal:' . now()->subYear()->toDateString(),
            'preferred_tenants' => 'nullable|array', 'preferred_tenants.*' => 'in:' . implode(',', array_keys(config('properties.preferred_tenants'))),
            'ideal_for' => 'nullable|array', 'ideal_for.*' => 'in:' . implode(',', array_keys(config('properties.commercial.ideal_for')))]);
        $p->rentTerms()->updateOrCreate([], $d + ['rent_negotiable' => $r->boolean('rent_negotiable'), 'maintenance_extra' => $r->boolean('maintenance_extra'), 'deposit_negotiable' => $r->boolean('deposit_negotiable')]);
        $p->price_sort = $d['rent'];
    }

    private function saveSale(Request $r, Property $p): void
    {
        $d = $r->validate(['price' => 'required|numeric|min:1|max:99999999999', 'maintenance' => 'nullable|numeric|min:0|max:99999999', 'brokerage' => 'nullable|numeric|min:0|max:999999999',
            'possession' => 'required|in:' . implode(',', array_keys(config('properties.possession'))), 'possession_by' => 'nullable|required_if:possession,under_construction|date',
            'project_name' => 'nullable|string|max:160', 'builder_name' => 'nullable|string|max:160', 'ownership' => 'nullable|in:' . implode(',', array_keys(config('properties.ownership'))),
            'ideal_for' => 'nullable|array', 'ideal_for.*' => 'in:' . implode(',', array_keys(config('properties.commercial.ideal_for')))]);
        $p->saleTerms()->updateOrCreate([], $d + ['price_negotiable' => $r->boolean('price_negotiable'), 'is_new_project' => $r->boolean('is_new_project')]);
        $p->price_sort = $d['price'];
    }

    private function saveRooms(Request $r, Property $p): void
    {
        $pg = config('properties.pg');
        $d = $r->validate(['gender' => 'required|in:' . implode(',', array_keys($pg['gender'])), 'tenant_type' => 'required|in:' . implode(',', array_keys($pg['tenant_type'])),
            'rooms' => 'required|array', 'rooms.*.rent' => 'nullable|numeric|min:1|max:99999999', 'rooms.*.deposit' => 'nullable|numeric|min:0|max:99999999', 'rooms.*.beds' => 'nullable|integer|between:1,500',
            'meals' => 'nullable|array', 'meals.*' => 'in:' . implode(',', array_keys($pg['meals'])), 'rules' => 'nullable|array', 'rules.*' => 'in:' . implode(',', array_keys($pg['rules'])),
            'services' => 'nullable|array', 'services.*' => 'in:' . implode(',', array_keys($pg['services'])), 'gate_closing' => 'nullable|date_format:H:i', 'available_from' => 'required|date']);
        $rooms = collect($d['rooms'])->only(array_keys($pg['rooms']))->filter(fn ($room) => !empty($room['rent']))
            ->map(fn ($room, $type) => ['type' => $type, 'rent' => (float) $room['rent'], 'deposit' => isset($room['deposit']) ? (float) $room['deposit'] : null, 'beds' => $room['beds'] ?? null])->values();
        if ($rooms->isEmpty()) throw \Illuminate\Validation\ValidationException::withMessages(['rooms' => 'Enter the rent for at least one room type.']);
        $p->pgTerms()->updateOrCreate([], ['rooms' => $rooms->all()] + Arr::except($d, ['rooms']));
        $p->price_sort = $rooms->min('rent');
    }

    private function saveAmenities(Request $r, Property $p): void
    {
        if ($p->segment === 'commercial') {
            $c = config('properties.commercial');
            $d = $r->validate(['amenities.power_backup' => 'required|in:' . implode(',', array_keys($c['power_backup'])), 'amenities.lift' => 'required|in:' . implode(',', array_keys($c['lift'])),
                'amenities.parking' => 'required|in:' . implode(',', array_keys($c['parking'])), 'amenities.washrooms' => 'required|in:' . implode(',', array_keys($c['washrooms'])),
                'amenities.water_storage' => 'nullable|in:yes,no', 'amenities.security' => 'nullable|in:yes,no', 'amenities.condition' => 'nullable|in:' . implode(',', array_keys($c['condition'])),
                'amenities.current_business' => 'nullable|in:' . implode(',', array_keys($c['current_business'])), 'amenities.similar_units' => 'nullable|in:yes,no']);
            $p->amenities = array_filter($d['amenities'], fn ($v) => $v !== null);
            return;
        }
        $d = $r->validate(['amenities' => 'nullable|array', 'amenities.*' => 'in:' . implode(',', array_keys(config('properties.amenities'))),
            'furnishings' => 'nullable|array', 'furnishings.*' => 'nullable|integer|between:0,50']);
        $p->amenities = array_values($d['amenities'] ?? []);
        $p->furnishings = collect($d['furnishings'] ?? [])->only(array_keys(config('properties.furnishings')))->map(fn ($n) => (int) $n)->filter()->all();
    }

    private function saveGallery(Request $r, Property $p): void
    {
        // Files are sent by upload(); "Save & continue" here only moves on.
    }

    private function saveAdditional(Request $r, Property $p): void
    {
        $o = config('properties');
        $d = $r->validate(['description' => 'nullable|string|max:5000', 'directions' => 'nullable|string|max:500',
            'highlights' => 'nullable|array', 'highlights.*' => 'in:' . implode(',', array_keys($o['highlights'])),
            'd.covered_parking' => 'nullable|integer|between:0,50', 'd.open_parking' => 'nullable|integer|between:0,50', 'd.parking' => 'nullable|in:' . implode(',', array_keys($o['parking'])),
            'd.power_backup' => 'nullable|in:' . implode(',', array_keys($o['power_backup'])), 'd.open_sides' => 'nullable|integer|between:1,4',
            'd.flooring' => 'nullable|in:' . implode(',', array_keys($o['flooring'])), 'd.road_width' => 'nullable|numeric|min:1|max:999', 'd.road_unit' => 'nullable|in:ft,m',
            'd.water_supply' => 'nullable|in:' . implode(',', array_keys($o['water_supply'])), 'd.gated' => 'nullable|in:yes,no', 'd.non_veg' => 'nullable|in:yes,no', 'd.pets' => 'nullable|in:yes,no',
            'overlooking' => 'nullable|array', 'overlooking.*' => 'in:' . implode(',', array_keys($o['overlooking'])),
            'plot_amenities' => 'nullable|array', 'plot_amenities.*' => 'in:' . implode(',', array_keys($o['plot_amenities']))]);
        $p->fill(['description' => $d['description'] ?? null, 'directions' => $d['directions'] ?? null, 'highlights' => array_values($d['highlights'] ?? []),
            'details' => array_merge($p->details ?? [], array_filter($d['d'] ?? [], fn ($v) => $v !== null && $v !== ''), ['overlooking' => array_values($d['overlooking'] ?? [])])]);
        // Plots have no amenities step; their layout amenities are picked here.
        if ($p->isLand()) $p->amenities = array_values($d['plot_amenities'] ?? []);
    }

    private function saveSchedule(Request $r, Property $p): void
    {
        $d = $r->validate(['availability' => 'nullable|in:' . implode(',', array_keys(config('properties.availability'))), 'visit_all_day' => 'nullable|boolean',
            'visit_start' => 'nullable|required_without:visit_all_day|date_format:H:i', 'visit_end' => 'nullable|required_without:visit_all_day|date_format:H:i|after:visit_start']);
        $all = $r->boolean('visit_all_day');
        $p->fill(['availability' => $d['availability'] ?? 'everyday', 'visit_all_day' => $all, 'visit_start' => $all ? null : $d['visit_start'], 'visit_end' => $all ? null : $d['visit_end']]);
    }
}
