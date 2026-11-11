@extends('layouts.site')
@section('title', $idea->title . ' — interior design idea')
@section('content')
@include('interiors._head')
<div class="pr" style="padding-bottom:60px"><div class="pr-wrap">
    <nav class="pr-mute" style="font-size:13.5px;margin:18px 0 10px"><a href="{{ route('interiors.ideas') }}">Design ideas</a>@if($idea->room) › <a href="{{ route('interiors.ideas', ['room' => $idea->room]) }}">{{ $idea->roomLabel() }}</a>@endif › {{ $idea->title }}</nav>
    <div style="display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:22px;align-items:start" class="in-idea">
        <img src="{{ $idea->imageUrl() }}" alt="{{ $idea->title }}" style="width:100%;border-radius:18px;display:block">
        <div class="pr-card-box" style="padding:22px">
            <h1 style="margin:0 0 8px">{{ $idea->title }}</h1>
            <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
                @if($idea->roomLabel())<a class="pr-tag" href="{{ route('interiors.ideas', ['room' => $idea->room]) }}">{{ $idea->roomLabel() }}</a>@endif
                @if($idea->styleLabel())<a class="pr-tag" href="{{ route('interiors.ideas', ['style' => $idea->style]) }}">{{ $idea->styleLabel() }}</a>@endif
                @if($idea->categoryLabel())<a class="pr-tag" href="{{ route('interiors.ideas', ['category' => $idea->category]) }}">{{ $idea->categoryLabel() }}</a>@endif
            </div>
            @if($idea->description)<p style="line-height:1.6">{{ $idea->description }}</p>@endif
            <a class="in-red" style="display:block;text-align:center" href="{{ route('interiors.quote', ['idea' => $idea->slug, 'style' => $idea->style]) }}">Get this design — free quote</a>
        </div>
    </div>
    @if($more->isNotEmpty())
        <h2 style="margin:30px 0 14px">More like this</h2>
        <div class="in-row" style="grid-auto-columns:minmax(220px,260px)">@foreach($more as $i)<a class="in-tile" href="{{ route('interiors.idea', $i) }}" style="background-image:url('{{ $i->imageUrl() }}');min-height:240px"><div><b>{{ $i->title }}</b></div></a>@endforeach</div>
    @endif
</div></div>
@push('styles')<style>@media(max-width:820px){.in-idea{grid-template-columns:1fr!important}}</style>@endpush
@endsection
