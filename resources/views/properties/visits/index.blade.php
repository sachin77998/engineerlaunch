@extends('layouts.site')
@section('title', 'My site visits')
@section('content')
@include('properties._nav')
@php($tone = ['requested' => 'amber', 'confirmed' => '', 'completed' => '', 'no_show' => 'amber', 'cancelled' => 'amber'])
<div class="pr"><div class="pr-wrap" style="max-width:900px;margin-top:22px">
    @if(session('error'))<div class="pr-flash err">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <h1 style="margin:0">My site visits</h1>
        <span style="display:flex;gap:8px"><a class="pr-btn ghost sm" href="{{ route('site-visits.enquiries') }}">My enquiries</a><a class="pr-btn ghost sm" href="{{ route('site-visits.hosting') }}">Visits to my listings</a></span>
    </div>

    <h2 style="font-size:19px;margin:20px 0 8px">Upcoming</h2>
    @forelse($upcoming as $v)
        <div class="pr-card-box" style="margin-bottom:12px;display:grid;gap:10px">
            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
                <div>@if($v->url())<a href="{{ $v->url() }}" style="font-weight:700;font-size:17px">{{ $v->title() }}</a>@else<b>{{ $v->title() }}</b>@endif
                    <br><span class="pr-mute">{{ $v->place() }}</span></div>
                <div style="text-align:right"><b style="font-size:17px">{{ $v->visit_at->format('D d M, g:i A') }}</b><br><span class="pr-tag {{ $tone[$v->status] ?? '' }}">{{ $v->statusLabel() }}</span> <small class="pr-mute">{{ $v->reference() }} · {{ $v->people }} {{ $v->people === 1 ? 'person' : 'people' }}</small></div>
            </div>
            @if($v->host_note)<div class="pr-mute" style="font-size:14px">💬 {{ $v->host_note }}</div>@endif
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <details style="flex:1;min-width:260px"><summary class="pr-btn ghost sm" style="list-style:none;cursor:pointer;display:inline-block">Reschedule</summary>
                    <form method="post" action="{{ route('site-visits.reschedule', $v) }}" style="display:grid;gap:10px;margin-top:10px">@csrf
                        @include('properties.visits._slot_picker', ['days' => $slotsFor[$v->id] ?? [], 'pickerId' => 'vs-' . $v->id])
                        @if(!empty($slotsFor[$v->id]))<button class="pr-btn sm" style="justify-self:start">Move visit</button>@endif
                    </form></details>
                <form method="post" action="{{ route('site-visits.cancel', $v) }}" onsubmit="return confirm('Cancel this visit?')">@csrf<button class="pr-btn grey sm">Cancel visit</button></form>
            </div>
        </div>
    @empty
        <div class="pr-card-box"><p class="pr-mute" style="margin:0">No upcoming visits. Book one from any listing or project page.</p></div>
    @endforelse

    @if($past->isNotEmpty())
        <h2 style="font-size:19px;margin:24px 0 8px">Past and cancelled</h2>
        <div class="pr-card-box" style="padding:0">
            @foreach($past as $v)
                <div style="display:flex;justify-content:space-between;gap:10px;padding:12px 16px;border-top:{{ $loop->first ? '0' : '1px solid var(--pr-line)' }};flex-wrap:wrap">
                    <span>@if($v->url())<a href="{{ $v->url() }}">{{ $v->title() }}</a>@else{{ $v->title() }}@endif<br><small class="pr-mute">{{ $v->visit_at->format('d M Y, g:i A') }} · {{ $v->reference() }}</small></span>
                    <span class="pr-tag {{ $tone[$v->status] ?? '' }}">{{ $v->statusLabel() }}{{ $v->status === 'cancelled' && $v->cancelled_by === 'host' ? ' by host' : '' }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div></div>
@endsection
