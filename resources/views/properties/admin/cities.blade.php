@extends('layouts.site')
@section('title', 'Cities and localities — admin')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap">
    @include('properties.admin._tabs')
    <div style="display:grid;grid-template-columns:300px minmax(0,1fr);gap:16px;align-items:start">
        <nav class="pr-card-box" style="padding:8px;max-height:80vh;overflow:auto">
            <a class="pr-btn sm" href="{{ route('admin.property-cities.index') }}" style="margin:4px 4px 8px">+ New city</a>
            @foreach($cities->groupBy('country') as $country => $list)
                <div class="pr-mute" style="font-size:12px;font-weight:700;margin:8px 10px 2px">{{ $country }}</div>
                @foreach($list as $c)<a href="{{ route('admin.property-cities.index', ['city' => $c->id]) }}" style="display:flex;justify-content:space-between;padding:6px 10px;border-radius:8px;text-decoration:none;{{ $city?->id === $c->id ? 'background:var(--pr-soft);font-weight:700' : 'color:#374151' }}"><span>{{ $c->name }}@unless($c->is_active) <small>(off)</small>@endunless</span><small>{{ $c->localities_count }}</small></a>@endforeach
            @endforeach
        </nav>
        <div style="display:grid;gap:16px">
            <form class="pr-card-box" method="post" enctype="multipart/form-data" action="{{ $city ? route('admin.property-cities.update', $city->id) : route('admin.property-cities.store') }}">@csrf
                <h1 style="margin-top:0;font-size:22px">{{ $city ? $city->name : 'New city' }} @if($city)<a href="{{ route('real-estate.city', $city->slug) }}" target="_blank" style="font-size:14px">view page ↗</a>@endif</h1>
                <div class="pr-grid">
                    <div><label class="f">Name</label><input name="name" value="{{ old('name', $city?->name) }}" required maxlength="80"></div>
                    <div><label class="f">State</label><input name="state" value="{{ old('state', $city?->state) }}" maxlength="80"></div>
                    <div><label class="f">Country</label><input name="country" value="{{ old('country', $city?->country ?? 'India') }}" required maxlength="60"></div>
                    <div><label class="f">Order</label><input type="number" name="sort" min="0" value="{{ old('sort', $city?->sort ?? 0) }}"></div>
                    <div class="full"><label class="f">Zones (comma separated)</label><input name="zones" value="{{ old('zones', implode(', ', $city?->zones ?? [])) }}" placeholder="e.g. {{ $city?->name ?? 'City' }} East, {{ $city?->name ?? 'City' }} North"></div>
                    <div><label class="f">Latitude</label><input name="latitude" value="{{ old('latitude', $city?->latitude) }}"></div>
                    <div><label class="f">Longitude</label><input name="longitude" value="{{ old('longitude', $city?->longitude) }}"></div>
                    <div><label class="f">Photo (upload)</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
                    <div><label class="f">…or photo URL</label><input type="url" name="image_url" value="{{ old('image_url', $city?->image_url) }}" maxlength="500"></div>
                </div>
                @php($img = $city?->image_path ? Storage::disk('public')->url($city->image_path) : $city?->image_url)
                @if($img)<img src="{{ $img }}" alt="" style="height:90px;border-radius:10px;margin-top:10px">@endif
                <label class="pr-check" style="margin-top:10px"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $city?->is_active ?? true))> Active</label>
                <button class="pr-btn" style="margin-top:12px">Save city</button>
            </form>
            @if($city)
                <form class="pr-card-box" method="post" action="{{ route('admin.property-cities.localities', $city->id) }}">@csrf
                    <h2 style="margin-top:0">Localities</h2>
                    @if($city->localities->isNotEmpty())
                        <table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:12px">
                            <thead><tr style="text-align:left"><th style="padding:6px">Locality</th><th style="padding:6px">Zone</th><th style="padding:6px">On</th></tr></thead>
                            @foreach($city->localities as $l)
                                <tr style="border-top:1px solid var(--pr-line)"><td style="padding:6px">{{ $l->name }}</td>
                                    <td style="padding:6px"><select name="rows[{{ $l->id }}][zone]"><option value="">—</option>@foreach($city->zones ?? [] as $z)<option @selected($l->zone === $z)>{{ $z }}</option>@endforeach</select></td>
                                    <td style="padding:6px"><input type="checkbox" name="rows[{{ $l->id }}][is_active]" value="1" @checked($l->is_active) aria-label="Active"></td></tr>
                            @endforeach
                        </table>
                    @endif
                    <div class="pr-grid">
                        <div class="full"><label class="f">Add localities (one per line)</label><textarea name="new" rows="4"></textarea></div>
                        <div><label class="f">Zone for new ones</label><select name="new_zone"><option value="">—</option>@foreach($city->zones ?? [] as $z)<option>{{ $z }}</option>@endforeach</select></div>
                    </div>
                    <button class="pr-btn" style="margin-top:12px">Save localities</button>
                </form>
            @endif
        </div>
    </div>
</div></div>
@endsection
