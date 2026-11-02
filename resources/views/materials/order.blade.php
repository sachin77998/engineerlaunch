@extends('layouts.site')
@section('title', 'Order ' . $order->order_no . ' — Home Building store')
@section('content')
@include('materials._nav')
@push('styles')<style>
.mt-od{display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start}
.mt-track{display:flex;gap:0;margin:8px 0 4px;overflow-x:auto}
.mt-track div{flex:1;min-width:110px;text-align:center;position:relative;font-size:13px;color:#a8a29e}
.mt-track div:before{content:'';position:absolute;top:15px;left:-50%;right:50%;height:3px;background:#e7e5e4;z-index:0}
.mt-track div:first-child:before{display:none}
.mt-track b{position:relative;z-index:1;display:grid;place-items:center;width:32px;height:32px;margin:0 auto 6px;border-radius:50%;background:#e7e5e4;color:#fff;font-size:14px}
.mt-track div.done{color:#166534}.mt-track div.done b{background:#16a34a}.mt-track div.done:before{background:#16a34a}
.mt-oi{display:grid;grid-template-columns:64px 1fr auto;gap:14px;align-items:center;padding:12px 0;border-bottom:1px solid #f1ece6}
.mt-oi img{width:64px;height:64px;object-fit:contain;border:1px solid var(--mt-line);border-radius:10px;background:#fff}
@media(max-width:900px){.mt-od{grid-template-columns:1fr}}
</style>@endpush
@php
    $steps = ['confirmed' => 'Confirmed', 'packed' => 'Packed', 'shipped' => 'Out for delivery', 'delivered' => 'Delivered'];
    $reached = array_search($order->status, array_keys($steps), true);
@endphp
<div class="container mt-wrap">
    <nav class="mt-crumbs" style="margin:4px 0 0"><a href="{{ route('materials.orders') }}">My orders</a> › <b>{{ $order->order_no }}</b></nav>
    @if($errors->any())<div class="mt-card" style="border-color:#fecaca;background:#fef2f2;color:#991b1b">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <div class="mt-od">
        <div style="display:grid;gap:20px">
            <section class="mt-card">
                <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:baseline">
                    <h1 style="margin:0">Order {{ $order->order_no }}</h1>
                    <span class="mt-tag {{ $order->status === 'cancelled' ? 'out' : 'ok' }}" style="font-size:13px">{{ $order->statusLabel() }}</span>
                </div>
                <p class="mt-muted">Placed {{ $order->created_at->format('d M Y, H:i') }}@if($order->slot) · Slot: {{ $order->slot }}@endif @if($order->tracking) · {{ $order->tracking }}@endif</p>
                @if($order->status === 'cancelled')
                    <p style="color:#b91c1c">Cancelled {{ $order->cancelled_at?->format('d M Y, H:i') }}.@if($order->payment_status === 'refunded') Your payment is being refunded to the original account.@endif</p>
                @elseif($order->status === 'created')
                    <p style="color:#b45309"><b>Awaiting payment.</b> Complete the payment to confirm this order.</p>
                @else
                    <div class="mt-track">@foreach($steps as $k => $label)<div class="{{ $reached !== false && $loop->index <= $reached ? 'done' : '' }}"><b>{{ $reached !== false && $loop->index <= $reached ? '✓' : $loop->iteration }}</b>{{ $label }}</div>@endforeach</div>
                @endif
            </section>
            <section class="mt-card">
                <h2 style="font-size:18px">Items</h2>
                @foreach($order->items as $i)
                    <div class="mt-oi">
                        @if($i->image_url)<img src="{{ $i->image_url }}" alt="">@else<span></span>@endif
                        <div>@if($i->material)<a href="{{ route('materials.show', $i->material) }}" style="color:var(--mt-ink);font-weight:600;text-decoration:none">{{ $i->title }}</a>@else<b>{{ $i->title }}</b>@endif
                            <div class="mt-muted">{{ $i->variant_title ? $i->variant_title . ' · ' : '' }}{{ $i->qty }} × ₹{{ number_format($i->unit_price, 2) }}</div>
                            @if($order->status === 'delivered' && $i->material)<a href="{{ route('materials.show', $i->material) }}#reviews" style="font-size:13px;color:#2563eb">★ Rate this product</a>@endif</div>
                        <b>₹{{ number_format($i->total) }}</b>
                    </div>
                @endforeach
            </section>
            <section class="mt-card">
                <h2 style="font-size:18px">Need help with this order?</h2>
                <form method="post" action="{{ route('materials.feedback') }}" style="display:grid;gap:10px">@csrf
                    <input type="hidden" name="order" value="{{ $order->order_no }}">
                    <select class="mt-in" name="kind">@foreach(\App\Models\MaterialFeedback::KINDS as $k => $label)<option value="{{ $k }}" @selected($k === 'issue')>{{ $label }}</option>@endforeach</select>
                    <textarea class="mt-in" name="message" rows="3" minlength="5" maxlength="3000" required placeholder="Tell us what happened — missing item, damage, delivery time…"></textarea>
                    <button class="mt-btn" style="justify-self:start">Send</button>
                </form>
            </section>
        </div>
        <aside style="display:grid;gap:20px">
            <section class="mt-card">
                <h2 style="font-size:18px">Payment</h2>
                <dl style="display:grid;grid-template-columns:1fr auto;gap:8px;margin:0">
                    <dt>Subtotal</dt><dd style="margin:0;text-align:right">₹{{ number_format($order->subtotal) }}</dd>
                    @if($order->bulk_saving > 0)<dt>Bulk saving</dt><dd class="mt-down" style="margin:0;text-align:right">₹{{ number_format($order->bulk_saving) }}</dd>@endif
                    @if($order->discount > 0)<dt>Discount</dt><dd class="mt-down" style="margin:0;text-align:right">− ₹{{ number_format($order->discount) }}</dd>@endif
                    <dt>Delivery</dt><dd style="margin:0;text-align:right">{{ $order->delivery_fee > 0 ? '₹' . number_format($order->delivery_fee) : 'FREE' }}</dd>
                    <dt style="font-weight:800">Total</dt><dd style="margin:0;text-align:right;font-weight:800">₹{{ number_format($order->total) }}</dd>
                </dl>
                <p class="mt-muted">{{ $order->payment_method === 'cod' ? 'Pay on delivery' : 'Online (Paytm)' }} · {{ ucfirst($order->payment_status) }}@if($order->paid_at) on {{ $order->paid_at->format('d M Y') }}@endif</p>
                @if($order->status === 'created' && $order->payment_status !== 'paid')
                    @if($paytm)<form method="post" action="{{ route('materials.orders.pay', $order) }}">@csrf<button class="mt-add">Pay ₹{{ number_format($order->total) }} now</button></form>@endif
                    <form method="post" action="{{ route('materials.orders.cod', $order) }}" style="margin-top:8px">@csrf<button class="mt-btn ghost" style="width:100%">Pay on delivery instead</button></form>
                @endif
            </section>
            <section class="mt-card">
                <h2 style="font-size:18px">Delivery address</h2>
                <p style="margin:0;line-height:1.6"><b>{{ $order->name }}</b><br>{{ $order->address_line1 }}@if($order->address_line2)<br>{{ $order->address_line2 }}@endif<br>{{ $order->city }}, {{ $order->state }} {{ $order->pincode }}<br>📞 {{ $order->phone }}</p>
                @if($order->notes)<p class="mt-muted">Note: {{ $order->notes }}</p>@endif
            </section>
            <section class="mt-card" style="display:grid;gap:8px">
                <form method="post" action="{{ route('materials.orders.reorder', $order) }}">@csrf<button class="mt-btn green" style="width:100%">Buy these again</button></form>
                @if($order->cancellable() && $order->user_id === auth()->id())
                    <form method="post" action="{{ route('materials.orders.cancel', $order) }}" onsubmit="return confirm('Cancel order {{ $order->order_no }}?')">@csrf<button class="mt-btn ghost" style="width:100%;color:#b91c1c">Cancel order</button></form>
                @endif
            </section>
        </aside>
    </div>
</div>
@endsection
