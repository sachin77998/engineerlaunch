@extends('layouts.site')
@section('title', 'Online rent agreement in ' . $city . ' — ' . config('properties.brand'))
@section('content')
@include('legal._head')
@php
    $PF = \App\Services\Property\PropertyFormat::class;
    $agreementService = $services[$o['agreement_service']] ?? null;
    $regService = $services[$o['registration_service']] ?? null;
    $addons = collect($o['addons'])->map(fn ($s) => $services[$s] ?? null)->filter();
    $fmtRule = function ($r) {
        if (!$r) return 'Confirmed on call';
        return isset($r['fixed']) ? '₹' . number_format($r['fixed']) . ' fixed' : rtrim(rtrim(number_format($r['pct'], 2), '0'), '.') . '% of ' . str_replace(['annual_rent_plus_deposit', 'total_rent_plus_deposit', 'annual_rent', 'consideration'], ['annual rent + deposit', 'total rent + deposit', 'annual rent', 'total rent + notional interest on deposit'], $r['base']);
    };
@endphp
@push('styles')<style>
.ra-hero{background:linear-gradient(180deg,#1e1b4b,#3b2f86);color:#fff;padding:30px 0 70px}
.ra-hero h1{color:#fff;font-size:clamp(26px,3.6vw,40px);margin:0}.ra-hero h1 em{font-style:normal;color:#c4b5fd}
.ra-hero form{display:inline-flex;gap:8px;align-items:center;margin-top:12px}.ra-hero select{width:auto!important;background:rgba(255,255,255,.1)!important;color:#fff!important;border-color:rgba(255,255,255,.3)!important}.ra-hero select option{color:#111}
.ra-strip{background:#fff;border-radius:18px;margin-top:-50px;padding:18px 22px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr)) auto;gap:16px;align-items:center;box-shadow:0 12px 30px rgba(17,24,39,.08)}
.ra-strip div{display:flex;gap:10px;font-size:14px}.ra-strip div i{font-style:normal;width:34px;height:34px;border-radius:10px;background:#f5f3ff;display:grid;place-items:center;flex:none}.ra-strip small{display:block;color:var(--pr-mute)}
.ra-cta{background:#e11d48;color:#fff;border-radius:10px;padding:13px 20px;font-weight:800;text-decoration:none;white-space:nowrap}.ra-cta:hover{background:#be123c;color:#fff}
.ra-sec{background:#fff;border:1px solid var(--pr-line);border-radius:20px;padding:24px;margin-top:22px}
.ra-addons{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}
.ra-addon{border:1px solid #ddd6fe;border-radius:16px;padding:16px;background:#faf5ff}.ra-addon h3{margin:0 0 6px;font-size:17px}.ra-addon p{margin:0;color:#475569;font-size:14px;line-height:1.5}.ra-addon b{display:block;margin-top:8px;font-size:14px}
.ra-calc{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.3fr);gap:0;padding:0;overflow:hidden}
.ra-calc>div{padding:24px}.ra-calc>div:first-child{border-right:1px solid var(--pr-line);display:flex;flex-direction:column}
.ra-lines{display:grid;gap:8px;margin:14px 0}.ra-lines div{display:flex;justify-content:space-between;gap:10px;font-size:14.5px}.ra-lines div span:last-child{font-weight:700}
.ra-total{margin-top:auto;background:#f5f3ff;border-radius:12px;padding:16px;display:flex;justify-content:space-between;align-items:center}.ra-total b{font-size:26px}
.ra-dur{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}.ra-dur label{position:relative}.ra-dur input{position:absolute;opacity:0}.ra-dur span{display:block;text-align:center;padding:11px;border:1px solid #d1d5db;border-radius:8px;cursor:pointer}
.ra-dur input:checked+span{border-color:#0f766e;background:#f0fdfa;color:#0f766e;font-weight:700}.ra-dur select{padding:10px!important}
.ra-checks{display:grid;gap:8px}.ra-checks label{display:flex;gap:8px;align-items:center;font-size:14.5px}
.ra-go{width:100%;background:#0f766e!important;padding:14px!important;font-size:16px!important}.ra-go:hover{background:#115e59!important}
.ra-tbl{width:100%;border-collapse:collapse;font-size:14px;margin-top:10px}.ra-tbl th,.ra-tbl td{text-align:left;padding:9px 10px;border-top:1px solid var(--pr-line)}.ra-tbl th{color:var(--pr-mute);font-weight:600}
.ra-sec details summary{cursor:pointer;font-weight:700;font-size:17px}
.ra-other{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px}
.ra-other a{border:1px solid var(--pr-line);border-radius:16px;padding:18px;text-decoration:none;color:inherit;background:linear-gradient(135deg,#fff,#f0f9ff)}.ra-other b{font-size:17px}.ra-other p{color:#475569;font-size:14px;margin:6px 0}
@media(max-width:860px){.ra-strip,.ra-calc{grid-template-columns:1fr}.ra-calc>div:first-child{border-right:0;border-bottom:1px solid var(--pr-line)}}
</style>@endpush
<div class="pr" style="padding-bottom:60px">
<section class="ra-hero"><div class="pr-wrap">
    <h1>Online rent agreement <em>in {{ $city }}</em></h1>
    <form method="get" onsubmit="location.href='{{ url('/rent-agreement') }}/'+this.city.value;return false">
        <select name="city" aria-label="City" onchange="this.form.onsubmit()">@foreach($cities as $c)<option value="{{ \Illuminate\Support\Str::slug($c) }}" @selected($c === $city)>{{ $c }}</option>@endforeach</select>
        @if($state)<span style="opacity:.8">{{ $state }}</span>@endif
    </form>
</div></section>
<div class="pr-wrap">
    <div class="ra-strip">
        <div><i>📝</i><div><b>Enter details or upload your draft</b><small>Our template or your old agreement</small></div></div>
        <div><i>✍️</i><div><b>Sign</b><small>Aadhaar e-sign or on printed copy</small></div></div>
        <div><i>📜</i><div><b>Get the stamped copy</b><small>Soft copy and optional delivery</small></div></div>
        <a class="ra-cta" href="#calc">Start creating agreement ›</a>
    </div>
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif

    @if($drafts->isNotEmpty())
        <div class="ra-sec"><h2 style="margin:0 0 10px">Your drafts</h2>
            @foreach($drafts as $d)<div style="display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-top:1px solid var(--pr-line);flex-wrap:wrap"><span>{{ $d->reference() }} · {{ $d->city }} · {{ $PF::full($d->rent) }}/month · {{ $d->months }} months</span>
                <a href="{{ route('rent-agreement.step', [$d, collect(array_keys($o['steps']))->first(fn ($s) => !$d->isDone($s)) ?? 'review']) }}">Continue →</a></div>@endforeach
        </div>
    @endif

    @if($addons->isNotEmpty())
        <section class="ra-sec"><h2 style="margin:0 0 14px;text-align:center;color:#6d28d9">Add-ons available with your agreement</h2>
            <div class="ra-addons">@foreach($addons as $s)<div class="ra-addon"><h3>{{ $s->name }}</h3><p>{{ $s->summary }}</p><b>{{ $s->priceLabel() }}</b></div>@endforeach</div>
        </section>
    @endif

    <section class="ra-sec ra-calc" id="calc">
        <div>
            <h2 style="margin:0">Rent agreement cost</h2><span class="pr-mute" style="font-size:13.5px">From rent, deposit and duration</span>
            <div class="ra-lines" data-ra-lines></div>
            <div class="ra-total"><span>Total (estimate)</span><b data-ra-total>₹0</b></div>
            <small class="pr-mute" style="margin-top:8px">Stamp duty and registration are government charges, estimated from your state's rules and confirmed before stamping.</small>
        </div>
        <div>
            <form method="post" action="{{ route('rent-agreement.start') }}" data-ra-form style="display:grid;gap:14px">
                @csrf
                <div><label class="f" for="ra-city">Where is your property?</label><select id="ra-city" name="city">@foreach($cities as $c)<option @selected($c === $city)>{{ $c }}</option>@endforeach</select></div>
                <div><label class="f" for="ra-rent">Monthly rent</label><div class="hl-in" style="display:flex;border:1px solid #d1d5db;border-radius:10px;overflow:hidden"><span style="display:grid;place-items:center;width:40px;background:#f3f4f6;color:#6b7280">₹</span><input id="ra-rent" type="number" name="rent" min="500" required placeholder="Enter the amount" style="border:0;border-radius:0"></div></div>
                <div><label class="f" for="ra-dep">Security deposit</label><div style="display:flex;border:1px solid #d1d5db;border-radius:10px;overflow:hidden"><span style="display:grid;place-items:center;width:40px;background:#f3f4f6;color:#6b7280">₹</span><input id="ra-dep" type="number" name="deposit" min="0" placeholder="Enter the amount" style="border:0;border-radius:0"></div></div>
                <div><label class="f">How long is the agreement? (months)</label>
                    <div class="ra-dur">
                        @foreach($o['durations'] as $m)<label><input type="radio" name="dur" value="{{ $m }}" @checked($loop->first)><span>{{ $m }} months</span></label>@endforeach
                        <select name="dur_other" aria-label="Other duration"><option value="">Other</option>@for($m = 1; $m <= $o['max_months']; $m++)@unless(in_array($m, $o['durations'], true))<option value="{{ $m }}">{{ $m }} months</option>@endunless @endfor</select>
                    </div>
                    <input type="hidden" name="months" value="{{ $o['durations'][0] }}">
                </div>
                <div><label class="f">Agreement type</label>
                    <div class="ra-checks">@foreach($o['types'] as $k => [$label, $hint])<label><input type="radio" name="type" value="{{ $k }}" @checked($loop->first)> <span><b>{{ $label }}</b> <span class="pr-mute">— {{ $hint }}</span></span></label>@endforeach</div>
                    <small class="pr-mute" data-ra-regnote hidden>Registration is required for this term / state.</small>
                </div>
                @if($addons->isNotEmpty())
                    <div><label class="f">Add-ons</label><div class="ra-checks">@foreach($addons as $s)<label><input type="checkbox" name="addons[]" value="{{ $s->slug }}"> {{ $s->name }} <span class="pr-mute">({{ $s->priceLabel() }})</span></label>@endforeach</div></div>
                @endif
                @auth<button class="pr-btn ra-go">Create agreement</button>
                @else<a class="pr-btn ra-go" href="{{ route('login', ['redirect' => request()->getRequestUri() . '#calc']) }}">Sign in to create agreement</a>@endauth
            </form>
        </div>
    </section>

    <section class="ra-sec">
        <details><summary>Stamp duty &amp; registration by state</summary>
            <table class="ra-tbl"><thead><tr><th>State</th><th>Stamp duty (estimate)</th><th>Registration fee</th></tr></thead><tbody>
                @foreach($o['states'] as $st => $r)<tr @if($st === $state) style="background:#f5f3ff" @endif><td><b>{{ $st }}</b>@if($r['registration_required'] ?? false) <span class="pr-tag amber">registration required</span>@endif</td>
                    <td>@foreach($r['stamp'] as $b){{ $b['upto'] ? 'Up to ' . $b['upto'] . ' months: ' : ($loop->count > 1 ? 'Longer: ' : '') }}{{ $fmtRule($b) }}@if(!$loop->last)<br>@endif @endforeach</td><td>{{ $fmtRule($r['registration']) }}</td></tr>@endforeach
                <tr><td><b>Other states</b></td><td>Confirmed on call (minimum ₹{{ number_format($o['min_stamp']) }} stamp paper)</td><td>Confirmed on call</td></tr>
            </tbody></table>
        </details>
        <details style="margin-top:12px"><summary>Documents to keep ready</summary><ul style="margin:10px 0 0;line-height:1.9">@foreach($o['documents'] as $d)<li>{{ $d }}</li>@endforeach</ul></details>
    </section>

    @if($other->isNotEmpty())
        <section class="ra-sec"><h2 style="margin:0 0 14px">Other legal services</h2>
            <div class="ra-other">
                @foreach($other as $s)<a href="{{ route('legal.show', [$citySlug, $s]) }}"><b>{{ $s->name }}</b><p>{{ \Illuminate\Support\Str::limit($s->summary, 80) }}</p><span style="color:#0f766e;font-weight:700">{{ $s->price ? 'From ' . $PF::full($s->price) : 'Details' }} →</span></a>@endforeach
                <a href="{{ route('legal.index', $citySlug) }}"><b>All property legal services</b><p>Agreements, verification, deeds, registration and CA services</p><span style="color:#0f766e;font-weight:700">Explore →</span></a>
            </div>
        </section>
    @endif

    @if($reviews->isNotEmpty())
        <section class="ra-sec"><h2 style="margin:0">Customer reviews</h2>
            <div style="font-size:30px;font-weight:800;color:#b45309">★ {{ number_format($reviews->avg('rating'), 1) }}</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-top:10px">
                @foreach($reviews as $r)<div style="border:1px solid var(--pr-line);border-radius:12px;padding:14px"><b>{{ $r->user?->name ?? 'Customer' }}</b> <span class="pr-tag ok">★ {{ $r->rating }}</span><p style="margin:8px 0 0;color:#475569;font-size:14px">“{{ \Illuminate\Support\Str::limit($r->body, 160) }}”</p></div>@endforeach
            </div>
        </section>
    @endif
</div>
</div>
@push('scripts')<script>
(function () {
    var states = @json($o['states']), cityState = @json($o['cities']), minStamp = {{ $o['min_stamp'] }};
    var fees = { agreement: @json($agreementService?->price), registration: @json($regService?->price) }, addonFees = @json($addons->mapWithKeys(fn ($s) => [$s->slug => ['name' => $s->name, 'price' => $s->price]]));
    var f = document.querySelector('[data-ra-form]'), lines = document.querySelector('[data-ra-lines]'), totalEl = document.querySelector('[data-ra-total]');
    var inr = function (n) { return '₹' + Math.round(n).toLocaleString('en-IN'); };
    function base(b, rent, dep, m) { return { annual_rent: rent * 12, annual_rent_plus_deposit: rent * 12 + dep, total_rent_plus_deposit: rent * m + dep, consideration: rent * m + dep * 0.1 * m / 12 }[b] || 0; }
    function stamp(st, rent, dep, m) {
        var r = states[st] && states[st].stamp; if (!r) return null;
        var b = r.find(function (x) { return x.upto === null || m <= x.upto; }) || r[r.length - 1];
        return Math.max(minStamp, Math.ceil(b.fixed !== undefined ? b.fixed : base(b.base, rent, dep, m) * b.pct / 100));
    }
    function reg(st, rent, dep, m) { var r = states[st] && states[st].registration; if (!r) return null; return Math.ceil(r.fixed !== undefined ? r.fixed : base(r.base, rent, dep, m) * r.pct / 100); }
    function months() { return parseInt(f.dur_other.value || (f.querySelector('[name=dur]:checked') || {}).value || 11, 10); }
    function calc() {
        var st = cityState[f.city.value], rent = +f.rent.value || 0, dep = +f.deposit.value || 0, m = months();
        f.months.value = m;
        var must = m > 11 || (states[st] && states[st].registration_required);
        if (must) f.querySelector('[name=type][value=registered]').checked = true;
        f.querySelectorAll('[name=type]').forEach(function (r) { r.disabled = must && r.value !== 'registered'; });
        document.querySelector('[data-ra-regnote]').hidden = !must;
        var registered = f.querySelector('[name=type]:checked').value === 'registered', rows = [], total = 0, onCall = false;
        function add(label, v) { rows.push([label, v === null ? 'On call' : inr(v)]); if (v === null) onCall = true; else total += v; }
        add('Agreement drafting', fees.agreement);
        if (registered) add('Registration support', fees.registration);
        f.querySelectorAll('[name="addons[]"]:checked').forEach(function (c) { var a = addonFees[c.value]; add(a.name, a.price); });
        add('Stamp duty (estimate)', rent ? stamp(st, rent, dep, m) : 0);
        if (registered) add('Registration fee (estimate)', rent ? reg(st, rent, dep, m) : 0);
        lines.innerHTML = '';
        rows.forEach(function (r) { var d = document.createElement('div'), a = document.createElement('span'), b = document.createElement('span'); a.textContent = r[0]; b.textContent = r[1]; d.append(a, b); lines.appendChild(d); });
        totalEl.textContent = inr(total) + (onCall ? ' + on call' : '');
    }
    f.addEventListener('input', calc); f.addEventListener('change', function (e) {
        if (e.target.name === 'dur') f.dur_other.value = '';
        if (e.target.name === 'dur_other' && e.target.value) f.querySelectorAll('[name=dur]').forEach(function (r) { r.checked = false; });
        calc();
    });
    calc();
})();
</script>@endpush
@include('partials.property-services')
@endsection
