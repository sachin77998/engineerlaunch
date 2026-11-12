@extends('layouts.site')
@section('title', $builder->name . ' — projects')
@section('meta_description', \Illuminate\Support\Str::limit($builder->description ?: $builder->name . ' projects by city.', 155))
@section('content')
@include('properties._nav')
@include('properties.city._styles')
<div class="pr">
    <section class="cp-hero"><div class="pr-wrap" style="display:flex;gap:18px;align-items:center">
        <span class="cp-logo" style="width:84px;height:84px">@if($builder->logoUrl())<img src="{{ $builder->logoUrl() }}" alt="">@else{{ $builder->initials() }}@endif</span>
        <div><h1 style="color:#fff;margin:0">{{ $builder->name }}</h1>
            <span style="color:#c7d2fe">{{ $projects->total() }} {{ $projects->total() === 1 ? 'project' : 'projects' }}@if($builder->established_year) · since {{ $builder->established_year }}@endif @if($builder->website) · <a href="{{ $builder->website }}" target="_blank" rel="noopener nofollow" style="color:#fff">website ↗</a>@endif</span></div>
    </div></section>
    <div class="pr-wrap" style="margin-top:22px">
        @if($builder->description)<p style="max-width:820px;white-space:pre-line">{{ $builder->description }}</p>@endif
        @if($cities->count() > 1)<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">@foreach($cities as $c)<a class="cp-pill" href="{{ route('projects.index', [$c->city_slug, 'builder' => $builder->slug]) }}">{{ $c->city }} ({{ $c->n }})</a>@endforeach</div>@endif
        <div class="cp-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
            @foreach($projects as $pj)
                @include('properties.city._project', ['pj' => $pj])
            @endforeach
        </div>
        <div style="margin-top:18px">{{ $projects->links() }}</div>
    </div>
</div>
@endsection
