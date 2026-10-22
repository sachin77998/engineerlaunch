@extends('layouts.site')
@section('title', 'Tax saver — capital gains, family transfers and salary deductions')
@php($F = \App\Services\Money\Format::class)
@section('content')
<div class="mp">
@include('money._layout', ['mpTitle' => 'Save tax legally', 'mpSub' => 'Compare every way to cut the tax on your gains and salary, with the Income-tax section for each. Rules for ' . config('money.tax_year') . '.'])
<div class="mp-wrap">
<div class="mp-grid">
    <form class="mp-card" method="get" action="{{ route('money.tax') }}">
        <h2>Tax on your gains</h2><p class="sub">Example: invested for years and earned ₹50 lakh.</p>
        <div class="mp-f"><label>What did you sell / will sell?</label><select name="asset">@foreach(['equity' => 'Equity mutual funds / shares', 'debt' => 'Debt mutual funds (bought after Apr 2023)', 'property' => 'House / land', 'gold' => 'Gold / gold funds', 'fd' => 'FD / bond interest'] as $k => $v)<option value="{{ $k }}" @selected($cgIn['asset'] === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="mp-row">
            <div class="mp-f"><label>Gain (profit) ₹</label><input type="number" name="gain" min="0" value="{{ (int) $cgIn['gain'] }}" required></div>
            <div class="mp-f"><label>Held for (months)</label><input type="number" name="holding_months" min="0" value="{{ $cgIn['holding_months'] }}"></div>
            <div class="mp-f"><label>Your yearly salary ₹</label><input type="number" name="cg_salary" min="0" value="{{ (int) $cgIn['salary'] }}"></div>
            <div class="mp-f"><label>Other yearly income ₹</label><input type="number" name="other_income" min="0" value="{{ (int) $cgIn['other_income'] }}"></div>
            <div class="mp-f"><label>Your age</label><input type="number" name="cg_age" min="18" max="100" value="{{ $cgIn['age'] }}"></div>
            <div class="mp-f"><label>Spread sale over (years)</label><input type="number" name="years" min="1" max="10" value="{{ $cgIn['years'] }}"></div>
        </div>
        <div class="mp-f"><label>Tax regime</label><select name="regime"><option value="new" @selected($cgIn['regime'] === 'new')>New</option><option value="old" @selected($cgIn['regime'] === 'old')>Old</option></select></div>
        <p class="sub">Family used: {{ collect($p['family'])->map(fn ($m) => ($m['name'] ?: $relations[$m['relation']]) . ($m['income'] ? ' (₹' . $F::inr($m['income']) . '/yr)' : ''))->implode(', ') ?: 'none yet' }} · <a href="{{ route('money.index') }}">edit family</a></p>
        <button class="mp-btn" style="width:100%">Compare ways to save</button>
    </form>
    <div>
        <div class="mp-kpis">
            <div class="mp-kpi bad"><span>Tax if sold at once</span><b>₹{{ $F::inr($cg['base_tax']) }}</b><small>Section {{ $cg['section'] }} · {{ $cg['long_term'] ? 'long-term' : 'short-term' }}</small></div>
            <div class="mp-kpi ok"><span>Lowest tax</span><b>₹{{ $F::inr($cg['best']['tax']) }}</b><small>{{ $cg['best']['title'] }}</small></div>
            <div class="mp-kpi hl"><span>You can save</span><b>₹{{ $F::inr($cg['best']['saved']) }}</b><small>on a gain of {{ $F::short($cg['gain']) }}</small></div>
        </div>
        <section class="mp-card"><h2>Ways to reduce the tax</h2><p class="sub">Lowest tax first. Combine them — e.g. spread the sale and gift part to adult children.</p>
            <div class="mp-chart" style="height:{{ max(180, count($cg['options']) * 38) }}px"><canvas id="ch-tax" aria-label="Tax under each option"></canvas></div>
            @foreach($cg['options'] as $o)
                <div class="mp-opt {{ $loop->first && $o['saved'] > 0 ? 'best' : '' }}">
                    <div><h3>{{ $o['title'] }}<span class="mp-sec">{{ $o['section'] }}</span></h3><p>{{ $o['how'] }}</p>
                        @if(!empty($o['split']))<p style="margin-top:6px">@foreach($o['split'] as $s)<span class="mp-pill hold" style="margin:2px 4px 0 0">{{ $s['name'] }}: ₹{{ $F::inr($s['tax']) }}</span>@endforeach</p>@endif</div>
                    <div class="tax"><b>₹{{ $F::inr($o['tax']) }}</b>@if($o['saved'] > 0)<small>saves ₹{{ $F::inr($o['saved']) }}</small>@else<small style="color:var(--mp-muted)">no saving</small>@endif</div>
                </div>
            @endforeach
            <div class="mp-warn"><b>Wife / minor children:</b> moving money to them does <b>not</b> save tax — income and gains from gifted money are added back to yours (s.64 clubbing). Gifts to <b>adult children, parents, brothers and sisters</b> are tax-free (s.56(2)(x)) and they pay tax at their own rate. Keep a gift deed and transfer through banking channels.</div>
        </section>
    </div>
</div>

<div class="mp-grid">
    <form class="mp-card" method="get" action="{{ route('money.tax') }}">
        @foreach(request()->only(['asset', 'gain', 'holding_months', 'cg_salary', 'other_income', 'cg_age', 'years', 'regime']) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
        <h2>Tax on your salary</h2><p class="sub">Yearly amounts. Pre-filled from your investments.</p>
        <div class="mp-row">
            <div class="mp-f"><label>Monthly salary ₹</label><input type="number" name="monthly_salary" min="0" value="{{ (int) $salIn['monthly_salary'] }}"></div>
            <div class="mp-f"><label>Basic pay % of salary</label><input type="number" name="basic_pct" min="10" max="100" value="{{ $salIn['basic_pct'] }}"></div>
            <div class="mp-f"><label>80C (EPF, PPF, ELSS, LIC…)</label><input type="number" name="d_80c" min="0" value="{{ (int) $salIn['d_80c'] }}"></div>
            <div class="mp-f"><label>80CCD(1B) own NPS</label><input type="number" name="d_nps" min="0" value="{{ (int) $salIn['d_nps'] }}"></div>
            <div class="mp-f"><label>80D health insurance</label><input type="number" name="d_80d" min="0" value="{{ (int) $salIn['d_80d'] }}"></div>
            <div class="mp-f"><label>HRA exemption</label><input type="number" name="d_hra" min="0" value="{{ (int) $salIn['d_hra'] }}"></div>
            <div class="mp-f"><label>24(b) home-loan interest</label><input type="number" name="d_home_loan" min="0" value="{{ (int) $salIn['d_home_loan'] }}"></div>
            <div class="mp-f"><label>80CCD(2) employer NPS</label><input type="number" name="employer_nps" min="0" value="{{ (int) $salIn['employer_nps'] }}"></div>
        </div>
        <button class="mp-btn" style="width:100%">Compare regimes</button>
    </form>
    <div>
        <div class="mp-kpis">
            <div class="mp-kpi {{ $sal['better'] === 'new' ? 'ok' : '' }}"><span>New regime</span><b>₹{{ $F::inr($sal['new']['total']) }}</b><small>{{ $sal['new']['effective_rate'] }}% of income</small></div>
            <div class="mp-kpi {{ $sal['better'] === 'old' ? 'ok' : '' }}"><span>Old regime</span><b>₹{{ $F::inr($sal['old']['total']) }}</b><small>after ₹{{ $F::inr($sal['old']['deductions']) }} deductions</small></div>
            <div class="mp-kpi hl"><span>Choose the {{ $sal['better'] }} regime</span><b>Save ₹{{ $F::inr($sal['difference']) }}</b><small>Take-home ₹{{ $F::inr($sal['monthly_take_home_new']) }}/month (new)</small></div>
        </div>
        <section class="mp-card"><h2>Deductions you have not used yet</h2><p class="sub">Only the old regime allows these (except employer NPS 80CCD(2), allowed in both).</p>
            @forelse($sal['room'] as $r)
                <div class="mp-opt"><div><h3>Section {{ $r['section'] }}<span class="mp-sec">₹{{ $F::inr($r['unused']) }} unused</span></h3><p>{{ $r['how'] }}</p></div><div class="tax"><b>₹{{ $F::inr($r['saves']) }}</b><small>tax saved (old regime)</small></div></div>
            @empty<div class="mp-note">You already use the main deductions fully.</div>@endforelse
            <table class="mp-table" style="margin-top:8px"><tbody>
                @foreach(config('money.tax.deductions') as $sec => $d)<tr><td><b>{{ $sec }}</b></td><td>{{ $d['label'] }}</td><td class="num">{{ isset($d['limit']) && $d['limit'] ? '₹' . $F::inr($d['limit']) : (isset($d['limit_pct_basic']) ? $d['limit_pct_basic'] . '% of basic' : 'No cap') }}</td><td class="num">{{ implode(' / ', array_map('ucfirst', $d['regimes'])) }}</td></tr>@endforeach
            </tbody></table>
        </section>
    </div>
</div>
<p class="mp-disc">Estimates for a resident individual; surcharge marginal relief is not applied. Confirm with a chartered accountant before filing. Educational guidance, not tax or investment advice.</p>
</div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(() => {
  const o = @json($cg['options']);
  new Chart(document.getElementById('ch-tax'), { type: 'bar', data: { labels: o.map(x => x.title), datasets: [{ label: 'Tax', data: o.map(x => x.tax), backgroundColor: o.map((x, i) => i === 0 && x.saved > 0 ? '#22c55e' : (x.saved > 0 ? '#60a5fa' : '#f87171')), borderRadius: 6 }] },
    options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' Tax: ₹' + Math.round(c.raw).toLocaleString('en-IN') } } },
      scales: { x: { ticks: { callback: v => '₹' + (v >= 1e5 ? (v / 1e5).toFixed(1) + ' L' : v.toLocaleString('en-IN')) } }, y: { ticks: { autoSkip: false, font: { size: 11 } } } } } });
})();
</script>
@endpush
