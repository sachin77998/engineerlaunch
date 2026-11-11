@php($p = old('property', $a->property ?? []))
<form method="post" action="{{ route('rent-agreement.save', [$a, 'property']) }}" style="display:grid;gap:16px">
    @csrf
    <div><label class="f">Use</label><div class="hl-pills">@foreach($o['usage'] as $k => $label)<label><input type="radio" name="usage" value="{{ $k }}" required @checked(old('usage', $a->usage) === $k)><span>{{ $label }}</span></label>@endforeach</div></div>
    <div><label class="f">Property type</label><div class="hl-pills">@foreach($o['property_types'] as $k => $label)<label><input type="radio" name="property[type]" value="{{ $k }}" required @checked(($p['type'] ?? 'flat') === $k)><span>{{ $label }}</span></label>@endforeach</div></div>
    <div><label class="f" for="pr-addr">Address of the rented property <em>*</em></label><input id="pr-addr" type="text" name="property[address]" required maxlength="300" value="{{ $p['address'] ?? '' }}" placeholder="Flat / house no., building, street, locality, {{ $a->city }}"></div>
    <div class="hl-two">
        <div><label class="f" for="pr-pin">PIN code</label><input id="pr-pin" type="text" name="property[pincode]" maxlength="6" inputmode="numeric" value="{{ $p['pincode'] ?? '' }}"></div>
        <div><label class="f" for="pr-bhk">Bedrooms (BHK)</label><input id="pr-bhk" type="number" name="property[bhk]" min="0" max="20" value="{{ $p['bhk'] ?? '' }}"></div>
    </div>
    <div><label class="f">Furnishing</label><div class="hl-pills">@foreach(config('properties.furnishing') as $k => $label)<label><input type="radio" name="property[furnishing]" value="{{ $k }}" required @checked(($p['furnishing'] ?? 'none') === $k)><span>{{ $label }}</span></label>@endforeach</div></div>
    <div><label class="f">Fixtures &amp; fittings handed over</label>
        <div class="ra-fix">@foreach(config('properties.furnishings') as $k => $label)<label>{{ $label }}<input type="number" name="fixtures[{{ $k }}]" min="0" max="99" value="{{ old('fixtures.' . $k, $p['fixtures'][$k] ?? '') }}" placeholder="0"></label>@endforeach</div></div>
    <div class="hl-bar"><a class="pr-btn ghost" style="min-width:0;background:#fff;color:#0f766e" href="{{ route('rent-agreement.step', [$a, 'parties']) }}">Back</a><button class="pr-btn">Continue</button></div>
</form>
