@extends('layouts.site')
@section('title', 'Visits to my listings')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap" style="max-width:1000px;margin-top:22px">
    @if(session('error'))<div class="pr-flash err">{{ session('error') }}</div>@endif
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <h1 style="margin:0">Visits to my listings</h1><a class="pr-btn ghost sm" href="{{ route('properties.mine') }}">My properties</a>
    </div>
    <p class="pr-mute">Confirm requests so visitors know you'll be there. After the visit, mark whether they came.</p>
    @forelse($visits as $v)
        <div class="pr-card-box" style="margin-bottom:10px;display:grid;gap:8px">
            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
                <div><b style="font-size:16px">{{ $v->visit_at->format('D d M, g:i A') }}</b> · {{ $v->name }} · <a href="tel:{{ preg_replace('/[^\d+]/', '', $v->phone) }}">{{ $v->phone }}</a>@if($v->email) · {{ $v->email }}@endif
                    <br><span class="pr-mute">@if($v->url())<a href="{{ $v->url() }}">{{ $v->title() }}</a>@else{{ $v->title() }}@endif · {{ $v->people }} {{ $v->people === 1 ? 'person' : 'people' }} · {{ $v->reference() }}</span>
                    @if($v->notes)<br><small>“{{ $v->notes }}”</small>@endif</div>
                <span class="pr-tag">{{ $v->statusLabel() }}</span>
            </div>
            @include('properties.visits._host_actions', ['v' => $v, 'action' => route('site-visits.host', $v)])
        </div>
    @empty
        <div class="pr-card-box"><p class="pr-mute" style="margin:0">No visit requests yet.</p></div>
    @endforelse
</div></div>
@endsection
