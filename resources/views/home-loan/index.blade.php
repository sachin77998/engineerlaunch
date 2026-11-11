@extends('layouts.site')
@section('title', 'Home loan — compare banks, EMI & eligibility — ' . config('properties.brand'))
@section('content')
@include('properties._nav')
@include('home-loan._styles')
@php
    use App\Services\Property\PropertyFormat as PF;
    $o = config('home_loans');
    $d = $o['defaults'];
    $low = $banks->min('rate_from');
    $updated = $banks->max('updated_at');
    $show = 6;
@endphp
<div class="pr">
<section class="hl-hero"><div class="pr-wrap">
    <div>
        <span class="pr-tag" style="background:rgba(255,255,255,.14);color:#fff">HOME LOAN</span>
        <h1>Home loan at <em>the lowest interest</em><br>Apply online</h1>
        @if($property)<p style="margin:0;opacity:.9">For <a href="{{ route('properties.show', $property) }}" style="color:#fff;font-weight:700">{{ $property->title }}</a> · {{ $property->headlinePrice() }}</p>@endif
        <div class="hl-chips">
            @if($low)<span>✓ Rates from {{ rtrim(rtrim(number_format($low, 2), '0'), '.') }}% p.a.</span>@endif
            <span>✓ Compare {{ $banks->count() }} banks &amp; NBFCs</span><span>✓ EMI &amp; eligibility in one place</span><span>✓ Upload documents online</span>
        </div>
        <div class="hl-stats">
            <div><b>{{ $low ? rtrim(rtrim(number_format($low, 2), '0'), '.') . '%' : '—' }}</b><small>Lowest rate</small></div>
            <div><b>{{ $banks->count() }}</b><small>Lenders</small></div>
            <div><b>{{ $banks->max('max_funding') ?? '—' }}%</b><small>Max funding</small></div>
            <div><b>{{ $banks->max('max_tenure') ?? '—' }} yrs</b><small>Max tenure</small></div>
        </div>
        @if($mine)<p style="margin:18px 0 0"><a class="pr-btn ghost sm" href="{{ route('home-loan.mine') }}">Your application {{ $mine->reference() }} · {{ $mine->statusLabel() }} →</a></p>@endif
    </div>
    <div class="hl-form" id="apply">
        <header><b>Check your eligibility — free</b><small>Takes under a minute</small></header>
        <form method="post" action="{{ route('home-loan.start') }}" data-hl-form>
            @csrf
            @if($errors->any())<div class="pr-flash err" style="margin:0">{{ $errors->first() }}</div>@endif
            <input type="hidden" name="bank" value="{{ old('bank') }}"><input type="hidden" name="tenure" value="{{ old('tenure') }}"><input type="hidden" name="income" value="{{ old('income') }}">
            @if($property)<input type="hidden" name="property" value="{{ $property->slug }}">@endif
            <div><label class="f" for="hl-name">Full name <em>*</em></label><input id="hl-name" type="text" name="name" required maxlength="120" value="{{ old('name', auth()->user()?->name) }}" placeholder="Enter your full name"></div>
            <div><label class="f" for="hl-phone">Mobile number <em>*</em></label><div class="hl-dial"><span>+91</span><input id="hl-phone" type="tel" name="phone" required pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" value="{{ old('phone') }}" placeholder="10-digit mobile number"></div></div>
            <div><label class="f" for="hl-email">Email <span class="pr-mute">(optional)</span></label><input id="hl-email" type="email" name="email" maxlength="191" value="{{ old('email', auth()->user()?->email) }}" placeholder="you@example.com"></div>
            <div class="hl-two">
                <div><label class="f" for="hl-city">City <em>*</em></label><select id="hl-city" name="city" required><option value="">Select city</option>@foreach($cities as $c)<option @selected(old('city', $property?->city) === $c)>{{ $c }}</option>@endforeach</select></div>
                <div><label class="f" for="hl-amt">Loan amount required</label>
                    <select id="hl-amt" name="loan_amount"><option value="">Select amount</option>
                        @foreach($o['amounts'] as $v => $label)<option value="{{ $v }}" @selected((int) old('loan_amount') === $v)>{{ $label }}</option>@endforeach
                        <option value="" data-custom hidden></option>
                    </select></div>
            </div>
            <div><label class="f">Property finalised? <em>*</em></label>
                <div class="hl-seg">
                    <label><input type="radio" name="property_finalized" value="1" required @checked(old('property_finalized', $property ? '1' : null) === '1')><span>Yes, finalised</span></label>
                    <label><input type="radio" name="property_finalized" value="0" @checked(old('property_finalized') === '0')><span>Still searching</span></label>
                </div></div>
            <button class="pr-btn hl-red" style="width:100%;padding:13px">Check my eligibility →</button>
            <small class="pr-mute">🔒 Your details are used only to contact you about this loan.</small>
        </form>
    </div>
