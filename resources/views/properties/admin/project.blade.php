@extends('layouts.site')
@section('title', ($p->exists ? $p->name : 'New project') . ' — admin')
@section('content')
@include('properties._nav')
@php
    $o = config('properties');
    $v = fn ($k) => old($k, $p->{$k});
    $cityId = old('city_id', $cities->firstWhere('slug', $p->city_slug)?->id);
@endphp
<div class="pr"><div class="pr-wrap">
    @include('properties.admin._tabs')
    <h1 style="font-size:22px">{{ $p->exists ? $p->name : 'New project' }} @if($p->exists)<a href="{{ route('projects.show', $p) }}" target="_blank" style="font-size:14px">view page ↗</a>@endif</h1>
    <form class="pr-card-box" method="post" enctype="multipart/form-data" action="{{ $p->exists ? route('admin.projects.update', $p->id) : route('admin.projects.store') }}">@csrf
        <div class="pr-grid">
            <div><label class="f">Name <em>*</em></label><input name="name" value="{{ $v('name') }}" required maxlength="160"></div>
            <div><label class="f">Builder</label><select name="builder_id"><option value="">—</option>@foreach($builders as $b)<option value="{{ $b->id }}" @selected((int) $v('builder_id') === $b->id)>{{ $b->name }}</option>@endforeach</select>
                <small><a href="{{ route('admin.builders.index') }}">Add a builder</a></small></div>
            <div><label class="f">Segment</label><select name="segment">@foreach(['residential' => 'Residential', 'commercial' => 'Commercial'] as $k => $label)<option value="{{ $k }}" @selected($v('segment') === $k)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="f">City <em>*</em></label><select name="city_id" required><option value="">Select</option>@foreach($cities as $c)<option value="{{ $c->id }}" @selected((int) $cityId === $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div><label class="f">Locality</label><input name="locality" value="{{ $v('locality') }}" maxlength="150"></div>
            <div><label class="f">Address</label><input name="address" value="{{ $v('address') }}" maxlength="300"></div>
            <div><label class="f">Latitude</label><input name="latitude" value="{{ $v('latitude') }}"></div>
            <div><label class="f">Longitude</label><input name="longitude" value="{{ $v('longitude') }}"></div>
            <div><label class="f">Status</label><select name="status">@foreach($o['project_statuses'] as $k => $label)<option value="{{ $k }}" @selected($v('status') === $k)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="f">Launched on</label><input type="date" name="launched_on" value="{{ old('launched_on', $p->launched_on?->toDateString()) }}"></div>
            <div><label class="f">Possession on</label><input type="date" name="possession_on" value="{{ old('possession_on', $p->possession_on?->toDateString()) }}"></div>
            <div><label class="f">RERA number</label><input name="rera_number" value="{{ $v('rera_number') }}" maxlength="80"></div>
            <div><label class="f">RERA website (to verify)</label><input type="url" name="rera_website" value="{{ $v('rera_website') }}" maxlength="191"></div>
            <div><label class="f">Price from (₹)</label><input type="number" name="price_min" min="0" value="{{ $v('price_min') }}"></div>
            <div><label class="f">Price up to (₹)</label><input type="number" name="price_max" min="0" value="{{ $v('price_max') }}"></div>
            <div class="full"><label class="f">Configurations (comma separated)</label><input name="configurations" value="{{ old('configurations', implode(', ', $p->configurations ?? [])) }}" placeholder="2 BHK Apartment, 3 BHK Apartment"></div>
            <div><label class="f">Total units</label><input type="number" name="total_units" min="1" value="{{ $v('total_units') }}"></div>
            <div><label class="f">Towers</label><input type="number" name="towers" min="1" value="{{ $v('towers') }}"></div>
            <div><label class="f">Land (acres)</label><input type="number" step="0.01" name="land_acres" min="0" value="{{ $v('land_acres') }}"></div>
            <div class="full"><label class="f">Description</label><textarea name="description" rows="5" maxlength="10000">{{ $v('description') }}</textarea></div>
            <div class="full"><label class="f">Site visit days</label>
                <div class="pr-chips">@foreach([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $d => $label)<label class="pr-chip"><input type="checkbox" name="visit_days[]" value="{{ $d }}" @checked(in_array($d, array_map('intval', old('visit_days', $p->visit_days ?? range(1, 7))), true))>{{ $label }}</label>@endforeach</div></div>
            <div><label class="f">Visits from</label><input type="time" name="visit_start" value="{{ old('visit_start', $p->visit_start ?? config('site_visits.project.start')) }}"></div>
            <div><label class="f">Visits until</label><input type="time" name="visit_end" value="{{ old('visit_end', $p->visit_end ?? config('site_visits.project.end')) }}"></div>
            <div><label class="f">Visitors per 1-hour slot</label><input type="number" name="visit_capacity" min="1" max="50" value="{{ old('visit_capacity', $p->visit_capacity ?? config('site_visits.project.capacity')) }}"></div>
            <div><label class="f">Sales phone (shown on "View number")</label><input name="contact_phone" value="{{ $v('contact_phone') }}" maxlength="20" placeholder="Builder's number is used if empty"></div>
            <div><label class="f">Sales email</label><input type="email" name="contact_email" value="{{ $v('contact_email') }}" maxlength="191"></div>
            <div><label class="f">Project logo</label><input type="file" name="logo" accept="image/jpeg,image/png,image/webp">@if($p->logoUrl())<img src="{{ $p->logoUrl() }}" alt="" style="height:40px;margin-top:6px">@endif</div>
            <div><label class="f">Brochure (PDF)</label><input type="file" name="brochure" accept="application/pdf">
                @if($p->brochure_path)<small>Current: <a href="{{ Storage::disk('public')->url($p->brochure_path) }}" target="_blank">brochure.pdf</a> · <label><input type="checkbox" name="remove_brochure" value="1"> remove</label></small>@endif</div>
        </div>
        <label class="f" style="margin-top:12px">Amenities</label>
        <div class="pr-chips">@foreach($o['amenities'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="amenities[]" value="{{ $k }}" @checked(in_array($k, old('amenities', $p->amenities ?? []), true))>{{ $label }}</label>@endforeach</div>
        <div style="display:flex;gap:16px;margin-top:12px">
            <label class="pr-check"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $p->is_featured))> Featured</label>
            <label class="pr-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $p->is_active))> Active</label>
            <label class="pr-check"><input type="checkbox" name="no_brokerage" value="1" @checked(old('no_brokerage', $p->no_brokerage))> No brokerage (sold directly)</label>
        </div>
        <button class="pr-btn" style="margin-top:12px">Save project</button>
    </form>

    @if($p->exists)
        @include('properties.admin._project_details')
        <section class="pr-card-box" style="margin-top:16px">
            <h2 style="margin-top:0">Photos ({{ $p->images->count() }})</h2>
            <form method="post" enctype="multipart/form-data" action="{{ route('admin.projects.images', $p->id) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">@csrf
                <div><label class="f">Photos</label><input type="file" name="images[]" multiple required accept="image/jpeg,image/png,image/webp"></div>
                <div><label class="f">Kind</label><select name="kind">@foreach($o['image_kinds'] as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select></div>
                <div><label class="f">Caption</label><input name="caption" maxlength="160"></div>
                <button class="pr-btn sm">Upload</button>
            </form>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-top:14px">
                @foreach($p->images as $img)
                    <div style="border:1px solid var(--pr-line);border-radius:12px;overflow:hidden">
                        <img src="{{ $img->url() }}" alt="" style="width:100%;height:130px;object-fit:cover">
                        <form method="post" action="{{ route('admin.projects.image', [$p->id, $img->id]) }}" style="padding:8px;display:grid;gap:6px">@csrf
                            <select name="kind" aria-label="Kind">@foreach($o['image_kinds'] as $k => $label)<option value="{{ $k }}" @selected($img->kind === $k)>{{ $label }}</option>@endforeach</select>
                            <input name="caption" value="{{ $img->caption }}" maxlength="160" placeholder="Caption" aria-label="Caption">
                            <div style="display:flex;gap:6px;flex-wrap:wrap"><button class="pr-btn ghost sm" name="action" value="update">Save</button>
                                @if($img->is_cover)<span class="pr-tag">Cover</span>@else<button class="pr-btn ghost sm" name="action" value="cover">Make cover</button>@endif
                                <button class="pr-btn danger sm" name="action" value="delete" onclick="return confirm('Delete this photo?')">Delete</button></div>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="pr-card-box" style="margin-top:16px">
            <h2 style="margin-top:0">Offers</h2>
            <p class="pr-mute" style="margin-top:0">Only offers the builder actually runs. They show on the city page and project page until the end date.</p>
            @foreach($p->offers as $offer)
                <form method="post" action="{{ route('admin.projects.offer', [$p->id, $offer->id]) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;border-top:1px solid var(--pr-line);padding:10px 0">@csrf
                    <div><label class="f">Title</label><input name="title" value="{{ $offer->title }}" required maxlength="160"></div>
                    <div style="flex:1;min-width:200px"><label class="f">Details</label><input name="details" value="{{ $offer->details }}" maxlength="500"></div>
                    <div><label class="f">Valid until</label><input type="date" name="valid_until" value="{{ $offer->valid_until?->toDateString() }}"></div>
                    <label class="pr-check"><input type="checkbox" name="is_active" value="1" @checked($offer->is_active)> On</label>
                    <button class="pr-btn ghost sm">Save</button><button class="pr-btn danger sm" name="delete" value="1">Delete</button>
                    @if($offer->valid_until && $offer->valid_until->isPast())<span class="pr-tag amber">Expired</span>@endif
                </form>
            @endforeach
            <form method="post" action="{{ route('admin.projects.offers', $p->id) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end;border-top:2px solid var(--pr-line);padding-top:10px">@csrf
                <div><label class="f">New offer</label><input name="title" required maxlength="160" placeholder="e.g. No cost EMI"></div>
                <div style="flex:1;min-width:200px"><label class="f">Details</label><input name="details" maxlength="500"></div>
                <div><label class="f">Valid until</label><input type="date" name="valid_until"></div>
                <button class="pr-btn sm">Add offer</button>
            </form>
        </section>
    @endif
</div></div>
@endsection
