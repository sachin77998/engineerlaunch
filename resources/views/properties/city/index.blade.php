@extends('layouts.site')
@section('title', 'Real estate by city')
@section('meta_description', 'Choose a city to see its properties, projects, builders, localities and demand.')
@section('content')
@include('properties._nav')
@include('properties.city._styles')
<div class="pr">
    <section class="cp-hero"><div class="pr-wrap" style="padding-bottom:10px">
        @include('properties.city._chooser', ['inline' => true])
    </div></section>
    <div class="pr-wrap" style="margin-top:24px">
        @foreach($cities as $country => $list)
            <section class="cp-sec">
                <h2 style="margin-bottom:14px">{{ $country }}</h2>
                <div class="cp-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px">
                    @foreach($list as $c)
                        @include('properties.city._city', ['city' => $c, 'count' => (int) ($counts[$c->slug] ?? 0)])
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
@endsection
