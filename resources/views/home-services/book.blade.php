@extends('layouts.site')
@php
    $c = config('home_services');
    [$catLabel, $catIcon] = $c['categories'][$category];
    $packers = $category === 'packers';
@endphp
@section('title', $catLabel . ' in ' . $city . ' — book online — ' . config('properties.brand'))
@section('content')
@include('home-services._head')
@push('styles')<style>
.hb{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr);gap:22px;align-items:start;margin-top:20px}
.hb-box{background:#fff;border:1px solid var(--pr-line);border-radius:16px;padding:20px;margin-bottom:16px}.hb-box h2{font-size:18px;margin:0 0 12px}
.hb-svc{display:flex;justify-content:space-between;gap:12px;padding:12px 0;border-top:1px solid var(--pr-line);align-items:center}.hb-svc:first-of-type{border-top:0}
.hb-svc small{display:block;color:var(--pr-mute)}.hb-svc .inc{font-size:12.5px;color:#4b5563}
.hb-n{display:flex;align-items:center;gap:8px;flex:none}.hb-n button{width:32px;height:32px;border-radius:50%;border:1px solid #d1d5db;background:#fff;font-size:18px;cursor:pointer}.hb-n output{min-width:18px;text-align:center;font-weight:700}
.hb-days{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px}.hb-days label,.hb-slots label{position:relative}.hb-days input,.hb-slots input{position:absolute;opacity:0}
.hb-days span{display:block;min-width:72px;text-align:center;border:1px solid #d1d5db;border-radius:10px;padding:8px;cursor:pointer;font-size:13px}.hb-days span b{display:block;font-size:14px}
.hb-slots{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:12px}.hb-slots span{display:block;text-align:center;border:1px solid #d1d5db;border-radius:10px;padding:10px;cursor:pointer;font-size:13.5px}
.hb-days input:checked+span,.hb-slots input:checked+span{border-color:#e11d48;background:#fff1f2;color:#be123c;font-weight:700}.hb-slots input:disabled+span{opacity:.35;cursor:not-allowed;text-decoration:line-through}
.hb-days input:focus-visible+span,.hb-slots input:focus-visible+span{outline:2px solid #fda4af}
.hb-sum{position:sticky;top:12px}.hb-sum .ln{display:flex;justify-content:space-between;font-size:14px;padding:5px 0}
.hb-go{width:100%;background:#e11d48!important;padding:14px!important;font-size:16px!important}
@media(max-width:860px){.hb{grid-template-columns:1fr}.hb-sum{position:static}.hb-slots{grid-template-columns:repeat(2,1fr)}}
</style>@endpush
<div class="pr" style="padding-bottom:60px"><div class="pr-wrap">
    <nav class="pr-mute" style="font-size:13.5px;margin-top:16px"><a href="{{ route('home-services.city', $citySlug) }}">Home services in {{ $city }}</a> › {{ $catLabel }}</nav>
    <h1 style="margin:6px 0 0">{{ $catIcon }} {{ $catLabel }} in {{ $city }}</h1>
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('home-services.store', [$citySlug, $category]) }}" class="hb" data-hb>
        @csrf
        <div>
            @if($packers)
                <div class="hb-box"><h2>Your move</h2>
                    <div class="pr-grid">
                        <div><label class="f">Move type</label><select name="move[type]" data-move-type required>@foreach($c['move']['types'] as $k => $l)<option value="{{ $k }}" @selected(old('move.type', $moveType) === $k)>{{ $l }}</option>@endforeach</select></div>
                        <div><label class="f">House size</label><select name="move[size]" required>@foreach($c['move']['sizes'] as $k => $l)<option value="{{ $k }}" @selected(old('move.size', '2bhk') === $k)>{{ $l }}</option>@endforeach</select></div>
                        <div><label class="f" for="mv-from">Moving from (address in {{ $city }})</label><input id="mv-from" type="text" name="move[from]" required maxlength="200" value="{{ old('move.from') }}"></div>
                        <div><label class="f" for="mv-to">Moving to (address)</label><input id="mv-to" type="text" name="move[to]" required maxlength="200" value="{{ old('move.to') }}"></div>
                        <div data-to-city @if(old('move.type', $moveType) !== 'intercity') hidden @endif><label class="f" for="mv-city">Destination city</label><input id="mv-city" type="text" name="move[to_city]" maxlength="80" list="hs-city-list" value="{{ old('move.to_city') }}"><datalist id="hs-city-list">@foreach($cities as $ct)<option value="{{ $ct }}">@endforeach</datalist></div>
                        <div><label class="f">Floors (from / to)</label><div style="display:flex;gap:8px"><input type="number" name="move[from_floor]" min="0" max="60" placeholder="From" value="{{ old('move.from_floor') }}"><input type="number" name="move[to_floor]" min="0" max="60" placeholder="To" value="{{ old('move.to_floor') }}"></div></div>
                    </div>
                    <label style="display:flex;gap:8px;margin-top:10px"><input type="checkbox" name="move[lift]" value="1" @checked(old('move.lift'))> Lift available at both ends</label>
                    <div style="margin-top:10px"><label class="f">Add-ons</label><div style="display:flex;gap:12px;flex-wrap:wrap">@foreach($c['move']['extras'] as $k => $l)<label style="display:flex;gap:6px"><input type="checkbox" name="move[extras][]" value="{{ $k }}" @checked(in_array($k, old('move.extras', []), true))> {{ $l }}</label>@endforeach</div></div>
                </div>
            @else
                <div class="hb-box"><h2>Choose services</h2>
                    @foreach($services as $s)
                        <div class="hb-svc" id="s{{ $s->id }}">
                            <div><b>{{ $s->name }}</b><small>{{ $s->priceLabel() }}</small>@if($s->includes)<div class="inc">{{ implode(' · ', $s->includes) }}</div>@endif</div>
                            <div class="hb-n"><button type="button" data-d="-1" aria-label="Fewer {{ $s->name }}">−</button><output>{{ (int) old('qty.' . $s->id, $preset === $s->id ? 1 : 0) }}</output><button type="button" data-d="1" aria-label="More {{ $s->name }}">+</button>
                                <input type="hidden" name="qty[{{ $s->id }}]" value="{{ (int) old('qty.' . $s->id, $preset === $s->id ? 1 : 0) }}" data-price="{{ $s->price ?? '' }}" data-name="{{ $s->name }}"></div>
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="hb-box"><h2>{{ $packers ? 'Moving date & time' : 'When should we come?' }}</h2>
                <div class="hb-days">@foreach($days as $d)<label><input type="radio" name="slot_date" value="{{ $d['date'] }}" required @checked(old('slot_date', $days[0]['date'] ?? null) === $d['date'])><span><b>{{ $d['label'] }}</b>{{ $d['day'] }}</span></label>@endforeach</div>
                @foreach($days as $d)<div class="hb-slots" data-slots="{{ $d['date'] }}" hidden>@foreach($d['slots'] as $s)<label><input type="radio" name="slot" value="{{ $s['key'] }}" data-date="{{ $d['date'] }}" @disabled(!$s['open']) @checked(old('slot_date') === $d['date'] && old('slot') === $s['key'])><span>{{ $s['label'] }}</span></label>@endforeach</div>@endforeach
            </div>
            <div class="hb-box"><h2>Address &amp; contact</h2>
                <div class="pr-grid">
                    <div><label class="f" for="hb-name">Name <em>*</em></label><input id="hb-name" type="text" name="name" required maxlength="120" value="{{ old('name', auth()->user()?->name) }}"></div>
                    <div><label class="f" for="hb-phone">Mobile <em>*</em></label><input id="hb-phone" type="tel" name="phone" required pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" value="{{ old('phone') }}"></div>
                </div>
                <div style="margin-top:12px"><label class="f" for="hb-addr">{{ $packers ? 'Pickup' : 'Service' }} address in {{ $city }} <em>*</em></label><input id="hb-addr" type="text" name="address" required maxlength="300" value="{{ old('address') }}" placeholder="House / flat no., building, street, locality"></div>
                <div class="pr-grid" style="margin-top:12px">
                    <div><label class="f" for="hb-pin">PIN code</label><input id="hb-pin" type="text" name="pincode" maxlength="6" inputmode="numeric" value="{{ old('pincode') }}"></div>
                    <div><label class="f" for="hb-notes">Notes</label><input id="hb-notes" type="text" name="notes" maxlength="500" value="{{ old('notes') }}" placeholder="Anything we should know?"></div>
                </div>
            </div>
        </div>
        <aside class="hb-box hb-sum">
            <h2>{{ $packers ? 'Get a free quote' : 'Summary' }}</h2>
            @unless($packers)<div data-lines><span class="pr-mute">Add a service to continue.</span></div><div class="ln" style="border-top:1px solid var(--pr-line);margin-top:6px;padding-top:10px;font-size:17px"><span>Total</span><b data-total>₹0</b></div>@endunless
            <p class="pr-mute" style="font-size:13px">{{ $packers ? 'Our team calls you with a quote after checking your move. No payment now.' : 'Pay after the service. Items marked "price after inspection" are quoted on site.' }}</p>
            @auth<button class="pr-btn hb-go">{{ $packers ? 'Request quote' : 'Book now' }}</button>
            @else<a class="pr-btn hb-go" href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">Sign in to book</a>@endauth
        </aside>
    </form>
</div></div>
@push('scripts')<script>
(function () {
    var f = document.querySelector('[data-hb]'), inr = function (n) { return '₹' + Math.round(n).toLocaleString('en-IN'); }, max = {{ $c['max_qty'] }};
    function showSlots() {
        var d = (f.querySelector('[name=slot_date]:checked') || {}).value;
        f.querySelectorAll('[data-slots]').forEach(function (g) { g.hidden = g.dataset.slots !== d; g.querySelectorAll('input').forEach(function (i) { if (g.hidden) i.checked = false; }); });
    }
    function total() {
        var lines = f.querySelector('[data-lines]'); if (!lines) return;
        var sum = 0, quote = false, frag = document.createDocumentFragment();
        f.querySelectorAll('[data-price]').forEach(function (i) {
            var n = +i.value; if (!n) return;
            var p = i.dataset.price ? +i.dataset.price * n : null; if (p === null) quote = true; else sum += p;
            var d = document.createElement('div'), a = document.createElement('span'), b = document.createElement('span');
            d.className = 'ln'; a.textContent = i.dataset.name + ' × ' + n; b.textContent = p === null ? 'On inspection' : inr(p); d.append(a, b); frag.appendChild(d);
        });
        lines.innerHTML = ''; if (frag.childNodes.length) lines.appendChild(frag); else lines.innerHTML = '<span class="pr-mute">Add a service to continue.</span>';
        f.querySelector('[data-total]').textContent = inr(sum) + (quote ? ' + on inspection' : '');
    }
    f.addEventListener('click', function (e) {
        var b = e.target.closest('[data-d]'); if (!b) return;
        var w = b.parentNode, i = w.querySelector('input'), n = Math.max(0, Math.min(max, +i.value + +b.dataset.d));
        i.value = n; w.querySelector('output').textContent = n; total();
    });
    f.addEventListener('change', function (e) {
        if (e.target.name === 'slot_date') showSlots();
        if (e.target.matches('[data-move-type]')) f.querySelector('[data-to-city]').hidden = e.target.value !== 'intercity';
    });
    f.addEventListener('submit', function (e) {
        if (!f.querySelector('[name=slot]:checked')) { e.preventDefault(); alert('Pick a time slot.'); return; }
        if (f.querySelector('[data-price]') && ![].some.call(f.querySelectorAll('[data-price]'), function (i) { return +i.value > 0; })) { e.preventDefault(); alert('Choose at least one service.'); }
    });
    showSlots(); total();
    if (location.hash) { var t = document.querySelector(location.hash); if (t) t.style.background = '#fff7ed'; }
})();
</script>@endpush
@endsection
