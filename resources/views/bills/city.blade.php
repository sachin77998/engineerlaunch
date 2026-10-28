@extends('layouts.site')
@section('title', $city['name'] . ' municipal services — property tax, water & sewerage bill')
@section('meta_description', 'Pay property tax, water and sewerage bills, apply for trade licence, fire NOC and building plan approval with ' . $city['body'] . ' — official ' . $city['state'] . ' government links.')
@section('content')
@include('bills._nav')
<div class="container bp-wrap">
    <section class="bp-card">
        <p class="bp-muted" style="margin:0 0 4px"><a href="{{ route('bills.index') }}">Bills &amp; Payments</a> › {{ $city['state'] }}</p>
        <h1>{{ $city['body'] }}</h1>
        @if($city['url'])<p style="margin:0"><a href="{{ $city['url'] }}" target="_blank" rel="noopener">Official website ↗</a> <span class="bp-muted">· notices, tenders, budget, citizen charter, RTI</span></p>
        @else<p class="bp-muted" style="margin:0">The corporation's own website is not listed on e-Nigam; use the state's online services below.</p>@endif
    </section>
    <section class="bp-card">
        <h2>Pay &amp; apply online</h2>
        <div class="bp-list">
            @foreach($services as [$label, $url])<a href="{{ $url }}" target="_blank" rel="noopener"><strong>{{ $label }}</strong><span class="bp-muted">{{ parse_url($url, PHP_URL_HOST) }} ↗</span></a>@endforeach
        </div>
        <div class="bp-note">These are the Local Government {{ $city['state'] }} portals the corporation links to; choose {{ $city['name'] }} on the portal and enter your property / connection ID.</div>
    </section>
    <section class="bp-card">
        <h2>Electricity, gas and other bills in {{ $city['state'] }}</h2>
        <div class="bp-grid">
            @foreach($categories as $c) @php($local = collect($c['billers'])->firstWhere('state', $city['state'])) @continue(!$local)
                <a class="bp-tile" href="{{ route('bills.category', [$c['key'], 'biller' => $local['slug']]) }}#pay"><span class="bp-mono" style="background:#eef4ff;font-size:30px">{{ $c['icon'] }}</span><strong>{{ $c['name'] }}</strong><span class="bp-muted">{{ $local['name'] }}</span></a>
            @endforeach
        </div>
    </section>
    @if($others->isNotEmpty())
    <section class="bp-card"><h2>Other cities in {{ $city['state'] }}</h2>
        <div class="bp-chips">@foreach($others as $o)<a href="{{ route('bills.city', $o['slug']) }}">{{ $o['name'] }}</a>@endforeach</div></section>
    @endif
</div>
@endsection
