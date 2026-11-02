@extends('layouts.site')
@section('title', 'Checkout — Home Building store')
@section('content')
@include('materials._nav')
@push('styles')<style>
.mt-co{display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start}
.mt-co .two{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.mt-pay{display:grid;gap:10px}
.mt-pay label{display:flex;gap:12px;align-items:center;padding:14px;border:1px solid var(--mt-line);border-radius:12px;cursor:pointer}
.mt-pay label:has(input:checked){border-color:var(--mt-accent);background:#fff7ed}
.mt-pay label.off{opacity:.5;cursor:not-allowed}
.mt-pay i{font-style:normal;font-size:24px}
.mt-mini{display:flex;gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid #f5f0ea;font-size:13.5px}
.mt-mini img{width:44px;height:44px;object-fit:contain;border-radius:8px;border:1px solid var(--mt-line)}
.mt-mini span{flex:1}
.mt-err{color:#b91c1c;font-size:13px;margin-top:4px}
@media(max-width:900px){.mt-co,.mt-co .two{grid-template-columns:1fr}}
</style>@endpush
@php($codOk = $cod['enabled'] && $t['total'] <= $cod['max_order'])
<div class="container mt-wrap">
    <h1 class="mt-h1" style="margin:0">Checkout</h1>
    @if($errors->any())<div class="mt-card" style="border-color:#fecaca;background:#fef2f2;color:#991b1b">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    <form method="post" action="{{ route('materials.checkout.place') }}" class="mt-co" id="co-form">@csrf
        <div style="display:grid;gap:20px">
            <section class="mt-card">
                <h2 style="font-size:19px">1. Delivery address</h2>
                <div class="two">
                    <div><label class="mt-label" for="name">Full name</label><input class="mt-in" id="name" name="name" value="{{ old('name', $prefill['name'] ?? '') }}" required maxlength="120" autocomplete="name"></div>
                    <div><label class="mt-label" for="phone">Mobile number</label><input class="mt-in" id="phone" name="phone" value="{{ old('phone', $prefill['phone'] ?? '') }}" required inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10" autocomplete="tel-national"></div>
                </div>
                <label class="mt-label" for="a1">House / flat, building, street</label><input class="mt-in" id="a1" name="address_line1" value="{{ old('address_line1', $prefill['address_line1'] ?? '') }}" required maxlength="200" autocomplete="address-line1">
                <label class="mt-label" for="a2">Area, landmark (optional)</label><input class="mt-in" id="a2" name="address_line2" value="{{ old('address_line2', $prefill['address_line2'] ?? '') }}" maxlength="200" autocomplete="address-line2">
                <div class="two" style="grid-template-columns:1fr 1fr 1fr">
                    <div><label class="mt-label" for="pin">PIN code</label><input class="mt-in" id="pin" name="pincode" value="{{ old('pincode', $prefill['pincode'] ?? '') }}" required inputmode="numeric" pattern="[1-9][0-9]{5}" maxlength="6" autocomplete="postal-code"><div class="mt-muted" id="pin-msg"></div></div>
                    <div><label class="mt-label" for="city">City</label><input class="mt-in" id="city" name="city" value="{{ old('city', $prefill['city'] ?? '') }}" required maxlength="80" autocomplete="address-level2"></div>
                    <div><label class="mt-label" for="state">State</label><select class="mt-in" id="state" name="state" required><option value="">Select</option>@foreach($states as $s)<option @selected(old('state', $prefill['state'] ?? '') === $s)>{{ $s }}</option>@endforeach</select></div>
                </div>
            </section>
            <section class="mt-card">
                <h2 style="font-size:19px">2. Delivery slot</h2>
                <select class="mt-in" name="slot">@foreach($slots as $s)<option @selected(old('slot') === $s)>{{ $s }}</option>@endforeach</select>
                <label class="mt-label" for="notes">Notes for delivery (optional)</label>
                <input class="mt-in" id="notes" name="notes" maxlength="500" value="{{ old('notes') }}" placeholder="e.g. Unload at the site gate, call before arriving">
            </section>
            <section class="mt-card">
                <h2 style="font-size:19px">3. Payment</h2>
                <div class="mt-pay">
                    <label class="{{ $paytm ? '' : 'off' }}"><input type="radio" name="payment_method" value="paytm" @checked(old('payment_method', $paytm ? 'paytm' : 'cod') === 'paytm') @disabled(!$paytm)><i>💳</i><span><b>Pay online</b><br><span class="mt-muted">UPI, cards, net banking, wallets — via Paytm{{ $paytm ? '' : ' (not available right now)' }}</span></span></label>
                    <label class="{{ $codOk ? '' : 'off' }}"><input type="radio" name="payment_method" value="cod" @checked(old('payment_method', $paytm ? 'paytm' : 'cod') === 'cod') @disabled(!$codOk)><i>💵</i><span><b>Pay on delivery</b><br><span class="mt-muted">{{ $codOk ? 'Cash or UPI when your order arrives' : 'Available for orders up to ₹' . number_format($cod['max_order']) }}</span></span></label>
                </div>
            </section>
        </div>
        <aside class="mt-card" style="position:sticky;top:180px">
            <h2 style="font-size:19px">Your order</h2>
            @foreach($rows as $r)
                <div class="mt-mini">@if($r['m']->image_url)<img src="{{ $r['m']->image_url }}" alt="">@endif<span>{{ Str::limit($r['m']->title, 48) }}<br><span class="mt-muted">{{ $r['variant']['title'] ?? '' }} × {{ $r['qty'] }}</span></span><b>₹{{ number_format($r['total']) }}</b></div>
            @endforeach
            <dl style="display:grid;grid-template-columns:1fr auto;gap:8px;margin:14px 0">
                <dt>Subtotal</dt><dd style="margin:0;text-align:right">₹{{ number_format($t['subtotal']) }}</dd>
                @if($t['discount'] > 0)<dt>Order discount</dt><dd class="mt-down" style="margin:0;text-align:right">− ₹{{ number_format($t['discount']) }}</dd>@endif
                <dt>Delivery</dt><dd style="margin:0;text-align:right">{{ $t['delivery_fee'] > 0 ? '₹' . number_format($t['delivery_fee']) : 'FREE' }}</dd>
                <dt style="font-weight:800;font-size:19px">Total</dt><dd style="margin:0;text-align:right;font-weight:800;font-size:19px">₹{{ number_format($t['total']) }}</dd>
            </dl>
            <button class="mt-add" id="co-btn">Place order</button>
            <p class="mt-muted" style="margin:10px 0 0">By placing the order you agree to pay ₹{{ number_format($t['total']) }}. You can cancel until the order is packed.</p>
        </aside>
    </form>
</div>
@push('scripts')<script>
(function () {
    var pin = document.getElementById('pin'), msg = document.getElementById('pin-msg'), form = document.getElementById('co-form');
    function check() {
        if (!/^[1-9]\d{5}$/.test(pin.value)) { msg.textContent = ''; return; }
        fetch(@json(route('materials.pincode')) + '?pin=' + pin.value, {headers: {Accept: 'application/json'}}).then(function (r) { return r.json(); }).then(function (d) {
            msg.textContent = d.message || ''; msg.style.color = d.serviceable === false ? '#b91c1c' : '#15803d';
            if (d.district && !document.getElementById('city').value) document.getElementById('city').value = d.district;
            if (d.state) { var s = Array.from(document.getElementById('state').options).find(function (o) { return o.value.toLowerCase() === d.state.toLowerCase(); }); if (s) document.getElementById('state').value = s.value; }
        }).catch(function () {});
    }
    pin.addEventListener('change', check); if (pin.value) check();
    form.addEventListener('submit', function () { var b = document.getElementById('co-btn'); b.disabled = true; b.textContent = 'Placing order…'; });
})();
</script>@endpush
@endsection
