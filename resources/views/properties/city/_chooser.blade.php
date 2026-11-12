{{-- "Choose a city": pick Buy / Rent / Plots / PG, residential or commercial, then a city or one of its zones.
     Expects $directory (CityDirectory::all()) and $trail; optional $inline (render as a panel instead of a dialog). --}}
@php
    $inline = $inline ?? false;
    $countries = collect($directory)->pluck('country')->unique()->values();
    $urls = [
        'city' => route('real-estate.city', '__C__'),
        'buy' => route('properties.search', ['buy', '__C__']), 'rent' => route('properties.search', ['rent', '__C__']),
        'commercial-buy' => route('properties.search', ['commercial-buy', '__C__']), 'commercial-rent' => route('properties.search', ['commercial-rent', '__C__']),
    ];
@endphp
<div class="cp-chooser-wrap {{ $inline ? 'inline' : '' }}" id="cpChooser" @unless($inline) hidden role="dialog" aria-modal="true" aria-labelledby="cpChooserTitle" @endunless>
    <div class="cp-chooser" data-urls='@json($urls)' data-cities='@json($directory)'>
        @unless($inline)<button type="button" class="cp-x" data-chooser-close aria-label="Close">×</button>@endunless
        <h2 id="cpChooserTitle">Choose a city</h2>
        <div class="cp-ctabs" role="tablist">
            @foreach(['buy' => 'Buy', 'rent' => 'Rent / lease', 'plots' => 'Plots / land', 'pg' => 'PG / co-living'] as $k => $label)
                <button type="button" role="tab" data-ctab="{{ $k }}" class="{{ $loop->first ? 'on' : '' }}">{{ $label }}</button>
            @endforeach
        </div>
        <form class="cp-cbar" data-chooser-form>
            <select data-segment aria-label="Segment"><option value="residential">Residential</option><option value="commercial">Commercial</option></select>
            <div class="cp-cinput"><span aria-hidden="true">🔍</span><input type="text" data-city-input placeholder="City name" autocomplete="off" aria-label="City name">
                <div class="cp-clist" data-city-list hidden></div></div>
            <button class="pr-btn">Explore</button>
        </form>
        <p class="cp-cerr" data-city-err hidden>Pick a city from the list.</p>
        @if($trail)
            <div class="cp-trail"><span class="pr-mute">Recently browsed</span>@foreach($trail as $t)<a href="{{ $t['url'] }}">{{ $t['icon'] }} {{ $t['label'] }}</a>@endforeach</div>
        @endif
        <div class="cp-cfoot">
            @if($countries->count() > 1)
                <button type="button" data-country="" class="on">All</button>@foreach($countries as $country)<button type="button" data-country="{{ $country }}">{{ $country }}</button>@endforeach
            @endif
            <a href="{{ route('real-estate.index') }}" style="margin-left:auto">View top cities ›</a>
        </div>
    </div>
</div>
@once
@push('scripts')<script>
(function () {
    var wrap = document.getElementById('cpChooser'); if (!wrap) return;
    var box = wrap.querySelector('.cp-chooser'), urls = JSON.parse(box.dataset.urls), cities = JSON.parse(box.dataset.cities);
    var input = box.querySelector('[data-city-input]'), list = box.querySelector('[data-city-list]'), seg = box.querySelector('[data-segment]'), err = box.querySelector('[data-city-err]');
    var tab = 'buy', country = '', picked = null;
    // One entry per city and per zone of a city ("Bengaluru East" → Bengaluru, zone East).
    var entries = [];
    cities.forEach(function (c) {
        entries.push({label: c.name, slug: c.slug, zone: null, country: c.country, hint: c.count ? c.count.toLocaleString('en-IN') + ' properties' : 'City'});
        (c.zones || []).forEach(function (z) { entries.push({label: z, slug: c.slug, zone: z, country: c.country, hint: 'Area in ' + c.name}); });
    });
    function urlFor(e) {
        var commercial = seg.value === 'commercial', u;
        if (tab === 'buy') u = commercial ? urls['commercial-buy'] : urls.city + (e.zone ? '?zone=' + encodeURIComponent(e.zone) : '');
        else if (tab === 'rent') u = commercial ? urls['commercial-rent'] : urls.rent;
        else if (tab === 'plots') u = urls.buy + '?type%5B%5D=plot';
        else u = urls.rent + '?kind=pg';
        return u.replace('__C__', e.slug);
    }
    function render() {
        var q = input.value.trim().toLowerCase();
        var hits = entries.filter(function (e) { return (!country || e.country === country) && (!q || e.label.toLowerCase().indexOf(q) !== -1); }).slice(0, 60);
        list.innerHTML = '<small>' + (q ? (hits.length ? 'Matching cities' : 'No city found') : 'Choose a city below to start exploring:') + '</small>';
        hits.forEach(function (e) {
            var b = document.createElement('button'); b.type = 'button';
            b.innerHTML = '<span></span><em></em>'; b.firstChild.textContent = e.label; b.lastChild.textContent = e.hint;
            b.addEventListener('click', function () { picked = e; input.value = e.label; list.hidden = true; location.href = urlFor(e); });
            list.appendChild(b);
        });
        list.hidden = false;
    }
    input.addEventListener('focus', render); input.addEventListener('input', function () { picked = null; err.hidden = true; render(); });
    document.addEventListener('click', function (ev) { if (!box.querySelector('.cp-cinput').contains(ev.target)) list.hidden = true; });
    box.querySelectorAll('[data-ctab]').forEach(function (b) { b.addEventListener('click', function () {
        tab = b.dataset.ctab; box.querySelectorAll('[data-ctab]').forEach(function (x) { x.classList.toggle('on', x === b); });
        seg.hidden = tab === 'plots' || tab === 'pg';
    }); });
    box.querySelectorAll('[data-country]').forEach(function (b) { b.addEventListener('click', function () {
        country = b.dataset.country; box.querySelectorAll('[data-country]').forEach(function (x) { x.classList.toggle('on', x === b); }); input.focus(); render();
    }); });
    box.querySelector('[data-chooser-form]').addEventListener('submit', function (ev) {
        ev.preventDefault();
        var q = input.value.trim().toLowerCase();
        var e = picked || entries.find(function (x) { return x.label.toLowerCase() === q; }) || (q && entries.find(function (x) { return x.label.toLowerCase().indexOf(q) === 0; }));
        if (!e) { err.hidden = false; input.focus(); return; }
        location.href = urlFor(e);
    });
    function open() { wrap.hidden = false; setTimeout(function () { input.focus(); }, 30); }
    function close() { if (!wrap.classList.contains('inline')) wrap.hidden = true; }
    document.querySelectorAll('[data-chooser-open]').forEach(function (b) { b.addEventListener('click', function (ev) { ev.preventDefault(); open(); }); });
    wrap.addEventListener('click', function (ev) { if (ev.target === wrap) close(); });
    box.querySelectorAll('[data-chooser-close]').forEach(function (b) { b.addEventListener('click', close); });
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') close(); });
})();
</script>@endpush
@endonce
