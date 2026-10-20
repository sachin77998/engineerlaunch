@extends('layouts.site')
@section('title', 'Premium plans — Insights into Company')
@push('styles')@include('calculators.styles')
<style>
.pm-plans{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px}
.pm-plan{position:relative;display:flex;flex-direction:column;padding:24px;border:1px solid #e3e9f2;border-radius:16px;background:#fff;box-shadow:0 6px 22px rgba(11,37,69,.06)}
.pm-plan.pm-hot{border:2px solid #f5b800}
.pm-tag{position:absolute;top:-12px;right:18px;padding:4px 10px;border-radius:999px;background:#f5b800;color:#0b2545;font-weight:800;font-size:12px}
.pm-plan h2{margin:0 0 4px;color:#0b2545}.pm-price{font-size:34px;font-weight:800;color:#0b2545;margin:8px 0 2px}.pm-price small{font-size:14px;color:#5b6b82;font-weight:600}
.pm-fees{margin:14px 0;padding:12px;border-radius:10px;background:#f6f8fc}.pm-fees div{display:flex;justify-content:space-between;padding:4px 0;color:#425066;font-size:14px}.pm-fees div:last-child{border-top:1px solid #dfe6f0;margin-top:4px;padding-top:8px;font-weight:800;color:#0b2545}
.pm-btn{margin-top:auto;padding:12px 16px;border:0;border-radius:10px;background:#0b2545;color:#fff;font:800 15px inherit;cursor:pointer;width:100%}.pm-hot .pm-btn{background:#f5b800;color:#0b2545}
.pm-features{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:10px 24px;margin:0;padding:0;list-style:none}.pm-features li{padding-left:26px;position:relative;color:#26364f}.pm-features li:before{content:'✓';position:absolute;left:0;color:#087443;font-weight:800}
.pm-flash{padding:12px 14px;border-radius:10px;margin-bottom:16px}.pm-flash.ok{background:#e9f7ef;color:#0b5d33}.pm-flash.err{background:#fef3f2;color:#b42318}
.pm-status{display:inline-block;padding:2px 9px;border-radius:999px;font-size:12px;font-weight:800;background:#eef2f7;color:#425066}.pm-status.activated{background:#e9f7ef;color:#0b5d33}
</style>@endpush
@section('content')
<section class="cx-hero">
    <div class="cx-wrap">
        <h1>Premium</h1>
        <p>Full company financials, ratios, health score, borrowings, directors, peers and vendor checks in Insights into Company.</p>
    </div>
</section>
<main class="cx-wrap cx-main">
    @if(session('success'))<p class="pm-flash ok">{{ session('success') }}</p>@endif
    @if($errors->any())<p class="pm-flash err">{{ $errors->first() }}</p>@endif
    @if($user?->hasActivePremium())
        <p class="pm-flash ok">Your Premium is active{{ $user->premium_expires_at ? ' until ' . $user->premium_expires_at->format('d M Y') : '' }}. Buying another plan extends it.</p>
    @endif

    <div class="pm-plans">
        @foreach($plans as $key => $plan)
            <form class="pm-plan {{ $key === 'yearly' ? 'pm-hot' : '' }}" method="post" action="{{ route('premium.checkout') }}">
                @csrf
                <input type="hidden" name="plan" value="{{ $key }}">
                @if(!empty($plan['tag']))<span class="pm-tag">{{ $plan['tag'] }}</span>@endif
                <h2>{{ $plan['name'] }}</h2>
                <span class="cx-note" style="margin:0">{{ $plan['months'] }} {{ \Illuminate\Support\Str::plural('month', $plan['months']) }} of Premium</span>
                <div class="pm-price">₹{{ number_format($plan['price']) }} <small>+ GST</small></div>
                <span class="cx-note" style="margin:0">₹{{ number_format($plan['per_month'], 0) }} a month{{ $plan['saving'] ? ' · save ' . $plan['saving'] . '%' : '' }}</span>
                <div class="pm-fees">
                    <div><span>Plan price</span><span>₹{{ number_format($plan['price'], 2) }}</span></div>
                    <div><span>GST ({{ rtrim(rtrim(number_format($plan['gst_rate'], 2), '0'), '.') }}%)</span><span>₹{{ number_format($plan['gst'], 2) }}</span></div>
                    <div><span>Total payable</span><span>₹{{ number_format($plan['total'], 2) }}</span></div>
                </div>
                @auth
                    <button class="pm-btn" type="submit">Buy {{ strtolower($plan['name']) }} plan</button>
                @else
                    <a class="pm-btn" style="text-align:center;text-decoration:none" href="{{ route('login') }}">Sign in to buy</a>
                @endauth
            </form>
        @endforeach
    </div>

    <section class="cx-card" style="margin-top:22px">
        <div class="cx-head"><h2>What Premium includes</h2></div>
        <ul class="pm-features">@foreach($features as $feature)<li>{{ $feature }}</li>@endforeach</ul>
        <p class="cx-note">Prices include no hidden charges: the total shown is what you pay. {{ $gateway ? 'Pay by UPI, card, net banking or wallet (Razorpay).' : 'Online payment is being set up: your order is confirmed and Premium is switched on as soon as the payment is received.' }}</p>
    </section>

    @if($orders->isNotEmpty())
        <section class="cx-card">
            <div class="cx-head"><h2>Your orders</h2></div>
            <div class="cx-scroll"><table class="cx-table"><thead><tr><th>Order</th><th>Plan</th><th>Amount</th><th>Status</th><th>Date</th><th>Premium until</th></tr></thead><tbody>
                @foreach($orders as $order)
                    <tr><td><a href="{{ route('premium.pay', $order->order_no) }}">{{ $order->order_no }}</a></td><td>{{ ucfirst($order->plan) }}</td><td>₹{{ number_format($order->total_amount, 2) }}</td>
                        <td><span class="pm-status {{ $order->status }}">{{ $order->status === 'created' ? 'Awaiting payment' : ucfirst($order->status) }}</span></td><td>{{ $order->created_at->format('d M Y') }}</td><td>{{ $order->premium_until?->format('d M Y') ?? '—' }}</td></tr>
                @endforeach
            </tbody></table></div>
        </section>
    @endif
</main>
@endsection
