@extends('layouts.site')
@section('title', 'All categories — construction & interior materials')
@section('meta_description', 'Every category of construction and interior materials: cement, plywood, paints, wires, plumbing, hardware, tools and more.')
@section('content')
@include('materials._nav')
<div class="container mt-wrap">
    <nav class="mt-crumbs" aria-label="Breadcrumb" style="margin:4px 0 0"><a href="{{ route('materials.index') }}">Home</a> › <b>All categories</b></nav>
    <h1 class="mt-h1" style="margin-top:0">All categories <span class="mt-muted" style="font-size:16px;font-weight:500">{{ $stats['categories'] }} categories · {{ number_format($stats['products']) }} products</span></h1>
    @foreach(config('materials.groups') + ['more' => 'More categories'] as $gk => $gname) @continue(empty($collections[$gk]))
        <section id="{{ $gk }}">
            <div class="mt-sec-h"><h2>{{ $gname }}</h2><span class="mt-muted">{{ count($collections[$gk]) }} categories</span></div>
            <div class="mt-tiles">
                @foreach($collections[$gk] as $slug => $c)
                    <a class="mt-tile" href="{{ route('materials.category', $slug) }}">
                        <span class="mt-ico">@if($c['image'])<img src="{{ $c['image'] }}" alt="" loading="lazy">@else{{ $c['icon'] }}@endif</span>
                        {{ $c['title'] }}<span class="mt-muted">{{ number_format($c['count']) }} products</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
