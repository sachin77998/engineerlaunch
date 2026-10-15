@extends('layouts.site')
@section('title', 'Mutual funds — explore, compare and invest | Do Trading')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'Mutual Funds', 'subtitle' => number_format($stats['schemes']) . ' schemes from ' . $stats['amcs'] . ' fund houses · NAV as of ' . ($stats['nav_date'] ? \Carbon\Carbon::parse($stats['nav_date'])->format('d M Y') : '—') . ' · Source: AMFI'])
<main class="dt-wrap dt-main">
    @if($stats['schemes'] === 0)
        <div class="dt-card dt-empty"><b style="font-size:16px;color:#1b2437">Loading the fund catalogue…</b><p>All mutual funds, NAVs and returns are being imported from AMFI. This takes a few minutes after a new deployment; refresh shortly.</p></div>
    @endif
    @if($marketClosed)<p class="dt-flash warn">{{ $marketClosed }} Orders open again on {{ $nextDay->format('l, d M Y') }}.</p>@endif

    <section class="dt-card">
        <div class="dt-sec-head"><h2>Collections</h2><a href="{{ route('trading.funds') }}">All mutual funds →</a></div>
        <div class="dt-collections">
            @foreach($collections as $c)
                <a class="dt-collection" href="{{ route('trading.collection', $c) }}">
                    <span class="ic">@include('trading.partials.icon', ['name' => $c->icon])</span>
                    <b>{{ $c->title }}</b><small>{{ number_format($c->fund_count) }} funds</small>
                </a>
            @endforeach
        </div>
    </section>

    @if($popular->isNotEmpty())
    <section class="dt-card">
        <div class="dt-sec-head"><h2>Popular funds</h2><a href="{{ route('trading.collection', 'high-return') }}">See high-return funds →</a></div>
        <div class="dt-cards">
            @foreach($popular as $f)
                <a class="dt-fcard" href="{{ route('trading.fund', $f) }}">
                    <span style="display:flex;gap:12px;align-items:center">@include('trading.partials.logo', ['name' => $f->amc, 'color' => $f->fundHouse?->color])<small class="dt-muted">{{ $f->sub_category }}</small></span>
                    <b>{{ $f->displayName() }}</b>
                    <span class="row"><span class="ret {{ ($f->return_3y ?? 0) >= 0 ? 'up' : 'down' }}">{{ $f->return_3y !== null ? number_format($f->return_3y, 2) . '%' : '—' }}</span><span class="dt-muted">3Y returns</span></span>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    <section class="dt-card">
        <div class="dt-sec-head"><h2>Fund houses</h2><a href="{{ route('trading.funds') }}">Browse all →</a></div>
        <div class="dt-amcs">
            @foreach($amcs as $a)
                <a class="dt-amc" href="{{ route('trading.amc', $a) }}">@include('trading.partials.logo', ['name' => $a->short_name, 'color' => $a->color])<span><b>{{ $a->short_name }}</b><small>{{ number_format($a->scheme_count) }} schemes</small></span></a>
            @endforeach
        </div>
    </section>

    <section class="dt-card">
        <h2>Explore by category</h2>
        @foreach($categories as $asset => $cats)
            <h3>{{ $asset }}</h3>
            <div class="dt-chips">
                @foreach($cats as $cat)<a class="dt-chip" href="{{ route('trading.category', $cat->slug) }}">{{ $cat->sub_category }} <span class="dt-muted" style="font-weight:500">{{ $cat->scheme_count }}</span></a>@endforeach
            </div>
        @endforeach
    </section>

    <section class="dt-cards">
        <a class="dt-fcard" href="{{ route('trading.calculators') }}"><b>SIP calculator</b><span class="dt-muted">See what a monthly SIP can grow to, with yearly step-up.</span></a>
        <a class="dt-fcard" href="{{ route('trading.stocks') }}"><b>Stocks</b><span class="dt-muted">Every NSE and BSE listed company with prices and live ticks.</span></a>
        <a class="dt-fcard" href="{{ route('trading.etfs') }}"><b>ETFs</b><span class="dt-muted">Index, gold, silver, debt and international ETFs.</span></a>
        <a class="dt-fcard" href="{{ route('trading.loans') }}"><b>Loan amortization</b><span class="dt-muted">EMI, bullet and interest-only schedules with moratorium.</span></a>
    </section>
</main>
@endsection