</div></section>

<div class="pr-wrap">
    <section class="hl-sec" id="banks">
        <h2 style="font-size:24px;margin:0">Compare banks &amp; housing finance companies</h2>
        <span class="pr-mute">Lowest interest rate, longest tenure and highest funding.</span>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
            <div class="hl-tabs" data-hl-sort>@foreach(\App\Models\HomeLoan\HomeLoanBank::SORTS as $k => $label)<button type="button" data-sort="{{ $k }}" class="{{ $k === $sort ? 'on' : '' }}">{{ $label }}</button>@endforeach</div>
            @if($updated)<small class="pr-mute">Rates updated {{ $updated->format('d M Y') }} · indicative, confirm with the lender</small>@endif
        </div>
        <div style="overflow-x:auto">
        <table class="hl-table">
            <thead><tr><th>Bank / NBFC</th><th>Interest rate</th><th>Processing fee</th><th>Max loan amount</th><th>Max tenure</th><th>Max funding</th><th>Action</th></tr></thead>
            <tbody data-hl-rows data-show="{{ $show }}">
            @foreach($banks as $i => $b)
                <tr data-rate="{{ $b->rate_from }}" data-tenure="{{ $b->max_tenure }}" data-funding="{{ $b->max_funding }}" @if($i >= $show) hidden @endif>
                    <td><div class="hl-bank"><i style="background:{{ $b->color }}">{{ $b->initials() }}</i><div><b>{{ $b->name }}</b><small>{{ $b->kindLabel() }}</small></div></div></td>
                    <td><b>{{ rtrim(rtrim(number_format($b->rate_from, 2), '0'), '.') }}% onwards</b></td>
                    <td class="pr-mute" style="max-width:240px">{{ $b->processing_fee ?: '—' }}</td>
                    <td>{{ $b->max_amount ?: 'Based on eligibility' }}</td>
                    <td>{{ $b->max_tenure }} years</td>
                    <td>Up to {{ $b->max_funding }}%</td>
                    <td><button type="button" class="pr-btn hl-red sm" data-apply-bank="{{ $b->id }}" data-rate="{{ $b->rate_from }}">Apply now</button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @if($banks->count() > $show)<p style="text-align:center;margin:16px 0 0"><button type="button" class="pr-btn ghost" data-hl-more style="color:#e11d48;border-color:#fecdd3">Show {{ $banks->count() - $show }} more banks &amp; NBFCs →</button></p>@endif
    </section>

    <section class="hl-sec" id="calculators">
        <h2 style="font-size:24px;margin:0 0 16px">Plan your loan</h2>
        <div class="hl-calc">
            <div class="hl-card" data-emi>
                <h3><i>₹</i>EMI calculator</h3>
                <div class="hl-field"><label for="e-amt">Loan amount</label><div class="hl-in"><span>₹</span><input id="e-amt" type="text" inputmode="numeric" data-num="amt" value="{{ $amount }}"></div><input type="range" data-range="amt" min="100000" max="50000000" step="50000" value="{{ $amount }}" aria-label="Loan amount"></div>
                <div class="hl-two">
                    <div class="hl-field"><label for="e-rate">Rate of interest (p.a.)</label><div class="hl-in"><span>%</span><input id="e-rate" type="text" inputmode="decimal" data-num="rate" value="{{ $d['rate'] }}"></div><input type="range" data-range="rate" min="5" max="15" step="0.05" value="{{ $d['rate'] }}" aria-label="Rate"></div>
                    <div class="hl-field"><label for="e-yrs">Loan tenure</label><div class="hl-in"><span>Yr</span><input id="e-yrs" type="text" inputmode="numeric" data-num="yrs" value="{{ $d['years'] }}"></div><input type="range" data-range="yrs" min="1" max="30" step="1" value="{{ $d['years'] }}" aria-label="Tenure"></div>
                </div>
                <div class="hl-out"><small>Estimated monthly EMI</small><b data-out="emi">—</b><p>Indicative · actual EMI may vary by bank</p></div>
                <div class="hl-split"><i data-out="split"></i></div>
                <div class="hl-legend"><span>● Principal <b data-out="principal"></b></span><span style="color:#e11d48">● Interest <b data-out="interest"></b></span></div>
                <div class="hl-mini"><div><small>Total repayment</small><b data-out="total"></b></div><div><small>Tenure</small><b data-out="tenure"></b></div></div>
                <button type="button" class="pr-btn hl-red" style="margin-top:auto" data-apply-emi>Like this EMI? Apply free →</button>
            </div>
            <div class="hl-card" data-elig>
                <h3><i style="background:#e11d48">%</i>Eligibility calculator</h3>
                <div class="hl-two">
                    <div class="hl-field"><label for="g-amt">Loan amount you need</label><div class="hl-in"><span>₹</span><input id="g-amt" type="text" inputmode="numeric" data-num="amt" value="{{ $amount }}"></div><input type="range" data-range="amt" min="100000" max="50000000" step="50000" value="{{ $amount }}" aria-label="Loan amount"></div>
                    <div class="hl-field"><label for="g-inc">Monthly income (net)</label><div class="hl-in"><span>₹</span><input id="g-inc" type="text" inputmode="numeric" data-num="inc" value="{{ $d['income'] }}"></div><input type="range" data-range="inc" min="10000" max="1000000" step="5000" value="{{ $d['income'] }}" aria-label="Income"></div>
                    <div class="hl-field"><label for="g-emi">Other monthly EMIs</label><div class="hl-in"><span>₹</span><input id="g-emi" type="text" inputmode="numeric" data-num="oth" value="0"></div><input type="range" data-range="oth" min="0" max="500000" step="1000" value="0" aria-label="Other EMIs"></div>
                    <div class="hl-field"><label for="g-rate">Rate of interest (p.a.)</label><div class="hl-in"><span>%</span><input id="g-rate" type="text" inputmode="decimal" data-num="rate" value="{{ $d['rate'] }}"></div><input type="range" data-range="rate" min="5" max="15" step="0.05" value="{{ $d['rate'] }}" aria-label="Rate"></div>
                    <div class="hl-field"><label for="g-yrs">Loan tenure</label><div class="hl-in"><span>Yrs</span><input id="g-yrs" type="text" inputmode="numeric" data-num="yrs" value="{{ $d['years'] }}"></div><input type="range" data-range="yrs" min="1" max="30" step="1" value="{{ $d['years'] }}" aria-label="Tenure"></div>
                </div>
                <div class="hl-out" style="background:linear-gradient(135deg,#4c1d95,#7c3aed)"><small>Maximum eligible loan amount</small><b data-out="max">—</b><p>Indicative · not a bank sanction</p></div>
                <div class="hl-mini"><div><small>EMI for amount entered</small><b data-out="emi"></b></div><div><small>Loan amount entered</small><b data-out="amt"></b></div></div>
                <div class="hl-note" data-out="note"></div>
                <button type="button" class="pr-btn hl-red" style="margin-top:auto" data-apply-elig>Check exact eligibility →</button>
            </div>
        </div>
    </section>
