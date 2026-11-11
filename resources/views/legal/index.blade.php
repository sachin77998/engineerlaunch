@extends('layouts.site')
@section('title', 'Property legal services in ' . $city . ' — ' . config('properties.brand'))
@section('content')
@include('legal._head')
@php
    $cats = collect(config('legal.categories'))->filter(fn ($c, $k) => $services->contains('category', $k));
    $first = $cats->keys()->first();
    $consult = $services->firstWhere('slug', 'lawyer-consultation');
@endphp
@push('styles')<style>
.lg-stats{display:flex;justify-content:center;gap:0;flex-wrap:wrap;margin:6px 0 18px}.lg-stats div{padding:4px 26px;border-left:1px solid rgba(255,255,255,.25);text-align:left}.lg-stats div:first-child{border:0}.lg-stats b{display:block;font-size:22px}.lg-stats span{font-size:14px;opacity:.85}
.lg-consult{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;background:linear-gradient(110deg,#14532d,#166534 55%,#1f2937);color:#fff;border-radius:22px;padding:24px 30px;margin-top:26px}
.lg-consult h2{color:#fff;margin:0;font-size:26px}.lg-consult p{margin:4px 0 0;font-size:17px;opacity:.9}
.lg-consult a,.lg-consult button{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.35);color:#fff;border-radius:999px;padding:14px 26px;font-weight:700;font-size:17px;text-decoration:none;cursor:pointer}
.lg-sec{margin-top:44px}.lg-sec>h2{font-size:30px;margin:0 0 18px}
.lg-scroll{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(300px,520px);gap:18px;overflow-x:auto;padding-bottom:8px;scroll-snap-type:x mandatory}.lg-scroll>*{scroll-snap-align:start}
.lg-partner{background:#fff;border:1px solid var(--pr-line);border-radius:22px;padding:22px 30px;position:relative}
.lg-partner .aff{position:absolute;top:0;right:22px;background:#e0e7ff;color:#3730a3;padding:8px 18px;border-radius:0 0 14px 14px;font-weight:600;font-size:14px}
.lg-partner header{display:flex;gap:12px;align-items:center}.lg-partner .rt{background:#ecfdf5;color:#047857;font-weight:700;border-radius:999px;padding:4px 12px}
.lg-partner header i{font-style:normal;width:38px;height:38px;border-radius:50%;background:#1e1b4b;color:#fff;display:grid;place-items:center;font-weight:700}
.lg-partner p{color:#374151;font-size:15.5px;line-height:1.55}
.lg-pstats{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid var(--pr-line);padding-top:14px}.lg-pstats div{padding:0 12px;border-left:1px solid var(--pr-line)}.lg-pstats div:first-child{border:0;padding-left:0}
.lg-pstats b{display:block;font-size:20px}.lg-pstats small{color:var(--pr-mute)}
.lg-reviews{display:grid;grid-template-columns:180px minmax(0,1fr);gap:24px;background:#f8fafc;border-radius:22px;padding:28px}
.lg-avg b{font-size:54px;color:#0f766e}.lg-avg b small{font-size:24px;color:#475569}.lg-avg .st{color:#0f766e;font-size:20px;letter-spacing:3px}
.lg-rev{background:#fff;border-radius:16px;padding:20px;display:flex;flex-direction:column;gap:10px}
.lg-rev header{display:flex;justify-content:space-between;gap:10px}.lg-rev h3{margin:0;font-size:19px}.lg-rev .rt{border:1px solid #a7f3d0;background:#ecfdf5;color:#047857;border-radius:8px;padding:3px 9px;font-weight:700;height:fit-content}
.lg-rev .tags span{display:inline-block;border:1px solid var(--pr-line);border-radius:8px;padding:4px 10px;margin:0 6px 6px 0;font-size:13.5px}
.lg-rev p{margin:0;color:#475569;line-height:1.55}.lg-rev p.clip{display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden}
.lg-rev button{border:0;background:none;color:#0f766e;font-weight:700;padding:0;cursor:pointer;align-self:flex-start}
.lg-rev footer{color:var(--pr-mute);font-size:14px;margin-top:auto}
.lg-faq-chips{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
.lg-faq-chips input{max-width:260px;border-radius:999px!important}
.lg-faq-chips button{border:1px solid var(--pr-line);background:#fff;border-radius:999px;padding:9px 18px;font-size:15px;cursor:pointer}.lg-faq-chips button.on{border-color:#6d28d9;background:#f5f3ff;color:#5b21b6}
.lg-faq details{background:#fff;border:1px solid var(--pr-line);border-radius:14px;padding:14px 18px;margin-bottom:10px}.lg-faq details[hidden]{display:none}
.lg-faq summary{font-weight:700;cursor:pointer}.lg-faq details p{margin:10px 0 0;color:#475569;line-height:1.55}
@media(max-width:760px){.lg-reviews{grid-template-columns:1fr}}
</style>@endpush
<div class="pr" style="padding-bottom:60px">
<section class="lg-hero">
    <div class="pr-wrap">
        <h1>Property legal services in {{ $city }}</h1>
        <div class="lg-stats">
            <div><b>{{ $services->count() }}</b><span>Services in {{ $city }}</span></div>
            @if($completed)<div><b>{{ number_format($completed) }}</b><span>Bookings completed</span></div>@endif
            @if($reviews)<div><b>{{ number_format($reviews['average'], 1) }}/5</b><span>Rating from {{ number_format($reviews['count']) }} {{ $reviews['count'] === 1 ? 'customer' : 'customers' }}</span></div>@endif
        </div>
        <form method="get" onsubmit="location.href='{{ url('/legal') }}/'+this.city.value;return false">
            <label for="lg-city" class="pr-mute" style="color:#c7d2fe">City</label>
            <select id="lg-city" name="city" onchange="this.form.onsubmit()">@foreach($cities as $c)<option value="{{ \Illuminate\Support\Str::slug($c) }}" @selected($c === $city)>{{ $c }}</option>@endforeach</select>
        </form>
        <div class="lg-tabs" role="tablist" data-lg-tabs>@foreach(config('legal.audiences') as $k => $label)<button type="button" role="tab" data-for="{{ $k }}" class="{{ $k === $for ? 'on' : '' }}">{{ $label }}</button>@endforeach</div>
    </div>
</section>
<div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }} · <a href="{{ route('legal.cart') }}">Go to cart →</a></div>@endif
    <div class="lg-panel">
        <div class="lg-search">
            <label><span aria-hidden="true">🔍</span><input type="search" placeholder="Explore services" data-lg-q aria-label="Search services"></label>
            <button type="button" data-lg-all>Explore all <span data-lg-for-label>{{ config('legal.audiences')[$for] }}</span> services →</button>
        </div>
        <div class="lg-body">
            <aside class="lg-cats" data-lg-cats>
                <h3>Service categories</h3>
                @foreach($cats as $k => [$label, $icon])<button type="button" data-cat="{{ $k }}" class="{{ $k === $first ? 'on' : '' }}"><span>{{ $icon }}</span>{{ \App\Models\Legal\LegalService::categoryName($k, $city) }}</button>@endforeach
            </aside>
            <div class="lg-list">
                <h2><span data-lg-title>Services for {{ $first ? \App\Models\Legal\LegalService::categoryName($first, $city) : '' }}</span></h2>
                <div class="lg-cards" data-lg-cards>
                    @foreach($services as $s)
                        <div class="lg-card" data-cat="{{ $s->category }}" data-for="{{ implode(' ', $s->audiences) }}" data-text="{{ mb_strtolower($s->name . ' ' . $s->summary . ' ' . $s->categoryLabel($city)) }}">
                            <a href="{{ route('legal.show', [$citySlug, $s]) }}" style="color:inherit;text-decoration:none"><h3>{{ $s->name }}</h3></a>
                            <p>{{ $s->summary }}</p>
                            <footer>
                                <b>{{ $s->priceLabel() }}</b>
                                <span style="display:flex;gap:6px">
                                    <a class="lg-add" href="{{ route('legal.show', [$citySlug, $s]) }}" style="text-decoration:none">Details</a>
                                    @if(in_array($s->id, $cart, true))<a class="lg-add in" href="{{ route('legal.cart') }}" style="text-decoration:none">✓ In cart</a>
                                    @else<form method="post" action="{{ route('legal.cart.add', $s) }}">@csrf<button class="lg-add">+ Add</button></form>@endif
                                </span>
                            </footer>
                        </div>
                    @endforeach
                </div>
                <div class="lg-empty" data-lg-empty hidden>No services match. Try another category or search word.</div>
            </div>
        </div>
        @if($consult)
            <div class="lg-consult">
                <div><h2>Need to consult?</h2><p>Talk to a property lawyer about your case</p></div>
                <form method="post" action="{{ route('legal.cart.add', $consult) }}">@csrf<input type="hidden" name="then" value="cart"><button>Book a consultation →</button></form>
            </div>
        @endif
    </div>

    @if($partners->isNotEmpty())
        <section class="lg-sec">
            <h2>Our legal partners</h2>
            <div class="lg-scroll">
                @foreach($partners as $p)
                    <article class="lg-partner">
                        @if($p->affiliation)<span class="aff">{{ $p->affiliation }}</span>@endif
                        <header>@if($p->rating)<span class="rt">★ {{ rtrim(rtrim(number_format($p->rating, 1), '0'), '.') }}</span>@endif<i>{{ $p->initials() }}</i><b style="font-size:18px">{{ $p->name }}</b></header>
                        <p>{{ $p->focus }}</p>
                        <div class="lg-pstats">
                            @if($p->cases_resolved !== null)<div><b>{{ number_format($p->cases_resolved) }}+</b><small>Cases resolved</small></div>@endif
                            @if($p->documents_reviewed !== null)<div><b>{{ number_format($p->documents_reviewed) }}+</b><small>Documents reviewed</small></div>@endif
                            @if($p->years_experience !== null)<div><b>{{ $p->years_experience }}+ years</b><small>Legal experience</small></div>@endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($reviews)
        <section class="lg-sec">
            <h2>Customer reviews</h2>
            <div class="lg-reviews">
                <div class="lg-avg"><b>{{ number_format($reviews['average'], 1) }}<small>/5</small></b><div class="st">{{ str_repeat('★', (int) round($reviews['average'])) }}{{ str_repeat('☆', 5 - (int) round($reviews['average'])) }}</div><div style="font-size:18px;margin-top:6px">{{ number_format($reviews['count']) }} {{ $reviews['count'] === 1 ? 'review' : 'reviews' }}</div></div>
                <div class="lg-scroll" style="grid-auto-columns:minmax(280px,480px)">
                    @foreach($reviews['latest'] as $r)
                        @php($tags = $r->booking?->items->pluck('name') ?? collect())
                        <article class="lg-rev">
                            <header><h3>{{ $r->title }}</h3><span class="rt">★ {{ $r->rating }}</span></header>
                            @if($tags->isNotEmpty())<div class="tags">@foreach($tags->take(2) as $t)<span>{{ $t }}</span>@endforeach @if($tags->count() > 2)<span>+{{ $tags->count() - 2 }}</span>@endif</div>@endif
                            <p class="{{ mb_strlen($r->body) > 220 ? 'clip' : '' }}">{{ $r->body }}</p>
                            @if(mb_strlen($r->body) > 220)<button type="button" data-lg-more>see more</button>@endif
                            <footer><b style="color:#475569">{{ $r->user?->name ?? 'Customer' }}</b> · {{ $r->created_at->format('M j, Y') }}</footer>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="lg-sec lg-faq">
        <h2>FAQ</h2>
        <div class="lg-faq-chips" data-lg-faq>
            <input type="search" placeholder="Search questions" data-faq-q aria-label="Search questions">
            @foreach(config('legal.faq') as $k => $qs)<button type="button" data-faq="{{ $k }}" class="{{ $loop->first ? 'on' : '' }}">{{ ucfirst($k) }}</button>@endforeach
        </div>
        @foreach(config('legal.faq') as $k => $qs)
            @foreach($qs as [$q, $a])<details data-faq-item="{{ $k }}" @unless($loop->parent->first) hidden @endunless><summary>{{ $q }}</summary><p>{{ $a }}</p></details>@endforeach
        @endforeach
    </section>
</div>
</div>
@push('scripts')<script>
(function () {
    var forKey = @json($for), cat = @json($first), all = false, labels = @json(config('legal.audiences')), catNames = {};
    document.querySelectorAll('[data-lg-cats] [data-cat]').forEach(function (b) { catNames[b.dataset.cat] = b.textContent.trim(); });
    var cards = [].slice.call(document.querySelectorAll('[data-lg-cards] .lg-card')), q = document.querySelector('[data-lg-q]');
    function render() {
        var term = q.value.trim().toLowerCase(), shown = 0, cats = {};
        cards.forEach(function (c) {
            var forOk = (' ' + c.dataset.for + ' ').indexOf(' ' + forKey + ' ') > -1;
            if (forOk) cats[c.dataset.cat] = 1;
            var ok = forOk && (term ? c.dataset.text.indexOf(term) > -1 : (all || c.dataset.cat === cat));
            c.hidden = !ok; if (ok) shown++;
        });
        document.querySelectorAll('[data-lg-cats] [data-cat]').forEach(function (b) { b.hidden = !cats[b.dataset.cat]; b.classList.toggle('on', !all && !term && b.dataset.cat === cat); });
        document.querySelector('[data-lg-title]').textContent = term ? 'Results for "' + q.value.trim() + '"' : (all ? 'All ' + labels[forKey].toLowerCase() + ' services' : 'Services for ' + (catNames[cat] || ''));
        document.querySelector('[data-lg-empty]').hidden = shown > 0;
        document.querySelector('[data-lg-for-label]').textContent = labels[forKey];
    }
    function firstCat() { var b = [].find.call(document.querySelectorAll('[data-lg-cats] [data-cat]'), function (b) { return !b.hidden; }); return b ? b.dataset.cat : cat; }
    document.querySelector('[data-lg-tabs]').addEventListener('click', function (e) {
        var b = e.target.closest('[data-for]'); if (!b) return;
        forKey = b.dataset.for; this.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
        all = false; render();
        if (document.querySelector('[data-lg-cats] [data-cat="' + cat + '"]').hidden) { cat = firstCat(); render(); }
        try { history.replaceState(null, '', '?for=' + forKey); } catch (err) {}
    });
    document.querySelector('[data-lg-cats]').addEventListener('click', function (e) { var b = e.target.closest('[data-cat]'); if (!b) return; cat = b.dataset.cat; all = false; q.value = ''; render(); });
    document.querySelector('[data-lg-all]').addEventListener('click', function () { all = true; q.value = ''; render(); });
    q.addEventListener('input', render);
    render(); if (document.querySelector('[data-lg-cats] [data-cat="' + cat + '"]')?.hidden) { cat = firstCat(); render(); }

    document.addEventListener('click', function (e) {
        var m = e.target.closest('[data-lg-more]'); if (!m) return;
        var p = m.previousElementSibling, open = p.classList.toggle('clip'); m.textContent = open ? 'see more' : 'see less';
    });
    var faqKey = @json(array_key_first(config('legal.faq'))), fq = document.querySelector('[data-faq-q]'), items = document.querySelectorAll('[data-faq-item]');
    function faq() { var t = fq.value.trim().toLowerCase(); items.forEach(function (d) { d.hidden = t ? d.textContent.toLowerCase().indexOf(t) < 0 : d.dataset.faqItem !== faqKey; }); }
    document.querySelector('[data-lg-faq]').addEventListener('click', function (e) { var b = e.target.closest('[data-faq]'); if (!b) return; faqKey = b.dataset.faq; fq.value = ''; this.querySelectorAll('[data-faq]').forEach(function (x) { x.classList.toggle('on', x === b); }); faq(); });
    fq.addEventListener('input', faq);
})();
</script>@endpush
@include('partials.property-services')
@endsection
