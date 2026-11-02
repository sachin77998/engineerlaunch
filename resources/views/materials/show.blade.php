@extends('layouts.site')
@section('title', $m->title . ' — ₹' . number_format($m->packPrice()) . ' | Buy online')
@section('meta_description', Str::limit($m->title . ': ₹' . number_format($m->packPrice()) . ($m->mrp > $m->price ? ' (MRP ₹' . number_format($m->packPrice(['price' => $m->mrp])) . ')' : '') . '. ' . ($m->summary ?? ''), 158))
@section('content')
@include('materials._nav')
@php
    $store = config('materials.store');
    $variants = collect($m->variants);
    $default = max(0, (int) $variants->search(fn ($v) => $v['price'] == $m->price && $v['available']));
    $asc = $history->reverse()->values();
    $changes = $asc->filter(fn ($p, $i) => $i === 0 || $p->price != $asc[$i - 1]->price)->reverse()->values();
    $gallery = $m->gallery();
    $freeAbove = collect($store['delivery']['slabs'])->first(fn ($s) => $s[1] == 0)[0] ?? null;
    $stars = fn ($r) => str_repeat('★', (int) round($r)) . str_repeat('☆', 5 - (int) round($r));
@endphp
@push('styles')<style>
.mt-pd{display:grid;grid-template-columns:minmax(260px,520px) 1fr;gap:34px;align-items:start}
.mt-gal{position:sticky;top:180px;display:grid;grid-template-columns:72px 1fr;gap:12px}
.mt-thumbs{display:grid;gap:8px;align-content:start;max-height:520px;overflow:auto}
.mt-thumbs button{padding:4px;border:1px solid var(--mt-line);border-radius:10px;background:#fff;cursor:pointer;aspect-ratio:1}
.mt-thumbs button.on{border-color:var(--mt-accent);box-shadow:0 0 0 2px rgba(234,88,12,.2)}
.mt-thumbs img{width:100%;height:100%;object-fit:contain}
.mt-main{position:relative;aspect-ratio:1;border:1px solid var(--mt-line);border-radius:18px;background:#fff;display:grid;place-items:center;overflow:hidden}
.mt-main img{width:92%;height:92%;object-fit:contain}
.mt-rate{display:inline-flex;align-items:center;gap:8px;color:#b45309;font-size:15px;text-decoration:none}
.mt-vars{display:flex;flex-wrap:wrap;gap:8px;margin:8px 0}
.mt-vars label{position:relative;border:1px solid #e7d8c9;border-radius:10px;padding:9px 13px;cursor:pointer;font-size:13.5px;background:#fff}
.mt-vars input{position:absolute;opacity:0}.mt-vars label:has(input:checked){border-color:var(--mt-accent);background:#fff7ed;font-weight:700}
.mt-vars label.na{opacity:.45;text-decoration:line-through}
.mt-step{display:inline-flex;align-items:center;border:1px solid #ddd6ce;border-radius:10px;overflow:hidden}
.mt-step button{width:42px;height:46px;border:0;background:#fafaf9;font-size:20px;cursor:pointer}
.mt-step input{width:70px;height:46px;border:0;text-align:center;font:600 16px Inter,system-ui,sans-serif}
.mt-buy{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px}
.mt-buy button{height:52px;font-size:17px}
.mt-sum{margin-top:12px;padding:12px 14px;border-radius:10px;background:#fafaf9;font-size:14px;line-height:1.7}
.mt-perks{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-top:16px}
.mt-perks div{display:flex;gap:10px;align-items:center;padding:10px 12px;border:1px solid var(--mt-line);border-radius:12px;font-size:13px;line-height:1.35}
.mt-perks i{font-style:normal;font-size:22px}
.mt-desc{line-height:1.7;color:#44403c;font-size:15px}
.mt-desc h2{font-size:20px;margin:22px 0 8px}.mt-desc h3{font-size:17px;margin:18px 0 6px}.mt-desc h4{font-size:15px;margin:14px 0 6px}
.mt-desc table{border-collapse:collapse;width:100%;margin:10px 0}.mt-desc td,.mt-desc th{border:1px solid var(--mt-line);padding:8px 10px;text-align:left}
.mt-desc-wrap{max-height:520px;overflow:hidden;position:relative}.mt-desc-wrap.open{max-height:none}
.mt-desc-wrap:not(.open):after{content:'';position:absolute;left:0;right:0;bottom:0;height:90px;background:linear-gradient(transparent,#fff)}
.mt-rv{display:grid;grid-template-columns:260px 1fr;gap:28px}
.mt-bars div{display:grid;grid-template-columns:34px 1fr 34px;gap:8px;align-items:center;font-size:13px;margin:5px 0}
.mt-bars span.b{height:8px;border-radius:4px;background:#f5f0ea;overflow:hidden}.mt-bars span.b i{display:block;height:100%;background:#f59e0b}
.mt-review{padding:14px 0;border-bottom:1px solid #f1ece6}
.mt-starpick{display:inline-flex;flex-direction:row-reverse;gap:2px}
.mt-starpick input{position:absolute;opacity:0}.mt-starpick label{font-size:30px;color:#d6d3d1;cursor:pointer}
.mt-starpick input:checked~label,.mt-starpick label:hover,.mt-starpick label:hover~label{color:#f59e0b}
@media(max-width:860px){.mt-pd,.mt-rv{grid-template-columns:1fr}.mt-gal{position:static;grid-template-columns:1fr}.mt-thumbs{grid-auto-flow:column;grid-auto-columns:64px;order:2;overflow-x:auto}}
</style>@endpush
<div class="container mt-wrap">
    <nav class="mt-crumbs" aria-label="Breadcrumb" style="margin:4px 0 0"><a href="{{ route('materials.index') }}">Home</a>
        @foreach($cats->take(2) as [$h, $c]) › <a href="{{ route('materials.category', $h) }}">{{ $c['title'] }}</a>@endforeach › <b>{{ Str::limit($m->title, 60) }}</b></nav>
    <section class="mt-pd">
        <div class="mt-gal">
            <div class="mt-thumbs">@foreach($gallery as $i => $img)<button type="button" class="{{ $i === 0 ? 'on' : '' }}" data-img="{{ $img }}" aria-label="Photo {{ $i + 1 }}"><img src="{{ $img }}" alt="" loading="lazy"></button>@endforeach</div>
            <div class="mt-main">@if($m->discountPercent())<span class="mt-badge" style="top:12px;left:12px">{{ $m->discountPercent() }}% OFF</span>@endif
                @if($gallery)<img id="mt-main-img" src="{{ $gallery[0] }}" alt="{{ $m->title }}">@else<span style="font-size:90px">🧱</span>@endif</div>
        </div>
        <div>
            <div class="mt-muted">@if($m->brand_slug)<a href="{{ route('materials.price-list', $m->brand_slug) }}" style="color:#9a3412">{{ $m->brand }}</a> · @endif{{ $m->product_type }}</div>
            <h1 style="margin:6px 0 8px;font-size:clamp(22px,2.6vw,30px);line-height:1.25">{{ $m->title }}</h1>
            @if($ratings['count'])<a class="mt-rate" href="#reviews">{{ $stars($ratings['average']) }} <b>{{ $ratings['average'] }}</b> <span class="mt-muted">{{ $ratings['count'] }} {{ Str::plural('review', $ratings['count']) }}</span></a>
            @else<a class="mt-rate" href="#reviews" style="color:#78716c">☆☆☆☆☆ Be the first to review</a>@endif
            <div class="mt-price" style="margin:14px 0 4px"><b style="font-size:30px" id="mt-price">₹{{ number_format($m->packPrice()) }}</b><s id="mt-mrp" style="font-size:17px"></s><span class="mt-off" id="mt-off" style="font-size:15px"></span></div>
            <div class="mt-muted">Inclusive of all taxes</div>
            <div class="mt-tags" style="margin-top:8px"><span class="mt-tag ok" id="mt-stock"></span><span class="mt-tag" id="mt-unit"></span></div>

            <form method="post" action="{{ route('materials.list.add') }}" id="mt-add">@csrf
                <input type="hidden" name="items[0][handle]" value="{{ $m->handle }}">
                @if($variants->count() > 1)
                    <div class="mt-label">{{ $variants->count() }} options</div>
                    <div class="mt-vars">@foreach($variants as $i => $v)<label class="{{ $v['available'] ? '' : 'na' }}"><input type="radio" name="items[0][variant]" value="{{ $i }}" @checked($i === $default) @disabled(!$v['available'])>{{ $v['title'] }} · ₹{{ number_format($m->packPrice($v)) }}</label>@endforeach</div>
                @else<input type="hidden" name="items[0][variant]" value="0">@endif
                <div class="mt-label">Quantity</div>
                <div class="mt-step"><button type="button" data-step="-1" aria-label="Less">−</button><input id="mt-q" name="items[0][qty]" type="number" min="1" max="{{ $store['max_qty'] }}" value="1" aria-label="Quantity"><button type="button" data-step="1" aria-label="More">+</button></div>
                <div class="mt-sum" id="mt-sum"></div>
                @if($m->canBuy())
                    <div class="mt-buy"><button class="mt-add" style="margin:0">Add to cart</button><button class="mt-btn" name="buy_now" value="1" style="margin:0">Buy now</button></div>
                @else<button class="mt-add" disabled style="margin-top:14px">Out of stock</button>@endif
            </form>

            <div class="mt-perks">
                <div><i>⏱️</i><span><b>{{ $store['delivery']['express_label'] }}</b><br>delivery in serviceable areas</span></div>
                @if($store['cod']['enabled'])<div><i>💵</i><span><b>Pay on delivery</b><br>up to ₹{{ number_format($store['cod']['max_order']) }}</span></div>@endif
                @if($freeAbove)<div><i>🚚</i><span><b>Free delivery</b><br>on orders above ₹{{ number_format($freeAbove) }}</span></div>@endif
                <div><i>🏷️</i><span><b>{{ $store['offer']['percent'] }}% off</b><br>on orders above ₹{{ number_format($store['offer']['min_order']) }}</span></div>
            </div>

            @if($m->bulk_tiers)
                <h2 style="font-size:17px;margin:22px 0 8px">Bulk prices</h2>
                <table class="mt-table"><tr><th>Buy</th><th class="n">Price each</th><th class="n">You save</th></tr>
                    <tr><td>1+</td><td class="n">₹{{ number_format($m->packPrice()) }}</td><td class="n">—</td></tr>
                    @foreach($m->bulk_tiers as $t)@php($tp = $m->packPrice(['price' => $t['price']]))<tr><td>{{ $t['qty'] }}+</td><td class="n">₹{{ number_format($tp) }}</td><td class="n mt-down">₹{{ number_format($m->packPrice() - $tp) }} each ({{ round(($m->price - $t['price']) / $m->price * 100, 1) }}%)</td></tr>@endforeach
                </table>
            @endif
        </div>
    </section>

    @if($m->description || $m->summary)
    <section class="mt-card">
        <h2>Product description</h2>
        <table class="mt-table" style="max-width:640px;margin-bottom:16px">
            @if($m->brand)<tr><th>Brand</th><td>{{ $m->brand }}</td></tr>@endif
            @if($m->product_type)<tr><th>Type</th><td>{{ $m->product_type }}</td></tr>@endif
            @if($variants->count() > 1)<tr><th>Options</th><td>{{ $variants->pluck('title')->filter()->implode(', ') }}</td></tr>@endif
            @if($m->unit_size)<tr><th>Pack</th><td>{{ $m->unit_size + 0 }} {{ $m->unit }}</td></tr>@endif
        </table>
        <div class="mt-desc-wrap" id="mt-desc"><div class="mt-desc">{!! $m->description ?: e($m->summary) !!}</div></div>
        <button type="button" class="mt-btn ghost sm" id="mt-desc-more" style="margin-top:10px">Read more</button>
    </section>
    @endif

    <section class="mt-card" id="reviews">
        <h2>Ratings &amp; reviews</h2>
        <div class="mt-rv">
            <div>
                <div style="font-size:44px;font-weight:800;line-height:1">{{ $ratings['average'] ?? '–' }}<span style="font-size:18px;color:#a8a29e">/5</span></div>
                <div style="color:#f59e0b;font-size:20px">{{ $stars($ratings['average'] ?? 0) }}</div>
                <div class="mt-muted">{{ $ratings['count'] }} {{ Str::plural('review', $ratings['count']) }}</div>
                <div class="mt-bars" style="margin-top:12px">@foreach($ratings['bars'] as $r => $n)<div><span>{{ $r }} ★</span><span class="b"><i style="width:{{ $ratings['count'] ? round($n / $ratings['count'] * 100) : 0 }}%"></i></span><span class="mt-muted">{{ $n }}</span></div>@endforeach</div>
                @auth
                    <form method="post" action="{{ route('materials.reviews.store', $m) }}" style="margin-top:18px">@csrf
                        <div class="mt-label">{{ $mine ? 'Update your review' : 'Write a review' }}</div>
                        <div class="mt-starpick">@for($r = 5; $r >= 1; $r--)<input type="radio" name="rating" id="r{{ $r }}" value="{{ $r }}" @checked(old('rating', $mine->rating ?? 0) == $r) required><label for="r{{ $r }}" title="{{ $r }} star">★</label>@endfor</div>
                        <input class="mt-in" name="title" maxlength="120" placeholder="Headline" value="{{ old('title', $mine->title ?? '') }}" style="margin-top:6px">
                        <textarea class="mt-in" name="body" rows="4" maxlength="3000" placeholder="How was the quality, packing and delivery?" style="margin-top:6px">{{ old('body', $mine->body ?? '') }}</textarea>
                        <button class="mt-btn" style="margin-top:8px;width:100%">Submit review</button>
                    </form>
                @else
                    <a class="mt-btn ghost" href="{{ route('login') }}" style="margin-top:18px;width:100%">Log in to write a review</a>
                @endauth
            </div>
            <div>
                @forelse($reviews as $r)
                    <div class="mt-review">
                        <div style="color:#f59e0b">{{ $stars($r->rating) }} <b style="color:#1c1917">{{ $r->title }}</b></div>
                        @if($r->body)<p style="margin:6px 0;line-height:1.6">{{ $r->body }}</p>@endif
                        <div class="mt-muted">{{ Str::of($r->user?->name ?? 'Customer')->before(' ') }} · {{ $r->created_at->format('d M Y') }}@if($r->verified) · <span style="color:#15803d;font-weight:700">✓ Verified purchase</span>@endif</div>
                    </div>
                @empty
                    <p class="mt-muted">No reviews yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="mt-card" id="ask">
        <h2>Ask a question or give feedback</h2>
        <form method="post" action="{{ route('materials.feedback') }}" style="display:grid;gap:10px;max-width:720px">@csrf
            <input type="hidden" name="material" value="{{ $m->handle }}">
            <select class="mt-in" name="kind">@foreach(\App\Models\MaterialFeedback::KINDS as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
            @guest<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px"><input class="mt-in" name="name" placeholder="Your name" value="{{ old('name') }}"><input class="mt-in" name="email" type="email" placeholder="Email for our reply" value="{{ old('email') }}" required></div>@endguest
            <textarea class="mt-in" name="message" rows="3" minlength="5" maxlength="3000" placeholder="e.g. Is this suitable for exterior walls? How many bags for 500 sq ft?" required>{{ old('message') }}</textarea>
            <button class="mt-btn" style="justify-self:start">Send</button>
        </form>
    </section>

    @if($changes->count() > 0)
    <section class="mt-card">
        <h2>Price history</h2>
        <div class="mt-scroll"><table class="mt-table"><tr><th>Since</th><th class="n">Price</th><th class="n">MRP</th><th class="n">Change</th></tr>
            @foreach($changes as $i => $p)@php($prev = $changes[$i + 1] ?? null)
                <tr><td>{{ $p->recorded_on->format('d M Y') }}</td><td class="n">₹{{ number_format($m->packPrice(['price' => $p->price])) }}</td><td class="n">{{ $p->mrp ? '₹' . number_format($m->packPrice(['price' => $p->mrp])) : '—' }}</td>
                    <td class="n">@if($prev)<span class="{{ $p->price > $prev->price ? 'mt-up' : 'mt-down' }}">{{ $p->price > $prev->price ? '▲' : '▼' }} {{ round(abs($p->price - $prev->price) / $prev->price * 100, 1) }}%</span>@else first recorded @endif</td></tr>
            @endforeach
        </table></div>
    </section>
    @endif

    @if($similar->isNotEmpty())
    <section><div class="mt-sec-h"><h2>Similar products</h2></div><div class="mt-grid">@foreach($similar as $s)@include('materials._card', ['m' => $s])@endforeach</div></section>
    @endif
</div>
@push('scripts')<script>
(function () {
    var variants = @json($variants->map(fn ($v) => $v + ['sell' => $m->packPrice($v), 'sell_mrp' => isset($v['mrp']) ? $m->packPrice(['price' => $v['mrp']]) : null])),
        tiers = @json(collect($m->bulk_tiers)->map(fn ($t) => ['qty' => $t['qty'], 'price' => $m->packPrice(['price' => $t['price']])])), base = {{ (float) $m->price }};
    var q = document.getElementById('mt-q'), sum = document.getElementById('mt-sum'), inr = function (n) { return '₹' + Math.round(n).toLocaleString('en-IN'); };
    var $ = function (id) { return document.getElementById(id); };
    function chosen() { var r = document.querySelector('#mt-add input[type=radio]:checked'); return variants[r ? +r.value : 0] || variants[0] || {sell: base, available: false}; }
    function render() {
        var v = chosen(), n = Math.max(1, parseInt(q.value || '1', 10)), each = v.sell, tiered = variants.length === 1 || v.price === base;
        if (tiered) tiers.forEach(function (t) { if (n >= t.qty && t.price < each) each = t.price; });
        $('mt-price').textContent = inr(v.sell);
        $('mt-mrp').textContent = v.sell_mrp && v.sell_mrp > v.sell ? inr(v.sell_mrp) : '';
        $('mt-off').textContent = v.sell_mrp && v.sell_mrp > v.sell ? Math.round((v.sell_mrp - v.sell) / v.sell_mrp * 100) + '% off' : '';
        $('mt-stock').textContent = v.available ? (v.qty ? v.qty + ' in stock' : 'In stock') : 'Out of stock';
        $('mt-stock').className = 'mt-tag ' + (v.available ? 'ok' : 'out');
        $('mt-unit').textContent = v.size ? inr(v.sell / v.size) + ' per ' + v.unit : ''; $('mt-unit').hidden = !v.size;
        var next = tiered ? tiers.filter(function (t) { return t.qty > n; })[0] : null;
        sum.innerHTML = n + ' × ' + inr(each) + ' = <b>' + inr(n * each) + '</b>' + (each < v.sell ? ' · bulk saving <b class="mt-down">' + inr((v.sell - each) * n) + '</b>' : '')
            + (next ? '<br>Add ' + (next.qty - n) + ' more to pay ' + inr(next.price) + ' each.' : '');
    }
    q.addEventListener('input', render);
    document.querySelectorAll('[data-step]').forEach(function (b) { b.addEventListener('click', function () { q.value = Math.max(1, (parseInt(q.value || '1', 10) + +b.dataset.step)); render(); }); });
    document.querySelectorAll('#mt-add input[type=radio]').forEach(function (r) { r.addEventListener('change', render); });
    document.querySelectorAll('.mt-thumbs button').forEach(function (b) { b.addEventListener('click', function () {
        $('mt-main-img').src = b.dataset.img; document.querySelectorAll('.mt-thumbs button').forEach(function (x) { x.classList.toggle('on', x === b); });
    }); });
    var desc = $('mt-desc'), more = $('mt-desc-more');
    if (desc && more) { if (desc.scrollHeight <= 540) { desc.classList.add('open'); more.hidden = true; } more.addEventListener('click', function () { desc.classList.toggle('open'); more.textContent = desc.classList.contains('open') ? 'Show less' : 'Read more'; }); }
    render();
})();
</script>@endpush
@endsection
