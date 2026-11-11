@extends('layouts.site')
@section('title', 'Shortlisted properties — ' . config('properties.brand'))
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap">
    <h1 style="margin:22px 0 14px">Shortlisted properties</h1>
    <div class="pr-list">
        @forelse($items as $p)
            @include('properties._card', ['p' => $p, 'shortlisted' => $items->pluck('id')->all()])
        @empty
            <div class="pr-card-box" style="text-align:center;padding:36px"><h2>Nothing shortlisted yet</h2><p class="pr-mute">Tap ♡ on any listing to keep it here.</p><a class="pr-btn" href="{{ route('properties.index') }}">Search properties</a></div>
        @endforelse
    </div>
    <div style="margin-top:16px">{{ $items->links() }}</div>
</div></div>
@endsection
