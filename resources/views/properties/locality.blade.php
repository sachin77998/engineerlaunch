@extends('layouts.site')
@section('title', $name . ', ' . $cityName . ' — locality reviews and property')
@section('meta_description', ($summary ? 'Rated ' . $summary['average'] . '/5 by ' . $summary['count'] . ' residents. ' : '') . 'Reviews, ratings and homes for sale and rent in ' . $name . ', ' . $cityName . '.')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    <nav class="pr-mute" style="font-size:13px;margin:14px 0 6px"><a href="{{ route('properties.index') }}" style="color:inherit">Home</a> › <a href="{{ route('properties.search', ['buy', $city]) }}" style="color:inherit">Property in {{ $cityName }}</a> › {{ $name }}</nav>
    <h1>{{ $name }}, {{ $cityName }}</h1>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px">
        <a class="pr-btn ghost sm" href="{{ route('properties.search', ['buy', $city, 'loc' => [$name]]) }}">Property for sale in {{ $name }}</a>
        <a class="pr-btn ghost sm" href="{{ route('properties.search', ['rent', $city, 'loc' => [$name]]) }}">Property for rent in {{ $name }}</a>
        @if($mine)<a class="pr-btn ghost sm" href="{{ route('localities.review', [$city, $locality]) }}">Edit my review</a>@endif
    </div>
    <section class="pr-card-box" style="background:transparent;border:0;padding:0">
        @include('properties._locality_reviews', ['show' => 30])
    </section>
    @if($listings->isNotEmpty())
        <section style="margin-top:26px"><h2>Listings in {{ $name }}</h2><div class="pr-list">@foreach($listings as $p)@include('properties._card', ['p' => $p])@endforeach</div></section>
    @endif
</div></div>
@endsection
