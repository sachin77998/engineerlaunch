@php
    $err = fn ($k) => $errors->has($k) ? '<div class="pr-err">' . e($errors->first($k)) . '</div>' : '';
    $country = $v('country', 'India');
@endphp
@push('styles')<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">@endpush
<div class="pr-grid">
    <div><label class="f" for="l-country">Country <em>*</em></label><select id="l-country" name="country">@foreach($o['countries'] as $c => $cfg)<option value="{{ $c }}" @selected($country === $c)>{{ $c }} ({{ $cfg['currency'] }})</option>@endforeach</select>{!! $err('country') !!}</div>
    <div><label class="f" for="l-city">City <em>*</em></label><input id="l-city" type="text" name="city" list="l-cities" maxlength="80" value="{{ $v('city') }}" placeholder="Start typing your city">{!! $err('city') !!}
        @foreach($o['cities'] as $c => $list)<datalist id="l-cities-{{ Str::slug($c) }}">@foreach($list as $city)<option value="{{ $city }}">@endforeach</datalist>@endforeach
        <datalist id="l-cities"></datalist></div>
    <div><label class="f" for="l-loc">Locality <em>*</em></label><input id="l-loc" type="text" name="locality" maxlength="150" value="{{ $v('locality') }}" placeholder="e.g. Indiranagar">{!! $err('locality') !!}</div>
    <div><label class="f" for="l-soc">Society / building / project</label><input id="l-soc" type="text" name="society" maxlength="150" value="{{ $v('society') }}">{!! $err('society') !!}</div>
    <div><label class="f" for="l-street">Street / area <em>*</em></label><input id="l-street" type="text" name="street" maxlength="200" value="{{ $v('street') }}" placeholder="House no., street">{!! $err('street') !!}</div>
    <div><label class="f" for="l-land">Landmark</label><input id="l-land" type="text" name="landmark" maxlength="150" value="{{ $v('landmark') }}" placeholder="Popular landmark nearby">{!! $err('landmark') !!}</div>
    <div><label class="f" for="l-state">State / region</label><input id="l-state" type="text" name="state" maxlength="80" value="{{ $v('state') }}"></div>
    <div><label class="f" for="l-pin">PIN / postal code</label><input id="l-pin" type="text" name="pincode" maxlength="12" value="{{ $v('pincode') }}"></div>
    <div class="full">
        <label class="f">Mark the location on the map</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap"><button type="button" class="pr-btn ghost sm" id="l-find" data-url="{{ route('properties.geocode') }}">Find from the address</button><button type="button" class="pr-btn ghost sm" id="l-here">Use my current location</button><span class="pr-mute" id="l-msg" style="align-self:center;font-size:13px"></span></div>
        <div id="l-map" style="height:300px;border-radius:12px;margin-top:10px;z-index:0"></div>
        <input type="hidden" name="latitude" id="l-lat" value="{{ $v('latitude') }}"><input type="hidden" name="longitude" id="l-lng" value="{{ $v('longitude') }}">
        <small class="pr-mute">Click the map or drag the pin to the exact spot.</small>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var country = document.getElementById('l-country'), list = document.getElementById('l-cities');
    function cities() { var src = document.getElementById('l-cities-' + country.value.toLowerCase().replace(/[^a-z0-9]+/g, '-')); list.innerHTML = src ? src.innerHTML : ''; }
    country.addEventListener('change', cities); cities();
    if (!window.L) return;
    var lat = document.getElementById('l-lat'), lng = document.getElementById('l-lng'), msg = document.getElementById('l-msg');
    var start = lat.value ? [parseFloat(lat.value), parseFloat(lng.value)] : [20.59, 78.96];
    var map = L.map('l-map').setView(start, lat.value ? 16 : 4), pin = null;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);
    function place(p, zoom) {
        lat.value = p.lat.toFixed(7); lng.value = p.lng.toFixed(7);
        if (!pin) { pin = L.marker(p, {draggable: true}).addTo(map); pin.on('dragend', function () { place(pin.getLatLng()); }); } else pin.setLatLng(p);
        if (zoom) map.setView(p, zoom);
    }
    if (lat.value) place(L.latLng(start));
    map.on('click', function (e) { place(e.latlng); });
    document.getElementById('l-find').addEventListener('click', function () {
        var q = ['l-street', 'l-soc', 'l-loc', 'l-city', 'l-state', 'l-pin'].map(function (id) { return document.getElementById(id).value.trim(); }).filter(Boolean).concat([country.value]).join(', ');
        msg.textContent = 'Searching…';
        fetch(this.dataset.url + '?q=' + encodeURIComponent(q), {headers: {Accept: 'application/json'}})
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (d) { place(L.latLng(d.lat, d.lng), 16); msg.textContent = 'Found. Drag the pin if it is not exact.'; })
            .catch(function () {
                // The full address may be too specific; try locality and city.
                var q2 = ['l-loc', 'l-city'].map(function (id) { return document.getElementById(id).value.trim(); }).filter(Boolean).concat([country.value]).join(', ');
                fetch(document.getElementById('l-find').dataset.url + '?q=' + encodeURIComponent(q2), {headers: {Accept: 'application/json'}})
                    .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                    .then(function (d) { place(L.latLng(d.lat, d.lng), 14); msg.textContent = 'Showing the locality. Click the exact spot.'; })
                    .catch(function () { msg.textContent = 'Not found. Click the spot on the map.'; });
            });
    });
    document.getElementById('l-here').addEventListener('click', function () {
        if (!navigator.geolocation) { msg.textContent = 'Location is not available in this browser.'; return; }
        msg.textContent = 'Locating…';
        navigator.geolocation.getCurrentPosition(function (pos) { place(L.latLng(pos.coords.latitude, pos.coords.longitude), 17); msg.textContent = ''; }, function () { msg.textContent = 'Location permission was denied.'; });
    });
})();
</script>
@endpush
