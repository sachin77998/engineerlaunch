@extends('layouts.site')
@section('title', 'My bookings — ' . config('properties.brand') . ' Legal Services')
@section('content')
@include('legal._head')
@php($PF = \App\Services\Property\PropertyFormat::class)
<div class="pr" style="padding-top:20px"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <h1>My bookings</h1>
    @forelse($items as $b)
        <div class="pr-card-box" style="margin-bottom:12px">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                    <b>{{ $b->reference() }}</b> <span class="pr-tag {{ in_array($b->status, ['confirmed', 'in_progress', 'completed'], true) ? 'ok' : ($b->status === 'requested' ? 'amber' : '') }}">{{ $b->statusLabel() }}</span>
                    <div class="pr-mute" style="font-size:13.5px">{{ $b->city }} · booked {{ $b->created_at->format('d M Y') }}@if($b->preferred_at) · preferred {{ $b->preferred_at->format('d M, g:i A') }}@endif</div>
                    <ul style="margin:8px 0 0;padding-left:18px">@foreach($b->items as $i)<li>{{ $i->name }} — {{ $i->price ? $PF::full($i->price) : 'price on call' }}</li>@endforeach</ul>
                    @if($b->rentAgreement)<p style="margin:8px 0 0">📄 <a href="{{ route('rent-agreement.preview', $b->rentAgreement) }}">View rent agreement {{ $b->rentAgreement->reference() }}</a></p>@endif
                    @if($b->admin_note)<p style="margin:8px 0 0"><b>From our team:</b> {{ $b->admin_note }}</p>@endif
                </div>
                <div style="text-align:right"><b style="font-size:18px">{{ $PF::full($b->total) }}</b>@if($b->has_quote_items)<div class="pr-mute" style="font-size:12.5px">+ items priced on call</div>@endif
                    @if($b->status === 'requested')<form method="post" action="{{ route('legal.bookings.cancel', $b) }}" onsubmit="return confirm('Cancel this booking?')" style="margin-top:8px">@csrf<button class="pr-btn grey sm">Cancel</button></form>@endif
                </div>
            </div>
            @if($b->status === 'completed')
                <details style="margin-top:12px" @if(!$b->review) open @endif>
                    <summary style="cursor:pointer;font-weight:700">{{ $b->review ? 'Edit your review' : 'Rate this service' }}</summary>
                    <form method="post" action="{{ route('legal.bookings.review', $b) }}" style="display:grid;gap:10px;margin-top:10px;max-width:560px">
                        @csrf
                        <div class="hl-pills" style="display:flex;gap:8px">@for($n = 5; $n >= 1; $n--)<label style="position:relative"><input type="radio" name="rating" value="{{ $n }}" required style="position:absolute;opacity:0" @checked((int) old('rating', $b->review?->rating) === $n)><span style="display:block;padding:7px 14px;border:1px solid #d1d5db;border-radius:999px;cursor:pointer">{{ $n }} ★</span></label>@endfor</div>
                        <input type="text" name="title" required maxlength="120" placeholder="Title, e.g. Smooth registration" value="{{ old('title', $b->review?->title) }}">
                        <textarea name="body" required minlength="20" maxlength="2000" rows="3" placeholder="How was the service?">{{ old('body', $b->review?->body) }}</textarea>
                        <button class="pr-btn sm" style="justify-self:start">{{ $b->review ? 'Update review' : 'Publish review' }}</button>
                    </form>
                </details>
            @endif
        </div>
    @empty
        <div class="lg-empty">No bookings yet. <a href="{{ route('legal.index') }}">Browse legal services →</a></div>
    @endforelse
</div></div>
@push('styles')<style>.hl-pills input:checked+span{background:#0f766e;color:#fff;border-color:#0f766e}</style>@endpush
@endsection
