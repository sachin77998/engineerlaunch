@extends('layouts.site')
@php
    $titles = ['contact' => 'Pay a saved contact', 'upi' => 'Payment using UPI', 'bank' => 'Payment to bank account'];
    $who = $p[2];
@endphp
@section('title', $titles[$method] . ' — ' . $p[0])
@section('content')
@include('pay-rent._layout', ['title' => $titles[$method], 'back' => route('pay-rent.purpose', $purpose)])
<div class="pr"><div class="pr-wrap pz-body">
    <div class="pz-card">
        @if($method === 'contact')
            <h2>Saved payees</h2>
            @forelse($contacts as $c)
                <a href="{{ route('pay-rent.form', [$purpose, $c->method, 'from' => $c->reference]) }}" style="display:flex;justify-content:space-between;gap:10px;padding:12px 0;border-top:1px solid var(--pr-line);color:inherit;text-decoration:none">
                    <span><b>{{ $c->payee_name }}</b> <span class="pr-mute">+91 {{ $c->payee_phone }}</span><br><small class="pr-mute">{{ $c->payeeLabel() }}</small></span><span style="color:#1d4ed8;font-weight:700">Pay ›</span></a>
            @empty
                <p class="pr-mute">No saved payees yet — people you pay are saved here.</p>
            @endforelse
            <p style="margin-top:16px"><a class="pr-btn ghost sm" href="{{ route('pay-rent.form', [$purpose, 'upi']) }}">New payee by UPI</a> <a class="pr-btn ghost sm" href="{{ route('pay-rent.form', [$purpose, 'bank']) }}">New payee by bank account</a></p>
        @else
            @if($errors->any())<div class="pr-flash err" style="margin-top:0">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ route('pay-rent.store', $purpose) }}" class="pz-form" autocomplete="off">
                @csrf
                <input type="hidden" name="method" value="{{ $method }}">
                @if($method === 'upi')
                    <div><label class="f" for="pz-vpa">Enter {{ strtolower($who) }}'s UPI ID</label><input id="pz-vpa" type="text" name="payee_vpa" required maxlength="100" value="{{ old('payee_vpa', $from?->payee_vpa) }}" placeholder="Enter UPI ID">
                        <div class="pz-note" style="margin-top:8px"><b>Note:</b> make sure the UPI ID is correct before continuing.</div></div>
                @else
                    <div><label class="f" for="pz-acc">Account number</label><input id="pz-acc" type="text" name="account" required inputmode="numeric" maxlength="18" value="{{ old('account', $from?->payee_account) }}" placeholder="Enter account number"></div>
                    <div><label class="f" for="pz-acc2">Re-enter account number</label><input id="pz-acc2" type="text" name="account_confirmation" required inputmode="numeric" maxlength="18" value="{{ old('account', $from?->payee_account) }}" placeholder="Re-enter account number" onpaste="return false"></div>
                    <div><label class="f" for="pz-ifsc">IFSC code</label><input id="pz-ifsc" type="text" name="payee_ifsc" required maxlength="11" value="{{ old('payee_ifsc', $from?->payee_ifsc) }}" placeholder="Enter IFSC code" style="text-transform:uppercase"></div>
                @endif
                <div><label class="f" for="pz-name">{{ $who }} name (as in bank)</label><input id="pz-name" type="text" name="payee_name" required maxlength="120" value="{{ old('payee_name', $from?->payee_name) }}"></div>
                <div><label class="f" for="pz-phone">{{ $who }} mobile number</label><input id="pz-phone" type="tel" name="payee_phone" required pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" value="{{ old('payee_phone', $from?->payee_phone) }}" placeholder="Enter {{ strtolower($who) }}'s mobile no."></div>
                <div><label class="f" for="pz-amt">Amount (₹)</label><input id="pz-amt" type="number" name="amount" required min="{{ config('pay_rent.min') }}" max="{{ config('pay_rent.max') }}" value="{{ old('amount', $from?->amount) }}"></div>
                @foreach($p[3] as $k => $label)<div><label class="f" for="pz-{{ $k }}">{{ $label }}</label><input id="pz-{{ $k }}" type="text" name="details[{{ $k }}]" maxlength="200" value="{{ old('details.' . $k, $from?->details[$k] ?? '') }}" placeholder="Optional"></div>@endforeach
                <label style="display:flex;gap:8px;align-items:flex-start"><input type="checkbox" name="confirm" value="1" required style="margin-top:4px"> I confirm this is a genuine {{ strtolower($p[0]) }} and the payee details are correct. Money sent to a wrong account may not be recoverable.</label>
                <div class="pz-actions"><button class="pr-btn">{{ $method === 'upi' ? 'Make payment' : 'Continue' }}</button></div>
            </form>
        @endif
    </div>
</div></div>
@endsection
