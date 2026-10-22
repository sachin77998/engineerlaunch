@extends('layouts.site')
@section('title', 'Retirement income & insurance — monthly pension plan and cover you need')
@php($F = \App\Services\Money\Format::class)
@section('content')
<div class="mp">
@include('money._layout', ['mpTitle' => 'Monthly income after retirement', 'mpSub' => 'Turn your corpus into a pension using government schemes, annuities and SWP — and check the insurance you need now.'])
<div class="mp-wrap">
<div class="mp-grid">
    <form class="mp-card" method="get" action="{{ route('money.income') }}">
        <h2>Your retirement money</h2><p class="sub">Pre-filled from your <a href="{{ route('money.index') }}">retirement plan</a>.</p>
        <div class="mp-f"><label>Corpus at retirement ₹</label><input type="number" name="corpus" min="0" value="{{ (int) $corpus }}"></div>
        <div class="mp-f"><label>Monthly income wanted ₹</label><input type="number" name="target" min="0" value="{{ (int) $inc['target'] }}"></div>
        <button class="mp-btn" style="width:100%">Build income plan</button>
        <p class="sub" style="margin-top:12px">Risk appetite: <b>{{ ucfirst($p['risk']) }}</b> · {{ $inc['couple'] ? 'Couple (both can invest in SCSS and POMIS)' : 'Single investor' }}</p>
    </form>
    <div>
        <div class="mp-kpis">
            <div class="mp-kpi hl"><span>Monthly income</span><b>₹{{ $F::inr($inc['monthly']) }}</b><small>₹{{ $F::inr($inc['monthly_after_tax']) }} after tax</small></div>
            <div class="mp-kpi {{ $inc['shortfall'] ? 'bad' : 'ok' }}"><span>Target</span><b>₹{{ $F::inr($inc['target']) }}</b><small>{{ $inc['shortfall'] ? 'Short by ₹' . $F::inr($inc['shortfall']) : 'Covered ✓' }}</small></div>
            <div class="mp-kpi"><span>Yearly tax on it</span><b>₹{{ $F::inr($inc['annual_tax']) }}</b><small>new regime, age 60+</small></div>
            <div class="mp-kpi"><span>Kept liquid</span><b>{{ $F::short($inc['emergency']) }}</b><small>2 years of expenses</small></div>
        </div>
        <section class="mp-card"><h2>Where to put the corpus</h2><p class="sub">Safest first; the SWP part keeps growing and protects against inflation.</p>
            <div class="mp-two" style="align-items:center"><div class="mp-donut"><canvas id="ch-buckets" aria-label="Corpus split"></canvas></div><div class="mp-chart" style="height:220px"><canvas id="ch-monthly" aria-label="Monthly income by source"></canvas></div></div>
            <div class="mp-scroll"><table class="mp-table" style="margin-top:10px"><thead><tr><th>Product</th><th class="num">Invest</th><th class="num">Rate</th><th class="num">Monthly</th><th>Payout</th><th>Tax · risk</th></tr></thead><tbody>
            @foreach($inc['buckets'] as $b)<tr><td><b>{{ $b['label'] }}</b></td><td class="num">{{ $F::short($b['amount']) }}</td><td class="num">{{ $b['rate'] }}%{{ $b['key'] === 'swp' ? ' withdrawal' : '' }}</td><td class="num"><b>₹{{ $F::inr($b['monthly']) }}</b></td><td>{{ ucfirst($b['payout']) }}</td><td style="font-size:12.5px;color:var(--mp-muted)">{{ $b['tax'] }} · {{ $b['risk'] }}</td></tr>@endforeach
            </tbody></table></div>
        </section>
        <section class="mp-card"><h2>Plans that pay a monthly pension</h2><p class="sub">Example plans by type. Compare current annuity rates on the insurer's site before buying.</p>
            <div class="mp-two">
                <div><h3 style="font-size:14px;margin:0 0 6px">Start pension now (immediate annuity)</h3><ul style="margin:0;padding-left:18px;line-height:1.8">@foreach($inc['plans']['immediate_annuity'] as $x)<li>{{ $x }}</li>@endforeach</ul></div>
                <div><h3 style="font-size:14px;margin:0 0 6px">Pay now, pension later (deferred annuity)</h3><ul style="margin:0;padding-left:18px;line-height:1.8">@foreach($inc['plans']['deferred_annuity'] as $x)<li>{{ $x }}</li>@endforeach</ul></div>
                <div><h3 style="font-size:14px;margin:0 0 6px">Low-cost pension while working</h3><ul style="margin:0;padding-left:18px;line-height:1.8">@foreach($inc['plans']['nps'] as $x)<li>{{ $x }} — extra ₹50,000 deduction u/s 80CCD(1B); at 60, 60% is tax-free and 40% buys an annuity</li>@endforeach</ul></div>
            </div>
        </section>
    </div>
