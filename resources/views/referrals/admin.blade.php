@extends('layouts.site')
@section('title', 'Refer & Earn — admin')
@section('content')
@include('properties._nav')
@php($o = config('referrals'))
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <h1 style="margin:22px 0 6px">Refer &amp; Earn</h1>
    <p class="pr-mute" style="margin:0 0 12px">Rewards: verified contact ₹{{ $o['rewards']['verified_contact'] }} · verified photo ₹{{ $o['rewards']['verified_photo'] }} · owner listed ₹{{ $o['rewards']['listed'] }} (set REFER_REWARD_* in .env)</p>

    @if($withdrawals->isNotEmpty())
        <h2 style="font-size:18px">Withdrawal requests to pay</h2>
        @foreach($withdrawals as $w)
            <form method="post" action="{{ route('admin.referrals.settle', $w) }}" class="pr-card-box" style="margin-bottom:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">@csrf
                <span style="flex:1;min-width:240px"><b>₹{{ number_format($w->amount) }}</b> → <b>{{ $w->upi_id }}</b> · {{ $w->user?->name }} ({{ $w->user?->email }}) · {{ $w->created_at->format('d M Y') }}</span>
                <input type="text" name="paid_ref" maxlength="80" placeholder="UPI ref no." style="max-width:160px"><input type="text" name="admin_note" maxlength="255" placeholder="Note" style="max-width:180px">
                <button class="pr-btn sm" name="status" value="paid">Mark paid</button><button class="pr-btn grey sm" name="status" value="rejected">Reject &amp; return</button>
            </form>
        @endforeach
    @endif

    <div style="display:flex;gap:8px;flex-wrap:wrap;margin:16px 0">@foreach($o['statuses'] as $k => $l)<a class="pr-btn sm {{ $status === $k ? '' : 'ghost' }}" href="{{ route('admin.referrals.index', ['status' => $k]) }}">{{ $l }} ({{ $counts[$k] ?? 0 }})</a>@endforeach</div>
    @forelse($items as $r)
        <div class="pr-card-box" style="margin-bottom:10px;display:grid;grid-template-columns:auto minmax(0,1fr) 300px;gap:14px;align-items:start">
            <div>@if($r->photoUrl())<a href="{{ $r->photoUrl() }}" target="_blank"><img src="{{ $r->photoUrl() }}" alt="Board photo" style="width:150px;height:110px;object-fit:cover;border-radius:8px"></a>@else<span style="font-size:34px">📇</span>@endif</div>
            <div>
                <b>#{{ $r->id }} · {{ $r->kind === 'photo' ? 'Board photo' : $r->owner_name }}</b> <span class="pr-tag">{{ $r->statusLabel() }}</span>
                @if($r->owner_phone)<div><a href="tel:+91{{ $r->owner_phone }}">+91 {{ $r->owner_phone }}</a>@if(in_array($r->owner_phone, $listedPhones, true)) <span class="pr-tag ok">has a live listing</span>@endif</div>@endif
                <div class="pr-mute" style="font-size:13.5px">{{ $r->city }}@if($r->property_type) · {{ $o['property_types'][$r->property_type] ?? '' }}@endif @if($r->listing) · {{ $o['listings'][$r->listing] }}@endif @if($r->latitude) · <a href="https://www.openstreetmap.org/?mlat={{ $r->latitude }}&mlon={{ $r->longitude }}#map=18/{{ $r->latitude }}/{{ $r->longitude }}" target="_blank" rel="noopener">map</a>@endif</div>
                @if($r->notes)<div style="font-size:14px">{{ $r->notes }}</div>@endif
                <div class="pr-mute" style="font-size:12.5px">By {{ $r->user?->name }} ({{ $r->user?->email }}) · {{ $r->created_at->format('d M Y H:i') }}@if($r->reward_total) · rewarded ₹{{ $r->reward_total }}@endif</div>
            </div>
            <form method="post" action="{{ route('admin.referrals.review', $r) }}" style="display:grid;gap:6px">@csrf
                @if($r->kind === 'photo')<input type="tel" name="owner_phone" pattern="[6-9][0-9]{9}" maxlength="10" value="{{ $r->owner_phone }}" placeholder="Number on the board">@endif
                <select name="reject_reason"><option value="">Reason (if rejecting)</option>@foreach($o['reject_reasons'] as $k => $l)<option value="{{ $k }}" @selected($r->reject_reason === $k)>{{ $l }}</option>@endforeach</select>
                <div style="display:flex;gap:4px;flex-wrap:wrap">
                    <button class="pr-btn sm" name="status" value="verified">Verified</button><button class="pr-btn sm" name="status" value="listed" style="background:#047857">Owner listed</button>
                    <button class="pr-btn grey sm" name="status" value="rejected">Reject</button><button class="pr-btn grey sm" name="status" value="duplicate">Duplicate</button>
                </div>
            </form>
        </div>
    @empty
        <div class="pr-card-box">Nothing here.</div>
    @endforelse
    {{ $items->links() }}
</div></div>
@endsection
