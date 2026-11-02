@extends('layouts.site')
@section('title', 'Cart — Home Building store')
@section('content')
@include('materials._nav')
@push('styles')<style>
.mt-cart{display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start}
.mt-line{display:grid;grid-template-columns:90px 1fr auto;gap:16px;padding:16px 0;border-bottom:1px solid #f1ece6}
.mt-line img{width:90px;height:90px;object-fit:contain;border:1px solid var(--mt-line);border-radius:12px;background:#fff}
.mt-line h3{margin:0 0 4px;font-size:16px}.mt-line h3 a{color:var(--mt-ink);text-decoration:none}
.mt-line .r{text-align:right;display:grid;gap:6px;justify-items:end;align-content:start}
.mt-qty{width:84px;text-align:center}
.mt-summary{position:sticky;top:180px}
.mt-summary dl{display:grid;grid-template-columns:1fr auto;gap:10px;margin:0 0 14px;font-size:15px}.mt-summary dt{color:#57534e}.mt-summary dd{margin:0;text-align:right}
.mt-summary .tot{font-size:20px;font-weight:800;color:var(--mt-ink)}
.mt-nudge{padding:10px 12px;border-radius:10px;background:#ecfdf5;color:#166534;font-size:13.5px;margin-bottom:10px}
.mt-progress{height:6px;border-radius:3px;background:#f5f0ea;overflow:hidden;margin-top:6px}.mt-progress i{display:block;height:100%;background:#16a34a}
@media(max-width:900px){.mt-cart{grid-template-columns:1fr}.mt-summary{position:static}.mt-line{grid-template-columns:64px 1fr}.mt-line img{width:64px;height:64px}.mt-line .r{grid-column:1/-1;justify-items:start;text-align:left;grid-auto-flow:column;align-items:center}}
</style>@endpush
@php($freeAt = collect(config('materials.store.delivery.slabs'))->first(fn ($s) => $s[1] == 0)[0] ?? null)
<div class="container mt-wrap">
    <h1 class="mt-h1" style="margin:0">Cart <span class="mt-muted" style="font-size:16px;font-weight:500">{{ $rows->count() }} {{ Str::plural('item', $rows->count()) }}</span></h1>
    @if($rows->isEmpty())
        <section class="mt-card" style="text-align:center;padding:50px 20px">
            <div style="font-size:60px">🛒</div>
            <p style="font-size:17px">Your cart is empty.</p>
            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap"><a class="mt-btn" href="{{ route('materials.index') }}">Shop materials</a><a class="mt-btn ghost" href="{{ route('materials.calculator') }}">Estimate my build</a></div>
        </section>
    @else
    <div class="mt-cart">
        <section class="mt-card">
            <form method="post" action="{{ route('materials.list.update') }}" id="ml-form">@csrf
                @foreach($rows as $r)
                    <div class="mt-line">
                        <a href="{{ route('materials.show', $r['m']) }}">@if($r['m']->image_url)<img src="{{ $r['m']->image_url }}" alt="">@endif</a>
                        <div>
                            <h3><a href="{{ route('materials.show', $r['m']) }}">{{ $r['m']->title }}</a></h3>
                            <div class="mt-muted">{{ $r['variant']['title'] ?? '' }}{{ $r['m']->brand ? ' · ' . $r['m']->brand : '' }}</div>
                            @unless($r['available'])<span class="mt-tag out">Out of stock — remove to check out</span>@endunless
                            @if($r['next'])<div class="mt-muted" style="color:#2563eb">Add {{ $r['next']['qty'] - $r['qty'] }} more to pay ₹{{ number_format($r['m']->packPrice(['price' => $r['next']['price']])) }} each</div>@endif
                            <div style="display:flex;gap:10px;align-items:center;margin-top:8px">
                                <input class="mt-in mt-qty" type="number" min="0" max="{{ config('materials.store.max_qty') }}" name="qty[{{ $r['key'] }}]" value="{{ $r['qty'] }}" aria-label="Quantity">
                                <button class="mt-btn sm ghost" name="qty[{{ $r['key'] }}]" value="0" title="Remove">Remove</button>
                            </div>
                        </div>
                        <div class="r">
                            <b style="font-size:18px">₹{{ number_format($r['total']) }}</b>
                            <span class="mt-muted">₹{{ number_format($r['unit'], 2) }} each</span>
                            @if($r['saving'] > 0)<span class="mt-off">Bulk saving ₹{{ number_format($r['saving']) }}</span>@endif
                        </div>
                    </div>
                @endforeach
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px">
                    <button class="mt-btn ghost">Update cart</button>
                    <a class="mt-btn ghost" href="{{ route('materials.list.export') }}">Download CSV</a>
                    <button class="mt-btn ghost" form="ml-clear">Empty cart</button>
                </div>
            </form>
            <form method="post" action="{{ route('materials.list.clear') }}" id="ml-clear">@csrf</form>
        </section>
        <aside class="mt-card mt-summary">
            <h2 style="font-size:19px">Order summary</h2>
            @if($t['to_free'] > 0 && $freeAt)
                <div class="mt-nudge">Add ₹{{ number_format($t['to_free']) }} more for <b>free delivery</b><div class="mt-progress"><i style="width:{{ min(100, round(($freeAt - $t['to_free']) / $freeAt * 100)) }}%"></i></div></div>
            @endif
            @if($t['to_offer'] > 0 && $t['to_offer'] < config('materials.store.offer.min_order') / 2)
                <div class="mt-nudge" style="background:#fffbeb;color:#92400e">Add ₹{{ number_format($t['to_offer']) }} more to get <b>{{ config('materials.store.offer.percent') }}% off</b> the whole order</div>
            @endif
            <dl>
                <dt>Items</dt><dd>₹{{ number_format($t['subtotal'] + $t['bulk_saving']) }}</dd>
                @if($t['bulk_saving'] > 0)<dt>Bulk prices</dt><dd class="mt-down">− ₹{{ number_format($t['bulk_saving']) }}</dd>@endif
                @if($t['discount'] > 0)<dt>{{ config('materials.store.offer.percent') }}% order discount</dt><dd class="mt-down">− ₹{{ number_format($t['discount']) }}</dd>@endif
                <dt>Delivery</dt><dd>{{ $t['delivery_fee'] > 0 ? '₹' . number_format($t['delivery_fee']) : 'FREE' }}</dd>
                <dt class="tot">Total</dt><dd class="tot">₹{{ number_format($t['total']) }}</dd>
            </dl>
            @if($t['mrp_saving'] + $t['bulk_saving'] + $t['discount'] > 0)<p class="mt-off" style="margin:0 0 12px">You save ₹{{ number_format($t['mrp_saving'] + $t['bulk_saving'] + $t['discount']) }} on this order</p>@endif
            @if($t['unavailable'])
                <button class="mt-add" disabled>Remove out-of-stock items to continue</button>
            @else
                <a class="mt-add" href="{{ route('materials.checkout') }}" style="display:block;text-align:center;text-decoration:none">Proceed to checkout</a>
            @endif
            <p class="mt-muted" style="margin:10px 0 0">Prices include GST. @guest You will be asked to log in. @endguest</p>
        </aside>
    </div>
    @endif
</div>
@endsection
