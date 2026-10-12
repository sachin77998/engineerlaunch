@extends('layouts.site')
@section('title', 'Market Data — Vehicle Sales & Commodity Prices')
@push('styles')<style>
.mk{width:min(1250px,calc(100% - 32px));margin:24px auto 56px;font-family:Inter,system-ui,sans-serif}
.mk h1{margin:0 0 4px;color:#0b2545}.mk-sub{margin:0 0 18px;color:#5b6b82}
.mk-box{background:#fff;border:1px solid #dfe6f1;border-radius:14px;padding:18px;margin-bottom:18px}
.mk-box h2{margin:0 0 12px;font-size:19px;color:#0b2545}
.mk-tabs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}.mk-tabs a{padding:7px 13px;border-radius:999px;border:1px solid #dfe6f1;color:#0b2545;text-decoration:none;font-weight:600;font-size:13.5px;background:#fff}
.mk-tabs a.on{background:#0b2545;color:#fff;border-color:#0b2545}
.mk-table-wrap{overflow-x:auto}
.mk-table{width:100%;border-collapse:collapse;font-size:14px}.mk-table th,.mk-table td{padding:9px 10px;border-bottom:1px solid #eef2f7;text-align:right;white-space:nowrap}
.mk-table th:first-child,.mk-table td:first-child,.mk-table th:nth-child(2),.mk-table td:nth-child(2){text-align:left}
.mk-table thead th{background:#f4f6fb;color:#334155;font-size:12.5px}
.up{color:#087443;font-weight:700}.down{color:#b42318;font-weight:700}.na{color:#94a3b8}
.mk-prices{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px}
.mk-price{padding:14px;border:1px solid #e2e8f2;border-radius:12px}.mk-price b{display:block;font-size:20px;color:#0b2545}.mk-price span{font-size:12.5px;color:#5b6b82}
.mk-empty{padding:16px;border:1px dashed #cbd5e1;border-radius:12px;color:#5b6b82;line-height:1.6}
.mk-news a{display:inline-block;margin:0 6px 6px 0;padding:6px 11px;border-radius:999px;background:#fff6dc;color:#7a5600;text-decoration:none;font-size:13px;font-weight:600}
</style>@endpush
@section('content')
@php
    $fmt = fn ($n) => $n === null ? '—' : number_format($n);
    $pct = fn ($v) => $v === null ? '<span class="na">—</span>' : '<span class="' . ($v >= 0 ? 'up' : 'down') . '">' . ($v >= 0 ? '+' : '') . $v . '%</span>';
@endphp
<main class="mk">
    <h1>Market Data</h1>
    <p class="mk-sub">Model-wise vehicle sales (wholesale and retail), growth over 3, 6 and 12 months, and commodity prices. Figures are from company releases, SIAM (wholesale) and FADA (retail); news below updates every 6 hours.</p>

    <section class="mk-box mk-news">
        <h2>Latest news</h2>
        @foreach(['car-sales' => 'Car sales', 'two-wheeler-sales' => 'Bikes & scooters', 'tractor-sales' => 'Tractors', 'mobile-brands' => 'Mobiles & wearables', 'tyres-components' => 'Tyres & components', 'electricals' => 'Electricals & cables', 'commodity-prices' => 'Gold, silver, copper, steel', 'mutual-funds' => 'Mutual funds', 'banking-markets' => 'Bank shares', 'education-news' => 'Schools & colleges', 'elections-politics' => 'Elections & politics', 'cricket-sports' => 'Cricket', 'bollywood-music' => 'Bollywood & music'] as $slug => $label)
            <a href="{{ route('news.index', ['category' => $slug]) }}">{{ $label }}</a>
        @endforeach
    </section>

    <section class="mk-box">
        <h2>Commodity prices</h2>
        @if($prices->isEmpty())
            <div class="mk-empty">No prices recorded yet. Gold and silver update automatically once a GoldAPI key is configured; copper, steel and sand prices can be uploaded on Production Setup. Until then, see <a href="{{ route('news.index', ['category' => 'commodity-prices']) }}">today's price news</a>.</div>
        @else
            <div class="mk-prices">
                @foreach($prices as $p)
                    <div class="mk-price"><span>{{ ucfirst($p->commodity) }} {{ $p->variant }} · per {{ $p->unit }}@if($p->city) · {{ $p->city }}@endif</span>
                        <b>₹{{ number_format($p->price, 2) }}</b>
                        <span>{{ \Carbon\Carbon::parse($p->priced_on)->format('d M Y') }}@if($p->change !== null) · {!! $p->change >= 0 ? '<span class="up">▲ ₹'.number_format($p->change, 2).'</span>' : '<span class="down">▼ ₹'.number_format(abs($p->change), 2).'</span>' !!}@endif</span>
                        <span>{{ $p->source }}</span></div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mk-box">
        <h2>Vehicle sales</h2>
        @if($segments->isEmpty())
            <div class="mk-empty">Monthly model-wise sales have not been loaded yet. The owner can upload the monthly figures (company releases, SIAM wholesale, FADA retail) as a CSV on Production Setup; totals for 3, 6 and 12 months, month-on-month and year-on-year growth and retail-to-wholesale ratios are then calculated automatically. Meanwhile, read the <a href="{{ route('news.index', ['category' => 'car-sales']) }}">latest monthly sales reports</a>.</div>
        @else
            <nav class="mk-tabs">@foreach($segments as $s)<a href="{{ route('market.index', ['segment' => $s]) }}" class="{{ $s === $segment ? 'on' : '' }}">{{ ucwords(str_replace('-', ' ', $s)) }}</a>@endforeach</nav>
            <nav class="mk-tabs"><a href="{{ route('market.index', ['segment' => $segment]) }}" class="{{ $brand ? '' : 'on' }}">All brands</a>@foreach($brands as $b)<a href="{{ route('market.index', ['segment' => $segment, 'brand' => $b->brand]) }}" class="{{ $brand === $b->brand ? 'on' : '' }}">{{ $b->brand }} · {{ $fmt($b->wholesale) }}</a>@endforeach</nav>
            <div class="mk-table-wrap">
                <table class="mk-table">
                    <thead><tr><th>Brand</th><th>Model</th><th>{{ $models->first()->month ?? 'Latest' }} wholesale</th><th>Retail</th><th>Retail / wholesale</th><th>MoM</th><th>YoY</th><th>Last 3 months</th><th>Last 6 months</th><th>Last 12 months</th><th>12-month growth</th></tr></thead>
                    <tbody>
                    @foreach($models as $m)
                        <tr><td>{{ $m->brand }}</td><td><strong>{{ $m->model }}</strong></td><td>{{ $fmt($m->wholesale) }}</td><td>{{ $fmt($m->retail) }}</td>
                            <td>{{ $m->retail_vs_wholesale === null ? '—' : $m->retail_vs_wholesale.'%' }}</td><td>{!! $pct($m->mom) !!}</td><td>{!! $pct($m->yoy) !!}</td>
                            <td>{{ $fmt($m->three) }}</td><td>{{ $fmt($m->six) }}</td><td>{{ $fmt($m->twelve) }}</td><td>{!! $pct($m->yoy12) !!}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</main>
@endsection
