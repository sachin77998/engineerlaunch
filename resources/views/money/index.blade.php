@extends('layouts.site')
@section('title', 'Retirement planner — how much you will have and what it pays every month')
@php($F = \App\Services\Money\Format::class)
@section('content')
<div class="mp">
@include('money._layout', ['mpTitle' => 'Plan your retirement income', 'mpSub' => 'Enter your salary and what you invest each month — see the corpus at retirement and the monthly income it pays.'])
<div class="mp-wrap">
@if(session('success'))<div class="mp-note">{{ session('success') }}</div>@endif
<div class="mp-grid">
    <form class="mp-card" method="post" action="{{ route('money.save') }}" id="mp-form">@csrf
        <h2>Your details</h2><p class="sub">Every number is yours to change. Saved {{ auth()->check() ? 'to your account' : 'for this visit' }} and used across the planner and the chat assistant.</p>
        <div class="mp-row">
            <div class="mp-f"><label for="age">Current age</label><input id="age" type="number" name="age" min="18" max="90" value="{{ $p['age'] }}" required></div>
            <div class="mp-f"><label for="ret">Retire at age</label><input id="ret" type="number" name="retirement_age" min="30" max="80" value="{{ $p['retirement_age'] }}" required></div>
            <div class="mp-f"><label for="sal">Monthly salary (₹)</label><input id="sal" type="number" name="monthly_salary" min="0" value="{{ (int) $p['monthly_salary'] }}" required></div>
            <div class="mp-f"><label for="exp">Monthly expenses (₹)</label><input id="exp" type="number" name="monthly_expenses" min="0" value="{{ (int) $p['monthly_expenses'] }}"></div>
            <div class="mp-f"><label for="tgt">Monthly income wanted (₹)</label><input id="tgt" type="number" name="target_monthly_income" min="0" value="{{ (int) $p['target_monthly_income'] }}" required></div>
            <div class="mp-f"><label for="tgt-t">That amount is in</label><select id="tgt-t" name="target_in_todays_money"><option value="0">Rupees at retirement</option><option value="1" @selected($p['target_in_todays_money'])>Today's rupees</option></select></div>
        </div>
        <h2 style="margin-top:8px">Monthly investments</h2><p class="sub">Return % and yearly step-up are optional — blank uses the typical rate.</p>
        <div class="mp-row3" style="font-size:11px;font-weight:750;color:var(--mp-muted)"><span>PRODUCT</span><span>₹ / MONTH</span><span>RETURN %</span><span>STEP-UP %</span><span></span></div>
        <div id="inv-rows">
            @foreach($p['investments'] as $i => $inv)
                <div class="mp-row3"><select name="investments[{{ $i }}][type]" aria-label="Product">@foreach($instruments as $k => $ins)<option value="{{ $k }}" @selected($inv['type'] === $k)>{{ $ins['label'] }}</option>@endforeach</select>
                    <input type="number" min="0" name="investments[{{ $i }}][monthly]" value="{{ (int) $inv['monthly'] }}" aria-label="Monthly amount"><input type="number" step="0.1" min="0" name="investments[{{ $i }}][return]" value="{{ $inv['return'] }}" placeholder="{{ $instruments[$inv['type']]['return'] }}" aria-label="Return %"><input type="number" step="1" min="0" name="investments[{{ $i }}][step_up]" value="{{ $inv['step_up'] }}" placeholder="{{ $p['step_up'] }}" aria-label="Step-up %"><button type="button" class="mp-x" data-remove aria-label="Remove">✕</button></div>
            @endforeach
        </div>
        <button type="button" class="mp-btn ghost" id="add-inv" style="margin:6px 0 14px">＋ Add investment</button>
        <details><summary style="cursor:pointer;font-weight:750;margin-bottom:10px">Assumptions, savings &amp; family</summary>
            <div class="mp-row">
                <div class="mp-f"><label>Savings already built (₹)</label><input type="number" name="current_savings" min="0" value="{{ (int) $p['current_savings'] }}"></div>
                <div class="mp-f"><label>Default yearly step-up %</label><input type="number" name="step_up" min="0" max="30" value="{{ $p['step_up'] }}"></div>
                <div class="mp-f"><label>Inflation %</label><input type="number" step="0.1" name="inflation" value="{{ $p['inflation'] }}"></div>
                <div class="mp-f"><label>Return after retirement %</label><input type="number" step="0.1" name="post_retirement_return" value="{{ $p['post_retirement_return'] }}"></div>
                <div class="mp-f"><label>Plan until age</label><input type="number" name="life_expectancy" value="{{ $p['life_expectancy'] }}"></div>
                <div class="mp-f"><label>Risk appetite</label><select name="risk">@foreach(['conservative' => 'Conservative', 'moderate' => 'Moderate', 'aggressive' => 'Aggressive'] as $k => $v)<option value="{{ $k }}" @selected($p['risk'] === $k)>{{ $v }}</option>@endforeach</select></div>
                <div class="mp-f"><label>Tax regime</label><select name="regime"><option value="new" @selected($p['regime'] === 'new')>New regime</option><option value="old" @selected($p['regime'] === 'old')>Old regime</option></select></div>
                <div class="mp-f"><label>City</label><select name="city_tier"><option value="metro" @selected($p['city_tier'] === 'metro')>Metro</option><option value="non_metro" @selected($p['city_tier'] === 'non_metro')>Non-metro</option></select></div>
                <div class="mp-f"><label>Existing life cover (₹)</label><input type="number" name="existing_life_cover" min="0" value="{{ (int) $p['existing_life_cover'] }}"></div>
                <div class="mp-f"><label>Loans outstanding (₹)</label><input type="number" name="liabilities" min="0" value="{{ (int) $p['liabilities'] }}"></div>
            </div>
            <label style="font-size:12.5px;font-weight:700">Family (used for tax planning and cover)</label>
            <div id="fam-rows">
                @foreach($p['family'] as $i => $m)
                    <div class="mp-row3" style="grid-template-columns:1.3fr 1fr .7fr 1fr auto"><select name="family[{{ $i }}][relation]">@foreach($relations as $k => $v)<option value="{{ $k }}" @selected($m['relation'] === $k)>{{ $v }}</option>@endforeach</select><input name="family[{{ $i }}][name]" value="{{ $m['name'] }}" placeholder="Name"><input type="number" name="family[{{ $i }}][age]" value="{{ $m['age'] ?: '' }}" placeholder="Age"><input type="number" name="family[{{ $i }}][income]" value="{{ (int) $m['income'] }}" placeholder="Yearly income"><button type="button" class="mp-x" data-remove>✕</button></div>
                @endforeach
            </div>
            <button type="button" class="mp-btn ghost" id="add-fam" style="margin:6px 0 8px">＋ Add family member</button>
        </details>
        <button class="mp-btn" style="width:100%;margin-top:10px">Calculate my plan</button>
    </form>

    <div id="result">
        <div class="mp-kpis">
            <div class="mp-kpi hl"><span>Corpus at {{ $p['retirement_age'] }}</span><b>{{ $F::short($plan['corpus']) }}</b><small>{{ $F::short($plan['corpus_today']) }} in today's money</small></div>
            <div class="mp-kpi {{ $plan['on_track'] ? 'ok' : 'bad' }}"><span>Monthly income it pays</span><b>₹{{ $F::inr($plan['income']) }}</b><small>≈ ₹{{ $F::inr($plan['income_today']) }} today · rises with inflation</small></div>
            <div class="mp-kpi"><span>Your target</span><b>₹{{ $F::inr($plan['target_monthly']) }}</b><small>≈ ₹{{ $F::inr($plan['target_today']) }} in today's money</small></div>
            <div class="mp-kpi {{ $plan['on_track'] ? 'ok' : 'bad' }}"><span>{{ $plan['on_track'] ? 'On track' : 'Shortfall' }}</span><b>{{ $plan['on_track'] ? '✓ ' . $F::short($plan['corpus'] - $plan['required']) . ' extra' : $F::short($plan['gap']) }}</b><small>Needed: {{ $F::short($plan['required']) }}</small></div>
            <div class="mp-kpi"><span>You invest</span><b>₹{{ $F::inr($plan['monthly_total']) }}/mo</b><small>{{ $plan['savings_rate'] !== null ? $plan['savings_rate'] . '% of salary' : '' }}</small></div>
            <div class="mp-kpi"><span>Total invested</span><b>{{ $F::short($plan['invested']) }}</b><small>Growth {{ $F::short($plan['gain']) }}</small></div>
        </div>
        @unless($plan['on_track'])<div class="mp-warn">To get ₹{{ $F::inr($plan['target_monthly']) }} a month, add about <b>₹{{ $F::inr($plan['extra_sip']) }} a month</b> to an equity SIP (stepping up {{ $p['step_up'] }}% a year), or retire later, or lower the target.</div>@endunless

        <section class="mp-card"><h2>How your money grows — and lasts</h2><p class="sub">Building up until {{ $p['retirement_age'] }}, then paying ₹{{ $F::inr($plan['income']) }} a month (rising {{ $p['inflation'] }}% a year) until {{ $p['life_expectancy'] }}.</p>
            <div class="mp-chart"><canvas id="ch-life" aria-label="Corpus by age"></canvas></div></section>

        <section class="mp-card mp-scroll"><h2>What each investment becomes</h2>
            <table class="mp-table"><thead><tr><th>Investment</th><th class="num">Monthly</th><th class="num">Return</th><th class="num">You put in</th><th class="num">Value at {{ $p['retirement_age'] }}</th><th class="num">Growth</th></tr></thead><tbody>
            @foreach($plan['rows'] as $r)<tr><td>{{ $r['label'] }}@if($r['protection'])<div style="font-size:12px;color:var(--mp-muted)">Pure protection — no maturity value</div>@endif</td><td class="num">₹{{ $F::inr($r['monthly']) }}</td><td class="num">{{ $r['return'] }}%</td><td class="num">{{ $F::short($r['invested']) }}</td><td class="num"><b>{{ $F::short($r['corpus']) }}</b></td><td class="num">{{ $r['invested'] ? round($r['corpus'] / max(1, $r['invested']), 1) . '×' : '—' }}</td></tr>@endforeach
            @if($plan['existing'])<tr><td>Savings already built</td><td class="num">—</td><td class="num">{{ $p['post_retirement_return'] }}%</td><td class="num">{{ $F::short($p['current_savings']) }}</td><td class="num"><b>{{ $F::short($plan['existing']) }}</b></td><td></td></tr>@endif
            </tbody></table>
            <div class="mp-donut" style="margin-top:14px"><canvas id="ch-split" aria-label="Share of corpus by investment"></canvas></div>
        </section>

        @if($plan['switch'])
        <section class="mp-card"><h2>Low-return products: what you give up</h2><p class="sub">Same monthly amount in an equity mutual fund at {{ config('money.instruments.equity_mf.return') }}%.</p>
            @foreach($plan['switch'] as $s)<div class="mp-opt"><div><h3>{{ $s['label'] }} · ₹{{ $F::inr($s['monthly']) }}/month</h3><p>Becomes {{ $F::short($s['now']) }} here vs {{ $F::short($s['in_equity']) }} in an equity fund.</p></div><div class="tax"><b>+{{ $F::short($s['difference']) }}</b><small>more at retirement</small></div></div>@endforeach
            <p class="sub">Insurance-cum-investment plans mix two jobs. A pure term plan for cover plus an SIP usually leaves you with more — see <a href="{{ route('money.income') }}#protection">insurance</a>.</p>
        </section>
        @endif

        <div class="mp-two">
            <section class="mp-card"><h2>Suggested mix for you</h2><p class="sub">By age {{ $p['age'] }} and {{ $p['risk'] }} risk.</p><div class="mp-donut"><canvas id="ch-mix" aria-label="Suggested allocation"></canvas></div>
                <a class="mp-btn ghost" style="margin-top:10px;width:100%" href="{{ route('money.funds') }}">See the funds for this mix →</a></section>
            <section class="mp-card" id="safety"><h2>Safety first</h2><p class="sub">Before investing more, make sure these are in place.</p>
                <table class="mp-table"><tbody>
                    <tr><td>Emergency fund (6 months of expenses)</td><td class="num"><b>₹{{ $F::inr($plan['safety']['emergency_fund']) }}</b></td></tr>
                    <tr><td>Term life cover needed</td><td class="num"><b>{{ $F::short($protection['term_cover']) }}</b><div style="font-size:12px;color:var(--mp-muted)">≈ ₹{{ $F::inr($protection['term_premium_month']) }}/month</div></td></tr>
                    <tr><td>Health cover (family floater)</td><td class="num"><b>{{ $F::short($protection['health_cover']) }}</b><div style="font-size:12px;color:var(--mp-muted)">≈ ₹{{ $F::inr($protection['health_premium_month']) }}/month</div></td></tr>
                </tbody></table>
                <a class="mp-btn ghost" style="margin-top:10px;width:100%" href="{{ route('money.income') }}">Retirement income &amp; insurance →</a></section>
        </div>
        <p class="mp-disc">Projections use the return you enter and are not guaranteed; markets go up and down. Educational guidance, not personalised investment advice.</p>
    </div>
