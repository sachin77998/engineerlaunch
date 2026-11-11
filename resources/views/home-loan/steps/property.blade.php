<form method="post" action="{{ route('home-loan.save', [$app, 'property']) }}" style="display:grid;gap:18px">
    @csrf
    @if($app->property)<div class="pr-flash ok" style="margin:0">Property: <a href="{{ route('properties.show', $app->property) }}">{{ $app->property->title }}</a></div>@endif
    <div><label class="f">Property finalised?</label>
        <div class="hl-pills">@foreach(['1' => 'Yes, finalised', '0' => 'Still searching'] as $v => $label)<label><input type="radio" name="property_finalized" value="{{ $v }}" required @checked((string) old('property_finalized', (int) $app->property_finalized) === (string) $v)><span>{{ $label }}</span></label>@endforeach</div></div>
    <div><label class="f">Loan for</label>
        <div class="hl-pills">@foreach($o['property_types'] as $k => $label)<label><input type="radio" name="property_type" value="{{ $k }}" required @checked(old('property_type', $app->property_type) === $k)><span>{{ $label }}</span></label>@endforeach</div></div>
    <div><label class="f">Property status</label>
        <div class="hl-pills">@foreach($o['property_stages'] as $k => $label)<label><input type="radio" name="property_stage" value="{{ $k }}" @checked(old('property_stage', $app->property_stage) === $k)><span>{{ $label }}</span></label>@endforeach</div></div>
    <div class="hl-two">
        <div><label class="f" for="hl-pcity">Property city</label><select id="hl-pcity" name="property_city" required><option value="">Select city</option>@foreach($cities as $c)<option @selected(old('property_city', $app->property_city ?? $app->city) === $c)>{{ $c }}</option>@endforeach</select></div>
        <div><label class="f" for="hl-pval">Property value (approx.)</label><div class="hl-in"><span>₹</span><input id="hl-pval" type="number" name="property_value" min="100000" value="{{ old('property_value', $app->property_value ?? $app->property?->saleTerms?->price) }}" placeholder="Optional"></div></div>
    </div>
    <div><label class="f" for="hl-paddr">Locality / project / address</label><input id="hl-paddr" type="text" name="property_address" maxlength="255" value="{{ old('property_address', $app->property_address) }}" placeholder="Optional"></div>
    <div class="hl-bar"><a class="pr-btn ghost" style="min-width:0;background:#fff;color:#0f766e" href="{{ route('home-loan.step', [$app, 'offers']) }}">Back</a><button class="pr-btn">Continue</button></div>
</form>
