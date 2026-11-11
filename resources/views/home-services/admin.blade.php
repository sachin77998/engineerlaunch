@extends('layouts.site')
@section('title', 'Home services — admin')
@section('content')
@include('properties._nav')
@php
    $PF = \App\Services\Property\PropertyFormat::class;
    $c = config('home_services');
@endphp
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <h1 style="margin:22px 0 10px">Home service bookings</h1>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
        <a class="pr-btn sm {{ $status ? 'ghost' : '' }}" href="{{ route('admin.home-services.index') }}">All ({{ $counts->sum() }})</a>
        @foreach($c['statuses'] as $k => $l)<a class="pr-btn sm {{ $status === $k ? '' : 'ghost' }}" href="{{ route('admin.home-services.index', ['status' => $k]) }}">{{ $l }} ({{ $counts[$k] ?? 0 }})</a>@endforeach
        <a class="pr-btn sm ghost" href="#catalogue">Services &amp; prices</a>
    </div>
    @forelse($bookings as $b)
        <div class="pr-card-box" style="margin-bottom:10px;display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:14px">
            <div><b>{{ $b->reference() }} · {{ $b->categoryLabel() }} · {{ $b->slotLabel() }}</b> <span class="pr-tag">{{ $b->statusLabel() }}</span>
                <div style="font-size:14px">{{ $b->name }} · <a href="tel:+91{{ $b->phone }}">+91 {{ $b->phone }}</a> · {{ $b->city }} · {{ $b->address }}@if($b->pincode) - {{ $b->pincode }}@endif</div>
                @if($b->items->isNotEmpty())<div class="pr-mute" style="font-size:13.5px">@foreach($b->items as $i){{ $i->name }} × {{ $i->qty }} ({{ $i->price ? $PF::full($i->price * $i->qty) : 'quote' }})@if(!$loop->last), @endif @endforeach · <b>{{ $PF::full($b->total) }}</b></div>@endif
                @if($b->details)<div style="font-size:13.5px">🚚 {{ $c['move']['types'][$b->details['type']] ?? '' }}, {{ $c['move']['sizes'][$b->details['size']] ?? '' }} · {{ $b->details['from'] }} → {{ $b->details['to'] }}@if(!empty($b->details['to_city'])), {{ $b->details['to_city'] }}@endif · floors {{ $b->details['from_floor'] ?? '?' }}→{{ $b->details['to_floor'] ?? '?' }} {{ !empty($b->details['lift']) ? '(lift)' : '(no lift)' }}@if(!empty($b->details['extras'])) · {{ collect($b->details['extras'])->map(fn ($e) => $c['move']['extras'][$e] ?? $e)->implode(', ') }}@endif</div>@endif
                @if($b->notes)<div class="pr-mute" style="font-size:13px">{{ $b->notes }}</div>@endif
            </div>
            <form method="post" action="{{ route('admin.home-services.update', $b) }}" style="display:grid;gap:6px">@csrf
                <select name="status">@foreach($c['statuses'] as $k => $l)<option value="{{ $k }}" @selected($b->status === $k)>{{ $l }}</option>@endforeach</select>
                <input type="text" name="admin_note" maxlength="500" value="{{ $b->admin_note }}" placeholder="Note shown to customer (e.g. pro name, quote)"><button class="pr-btn sm">Save</button></form>
        </div>
    @empty
        <div class="pr-card-box">No bookings.</div>
    @endforelse
    {{ $bookings->links() }}

    <h2 style="margin:30px 0 8px">Customer reviews</h2>
    @forelse($reviews as $r)
        <div class="pr-card-box" style="margin-bottom:6px;display:flex;justify-content:space-between;gap:10px"><div><b>{{ $r->rating }} ★</b> {{ $c['categories'][$r->category][0] ?? $r->category }} · {{ $r->city }} — {{ $r->user?->name }}<p style="margin:4px 0 0">{{ $r->body }}</p></div>
            <form method="post" action="{{ route('admin.home-services.review', $r) }}">@csrf<button class="pr-btn sm {{ $r->is_visible ? 'grey' : '' }}">{{ $r->is_visible ? 'Hide' : 'Show' }}</button></form></div>
    @empty<div class="pr-card-box">No reviews yet.</div>@endforelse

    <h2 id="catalogue" style="margin:30px 0 6px">Services &amp; prices</h2>
    <p class="pr-mute" style="margin-top:0">Leave price empty for "Price after inspection".</p>
    <div style="overflow-x:auto"><table class="pr-card-box" style="width:100%;border-collapse:collapse;padding:0">
        <thead><tr class="pr-mute" style="text-align:left;font-size:13px"><th style="padding:10px">Service</th><th>Price ₹</th><th>Unit</th><th>Order</th><th>Live</th><th></th></tr></thead>
        <tbody>@foreach($services as $s)<tr style="border-top:1px solid var(--pr-line)">
            <td style="padding:8px 10px"><b>{{ $s->name }}</b><div class="pr-mute" style="font-size:12px">{{ $c['categories'][$s->category][0] ?? $s->category }}</div></td>
            <td><input form="hs-{{ $s->id }}" type="number" name="price" min="0" value="{{ $s->price }}" style="width:110px"></td>
            <td><input form="hs-{{ $s->id }}" type="text" name="unit" maxlength="30" value="{{ $s->unit }}" style="width:120px"></td>
            <td><input form="hs-{{ $s->id }}" type="number" name="sort" value="{{ $s->sort }}" style="width:64px"></td>
            <td><input form="hs-{{ $s->id }}" type="checkbox" name="is_active" value="1" @checked($s->is_active)></td>
            <td><form id="hs-{{ $s->id }}" method="post" action="{{ route('admin.home-services.service', $s->id) }}">@csrf<button class="pr-btn sm">Save</button></form></td>
        </tr>@endforeach</tbody>
    </table></div>
</div></div>
@endsection
