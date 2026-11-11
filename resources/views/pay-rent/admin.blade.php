@extends('layouts.site')
@section('title', 'Pay rent — admin')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    <h1 style="margin:22px 0 6px">Card payments to landlords &amp; tutors</h1>
    <div class="pr-flash {{ $live ? 'ok' : 'warn' }}">{{ $live ? 'Live: cards are charged through Paytm and paid out through Cashfree.' : 'Off: nothing is charged. Needs PAY_RENT_LIVE=true, Paytm keys and Cashfree Payouts keys (CASHFREE_PAYOUTS_CLIENT_ID / _SECRET / _ENV).' }}</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
        <a class="pr-btn sm {{ $status ? 'ghost' : '' }}" href="{{ route('admin.pay-rent.index') }}">All ({{ $counts->sum() }})</a>
        @foreach(config('pay_rent.statuses') as $k => $label)@if($counts[$k] ?? 0)<a class="pr-btn sm {{ $status === $k ? '' : 'ghost' }}" href="{{ route('admin.pay-rent.index', ['status' => $k]) }}">{{ $label }} ({{ $counts[$k] }})</a>@endif @endforeach
    </div>
    @forelse($items as $t)
        <div class="pr-card-box" style="margin-bottom:10px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div><b>{{ $t->reference }} · {{ $t->purposeLabel() }}</b> <span class="pr-tag">{{ $t->statusLabel() }}</span>
                <div style="font-size:14px">{{ $t->user?->name }} ({{ $t->user?->email }}) → {{ $t->payee_name }}, +91 {{ $t->payee_phone }}, {{ $t->payeeLabel() }}</div>
                <div class="pr-mute" style="font-size:13px">₹{{ number_format($t->amount) }} + fee ₹{{ number_format($t->fee) }} = ₹{{ number_format($t->total) }} · {{ $t->created_at->format('d M Y H:i') }}@if($t->payout_ref) · Cashfree {{ $t->payout_ref }}@endif @if($t->failure_reason) · <span style="color:#b91c1c">{{ $t->failure_reason }}</span>@endif</div></div>
            @if($t->status === 'payout_failed')
                <div style="display:flex;gap:6px"><form method="post" action="{{ route('admin.pay-rent.retry', $t->id) }}">@csrf<button class="pr-btn sm">Retry payout</button></form>
                    <form method="post" action="{{ route('admin.pay-rent.refunded', $t->id) }}" onsubmit="return confirm('Mark as refunded to the customer\'s card?')">@csrf<button class="pr-btn grey sm">Mark refunded</button></form></div>
            @endif
        </div>
    @empty
        <div class="pr-card-box">No payments.</div>
    @endforelse
    {{ $items->links() }}
</div></div>
@endsection
