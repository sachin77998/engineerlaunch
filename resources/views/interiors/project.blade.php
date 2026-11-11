@extends('layouts.site')
@section('title', $p->title . ', ' . $p->city . ' — interiors project')
@section('content')
@include('interiors._head')
<div class="pr" style="padding-bottom:60px"><div class="pr-wrap">
    <nav class="pr-mute" style="font-size:13.5px;margin:18px 0 10px"><a href="{{ route('interiors.index') }}">Home interiors</a> › Projects › {{ $p->title }}</nav>
    <h1 style="margin:0">{{ $p->title }}</h1>
    <p class="pr-mute" style="margin:4px 0 16px">{{ $p->city }}@if($p->bhk) · {{ $p->bhk }} BHK @endif @if($p->styleLabel()) · {{ $p->styleLabel() }}@endif @if($p->rating) · ★ {{ $p->rating }}/5 @endif</p>
    @if($p->videoEmbed())
        <div style="position:relative;padding-top:56.25%;border-radius:18px;overflow:hidden;margin-bottom:18px"><iframe src="{{ $p->videoEmbed() }}" title="Video tour of {{ $p->title }}" style="position:absolute;inset:0;width:100%;height:100%;border:0" allow="encrypted-media; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>
    @elseif($p->video_url)
        <p><a class="pr-btn" href="{{ $p->video_url }}" target="_blank" rel="noopener nofollow">▶ Video tour</a></p>
    @endif
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px">
        @foreach(array_merge([$p->coverUrl()], $p->photoUrls()) as $url)<a href="{{ $url }}" target="_blank"><img src="{{ $url }}" alt="{{ $p->title }}" loading="lazy" style="width:100%;height:240px;object-fit:cover;border-radius:14px;display:block"></a>@endforeach
    </div>
    @if($p->review)<blockquote class="pr-card-box" style="margin:22px 0 0;font-size:17px;line-height:1.6">“{{ $p->review }}”@if($p->reviewer)<footer class="pr-mute" style="margin-top:8px">— {{ $p->reviewer }}</footer>@endif</blockquote>@endif
    <p style="margin-top:22px"><a class="in-red" href="{{ route('interiors.quote', ['style' => $p->style, 'bhk' => $p->bhk]) }}">Want a home like this? Get a free quote</a></p>
    @if($more->isNotEmpty())
        <h2 style="margin:30px 0 14px">More projects</h2>
        <div class="in-row" style="grid-auto-columns:minmax(240px,280px)">@foreach($more as $m)<a class="in-tile" href="{{ route('interiors.project', $m) }}" style="background-image:url('{{ $m->coverUrl() }}');min-height:240px"><div style="display:block"><span class="in-pill">{{ $m->city }}</span><b style="display:block;margin-top:6px">{{ $m->title }}</b></div></a>@endforeach</div>
    @endif
</div></div>
@endsection