</div>
</div>
</div>
<template id="tpl-inv"><div class="mp-row3"><select name="investments[__][type]">@foreach($instruments as $k => $ins)<option value="{{ $k }}">{{ $ins['label'] }}</option>@endforeach</select><input type="number" min="0" name="investments[__][monthly]" placeholder="₹"><input type="number" step="0.1" name="investments[__][return]" placeholder="auto"><input type="number" name="investments[__][step_up]" placeholder="{{ $p['step_up'] }}"><button type="button" class="mp-x" data-remove>✕</button></div></template>
<template id="tpl-fam"><div class="mp-row3" style="grid-template-columns:1.3fr 1fr .7fr 1fr auto"><select name="family[__][relation]">@foreach($relations as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select><input name="family[__][name]" placeholder="Name"><input type="number" name="family[__][age]" placeholder="Age"><input type="number" name="family[__][income]" placeholder="Yearly income"><button type="button" class="mp-x" data-remove>✕</button></div></template>
@endsection
@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(() => {
  let n = 100;
  const adder = (btn, tpl, box) => document.getElementById(btn).addEventListener('click', () => { document.getElementById(box).insertAdjacentHTML('beforeend', document.getElementById(tpl).innerHTML.replaceAll('__', n++)); });
  adder('add-inv', 'tpl-inv', 'inv-rows'); adder('add-fam', 'tpl-fam', 'fam-rows');
  document.getElementById('mp-form').addEventListener('click', e => { if (e.target.matches('[data-remove]')) e.target.closest('.mp-row3').remove(); });
  const short = v => { const a = Math.abs(v); return a >= 1e7 ? '₹' + (v / 1e7).toFixed(2) + ' Cr' : a >= 1e5 ? '₹' + (v / 1e5).toFixed(1) + ' L' : '₹' + Math.round(v).toLocaleString('en-IN'); };
  const build = @json($plan['series']), draw = @json($plan['drawdown']);
  const ages = [...build.map(b => b.age), ...draw.slice(1).map(d => d.age)];
  new Chart(document.getElementById('ch-life'), { type: 'line', data: { labels: ages, datasets: [
      { label: 'Building corpus', data: [...build.map(b => b.corpus), ...draw.slice(1).map(() => null)], borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', fill: true, tension: .3, pointRadius: 0 },
      { label: 'After retirement (withdrawing)', data: [...build.slice(0, -1).map(() => null), ...draw.map(d => d.balance)], borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,.10)', fill: true, tension: .3, pointRadius: 0 } ] },
    options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { title: i => 'Age ' + i[0].label, label: c => c.raw == null ? null : ' ' + c.dataset.label + ': ' + short(c.raw) } } },
      scales: { y: { ticks: { callback: v => short(v) } }, x: { ticks: { maxTicksLimit: 12 } } } } });
  const rows = @json(collect($plan['rows'])->where('corpus', '>', 0)->values());
  const pal = ['#2563eb', '#22c55e', '#f97316', '#a855f7', '#06b6d4', '#eab308', '#ef4444', '#64748b'];
  new Chart(document.getElementById('ch-split'), { type: 'doughnut', data: { labels: rows.map(r => r.label), datasets: [{ data: rows.map(r => r.corpus), backgroundColor: pal, borderWidth: 0 }] },
    options: { maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'right' }, tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + short(c.raw) } } } } });
  const mix = @json($plan['allocation']);
  new Chart(document.getElementById('ch-mix'), { type: 'doughnut', data: { labels: ['Equity', 'Debt & hybrid', 'Gold'], datasets: [{ data: [mix.equity, mix.debt, mix.gold], backgroundColor: ['#2563eb', '#22c55e', '#eab308'], borderWidth: 0 }] },
    options: { maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'right' }, tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + c.raw + '%' } } } } });
})();
</script>
@endpush
