@extends('layouts.site')
@section('title', 'My enquiries')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap" style="max-width:900px;margin-top:22px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <h1 style="margin:0">My enquiries</h1><a class="pr-btn ghost sm" href="{{ route('site-visits.index') }}">My site visits</a>
    </div>
    <p class="pr-mute">Numbers you viewed, messages, callbacks, brochures and visits — for listings and projects.</p>
    @if($rows->isEmpty())
        <div class="pr-card-box"><p class="pr-mute" style="margin:0">Nothing yet.</p></div>
    @else
        <div class="pr-card-box" style="padding:0">
            @foreach($rows as $r)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:12px 16px;border-top:{{ $loop->first ? '0' : '1px solid var(--pr-line)' }};flex-wrap:wrap">
                    <span>@if($r['url'])<a href="{{ $r['url'] }}" style="font-weight:700">{{ $r['title'] }}</a>@else<b>{{ $r['title'] }}</b>@endif <small class="pr-mute">· {{ $r['type'] }}</small>
                        @if($r['detail'])<br><small class="pr-mute">{{ \Illuminate\Support\Str::limit($r['detail'], 140) }}</small>@endif</span>
                    <span style="text-align:right"><span class="pr-tag">{{ $r['kind'] }}</span><br><small class="pr-mute">{{ $r['when']->format('d M Y') }}</small></span>
                </div>
            @endforeach
        </div>
    @endif
</div></div>
@endsection
