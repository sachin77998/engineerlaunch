@extends('layouts.site')
@section('title', 'Your property searches')
@section('content')
@include('properties._nav')
@include('properties.city._styles')
<div class="pr"><div class="pr-wrap" style="max-width:820px;margin-top:22px">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <h1 style="margin:0">Your searches</h1>
        @if($rows->isNotEmpty())<form method="post" action="{{ route('real-estate.searches.clear') }}" onsubmit="return confirm('Clear your whole search history?')">@csrf<button class="pr-btn ghost sm">Clear all</button></form>@endif
    </div>
    @guest<p class="pr-mute"><a href="{{ route('login') }}">Log in</a> to keep your searches on every device.</p>@endguest
    @if($rows->isEmpty())
        <div class="cp-empty" style="margin-top:16px"><p class="pr-mute">No searches yet.</p><a class="pr-btn" href="{{ route('real-estate.index') }}">Explore a city</a></div>
    @else
        <div class="cp-box" style="margin-top:14px;padding:0">
            @foreach($rows as $r)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:12px 16px;border-top:{{ $loop->first ? '0' : '1px solid var(--pr-line)' }}">
                    <a href="{{ $r->url }}" style="text-decoration:none;color:inherit"><b>🕘 {{ $r->label }}</b><br><small class="pr-mute">{{ \Illuminate\Support\Carbon::parse($r->created_at)->diffForHumans() }}</small></a>
                    <form method="post" action="{{ route('real-estate.searches.clear') }}">@csrf<input type="hidden" name="id" value="{{ $r->id }}"><button class="pr-btn grey sm" aria-label="Remove">✕</button></form>
                </div>
            @endforeach
        </div>
    @endif
</div></div>
@endsection