</div>

<section class="mp-card" id="protection"><h2>Insurance you need today</h2><p class="sub">Based on salary ₹{{ $F::inr($p['monthly_salary']) }}/month, age {{ $p['age'] }}, loans ₹{{ $F::inr($p['liabilities']) }}.</p>
    <div class="mp-kpis">
        <div class="mp-kpi hl"><span>Term life cover</span><b>{{ $F::short($protection['term_cover']) }}</b><small>≈ ₹{{ $F::inr($protection['term_premium_month']) }}/month until {{ $p['retirement_age'] }}</small></div>
        <div class="mp-kpi"><span>Health cover</span><b>{{ $F::short($protection['health_cover']) }}</b><small>≈ ₹{{ $F::inr($protection['health_premium_month']) }}/month · 80D deduction</small></div>
        @if($protection['mixed_premium'])<div class="mp-kpi ok"><span>Freed if you switch</span><b>₹{{ $F::inr($protection['freed_if_term']) }}/mo</b><small>from ₹{{ $F::inr($protection['mixed_premium']) }} in endowment / ULIP</small></div>@endif
    </div>
    @if($protection['mixed_premium'])<div class="mp-note">You pay ₹{{ $F::inr($protection['mixed_premium']) }} a month into insurance-cum-investment plans. A term plan gives far bigger cover for about ₹{{ $F::inr($protection['term_premium_month']) }}; investing the remaining ₹{{ $F::inr($protection['freed_if_term']) }} in an SIP usually builds a larger corpus. Check surrender charges before stopping an existing policy.</div>@endif
    @if(!empty($inc['insurers']))
        <div class="mp-scroll"><table class="mp-table"><thead><tr><th>Insurer</th><th>Type</th><th class="num">Claim settlement</th><th class="num">Solvency</th></tr></thead><tbody>
        @foreach($inc['insurers'] as $sector => $list)@foreach($list as $ins)<tr><td><b>{{ $ins->name }}</b></td><td>{{ ucfirst($sector) }}</td><td class="num">{{ $ins->claim_settlement_ratio }}%</td><td class="num">{{ $ins->solvency_ratio ?? '—' }}</td></tr>@endforeach @endforeach
        </tbody></table></div>
    @endif
    <a class="mp-btn ghost" href="{{ route('insurance.index') }}" style="margin-top:10px">Compare insurance plans →</a>
</section>
<p class="mp-disc">Scheme rates change every quarter; SCSS and POMIS limits are per person. Educational guidance, not personalised investment advice.</p>
</div>
</div>
@endsection
@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(() => {
  const b = @json($inc['buckets']), pal = ['#2563eb', '#22c55e', '#f97316', '#a855f7', '#06b6d4', '#eab308'];
  const short = v => v >= 1e7 ? '₹' + (v / 1e7).toFixed(2) + ' Cr' : v >= 1e5 ? '₹' + (v / 1e5).toFixed(1) + ' L' : '₹' + Math.round(v).toLocaleString('en-IN');
  new Chart(document.getElementById('ch-buckets'), { type: 'doughnut', data: { labels: b.map(x => x.label), datasets: [{ data: b.map(x => x.amount), backgroundColor: pal, borderWidth: 0 }] },
    options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + short(c.raw) } } } } });
  new Chart(document.getElementById('ch-monthly'), { type: 'bar', data: { labels: b.map(x => x.label.split(' (')[0].replace('Senior Citizens Savings Scheme', 'SCSS').replace('Post Office Monthly Income Scheme', 'POMIS')), datasets: [{ label: 'Monthly ₹', data: b.map(x => x.monthly), backgroundColor: pal, borderRadius: 6 }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: v => '₹' + v.toLocaleString('en-IN') } }, x: { ticks: { font: { size: 10 } } } } } });
})();
</script>
@endpush