</div>
</div>
@push('scripts')<script>
(function () {
    var FOIR = {{ $o['foir'] }}, amounts = @json(array_keys($o['amounts']));
    var inr = function (n) { return '₹ ' + Math.round(n).toLocaleString('en-IN'); };
    var num = function (s) { var v = parseFloat(String(s).replace(/[^0-9.]/g, '')); return isNaN(v) ? 0 : v; };
    var emi = function (p, rate, yrs) { var n = yrs * 12, r = rate / 1200; if (p <= 0 || n <= 0) return 0; return r ? p * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1) : p / n; };

    function bind(card, calc) {
        card.querySelectorAll('[data-num]').forEach(function (input) {
            var range = card.querySelector('[data-range="' + input.dataset.num + '"]'), money = input.dataset.num !== 'rate' && input.dataset.num !== 'yrs';
            var show = function (v) { input.value = money ? Math.round(v).toLocaleString('en-IN') : v; };
            show(num(input.value));
            input.addEventListener('input', function () { range.value = num(input.value); calc(); });
            input.addEventListener('blur', function () { show(num(input.value)); });
            range.addEventListener('input', function () { show(num(range.value)); calc(); });
        });
        calc();
    }
    var v = function (card, k) { return num(card.querySelector('[data-num="' + k + '"]').value); };
    var out = function (card, k) { return card.querySelector('[data-out="' + k + '"]'); };

    var E = document.querySelector('[data-emi]');
    bind(E, function () {
        var p = v(E, 'amt'), y = v(E, 'yrs'), m = emi(p, v(E, 'rate'), y), total = m * y * 12;
        out(E, 'emi').textContent = inr(m); out(E, 'principal').textContent = inr(p); out(E, 'interest').textContent = inr(Math.max(0, total - p));
        out(E, 'total').textContent = inr(total); out(E, 'tenure').textContent = y + ' years';
        out(E, 'split').style.width = (total ? p / total * 100 : 0) + '%';
    });
    var G = document.querySelector('[data-elig]');
    bind(G, function () {
        var p = v(G, 'amt'), rate = v(G, 'rate'), y = v(G, 'yrs'), room = v(G, 'inc') * FOIR - v(G, 'oth'), per = emi(1000000, rate, y) / 1000000;
        var max = room > 0 && per > 0 ? Math.floor(room / per) : 0, note = out(G, 'note');
        out(G, 'max').textContent = inr(max); out(G, 'emi').textContent = inr(emi(p, rate, y)); out(G, 'amt').textContent = inr(p);
        var ok = max >= p; note.className = 'hl-note ' + (ok ? 'ok' : 'no');
        note.textContent = ok ? 'Good news — your income supports the loan amount you entered at this rate and tenure.' : 'The amount entered is above your eligibility. Try a longer tenure, a co-applicant\'s income or a smaller loan.';
    });

    // Apply buttons fill the eligibility form and scroll to it.
    var form = document.querySelector('[data-hl-form]');
    function apply(opts) {
        if (opts.bank) form.bank.value = opts.bank;
        if (opts.tenure) form.tenure.value = [10, 15, 20, 25, 30].reduce(function (a, b) { return Math.abs(b - opts.tenure) < Math.abs(a - opts.tenure) ? b : a; });
        if (opts.income) form.income.value = Math.round(opts.income);
        if (opts.amount) {
            var sel = form.loan_amount, custom = sel.querySelector('[data-custom]');
            custom.value = Math.round(opts.amount); custom.textContent = inr(opts.amount); custom.hidden = false; sel.value = custom.value;
        }
        document.getElementById('apply').scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(function () { form.name.focus({ preventScroll: true }); }, 400);
    }
    document.querySelector('[data-apply-emi]').addEventListener('click', function () { apply({ amount: v(E, 'amt'), tenure: v(E, 'yrs') }); });
    document.querySelector('[data-apply-elig]').addEventListener('click', function () { apply({ amount: v(G, 'amt'), tenure: v(G, 'yrs'), income: v(G, 'inc') }); });
    document.querySelectorAll('[data-apply-bank]').forEach(function (b) { b.addEventListener('click', function () { apply({ bank: b.dataset.applyBank }); }); });

    // Bank table: sort tabs and "show more".
    var rows = document.querySelector('[data-hl-rows]'), limit = parseInt(rows.dataset.show, 10), expanded = false, more = document.querySelector('[data-hl-more]');
    function layout() { [].forEach.call(rows.children, function (r, i) { r.hidden = !expanded && i >= limit; }); }
    document.querySelector('[data-hl-sort]').addEventListener('click', function (e) {
        var b = e.target.closest('[data-sort]'); if (!b) return;
        this.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
        var key = b.dataset.sort, list = [].slice.call(rows.children);
        list.sort(function (a, c) {
            var byRate = a.dataset.rate - c.dataset.rate;
            if (key === 'tenure') return (c.dataset.tenure - a.dataset.tenure) || byRate;
            if (key === 'funding') return (c.dataset.funding - a.dataset.funding) || byRate;
            return byRate;
        });
        list.forEach(function (r) { rows.appendChild(r); }); layout();
    });
    if (more) more.addEventListener('click', function () { expanded = !expanded; layout(); more.textContent = expanded ? 'Show fewer' : more.dataset.label; });
    if (more) more.dataset.label = more.textContent;
})();
</script>@endpush
@include('partials.property-services')
@endsection
