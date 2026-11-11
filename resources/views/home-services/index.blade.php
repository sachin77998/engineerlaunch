@extends('layouts.site')
@section('title', 'Home services' . ($city ? ' in ' . $city : '') . ' — cleaning, packers & movers, repairs — ' . config('properties.brand'))
@section('content')
@include('home-services._head')
@php
    $c = config('home_services');
    $PF = \App\Services\Property\PropertyFormat::class;
    $linkUrl = fn ($l) => isset($l[3]) ? route($l[2], array_map(fn ($p) => $p === '{city}' ? \Illuminate\Support\Str::slug(session('legal_city', $city ?? config('legal.default_city'))) : $p, $l[3])) : route($l[2]);
    $book = fn ($cat, $q = []) => route('home-services.category', array_merge([$citySlug ?? '', $cat], $q));
    $stars = fn ($cat) => isset($ratings[$cat]) ? number_format($ratings[$cat]->r, 1) : null;
@endphp
@push('styles')<style>
.hs-hero{background:#eef2ff;padding:22px 0 30px}
.hs-search{display:flex;align-items:center;gap:8px;max-width:620px;margin:0 auto 22px;background:#fff;border:1px solid #d1d5db;border-radius:999px;padding:0 18px}.hs-search input{border:0!important;outline:0!important;padding:12px 0!important}
.hs-tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:18px 12px;max-width:900px;margin:0 auto}
.hs-tile{display:flex;flex-direction:column;align-items:center;gap:6px;text-decoration:none;color:#374151;font-size:14px;text-align:center}
.hs-tile i{font-style:normal;width:56px;height:56px;border-radius:50%;background:#fff;display:grid;place-items:center;font-size:26px;box-shadow:0 2px 8px rgba(17,24,39,.08)}
.hs-band{background:#dbe4f5;padding:24px 0}
.hs-feat{display:grid;grid-template-columns:1fr 1fr;gap:12px}.hs-feat a{display:grid;grid-template-columns:40% 1fr;background:#fff;border-radius:6px;overflow:hidden;text-decoration:none;color:#1f2937;min-height:140px}
.hs-feat a:last-child:nth-child(odd){grid-column:1/-1}.hs-feat a i{font-style:normal;font-size:60px;display:grid;place-items:center;background:#f5f7fb}
.hs-feat a div{display:flex;flex-direction:column;justify-content:center;align-items:center;gap:6px}.hs-feat b{font-size:24px}.hs-feat small{color:#6b7280;font-size:15px}
.hs-row{text-align:center;padding:26px 0;border-bottom:8px solid #f3f4f6}.hs-row h2{color:#1e1b4b;font-size:30px;margin:0 0 18px}
.hs-circles{display:flex;justify-content:center;gap:28px;flex-wrap:wrap}.hs-circles a{display:flex;flex-direction:column;align-items:center;gap:8px;text-decoration:none;color:#374151;width:110px}
.hs-circles i{font-style:normal;width:74px;height:74px;border-radius:50%;border:2px solid #f43f5e;display:grid;place-items:center;font-size:32px;background:#fff7f8}
.hs-pop{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}.hs-pop a{position:relative;border-radius:8px;min-height:160px;display:flex;align-items:flex-end;justify-content:space-between;padding:12px;color:#fff;text-decoration:none;font-weight:700}
.hs-pop a span.rt{background:rgba(0,0,0,.4);border-radius:6px;padding:2px 8px;font-size:12.5px}
.hs-sec{margin-top:30px}.hs-sec h2{display:flex;justify-content:space-between;align-items:baseline;gap:10px;font-size:22px}.hs-sec h2 a{font-size:14px;color:#e11d48}
.hs-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px}
.hs-card{background:#fff;border:1px solid var(--pr-line);border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:6px;text-decoration:none;color:inherit}.hs-card[hidden]{display:none}
.hs-card:hover{box-shadow:0 6px 18px rgba(17,24,39,.07)}.hs-card p{margin:0;color:#4b5563;font-size:14px}.hs-card b.p{margin-top:auto;font-size:14px;color:#047857}
.hs-revs{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}.hs-rev{background:#fff;border-radius:10px;padding:18px}.hs-rev header{display:flex;gap:10px;align-items:center;margin-bottom:8px}
.hs-rev header i{font-style:normal;width:40px;height:40px;border-radius:50%;background:#e0e7ff;display:grid;place-items:center;font-weight:800}.hs-rev small{color:var(--pr-mute)}.hs-rev p{margin:0;color:#374151;line-height:1.5}
@media(max-width:700px){.hs-feat{grid-template-columns:1fr}.hs-feat a:last-child:nth-child(odd){grid-column:auto}}
</style>@endpush
<div class="pr" style="padding-bottom:30px;background:#fff">
<section class="hs-hero"><div class="pr-wrap">
    @if($city)<label class="hs-search"><span aria-hidden="true">🔍</span><input type="search" placeholder="Search full house cleaning, AC service, plumber…" data-hs-q aria-label="Search services"></label>@endif
    <div class="hs-tiles">
        @foreach($c['categories'] as $k => [$label, $icon])
            @if(!$city || $services->has($k) || $k === 'packers')<a class="hs-tile" href="{{ $city ? $book($k) : '#' }}" @unless($city) data-open-city @endunless><i>{{ $icon }}</i>{{ $label }}</a>@endif
        @endforeach
        @foreach($c['links'] as $l)<a class="hs-tile" href="{{ $linkUrl($l) }}"><i>{{ $l[1] }}</i>{{ $l[0] }}</a>@endforeach
    </div>
</div></section>
@if(session('success'))<div class="pr-wrap"><div class="pr-flash ok">{{ session('success') }}</div></div>@endif
@if($city)
    <div class="lg-empty pr-mute pr-wrap" data-hs-empty hidden style="margin:20px auto;text-align:center">No services match your search.</div>
    <div data-hs-browse>
    <section class="hs-band"><div class="pr-wrap hs-feat">
        @foreach($c['featured'] as $cat => [$label, $icon])@if($services->has($cat))<a href="{{ $book($cat) }}"><i>{{ $icon }}</i><div><b>{{ $label }} ›</b><small>{{ isset($starting[$cat]) ? 'Starting @ ' . $PF::full($starting[$cat]) : 'Book a visit' }}</small></div></a>@endif @endforeach
    </div></section>
    @foreach(['cleaning' => 'Home cleaning services', 'repairs' => 'Home repair services', 'appliances' => 'AC & appliance services'] as $cat => $heading)
        @if($services->has($cat))
            <section class="hs-row"><h2>{{ $heading }}</h2><div class="hs-circles">
                @foreach($services[$cat]->take(6) as $s)<a href="{{ $book($cat, ['add' => $s->id]) }}#s{{ $s->id }}"><i>{{ $c['icons'][$s->slug] ?? $c['categories'][$cat][1] }}</i>{{ $s->name }}</a>@endforeach
            </div></section>
        @endif
    @endforeach
    <section class="hs-row"><div class="pr-wrap"><h2>Popular services</h2>
        <div class="hs-pop">
            @foreach([['Packers & movers', $book('packers'), 'packers', 'linear-gradient(135deg,#7c2d12,#c2410c)'], ['Home painting', $book('painting'), 'painting', 'linear-gradient(135deg,#1e3a8a,#2563eb)'], ['Home interiors', route('interiors.index'), null, 'linear-gradient(135deg,#14532d,#15803d)'], ['Rental agreement', route('rent-agreement.index'), null, 'linear-gradient(135deg,#78350f,#b45309)']] as [$label, $url, $cat, $bg])
                <a href="{{ $url }}" style="background:{{ $bg }}">{{ $label }}@if($cat && $stars($cat))<span class="rt">{{ $stars($cat) }} ★</span>@endif</a>
            @endforeach
        </div></div></section>
    <section class="hs-band"><div class="pr-wrap"><h2 style="text-align:center;color:#1e1b4b;font-size:30px;margin:0 0 18px">Relocation simplified</h2>
        <div class="hs-feat">
            <a href="{{ $book('packers', ['type' => 'intercity']) }}"><i>🚛</i><div><b>Between cities ›</b><small>Get a free quote</small></div></a>
            <a href="{{ $book('packers', ['type' => 'within']) }}"><i>📍</i><div><b>Within the city ›</b><small>Get a free quote</small></div></a>
        </div></div></section>
    </div>
    <div class="pr-wrap">
        @foreach($services as $cat => $list)
            <section class="hs-sec" data-hs-sec>
                <h2>{{ $c['categories'][$cat][1] }} {{ $c['categories'][$cat][0] }} in {{ $city }} <a href="{{ $book($cat) }}">Book →</a></h2>
                <div class="hs-cards">
                    @foreach($list as $s)<a class="hs-card" href="{{ $book($cat, ['add' => $s->id]) }}#s{{ $s->id }}" data-text="{{ mb_strtolower($s->name . ' ' . $s->summary . ' ' . $c['categories'][$cat][0]) }}"><b>{{ $s->name }}</b><p>{{ $s->summary }}</p><b class="p">{{ $s->priceLabel() }}</b></a>@endforeach
                </div>
            </section>
        @endforeach
        <section class="hs-sec" data-hs-sec><h2>🚚 Packers &amp; movers in {{ $city }} <a href="{{ $book('packers') }}">Get quote →</a></h2>
            <div class="hs-cards"><a class="hs-card" href="{{ $book('packers') }}" data-text="packers movers shifting relocation house move"><b>House shifting</b><p>Within {{ $city }} or to another city — tell us your move and get a quote.</p><b class="p">Free quote</b></a></div>
        </section>
    </div>
    @if($reviews->isNotEmpty())
        <section class="hs-band" style="margin-top:30px"><div class="pr-wrap"><h2 style="text-align:center;color:#1e1b4b;font-size:30px;margin:0 0 18px">Customer reviews</h2>
            <div class="hs-revs">@foreach($reviews as $r)<article class="hs-rev"><header><i>{{ mb_strtoupper(mb_substr($r->user?->name ?? 'C', 0, 1)) }}</i><div><b>{{ $r->user?->name ?? 'Customer' }}</b> <span style="color:#b45309">{{ $r->rating }} ★</span><br><small>{{ $c['categories'][$r->category][0] ?? '' }} · {{ $r->city }}</small></div></header><p>{{ \Illuminate\Support\Str::limit($r->body, 220) }}</p></article>@endforeach</div>
        </div></section>
    @endif
@endif
</div>
@if($city)
@push('scripts')<script>
(function () {
    var q = document.querySelector('[data-hs-q]'); if (!q) return;
    var browse = document.querySelector('[data-hs-browse]');
    q.addEventListener('input', function () {
        var t = q.value.trim().toLowerCase(), any = false;
        browse.hidden = !!t;
        document.querySelectorAll('[data-hs-sec]').forEach(function (sec) {
            var shown = 0; sec.querySelectorAll('.hs-card').forEach(function (c) { var ok = !t || c.dataset.text.indexOf(t) > -1; c.hidden = !ok; if (ok) shown++; });
            sec.hidden = !shown; if (shown) any = true;
        });
        document.querySelector('[data-hs-empty]').hidden = any;
    });
})();
</script>@endpush
@endif
@include('home-services._directory')
@include('partials.property-services')
@endsection
