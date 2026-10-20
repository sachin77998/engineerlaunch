@extends('layouts.site')
@section('title', 'Premium orders')
@push('styles')@include('calculators.styles')
<style>.pm-flash{padding:12px 14px;border-radius:10px;margin-bottom:16px;background:#e9f7ef;color:#0b5d33}.pm-act{padding:6px 12px;border:0;border-radius:8px;background:#0b2545;color:#fff;font-weight:700;cursor:pointer}</style>@endpush
@section('content')
<section class="cx-hero"><div class="cx-wrap"><h1>Premium orders</h1><p>Orders paid outside the gateway (UPI, bank transfer) are activated here.</p></div></section>
<main class="cx-wrap cx-main">
    @if(session('success'))<p class="pm-flash">{{ session('success') }}</p>@endif
    <div class="cx-ranges" style="margin-bottom:12px">
        @foreach(['' => 'All', 'created' => 'Awaiting payment', 'paid' => 'Paid', 'activated' => 'Activated', 'failed' => 'Failed'] as $s => $label)
            <a class="{{ request('status', '') === $s ? 'on' : '' }}" style="padding:6px 12px;border:1px solid #dbe3ef;border-radius:8px;text-decoration:none;color:inherit;{{ request('status', '') === $s ? 'background:#0b2545;color:#fff' : '' }}" href="{{ route('admin.premium.orders', array_filter(['status' => $s])) }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="cx-scroll" style="max-height:none"><table class="cx-table"><thead><tr><th>Order</th><th>User</th><th>Plan</th><th>Total</th><th>Status</th><th>Payment</th><th>Created</th><th>Premium until</th><th></th></tr></thead><tbody>
        @forelse($orders as $order)
            <tr><td>{{ $order->order_no }}</td><td style="text-align:left">{{ $order->user?->name }}<br><small>{{ $order->user?->email }}</small></td><td>{{ ucfirst($order->plan) }}</td><td>₹{{ number_format($order->total_amount, 2) }}</td>
                <td>{{ $order->status }}</td><td>{{ $order->gateway_payment_id ?? '—' }}</td><td>{{ $order->created_at->format('d M Y H:i') }}</td><td>{{ $order->premium_until?->format('d M Y') ?? '—' }}</td>
                <td>@if(!$order->activated_at)<form method="post" action="{{ route('admin.premium.activate', $order) }}" onsubmit="return confirm('Activate premium for this order? Do this only after the payment is received.')">@csrf<button class="pm-act">Activate</button></form>@endif</td></tr>
        @empty
            <tr><td colspan="9" style="text-align:center">No orders.</td></tr>
        @endforelse
    </tbody></table></div>
    <div style="margin-top:12px">{{ $orders->links() }}</div>
</main>
@endsection
