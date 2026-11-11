@extends('layouts.site')
@section('title', 'My cart — ' . config('properties.brand') . ' Legal Services')
@section('content')
@include('legal._head')
@php($PF = \App\Services\Property\PropertyFormat::class)
<div class="pr" style="padding-top:20px"><div class="pr-wrap">
    @if(session('error'))<div class="pr-flash err">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <h1>My cart</h1>
    @if($items->isEmpty())
        <div class="lg-empty">Your cart is empty. <a href="{{ route('legal.index', $citySlug) }}">Browse legal services →</a></div>
    @else
    <div style="display:grid;grid-template-columns:minmax(0,1fr) 400px;gap:22px;align-items:start" class="lg-show">
        <div>
            @foreach($items as $s)
                <div class="pr-card-box" style="display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:10px">
                    <div><a href="{{ route('legal.show', [$citySlug, $s]) }}" style="font-weight:700;color:inherit">{{ $s->name }}</a><div class="pr-mute" style="font-size:13.5px">{{ $s->categoryLabel($city) }}@if($s->turnaround) · {{ $s->turnaround }}@endif</div></div>
                    <div style="display:flex;gap:12px;align-items:center"><b>{{ $s->priceLabel() }}</b>
                        <form method="post" action="{{ route('legal.cart.remove', $s) }}">@csrf<button class="pr-btn grey sm" aria-label="Remove {{ $s->name }}">Remove</button></form></div>
                </div>
            @endforeach
            <div class="pr-card-box" style="display:flex;justify-content:space-between;font-size:17px">
                <span>Total</span><b>{{ $PF::full($items->sum('price')) }}@if($items->contains(fn ($s) => !$s->price)) <span class="pr-mute" style="font-weight:500;font-size:14px">+ items priced on call</span>@endif</b>
            </div>
            <p class="pr-mute" style="font-size:13px">Government fees and stamp duty are extra, at actuals. Nothing is charged now — our team confirms the price on a call.</p>
            <a href="{{ route('legal.index', $citySlug) }}">+ Add more services</a>
        </div>
        <div class="pr-card-box" style="padding:22px">
            <h2>Book these services</h2>
            @auth
            <form method="post" action="{{ route('legal.checkout') }}" style="display:grid;gap:12px">
                @csrf
                <div><label class="f" for="lb-name">Full name <em>*</em></label><input id="lb-name" type="text" name="name" required maxlength="120" value="{{ old('name', auth()->user()->name) }}"></div>
                <div><label class="f" for="lb-phone">Mobile number <em>*</em></label><div class="hl-dial" style="display:flex"><span style="display:grid;place-items:center;padding:0 12px;border:1px solid #d1d5db;border-right:0;border-radius:10px 0 0 10px;background:#f9fafb;font-weight:700">+91</span><input id="lb-phone" type="tel" name="phone" required pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" value="{{ old('phone') }}" style="border-radius:0 10px 10px 0"></div></div>
                <div><label class="f" for="lb-email">Email</label><input id="lb-email" type="email" name="email" maxlength="191" value="{{ old('email', auth()->user()->email) }}"></div>
                <div><label class="f" for="lb-city">City <em>*</em></label><select id="lb-city" name="city" required>@foreach($cities as $c)<option @selected(old('city', $city) === $c)>{{ $c }}</option>@endforeach</select></div>
                <div><label class="f" for="lb-addr">Property address</label><input id="lb-addr" type="text" name="address" maxlength="255" value="{{ old('address') }}" placeholder="Optional"></div>
                <div><label class="f" for="lb-when">Preferred call / visit time</label><input id="lb-when" type="datetime-local" name="preferred_at" value="{{ old('preferred_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}"></div>
                <div><label class="f" for="lb-notes">Anything we should know?</label><textarea id="lb-notes" name="notes" rows="3" maxlength="1000">{{ old('notes') }}</textarea></div>
                <button class="pr-btn" style="background:#0f766e">Place booking</button>
            </form>
            @else
                <p>Sign in to place your booking — your cart is kept.</p>
                <a class="pr-btn" href="{{ route('login', ['redirect' => route('legal.cart')]) }}">Sign in to continue</a>
            @endauth
        </div>
    </div>
    @endif
</div></div>
@push('styles')<style>@media(max-width:860px){.lg-show{grid-template-columns:1fr!important}}</style>@endpush
@endsection
