@extends('layouts.site')
@section('title', 'My home service bookings — ' . config('properties.brand'))
@section('content')
@include('home-services._head', ['city' => session('home_services_city'), 'cities' => collect(config('properties.cities.India'))])
@php($PF = \App\Services\Property\PropertyFormat::class)
<div class="pr"><div class="pr-wrap" style="max-width:960px">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    <h1 style="margin:22px 0 14px">My bookings</h1>
    @forelse($items as $b)
        <div class="pr-card-box" style="margin-bottom:10px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div>
                <b>{{ $b->reference() }} · {{ $b->categoryLabel() }}</b> <span class="pr-tag {{ in_array($b->status, ['confirmed', 'assigned', 'completed'], true) ? 'ok' : ($b->status === 'requested' ? 'amber' : '') }}">{{ $b->statusLabel() }}</span>
                <div class="pr-mute" style="font-size:13.5px">{{ $b->slotLabel() }} · {{ $b->city }} · {{ \Illuminate\Support\Str::limit($b->address, 60) }}</div>
                @if($b->items->isNotEmpty())<ul style="margin:6px 0 0;padding-left:18px;font-size:14px">@foreach($b->items as $i)<li>{{ $i->name }} × {{ $i->qty }} — {{ $i->price ? $PF::full($i->price * $i->qty) : 'on inspection' }}</li>@endforeach</ul>@endif
                @if($b->details)<div style="font-size:14px;margin-top:6px">🚚 {{ config('home_services.move.sizes')[$b->details['size']] ?? '' }}: {{ $b->details['from'] }} → {{ $b->details['to'] }}@if(!empty($b->details['to_city'])), {{ $b->details['to_city'] }}@endif</div>@endif
                @if($b->admin_note)<p style="margin:6px 0 0"><b>From our team:</b> {{ $b->admin_note }}</p>@endif
                @if($b->status === 'completed')
                    <details style="margin-top:10px" @if(!$b->review) open @endif><summary style="cursor:pointer;font-weight:700">{{ $b->review ? 'Edit your review' : 'Rate this service' }}</summary>
                        <form method="post" action="{{ route('home-services.review', $b) }}" style="display:grid;gap:8px;margin-top:8px;max-width:520px">@csrf
                            <select name="rating" required aria-label="Rating">@for($n = 5; $n >= 1; $n--)<option value="{{ $n }}" @selected((int) old('rating', $b->review?->rating ?? 5) === $n)>{{ str_repeat('★', $n) }} {{ $n }}</option>@endfor</select>
                            <textarea name="body" required minlength="15" maxlength="1500" rows="3" placeholder="How was the service?">{{ old('body', $b->review?->body) }}</textarea>
                            <button class="pr-btn sm" style="justify-self:start">{{ $b->review ? 'Update' : 'Publish' }} review</button></form></details>
                @endif
            </div>
            <div style="text-align:right">
                @if($b->items->isNotEmpty())<b style="font-size:17px">{{ $PF::full($b->total) }}</b>@if($b->has_quote_items)<div class="pr-mute" style="font-size:12px">+ on inspection</div>@endif @else<span class="pr-mute">Quote pending</span>@endif
                @if(in_array($b->status, ['requested', 'confirmed'], true))<form method="post" action="{{ route('home-services.cancel', $b) }}" onsubmit="return confirm('Cancel this booking?')" style="margin-top:8px">@csrf<button class="pr-btn grey sm">Cancel</button></form>@endif
            </div>
        </div>
    @empty
        <div class="pr-card-box">No bookings yet. <a href="{{ route('home-services.index') }}">Book a home service →</a></div>
    @endforelse
    {{ $items->links() }}
</div></div>
@endsection
