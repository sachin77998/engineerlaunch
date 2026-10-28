@extends('layouts.site')
@section('title', 'Bills & Payments — electricity, water, gas, recharge, property tax')
@section('meta_description', 'Pay electricity, water, piped gas, mobile, DTH, FASTag, challan, LIC and property tax bills on the official sites of every board and municipal corporation, city by city.')
@section('content')
@include('bills._nav')
<div class="container bp-wrap">
    <section class="bp-card">
        <h1>Bills &amp; Payments</h1>
        <form method="get" action="{{ route('bills.index') }}" style="display:flex;gap:10px;margin-bottom:6px">
            <input class="bp-in" name="q" value="{{ $q }}" placeholder="Search a board, operator or city — e.g. PSPCL, Jal Board, Hoshiarpur">
            <button class="bp-btn" style="width:auto;margin:0;padding:10px 22px">Search</button>
        </form>
        @if($q !== '')
            <div class="bp-list" style="margin-top:10px">
                @foreach($cityMatches as $c)<a href="{{ route('bills.city', $c['slug']) }}"><span><strong>{{ $c['body'] }}</strong> <span class="bp-muted">· property tax, water &amp; sewerage, licences</span></span><span class="bp-muted">{{ $c['state'] }}</span></a>@endforeach
                @forelse($matches as $b)<a href="{{ route('bills.category', [$b['category'], 'biller' => $b['slug']]) }}#pay"><span>{{ $b['name'] }} <span class="bp-muted">· {{ $categories[$b['category']]['name'] }}</span></span><span class="bp-muted">{{ $b['state'] }}</span></a>
                @empty @if($cityMatches->isEmpty())<p class="bp-muted">No biller or city matches “{{ $q }}”.</p>@endif @endforelse
            </div>
        @endif
    </section>

    <section class="bp-card">
        <h2>Pay a bill</h2>
        <div class="bp-grid">
            @foreach($categories as $c)<a class="bp-tile" href="{{ route('bills.category', $c['key']) }}"><span class="bp-mono" style="background:#eef4ff;font-size:32px">{{ $c['icon'] }}</span><strong>{{ $c['name'] }}</strong><span class="bp-muted">{{ count($c['billers']) }} billers</span></a>@endforeach
        </div>
    </section>

    <section class="bp-card" id="more">
        <h2>More</h2>
        <div class="bp-grid">
            @foreach($categories as $c) @continue(empty($c['more']))<a class="bp-tile" href="{{ route('bills.category', $c['key']) }}"><span class="bp-mono" style="background:#eef4ff;font-size:32px">{{ $c['icon'] }}</span><strong>{{ $c['name'] }}</strong><span class="bp-muted">{{ count($c['billers']) }} billers</span></a>@endforeach
            <a class="bp-tile" href="#cities"><span class="bp-mono" style="background:#eef4ff;font-size:32px">🏙️</span><strong>City services</strong><span class="bp-muted">{{ $cities->count() }} cities</span></a>
        </div>
    </section>

    <section class="bp-card" id="cities">
        <h2>Municipal services by city</h2>
        <p class="bp-muted" style="margin-top:-6px">Property tax, water and sewerage bills, trade licence, fire NOC and building plans on each corporation's official portal.</p>
        @foreach($cities->groupBy('state') as $stateName => $list)
            <h3 style="font-size:15px;margin:14px 0 8px">{{ $stateName }}</h3>
            <div class="bp-grid">
                @foreach($list as $c)@php([$ini, $bg] = \App\Support\Monogram::for($c['name']))
                    <a class="bp-tile" href="{{ route('bills.city', $c['slug']) }}"><span class="bp-mono" style="background:{{ $bg }}">{{ $ini }}</span>{{ $c['name'] }}<span class="bp-muted">{{ $c['body'] }}</span></a>
                @endforeach
            </div>
        @endforeach
        <h3 style="font-size:15px;margin:18px 0 8px">Other cities</h3>
        <div class="bp-grid">
            @foreach($categories['municipal']['billers'] as $b) @continue($b['state'] === 'Punjab') @php([$ini, $bg] = \App\Support\Monogram::for($b['name']))
                <a class="bp-tile" href="{{ $b['url'] }}" target="_blank" rel="noopener"><span class="bp-mono" style="background:{{ $bg }}">{{ $ini }}</span>{{ $b['name'] }}<span class="bp-muted">{{ $b['state'] }} ↗</span></a>
            @endforeach
        </div>
    </section>
</div>
@endsection
