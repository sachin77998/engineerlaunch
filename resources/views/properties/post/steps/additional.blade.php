@php
    $err = fn ($k) => $errors->has($k) ? '<div class="pr-err">' . e($errors->first($k)) . '</div>' : '';
    $d = $p->details ?? [];
    $dv = fn ($k) => old("d.$k", $d[$k] ?? '');
    $home = $p->segment === 'residential' && !$p->isLand();
@endphp
<label class="f">Key highlights</label>
<div class="pr-chips" style="margin-bottom:20px">@foreach($o['highlights'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="highlights[]" value="{{ $k }}" @checked(in_array($k, old('highlights', $p->highlights ?? []), true))>{{ $label }}</label>@endforeach</div>
@if($p->segment === 'residential')
    <label class="f">Overlooking</label>
    <div class="pr-chips" style="margin-bottom:20px">@foreach($o['overlooking'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="overlooking[]" value="{{ $k }}" @checked(in_array($k, old('overlooking', $d['overlooking'] ?? []), true))>{{ $label }}</label>@endforeach</div>
@endif
@if($p->isLand())
    <label class="f">Plot amenities</label>
    <div class="pr-chips" style="margin-bottom:20px">@foreach($o['plot_amenities'] as $k => [$label, $icon])<label class="pr-chip"><input type="checkbox" name="plot_amenities[]" value="{{ $k }}" @checked(in_array($k, old('plot_amenities', $p->amenities ?? []), true))>{{ $icon }} {{ $label }}</label>@endforeach</div>
@endif
<div class="pr-grid">
    <div class="full"><label class="f" for="x-desc">About the property</label>
        <textarea id="x-desc" name="description" rows="6" maxlength="5000" placeholder="Describe the layout, what is nearby, water and power, who it suits…">{{ $v('description') }}</textarea>{!! $err('description') !!}</div>
    @if($home)
        <div class="pr-grid" style="gap:12px">
            <div><label class="f" for="x-cp">Covered parking</label><input id="x-cp" type="number" min="0" max="50" name="d[covered_parking]" value="{{ $dv('covered_parking') }}"></div>
            <div><label class="f" for="x-op">Open parking</label><input id="x-op" type="number" min="0" max="50" name="d[open_parking]" value="{{ $dv('open_parking') }}"></div>
        </div>
        <div><label class="f" for="x-pb">Power backup</label><select id="x-pb" name="d[power_backup]"><option value="">Select</option>@foreach($o['power_backup'] as $k => $label)<option value="{{ $k }}" @selected($dv('power_backup') === $k)>{{ $label }}</option>@endforeach</select></div>
        <div><label class="f" for="x-ws">Water supply</label><select id="x-ws" name="d[water_supply]"><option value="">Select</option>@foreach($o['water_supply'] as $k => $label)<option value="{{ $k }}" @selected($dv('water_supply') === $k)>{{ $label }}</option>@endforeach</select></div>
        <div><label class="f" for="x-fl">Flooring</label><select id="x-fl" name="d[flooring]"><option value="">Select</option>@foreach($o['flooring'] as $k => $label)<option value="{{ $k }}" @selected($dv('flooring') === $k)>{{ $label }}</option>@endforeach</select></div>
        <div><label class="f" for="x-g">Gated security</label><select id="x-g" name="d[gated]"><option value="">Select</option><option value="yes" @selected($dv('gated') === 'yes')>Yes</option><option value="no" @selected($dv('gated') === 'no')>No</option></select></div>
        @if($p->listing === 'rent')
            <div><label class="f" for="x-nv">Non-veg allowed</label><select id="x-nv" name="d[non_veg]"><option value="">Select</option><option value="yes" @selected($dv('non_veg') === 'yes')>Yes</option><option value="no" @selected($dv('non_veg') === 'no')>No</option></select></div>
            <div><label class="f" for="x-pet">Pets allowed</label><select id="x-pet" name="d[pets]"><option value="">Select</option><option value="yes" @selected($dv('pets') === 'yes')>Yes</option><option value="no" @selected($dv('pets') === 'no')>No</option></select></div>
        @endif
    @elseif($p->segment === 'commercial')
        <div><label class="f" for="x-fl">Flooring</label><select id="x-fl" name="d[flooring]"><option value="">Select</option>@foreach($o['flooring'] as $k => $label)<option value="{{ $k }}" @selected($dv('flooring') === $k)>{{ $label }}</option>@endforeach</select></div>
    @endif
    @unless($p->isLand())
        <div><label class="f" for="x-os">Number of open sides</label><select id="x-os" name="d[open_sides]"><option value="">Select</option>@for($n = 1; $n <= 4; $n++)<option value="{{ $n }}" @selected((string) $dv('open_sides') === (string) $n)>{{ $n }}</option>@endfor</select></div>
    @endunless
    <div><label class="f" for="x-rw">Width of facing road</label>
        <div style="display:flex;gap:8px"><input id="x-rw" type="number" step="any" min="1" name="d[road_width]" value="{{ $dv('road_width') }}" style="flex:1">
            <select name="d[road_unit]" style="width:110px" aria-label="Road width unit"><option value="ft" @selected($dv('road_unit') !== 'm')>Feet</option><option value="m" @selected($dv('road_unit') === 'm')>Metres</option></select></div>{!! $err('d.road_width') !!}</div>
    <div class="full"><label class="f" for="x-dir">Directions for visitors</label>
        <input id="x-dir" type="text" name="directions" maxlength="500" value="{{ $v('directions') }}" placeholder="e.g. Take the lane opposite the bus depot, third building on the left">{!! $err('directions') !!}</div>
</div>
