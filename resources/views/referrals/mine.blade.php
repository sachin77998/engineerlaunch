@extends('layouts.site')
@section('title', 'My referrals — ' . config('properties.brand'))
@section('content')
@include('properties._nav')
@php($o = config('referrals'))
<div class="pr"><div class="pr-wrap" style="max-width:1000px">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin:22px 0 14px;flex-wrap:wrap"><h1 style="margin:0">My referrals</h1><a class="pr-btn" href="{{ route('referrals.index') }}">Refer another owner</a></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px">
        <div class="pr-card-box"><div class="pr-mute">Balance</div><div style="font-size:30px;font-weight:800">₹{{ number_format($balance) }}</div><div class="pr-mute" style="font-size:13px">Earned so far ₹{{ number_format($earned) }}</div></div>
        <form method="post" action="{{ route('referrals.withdraw') }}" class="pr-card-box" style="display:grid;gap:8px">
            @csrf<b>Withdraw to UPI</b>
            <input type="number" name="amount" min="{{ $o['min_withdrawal'] }}" max="{{ max($balance, $o['min_withdrawal']) }}" required value="{{ old('amount', $balance >= $o['min_withdrawal'] ? $balance : '') }}" placeholder="Amount (min ₹{{ $o['min_withdrawal'] }})" aria-label="Amount">
            <input type="text" name="upi_id" required maxlength="100" value="{{ old('upi_id') }}" placeholder="yourname@bank" aria-label="UPI ID">
            <button class="pr-btn sm" @disabled($balance < $o['min_withdrawal'])>Request withdrawal</button>
            @if($balance < $o['min_withdrawal'])<small class="pr-mute">You can withdraw once your balance reaches ₹{{ $o['min_withdrawal'] }}.</small>@endif
        </form>
    </div>
    @if($withdrawals->isNotEmpty())
        <h2 style="margin:22px 0 8px;font-size:18px">Withdrawals</h2>
        @foreach($withdrawals as $w)<div class="pr-card-box" style="margin-bottom:6px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap"><span>₹{{ number_format($w->amount) }} → {{ $w->upi_id }} · {{ $w->created_at->format('d M Y') }}</span><span><span class="pr-tag {{ $w->status === 'paid' ? 'ok' : ($w->status === 'requested' ? 'amber' : '') }}">{{ $w->statusLabel() }}</span>@if($w->paid_ref) <small class="pr-mute">Ref {{ $w->paid_ref }}</small>@endif @if($w->admin_note) <small class="pr-mute">{{ $w->admin_note }}</small>@endif</span></div>@endforeach
    @endif
    <h2 style="margin:22px 0 8px;font-size:18px">Referrals</h2>
    @forelse($items as $r)
        <div class="pr-card-box" style="margin-bottom:8px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center">
            <div style="display:flex;gap:12px;align-items:center">
                @if($r->photoUrl())<a href="{{ $r->photoUrl() }}" target="_blank"><img src="{{ $r->photoUrl() }}" alt="Board photo" style="width:64px;height:48px;object-fit:cover;border-radius:6px"></a>@endif
                <div><b>{{ $r->kind === 'photo' ? 'Board photo' : ($r->owner_name . ' · +91 ' . substr($r->owner_phone, 0, 2) . '••••' . substr($r->owner_phone, -4)) }}</b>
                    <div class="pr-mute" style="font-size:13px">{{ $r->city }}@if($r->property_type) · {{ $o['property_types'][$r->property_type] ?? '' }}@endif @if($r->listing) · {{ $o['listings'][$r->listing] }}@endif · {{ $r->created_at->format('d M Y') }}</div>
                    @if($r->rejectLabel())<div style="color:#b91c1c;font-size:13px">{{ $r->rejectLabel() }}</div>@endif</div>
            </div>
            <div style="text-align:right"><span class="pr-tag {{ in_array($r->status, ['verified', 'listed'], true) ? 'ok' : ($r->status === 'pending' ? 'amber' : '') }}">{{ $r->statusLabel() }}</span>@if($r->reward_total)<div style="font-weight:700;margin-top:4px">+₹{{ number_format($r->reward_total) }}</div>@endif</div>
        </div>
    @empty
        <div class="pr-card-box">No referrals yet. <a href="{{ route('referrals.index') }}">Share your first owner contact →</a></div>
    @endforelse
    {{ $items->links() }}
</div></div>
@endsection
