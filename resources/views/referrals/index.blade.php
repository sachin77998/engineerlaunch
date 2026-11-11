@extends('layouts.site')
@section('title', 'Refer & Earn — share owner contacts, get rewarded — ' . config('properties.brand'))
@section('content')
@include('properties._nav')
@php
    $o = config('referrals');
    $rw = $o['rewards'];
    $top = max($rw['verified_contact'] + $rw['listed'], $rw['verified_photo'] + $rw['listed']);
    $types = $o['property_types'];
@endphp
@push('styles')<style>
.re-hero{background:#f3f4f6;padding:44px 0}.re-hero .pr-wrap{display:grid;grid-template-columns:1.2fr 1fr;gap:30px;align-items:center}
.re-hero h1{font-size:clamp(36px,6vw,64px);color:#0f766e;margin:0;line-height:1.05}.re-hero h1+p{font-size:clamp(20px,2.4vw,30px);color:#0f766e;margin:8px 0 22px}
.re-btn{display:inline-block;background:#0f766e;color:#fff;border:0;border-radius:4px;padding:14px 24px;font-size:17px;font-weight:600;cursor:pointer;text-decoration:none}.re-btn:hover{background:#115e59;color:#fff}
.re-art{font-size:120px;text-align:center;line-height:1}
.re-sec{padding:40px 0;border-bottom:1px solid var(--pr-line)}.re-sec h2{text-align:center;color:#0f766e;font-weight:400;font-size:clamp(26px,3vw,36px);margin:0 0 24px}
.re-split{display:grid;grid-template-columns:1fr 1fr;gap:30px;align-items:center}.re-split p{font-size:clamp(18px,2vw,24px);color:#4b5563;font-weight:300;margin:0 0 20px}
.re-or{width:80px;height:80px;border-radius:50%;background:#99f6e4;color:#fff;display:grid;place-items:center;font-size:24px;margin:-40px auto 0;position:relative}
.re-cities b{color:#111827}
dialog.re-dlg{border:0;border-radius:4px;padding:0;width:min(600px,calc(100% - 24px));box-shadow:0 20px 50px rgba(0,0,0,.3)}
dialog.re-dlg::backdrop{background:rgba(0,0,0,.45)}
.re-dlg header{display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--pr-line)}.re-dlg header h2{margin:0;font-size:21px}.re-dlg header button{border:0;background:none;font-size:26px;cursor:pointer}
.re-dlg form{padding:22px 32px 28px;display:grid;gap:14px}
.re-dial{display:flex;border:1px solid #d1d5db;border-radius:10px;overflow:hidden}.re-dial span{display:grid;place-items:center;padding:0 12px;background:#f9fafb;font-weight:600}.re-dial input{border:0!important;border-radius:0!important}
.re-red{background:#f43f5e;color:#fff;border:0;border-radius:4px;padding:14px;font-size:17px;font-weight:700;cursor:pointer}.re-red:hover{background:#e11d48}
.re-faq details{border-bottom:1px solid var(--pr-line);padding:16px 0}.re-faq summary{font-weight:700;cursor:pointer;display:flex;justify-content:space-between;list-style:none}.re-faq summary::after{content:"+";font-size:22px;font-weight:400}.re-faq details[open] summary::after{content:"−"}
.re-faq p{color:#4b5563;line-height:1.6;margin:10px 0 0}
@media(max-width:760px){.re-hero .pr-wrap,.re-split{grid-template-columns:1fr}.re-art{font-size:80px}}
</style>@endpush
<div class="pr" style="background:#fff;padding-bottom:40px">
<section class="re-hero"><div class="pr-wrap">
    <div>
        <h1>Click and earn</h1>
        <p>Share an owner's contact — get rewarded @if($top) up to ₹{{ number_format($top) }}@endif</p>
        @auth<button type="button" class="re-btn" data-open="re-contact">Enter owner details</button>
        @else<a class="re-btn" href="{{ route('login', ['redirect' => route('referrals.index')]) }}">Sign in to start</a>@endauth
        @if($balance !== null)<p style="font-size:16px;color:#374151;margin:16px 0 0">Your balance: <b>₹{{ number_format($balance) }}</b> · <a href="{{ route('referrals.mine') }}">My referrals →</a></p>@endif
    </div>
    <div class="re-art" aria-hidden="true">🏠📲</div>
</div></section>

@if(session('success'))<div class="pr-wrap"><div class="pr-flash ok">{{ session('success') }}</div></div>@endif
@if($errors->any())<div class="pr-wrap"><div class="pr-flash err">{{ $errors->first() }}</div></div>@endif

<section class="re-sec"><div class="pr-wrap">
    <h2>How do I do it?</h2>
    <div class="re-split">
        <div class="re-art" aria-hidden="true" style="font-size:90px">📝🔍</div>
        <div><p>Share the contact details of a property owner who wants to rent out or sell, with their OK.@if($rw['verified_contact']) Earn ₹{{ number_format($rw['verified_contact']) }} when we verify it @if($rw['listed']), plus ₹{{ number_format($rw['listed']) }} if they list with us @endif.@endif</p>
            @auth<button type="button" class="re-btn" data-open="re-contact">Enter owner details</button>@endauth</div>
    </div>
</div></section>
<div class="re-or">OR</div>
<section class="re-sec" style="background:#f9fafb"><div class="pr-wrap">
    <div class="re-split">
        <div><p>Click and upload a picture of a <b>TO LET</b> or <b>FOR SALE</b> board outside any house, plot or commercial property @if($rw['verified_photo']) — earn ₹{{ number_format($rw['verified_photo']) }} for each usable photo @endif.</p>
            @auth<button type="button" class="re-btn" data-open="re-photo">Upload photo</button>@endauth</div>
        <div class="re-art" aria-hidden="true" style="font-size:90px">📸</div>
    </div>
</div></section>

<section class="re-sec"><div class="pr-wrap">
    <h2>A little extra money is always welcome</h2>
    <div class="re-split"><div class="re-art" aria-hidden="true" style="font-size:90px">👛</div>
        <div><p>Track every referral, see your rewards and withdraw them to your UPI ID.</p><a class="re-btn" href="{{ route('referrals.mine') }}">I'm in</a></div></div>
</div></section>

<section class="re-sec re-faq" style="border:0"><div class="pr-wrap" style="max-width:1100px">
    <h2 style="text-align:left;color:#111827;font-weight:700;font-size:24px">Common questions</h2>
    @foreach($o['faq'] as [$q, $a])<details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>@endforeach
</div></section>
</div>

@auth
<dialog class="re-dlg" id="re-contact" @if($errors->any() && old('kind') !== 'photo') data-reopen @endif>
    <header><h2>Refer &amp; Earn</h2><button type="button" data-close aria-label="Close">×</button></header>
    <form method="post" action="{{ route('referrals.store') }}">
        @csrf<input type="hidden" name="kind" value="contact">
        <div><b>Share an owner's contact @if($top) and get up to ₹{{ number_format($top) }}@endif</b><div class="pr-mute" style="font-size:14px;margin-top:4px">Enter the owner's correct name and phone so we can reach them quickly.</div></div>
        <select name="city" required aria-label="City"><option value="">Select city</option>@foreach($cities as $c)<option @selected(old('city') === $c)>{{ $c }}</option>@endforeach</select>
        <div class="re-dial"><span>🇮🇳 +91</span><input type="tel" name="owner_phone" required pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" placeholder="Owner contact number" value="{{ old('owner_phone') }}" aria-label="Owner contact number"></div>
        <input type="text" name="owner_name" required maxlength="120" placeholder="Owner name" value="{{ old('owner_name') }}" aria-label="Owner name">
        <div class="pr-grid" style="gap:10px">
            <select name="property_type" aria-label="Property type"><option value="">Select property type</option>@foreach($types as $k => $l)<option value="{{ $k }}" @selected(old('property_type') === $k)>{{ $l }}</option>@endforeach</select>
            <select name="listing" aria-label="Rent or sale"><option value="">Rent or sale?</option>@foreach($o['listings'] as $k => $l)<option value="{{ $k }}" @selected(old('listing') === $k)>{{ $l }}</option>@endforeach</select>
        </div>
        <input type="text" name="notes" maxlength="500" placeholder="Tell us more (locality, rent / price…)" value="{{ old('notes') }}" aria-label="Tell us more">
        <label style="display:flex;gap:8px;align-items:flex-start;font-size:14px"><input type="checkbox" name="consent" value="1" required style="margin-top:3px"> The owner knows I'm sharing their number and is happy to be contacted about renting / selling.</label>
        <button class="re-red">Send details</button>
    </form>
</dialog>
<dialog class="re-dlg" id="re-photo" @if($errors->any() && old('kind') === 'photo') data-reopen @endif>
    <header><h2>Upload a board photo</h2><button type="button" data-close aria-label="Close">×</button></header>
    <form method="post" action="{{ route('referrals.store') }}" enctype="multipart/form-data" data-geo>
        @csrf<input type="hidden" name="kind" value="photo"><input type="hidden" name="latitude"><input type="hidden" name="longitude">
        <div class="pr-mute" style="font-size:14px">Make sure the phone number on the TO LET / FOR SALE board is readable.</div>
        <input type="file" name="photo" accept="image/*" capture="environment" required aria-label="Board photo">
        <select name="city" required aria-label="City"><option value="">Select city</option>@foreach($cities as $c)<option @selected(old('city') === $c)>{{ $c }}</option>@endforeach</select>
        <div class="pr-grid" style="gap:10px">
            <select name="property_type" aria-label="Property type"><option value="">Property type</option>@foreach($types as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            <select name="listing" aria-label="Rent or sale"><option value="">To let or for sale?</option>@foreach($o['listings'] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        </div>
        <input type="text" name="notes" maxlength="500" placeholder="Street / locality" aria-label="Street or locality">
        <label style="font-size:14px;display:flex;gap:8px;align-items:center"><input type="checkbox" data-loc> Add my current location</label>
        <button class="re-red">Upload</button>
    </form>
</dialog>
@endauth
@push('scripts')<script>
(function () {
    document.querySelectorAll('[data-open]').forEach(function (b) { b.addEventListener('click', function () { var d = document.getElementById(b.dataset.open); if (d && d.showModal) d.showModal(); }); });
    document.querySelectorAll('dialog [data-close]').forEach(function (b) { b.addEventListener('click', function () { b.closest('dialog').close(); }); });
    document.querySelectorAll('dialog[data-reopen]').forEach(function (d) { if (d.showModal) d.showModal(); });
    var loc = document.querySelector('[data-loc]');
    if (loc) loc.addEventListener('change', function () {
        var f = loc.closest('form'); if (!loc.checked) { f.latitude.value = f.longitude.value = ''; return; }
        if (!navigator.geolocation) { loc.checked = false; return; }
        navigator.geolocation.getCurrentPosition(function (p) { f.latitude.value = p.coords.latitude.toFixed(6); f.longitude.value = p.coords.longitude.toFixed(6); }, function () { loc.checked = false; }, { timeout: 10000 });
    });
})();
</script>@endpush
@include('partials.property-services')
@endsection
