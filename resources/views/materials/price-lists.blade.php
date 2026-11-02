@extends('layouts.site')
@section('title', 'Construction material price lists — every brand and category')
@section('meta_description', 'Today\'s prices for cement, plywood, paints, putty, wires, switches, pipes and hardware, brand by brand, with the change over the last week.')
@section('content')
@include('materials._nav')
<div class="container mt-wrap">
    <section class="mt-card">
        <h1>Price lists by category</h1>
        <div class="mt-tiles">
            @foreach($collections as $slug => $c)
                <a class="mt-tile" href="{{ route('materials.price-list', $slug) }}"><span class="mt-ico" style="max-width:90px">@if($c['image'])<img src="{{ $c['image'] }}" alt="" loading="lazy">@else{{ $c['icon'] }}@endif</span>{{ $c['title'] }} prices<span class="mt-muted">{{ $c['count'] }} products</span></a>
            @endforeach
        </div>
    </section>
    <section class="mt-card">
        <h2>Price lists by brand</h2>
        <div class="mt-tiles">
            @foreach($brands as $b)@php([$ini, $bg] = \App\Support\Monogram::for($b->brand))
                <a class="mt-tile" href="{{ route('materials.price-list', $b->brand_slug) }}"><span style="width:52px;height:52px;border-radius:50%;display:grid;place-items:center;color:#fff;font:800 16px Inter,system-ui,sans-serif;background:{{ $bg }}">{{ $ini }}</span>{{ $b->brand }}<span class="mt-muted">{{ $b->n }} products · from ₹{{ number_format($b->low) }}</span></a>
            @endforeach
        </div>
    </section>
</div>
@endsection
