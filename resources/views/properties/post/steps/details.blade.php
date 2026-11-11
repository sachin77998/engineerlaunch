@php
    $err = fn ($k) => $errors->has($k) ? '<div class="pr-err">' . e($errors->first($k)) . '</div>' : '';
    $commercial = $p->segment === 'commercial';
    $namedFloors = $commercial ? $o['commercial']['floors'] : ['lower_basement' => 'Lower basement', 'upper_basement' => 'Upper basement', '0' => 'Ground'];
@endphp
<div class="pr-grid">
    @if($commercial)
        <div><label class="f" for="d-kind">Property type <em>*</em></label><select id="d-kind" name="kind">@foreach($o['kinds']['commercial'] as $k => $label)<option value="{{ $k }}" @selected($v('kind') === $k)>{{ $label }}</option>@endforeach</select>{!! $err('kind') !!}</div>
        <div><label class="f" for="d-bt">Building type <em>*</em></label><select id="d-bt" name="building_type"><option value="">Select</option>@foreach($o['building_types']['commercial'] as $k => $label)<option value="{{ $k }}" @selected($v('building_type') === $k)>{{ $label }}</option>@endforeach</select>{!! $err('building_type') !!}</div>
    @elseif(!$p->isLand())
        @if($p->kind !== 'pg')
            <div><label class="f" for="d-bhk">BHK type <em>*</em></label><select id="d-bhk" name="bhk"><option value="">Select</option>@foreach($o['bhk'] as $k => $label)<option value="{{ $k }}" @selected((string) $v('bhk') === (string) $k)>{{ $label }}</option>@endforeach</select>{!! $err('bhk') !!}</div>
        @endif
        <div><label class="f" for="d-bt">{{ $p->isPg() ? 'Building type' : 'Apartment / house type' }} @unless($p->isPg())<em>*</em>@endunless</label><select id="d-bt" name="building_type"><option value="">Select</option>@foreach($o['building_types']['residential'] as $k => $label)<option value="{{ $k }}" @selected($v('building_type') === $k)>{{ $label }}</option>@endforeach</select>{!! $err('building_type') !!}</div>
    @endif

    @unless($p->isLand())
        <div><label class="f" for="d-age">Age of property @if($commercial)<em>*</em>@endif</label><select id="d-age" name="age"><option value="">Select</option>@foreach($o['ages'] as $k => $label)<option value="{{ $k }}" @selected($v('age') === $k)>{{ $label }}</option>@endforeach</select>{!! $err('age') !!}</div>
        <div class="pr-grid" style="gap:12px">
            <div><label class="f" for="d-floor">Floor @if($commercial)<em>*</em>@endif</label><select id="d-floor" name="floor"><option value="">Select</option>@foreach($namedFloors as $k => $label)<option value="{{ $k }}" @selected((string) $v('floor') === (string) $k)>{{ $label }}</option>@endforeach @for($n = 1; $n <= 99; $n++)<option value="{{ $n }}" @selected((string) $v('floor') === (string) $n)>{{ $n }}</option>@endfor</select>{!! $err('floor') !!}</div>
            <div><label class="f" for="d-total">Total floors @if($commercial)<em>*</em>@endif</label><select id="d-total" name="total_floors"><option value="">Select</option>@if($commercial)@foreach($o['commercial']['total_floors'] as $k => $label)<option value="{{ $k }}" @selected($v('total_floors') === $k)>{{ $label }}</option>@endforeach @endif @for($n = 1; $n <= 99; $n++)<option value="{{ $n }}" @selected((string) $v('total_floors') === (string) $n)>{{ $n }}</option>@endfor</select>{!! $err('total_floors') !!}</div>
        </div>
    @endunless

    <div><label class="f" for="d-area">{{ $p->isLand() ? 'Plot area' : 'Super built-up area' }} @unless($p->isPg())<em>*</em>@endunless</label>
        <div style="display:flex;gap:8px"><input id="d-area" type="number" step="any" min="1" name="area_value" value="{{ $v('area_value') }}" placeholder="Area" style="flex:1">
            <select name="area_unit" aria-label="Unit" style="width:140px">@foreach($o['area_units'] as $k => [$label]) <option value="{{ $k }}" @selected($v('area_unit', 'sqft') === $k)>{{ $label }}</option>@endforeach</select></div>{!! $err('area_value') !!}</div>
    @if(!$p->isLand() && !$p->isPg())
        <div><label class="f" for="d-carpet">Carpet area</label><input id="d-carpet" type="number" step="any" min="1" name="carpet_area" value="{{ old('carpet_area', $p->carpet_area_sqft ? \App\Services\Property\PropertyFormat::fromSqft($p->carpet_area_sqft, $p->area_unit) : null) }}" placeholder="Same unit as above">{!! $err('carpet_area') !!}</div>
    @endif

    @unless($p->isLand())
        <div><label class="f" for="d-furn">Furnishing @unless($p->isPg())<em>*</em>@endunless</label><select id="d-furn" name="furnishing"><option value="">Select</option>@foreach($o['furnishing'] as $k => $label)<option value="{{ $k }}" @selected($v('furnishing') === $k)>{{ $label }}</option>@endforeach</select>{!! $err('furnishing') !!}</div>
    @endunless
    @if(!$commercial)
        <div><label class="f" for="d-facing">Facing</label><select id="d-facing" name="facing"><option value="">Select</option>@foreach($o['facing'] as $k => $label)<option value="{{ $k }}" @selected($v('facing') === $k)>{{ $label }}</option>@endforeach</select></div>
    @endif

    @if(!$commercial && !$p->isLand() && $p->kind !== 'pg')
        <div class="full pr-grid three" style="gap:12px">
            @foreach(['bedrooms' => 'Bedrooms', 'bathrooms' => 'Bathrooms', 'balconies' => 'Balconies'] as $k => $label)
                <div><label class="f" for="d-{{ $k }}">{{ $label }}</label><input id="d-{{ $k }}" type="number" min="0" max="20" name="{{ $k }}" value="{{ $v($k) }}">{!! $err($k) !!}</div>
            @endforeach
        </div>
    @endif

    @if($p->isLand())
        <div><label class="f" for="d-len">Plot length (ft)</label><input id="d-len" type="number" step="any" min="1" name="plot_length" value="{{ old('plot_length', $p->details['plot_length'] ?? null) }}"></div>
        <div><label class="f" for="d-br">Plot breadth (ft)</label><input id="d-br" type="number" step="any" min="1" name="plot_breadth" value="{{ old('plot_breadth', $p->details['plot_breadth'] ?? null) }}"></div>
        <div><label class="f" for="d-sides">Open sides</label><select id="d-sides" name="open_sides"><option value="">Select</option>@for($n = 1; $n <= 4; $n++)<option value="{{ $n }}" @selected((string) old('open_sides', $p->details['open_sides'] ?? '') === (string) $n)>{{ $n }}</option>@endfor</select></div>
        <div><label class="f" for="d-fa">Floors allowed for construction</label><input id="d-fa" type="number" min="1" max="99" name="floors_allowed" value="{{ old('floors_allowed', $p->details['floors_allowed'] ?? null) }}"></div>
        <div><label class="pr-check"><input type="checkbox" name="boundary_wall" value="1" @checked(old('boundary_wall', $p->details['boundary_wall'] ?? false))> Boundary wall built</label></div>
    @endif

    @if($commercial)
        <div class="full"><label class="f">Other features</label><div class="pr-chips">@foreach($o['commercial']['other_features'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="features[]" value="{{ $k }}" @checked(in_array($k, old('features', $p->features ?? []), true))>{{ $label }}</label>@endforeach</div></div>
    @endif
</div>
