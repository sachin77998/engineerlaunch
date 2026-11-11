@extends('layouts.site')
@section('title', $service->name . ' in ' . $city . ' — ' . config('properties.brand') . ' Legal Services')
@section('content')
@include('legal._head')
@php($inCart = in_array($service->id, $cart, true))
<div class="pr" style="padding-top:20px"><div class="pr-wrap">
    <nav class="pr-mute" style="font-size:13.5px;margin-bottom:12px"><a href="{{ route('legal.index', $citySlug) }}">Legal services in {{ $city }}</a> › {{ $service->categoryLabel($city) }} › {{ $service->name }}</nav>
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }} · <a href="{{ route('legal.cart') }}">Go to cart →</a></div>@endif
    <div style="display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:22px;align-items:start" class="lg-show">
        <div class="pr-card-box" style="padding:26px">
            <span class="pr-tag">{{ $service->categoryLabel($city) }}</span>
            <h1 style="margin:10px 0 8px">{{ $service->name }}</h1>
            <p style="font-size:17px;color:#374151;line-height:1.6;margin:0">{{ $service->summary }}</p>
            <p class="pr-mute" style="margin:10px 0 0">For: {{ collect($service->audiences)->map(fn ($a) => config('legal.audiences')[$a] ?? $a)->implode(', ') }}</p>
            @if($service->includes)<h2 style="margin:24px 0 10px">What's included</h2><ul style="margin:0;padding-left:20px;line-height:1.9">@foreach($service->includes as $i)<li>{{ $i }}</li>@endforeach</ul>@endif
            @if($service->documents)<h2 style="margin:24px 0 10px">Documents usually needed</h2><ul style="margin:0;padding-left:20px;line-height:1.9">@foreach($service->documents as $d)<li>{{ $d }}</li>@endforeach</ul>@endif
        </div>
        <aside class="pr-card-box" style="position:sticky;top:12px;padding:22px">
            <div class="pr-mute" style="font-size:13px">Price</div>
            <div style="font-size:26px;font-weight:800">{{ $service->priceLabel() }}</div>
            <div class="pr-mute" style="font-size:13px">Government fees and stamp duty extra, at actuals</div>
            @if($service->turnaround)<div style="margin-top:10px">⏱ {{ $service->turnaround }}</div>@endif
            <div style="display:grid;gap:8px;margin-top:16px">
                @if($service->slug === config('rent_agreement.agreement_service'))<a class="pr-btn" style="background:#e11d48" href="{{ route('rent-agreement.index', $citySlug) }}">Create it online →</a>@endif
                @if($inCart)<a class="pr-btn" href="{{ route('legal.cart') }}">✓ In cart · Go to cart</a>
                @else
                    <form method="post" action="{{ route('legal.cart.add', $service) }}">@csrf<input type="hidden" name="then" value="cart"><button class="pr-btn" style="width:100%;background:#0f766e">Book now</button></form>
                    <form method="post" action="{{ route('legal.cart.add', $service) }}">@csrf<button class="pr-btn ghost" style="width:100%">Add to cart</button></form>
                @endif
            </div>
        </aside>
    </div>
    @if($related->isNotEmpty())
        <h2 style="margin:30px 0 14px">More {{ mb_strtolower($service->categoryLabel($city)) }}</h2>
        <div class="lg-cards">@foreach($related as $s)<a class="lg-card" href="{{ route('legal.show', [$citySlug, $s]) }}"><h3>{{ $s->name }}</h3><p>{{ $s->summary }}</p><footer><b>{{ $s->priceLabel() }}</b></footer></a>@endforeach</div>
    @endif
</div></div>
@push('styles')<style>@media(max-width:860px){.lg-show{grid-template-columns:1fr!important}}</style>@endpush
@include('partials.property-services')
@endsection
