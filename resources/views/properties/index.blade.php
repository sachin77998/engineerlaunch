@extends('layouts.site')
@section('title', config('properties.brand') . ' — buy, rent and commercial property')
@section('meta_description', 'Flats, houses, plots, PG and flatmates, offices, shops and warehouses for rent and sale, posted directly by owners. Post your property ad free.')
@section('content')
@include('properties._nav')
@push('styles')<style>
.pr-hero{background:linear-gradient(135deg,#1e1b4b 0%,#3730a3 60%,#4f46e5 100%);padding:40px 0 70px;color:#fff}
.pr-hero h1{color:#fff!important;margin:0 0 8px}.pr-hero p{margin:0 0 22px;color:#c7d2fe}
.pr-hero .ps{margin-bottom:-60px;position:relative}
.pr-strip{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:14px}
.pr-tile{background:#fff;border:1px solid var(--pr-line);border-radius:14px;overflow:hidden;text-decoration:none;color:var(--pr-ink);display:block}
.pr-tile:hover{box-shadow:0 10px 24px rgba(17,24,39,.08)}
.pr-tile .pr-thumb{min-height:160px}.pr-tile div.t{padding:12px 14px}.pr-tile b{display:block;font-size:17px}.pr-tile span{display:block;font-size:13.5px;color:#374151;margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pr-post-cta{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:#fff;border:1px solid var(--pr-line);border-radius:16px;padding:20px 22px}
</style>@endpush
<div class="pr">
    <section class="pr-hero"><div class="pr-wrap">
        <h1>Homes, plots, PGs and commercial spaces</h1>
        <p>{{ number_format($counts->n ?? 0) }} live listings in {{ number_format($counts->cities ?? 0) }} {{ ($counts->cities ?? 0) === 1 ? 'city' : 'cities' }}, posted by owners, agents and builders.</p>
        @include('properties._search', ['cities' => $cities])
    </div></section>
    <div class="pr-wrap" style="margin-top:90px">
        @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
        @foreach($latest as $mode => $items)
            <section style="margin-bottom:28px">
                <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px"><h2>Latest {{ strtolower(config("properties.modes.$mode.label")) }} listings</h2><a href="{{ route('properties.search', $mode) }}">View all →</a></div>
                <div class="pr-strip">
                    @foreach($items as $p)
                        <a class="pr-tile" href="{{ route('properties.show', $p) }}">
                            <div class="pr-thumb">@if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="" loading="lazy">@else⌂@endif</div>
                            <div class="t"><b>{{ $p->headlinePrice() }}</b><span>{{ $p->title }}</span></div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
        <div class="pr-post-cta">
            <div><h2 style="margin:0 0 4px">Own a property?</h2><span class="pr-mute">List it free for rent or sale. Tenants and buyers contact you directly.</span></div>
            <a class="pr-btn" href="{{ route('properties.post') }}">Post free property ad</a>
        </div>
    </div>
</div>
@include('partials.property-services')
@endsection
