@extends('layouts.site')
@section('title', $t->purposeLabel() . ' ' . $t->reference)
@section('content')
@include('pay-rent._layout', ['title' => $t->purposeLabel(), 'back' => route('pay-rent.mine')])
@php($editable = in_array($t->status, ['draft', 'not_processed', 'payment_failed'], true))
<div class="pr"><div class="pr-wrap pz-body">
    <div class="pz-card">
        @foreach(['success' => 'ok', 'warning' => 'warn'] as $k => $cls)@if(session($k))<div class="pr-flash {{ $cls }}" style="margin-top:0">{{ session($k) }}</div>@endif @endforeach
        @if($errors->any())<div class="pr-flash err" style="margin-top:0">{{ $errors->first() }}</div>@endif
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap"><h2 style="margin:0">Pay {{ $t->payee_name }}</h2><span class="pr-tag {{ $t->status === 'completed' ? 'ok' : (str_contains($t->status, 'failed') ? '' : 'amber') }}">{{ $t->statusLabel() }}</span></div>
        <p class="pr-mute" style="margin:4px 0 18px">{{ $t->reference }} · +91 {{ $t->payee_phone }} · {{ $t->payeeLabel() }}</p>
        @if($t->details)<p style="margin:0 0 14px">@foreach($t->details as $k => $v){{ config('pay_rent.purposes')[$t->purpose][3][$k] ?? $k }}: <b>{{ $v }}</b>@if(!$loop->last) · @endif @endforeach</p>@endif
        <div style="display:grid;gap:8px;max-width:420px">
            <div style="display:flex;justify-content:space-between"><span>Amount to {{ $t->payee_name }}</span><b>₹{{ number_format($t->amount) }}</b></div>
            <div style="display:flex;justify-content:space-between"><span>Convenience fee (incl. GST)</span><b>₹{{ number_format($t->fee) }}</b></div>
            <div style="display:flex;justify-content:space-between;border-top:1px solid var(--pr-line);padding-top:8px;font-size:18px"><span>Charged to your credit card</span><b>₹{{ number_format($t->total) }}</b></div>
        </div>
        @if($t->failure_reason)<p class="pr-flash err">{{ $t->failure_reason }}@if($t->status === 'payout_failed') — our team will refund your card.@endif</p>@endif
        @if($editable)
            @unless($live)<div class="pz-off" style="margin-top:18px">Card payments are not switched on yet, so nothing will be charged{{ $t->status === 'not_processed' ? '. This request is saved.' : '.' }}</div>@endunless
            <div class="pz-actions" style="gap:10px">
                <form method="post" action="{{ route('pay-rent.destroy', $t) }}" onsubmit="return confirm('Remove this payment?')">@csrf<button class="pr-btn grey" style="min-width:0;background:#6b7280">Remove</button></form>
                <form method="post" action="{{ route('pay-rent.pay', $t) }}">@csrf<button class="pr-btn">{{ $live ? 'Pay ₹' . number_format($t->total) . ' by credit card' : 'Save request' }}</button></form>
            </div>
        @endif
    </div>
</div></div>
@endsection
