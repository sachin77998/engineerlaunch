@extends('layouts.site')
@section('title', 'Home Building — construction materials, prices and cost calculator')
@section('meta_description', 'Cement, plywood, tiles, paint, wires, plumbing and hardware with live prices, bulk prices and stock, a construction cost calculator and paint, tile and waterproofing estimators.')
@section('content')
@include('materials._nav')
@push('styles')<style>
.mt-hero{display:grid;grid-template-columns:1.1fr .9fr;gap:24px;align-items:center;padding:30px 34px;border-radius:22px;background:linear-gradient(120deg,#fff7ed 0%,#ffedd5 55%,#fde68a 100%);overflow:hidden}
.mt-hero h1{margin:0 0 10px;font-size:clamp(28px,3.6vw,44px);line-height:1.12;color:#1c1917}
.mt-hero p{margin:0 0 18px;color:#57534e;font-size:16px}
.mt-hero-stats{display:flex;gap:22px;flex-wrap:wrap;margin-top:18px}
.mt-hero-stats div{font-size:13px;color:#78716c}.mt-hero-stats b{display:block;font-size:24px;color:#9a3412}
.mt-collage{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.mt-collage a{aspect-ratio:1;border-radius:18px;background:#fff;display:grid;place-items:center;box-shadow:0 10px 24px rgba(154,52,18,.12);overflow:hidden}
.mt-collage a:nth-child(2),.mt-collage a:nth-child(5){transform:translateY(16px)}
.mt-collage img{width:84%;height:84%;object-fit:contain}
.mt-tools{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px}
.mt-tool{display:flex;gap:14px;align-items:center;padding:16px;border:1px solid var(--mt-line);border-radius:16px;text-decoration:none;color:#292524;background:#fff}
.mt-tool:hover{border-color:#fdba74;box-shadow:0 10px 24px rgba(234,88,12,.1)}
.mt-tool i{flex:0 0 52px;height:52px;border-radius:14px;display:grid;place-items:center;font-size:26px;font-style:normal}
.mt-tool strong{display:block;font-size:15px}.mt-tool span{font-size:12.5px;color:#78716c}
@media(max-width:860px){.mt-hero{grid-template-columns:1fr;padding:22px}.mt-collage{grid-template-columns:repeat(3,1fr)}}
</style>@endpush
@php($collage = $deals->merge($fresh)->filter(fn ($m) => $m->image_url)->unique('id')->take(6))
<div class="container mt-wrap">
    <section class="mt-hero">
        <div>
            <h1>Construction materials with live prices &amp; bulk rates</h1>
            <p>Cement, plywood, paint, wires, plumbing and hardware — compare brands, unlock bulk prices and estimate your whole build.</p>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a class="mt-btn" href="{{ route('materials.calculator') }}">🏠 Estimate my build cost</a>
                <a class="mt-btn green" href="{{ route('materials.search', ['sort' => 'discount', 'stock' => 1]) }}">🔥 Today's deals</a>
            </div>
            <div class="mt-hero-stats">
                <div><b>{{ number_format($stats['products']) }}</b>products</div>
                <div><b>{{ number_format($stats['in_stock']) }}</b>in stock</div>
                <div><b>{{ $stats['brands'] }}</b>brands</div>
                @if($stats['updated'])<div><b>{{ \Illuminate\Support\Carbon::parse($stats['updated'])->diffForHumans(null, true) }}</b>since price update</div>@endif
            </div>
        </div>
        <div class="mt-collage">@foreach($collage as $m)<a href="{{ route('materials.show', $m) }}" title="{{ $m->title }}"><img src="{{ $m->image_url }}" alt="{{ $m->title }}" loading="lazy"></a>@endforeach</div>
    </section>

    <section class="mt-tools">
        <a class="mt-tool" href="{{ route('materials.calculator') }}"><i style="background:#ffedd5">🏠</i><div><strong>Construction cost</strong><span>Full build by city &amp; quality</span></div></a>
        @foreach(config('materials.estimators') as $k => [$name, $icon])
            <a class="mt-tool" href="{{ route('materials.estimator', $k) }}"><i style="background:{{ ['paint' => '#fce7f3', 'tiles' => '#e0f2fe', 'waterproofing' => '#dcfce7'][$k] ?? '#f5f5f4' }}">{{ $icon }}</i><div><strong>{{ $name }}</strong><span>Quantity, packs &amp; cost</span></div></a>
        @endforeach
        <a class="mt-tool" href="{{ route('materials.price-lists') }}"><i style="background:#fef9c3">📋</i><div><strong>Price lists</strong><span>Every brand, weekly change</span></div></a>
        <a class="mt-tool" href="{{ route('materials.list') }}"><i style="background:#ede9fe">🧾</i><div><strong>My material list</strong><span>Bulk prices &amp; total</span></div></a>
    </section>

    @if($stats['products'] === 0)
        <section class="mt-card"><p class="mt-muted" style="margin:0">The catalogue is loading. It fills after the first <code>materials:sync</code> run (Production Setup → Home Building).</p></section>
    @endif

    @foreach(config('materials.groups') as $gk => $gname) @continue(empty($collections[$gk]))
        <section>
            <div class="mt-sec-h"><h2>{{ $gname }}</h2><a href="{{ route('materials.categories') }}#{{ $gk }}">All {{ count($collections[$gk]) }} →</a></div>
            <div class="mt-tiles">
                @foreach(array_slice($collections[$gk], 0, 12, true) as $slug => $c)
                    <a class="mt-tile" href="{{ route('materials.category', $slug) }}">
                        <span class="mt-ico">@if($c['image'])<img src="{{ $c['image'] }}" alt="" loading="lazy">@else{{ $c['icon'] }}@endif</span>
                        {{ $c['title'] }}<span class="mt-muted">{{ number_format($c['count']) }} products</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach

    @foreach([['🔥 Deals of the week', $deals, route('materials.search', ['sort' => 'discount', 'stock' => 1])], ['📦 Best bulk prices', $bulk, route('materials.search', ['bulk' => 1, 'stock' => 1])], ['✨ Newly launched', $fresh, route('materials.search', ['sort' => 'new'])]] as [$heading, $items, $more])
        @continue($items->isEmpty())
        <section>
            <div class="mt-sec-h"><h2>{{ $heading }}</h2><a href="{{ $more }}">See all →</a></div>
            <div class="mt-grid">@foreach($items as $m)@include('materials._card')@endforeach</div>
        </section>
    @endforeach
</div>
@endsection
