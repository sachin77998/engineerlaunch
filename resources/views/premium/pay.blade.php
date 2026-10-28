@extends('layouts.site')
@section('title', 'Premium order ' . $order->order_no)
@push('styles')@include('calculators.styles')
<style>.pm-flash{padding:12px 14px;border-radius:10px;margin-bottom:16px}.pm-flash.ok{background:#e9f7ef;color:#0b5d33}.pm-flash.err{background:#fef3f2;color:#b42318}.pm-flash.warn{background:#fff7e6;color:#8a5a00}
.pm-btn{display:inline-block;padding:12px 22px;border:0;border-radius:10px;background:#f5b800;color:#0b2545;font:800 15px inherit;cursor:pointer;text-decoration:none}</style>@endpush
@section('content')
<section class="cx-hero"><div class="cx-wrap"><div class="cx-crumb"><a href="{{ route('premium.index') }}">Premium</a> › Order {{ $order->order_no }}</div><h1>{{ ucfirst($order->plan) }} plan</h1></div></section>
<main class="cx-wrap cx-main" style="max-width:720px">
    @if($errors->any())<p class="pm-flash err">{{ $errors->first() }}</p>@endif
    <section class="cx-card">
        <div class="cx-results" style="margin-top:0">
            <div><span>Order number</span><b>{{ $order->order_no }}</b></div>
            <div><span>Plan</span><b>{{ ucfirst($order->plan) }} · {{ $order->months }} {{ \Illuminate\Support\Str::plural('month', $order->months) }}</b></div>
            <div><span>Plan price</span><b>₹{{ number_format($order->base_amount, 2) }}</b></div>
            <div><span>GST ({{ rtrim(rtrim(number_format($order->gst_rate, 2), '0'), '.') }}%)</span><b>₹{{ number_format($order->gst_amount, 2) }}</b></div>
            <div><span>Total payable</span><b>₹{{ number_format($order->total_amount, 2) }}</b></div>
            <div><span>Status</span><b>{{ $order->status === 'created' ? 'Awaiting payment' : ucfirst($order->status) }}</b></div>
            @if($order->premium_until)<div><span>Premium until</span><b>{{ $order->premium_until->format('d M Y') }}</b></div>@endif
        </div>
        @if($order->status === 'created')
            @if($paytm ?? false)
                <form method="post" action="{{ route('payments.paytm.initiate', $order->order_no) }}" style="margin-top:18px">@csrf<button class="pm-btn" style="background:#00b9f5;color:#fff">Pay ₹{{ number_format($order->total_amount, 2) }} with Paytm</button></form>
            @endif
            @if($gateway)
                <p style="margin-top:18px"><button class="pm-btn" id="pm-pay" type="button">Pay ₹{{ number_format($order->total_amount, 2) }}</button></p>
                <form id="pm-verify" method="post" action="{{ route('premium.verify', $order->order_no) }}" hidden>@csrf
                    <input name="razorpay_payment_id"><input name="razorpay_order_id"><input name="razorpay_signature"></form>
            @elseif(!($paytm ?? false))
                <p class="pm-flash warn" style="margin-top:18px">Your order is saved. Online payment is being set up; Premium is switched on as soon as your payment is received. Quote order {{ $order->order_no }} when you pay or <a href="{{ route('contact') }}?subject={{ rawurlencode('Premium order ' . $order->order_no) }}">contact us</a>.</p>
            @endif
        @elseif($order->status === 'activated')
            <p class="pm-flash ok" style="margin-top:18px">Premium is active until {{ $order->premium_until->format('d M Y') }}.</p>
        @endif
    </section>
</main>
@endsection
@if($order->status === 'created' && $gateway)
@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
document.getElementById('pm-pay').addEventListener('click', () => {
    const form = document.getElementById('pm-verify');
    new Razorpay({
        key: @json($keyId), order_id: @json($order->gateway_order_id), amount: {{ (int) round($order->total_amount * 100) }}, currency: 'INR',
        name: @json(config('platform.name', config('app.name'))), description: @json(ucfirst($order->plan) . ' Premium plan'),
        prefill: {name: @json(auth()->user()->name), email: @json(auth()->user()->email)},
        handler: r => { form.razorpay_payment_id.value = r.razorpay_payment_id; form.razorpay_order_id.value = r.razorpay_order_id; form.razorpay_signature.value = r.razorpay_signature; form.submit(); },
        theme: {color: '#0b2545'},
    }).open();
});
</script>
@endpush
@endif
