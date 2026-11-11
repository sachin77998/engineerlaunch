@php
    $err = fn ($k) => $errors->has($k) ? '<div class="pr-err">' . e($errors->first($k)) . '</div>' : '';
    $a = $p->amenities ?? [];
@endphp
@if($p->segment === 'commercial')
    @php($c = $o['commercial'])
    <div class="pr-grid">
        @foreach(['power_backup' => ['Power backup', true], 'lift' => ['Lift', true], 'parking' => ['Parking', true], 'washrooms' => ['Washroom(s)', true]] as $k => [$label, $req])
            <div><label class="f" for="a-{{ $k }}">{{ $label }} <em>*</em></label><select id="a-{{ $k }}" name="amenities[{{ $k }}]"><option value="">Select</option>@foreach($c[$k] as $key => $text)<option value="{{ $key }}" @selected(old("amenities.$k", $a[$k] ?? '') === $key)>{{ $text }}</option>@endforeach</select>{!! $err("amenities.$k") !!}</div>
        @endforeach
        @foreach(['water_storage' => 'Water storage facility', 'security' => 'Security'] as $k => $label)
            <div><label class="f" for="a-{{ $k }}">{{ $label }}</label><select id="a-{{ $k }}" name="amenities[{{ $k }}]"><option value="">Select</option>@foreach(['yes' => 'Yes', 'no' => 'No'] as $key => $text)<option value="{{ $key }}" @selected(old("amenities.$k", $a[$k] ?? '') === $key)>{{ $text }}</option>@endforeach</select></div>
        @endforeach
        <div><label class="f" for="a-cond">Current property condition</label><select id="a-cond" name="amenities[condition]"><option value="">Select</option>@foreach($c['condition'] as $key => $text)<option value="{{ $key }}" @selected(old('amenities.condition', $a['condition'] ?? '') === $key)>{{ $text }}</option>@endforeach</select></div>
        <div><label class="f" for="a-biz">What business is running now?</label><select id="a-biz" name="amenities[current_business]"><option value="">Select</option>@foreach($c['current_business'] as $key => $text)<option value="{{ $key }}" @selected(old('amenities.current_business', $a['current_business'] ?? '') === $key)>{{ $text }}</option>@endforeach</select></div>
        <div class="full"><label class="f">Do you have more similar units available?</label><div class="pr-chips">@foreach(['no' => 'No', 'yes' => 'Yes'] as $key => $text)<label class="pr-chip"><input type="radio" name="amenities[similar_units]" value="{{ $key }}" @checked(old('amenities.similar_units', $a['similar_units'] ?? '') === $key)>{{ $text }}</label>@endforeach</div></div>
    </div>
@else
    @php($furn = $p->furnishings ?? [])
    @if($p->kind !== 'land' && $p->furnishing !== 'none')
        <label class="f">Furnishing items <span class="pr-mute" style="font-weight:500">(how many of each)</span></label>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:10px;margin-bottom:22px">
            @foreach($o['furnishings'] as $k => $label)
                <label style="display:flex;justify-content:space-between;align-items:center;gap:8px;padding:8px 10px;border:1px solid var(--pr-line);border-radius:10px;font-size:14px">{{ $label }}
                    <input type="number" min="0" max="50" name="furnishings[{{ $k }}]" value="{{ old("furnishings.$k", $furn[$k] ?? '') }}" style="width:70px;padding:6px 8px" aria-label="{{ $label }} count"></label>
            @endforeach
        </div>
    @endif
    <label class="f">Society / building amenities</label>
    <div class="pr-chips">@foreach($o['amenities'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="amenities[]" value="{{ $k }}" @checked(in_array($k, old('amenities', is_array($a) && array_is_list($a) ? $a : []), true))>{{ $label }}</label>@endforeach</div>
@endif
