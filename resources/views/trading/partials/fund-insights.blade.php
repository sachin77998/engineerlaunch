@php
    $equity = in_array($scheme->asset_class, ['Equity'], true) || preg_match('/equity|elss|index|arbitrage/i', (string) $scheme->category);
    $debt = $scheme->asset_class === 'Debt' || preg_match('/debt|liquid|gilt|bond|money market|overnight/i', (string) $scheme->category);
    $name = $scheme->displayName();
    $head = $scheme->return_3y ?? $scheme->return_1y;
    $headLabel = $scheme->return_3y !== null ? '3Y annualised' : '1Y';
    $chg = $scheme->dayChange();
    $fmt = fn ($v, $d = 1) => $v === null ? '—' : ($v >= 0 ? '+' : '') . number_format($v, $d) . '%';
    $taxText = $equity
        ? 'Redeemed within 1 year: gains taxed at 20% (short-term). After 1 year: gains above ₹1.25 lakh a year taxed at 12.5% (long-term).'
        : ($debt ? 'Gains are added to your income and taxed at your income-tax slab rate, whatever the holding period (units bought from 1 April 2023).'
                 : 'Redeemed within 2 years: gains taxed at your income-tax slab rate. After 2 years: gains taxed at 12.5%.');
@endphp
<style>
.fg-head b{font-size:34px;color:#00a676;font-weight:800}.fg-head b.down{color:#e5484d}.fg-head span{color:#7c7e8c;font-weight:700;margin-left:6px}
.fg-chart{position:relative;height:300px;margin-top:10px}
.fg-ranges{display:flex;gap:10px;justify-content:center;margin-top:12px}.fg-ranges button{min-width:52px;padding:8px 12px;border:1px solid #e3e5ea;border-radius:999px;background:#fff;font:700 13px inherit;color:#44475b;cursor:pointer}
.fg-ranges button.on{border-color:#44475b;background:#f0f1f4}
.fg-sip{width:100%;border-collapse:collapse}.fg-sip th,.fg-sip td{padding:16px 12px;border-bottom:1px solid #eef0f3;text-align:right}.fg-sip th:first-child,.fg-sip td:first-child{text-align:left}
.fg-sip th{color:#7c7e8c;font-weight:600}.fg-sip td.become{color:#5367c4;font-weight:700}.fg-sip .bars{text-align:left;width:26%}.fg-sip .bars i{display:block;height:8px;border-radius:0 6px 6px 0;margin:3px 0}
.fg-sip .bars i.inv{background:#44475b}.fg-sip .bars i.val{background:#8b8fd6}
.fg-amount{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:6px}.fg-amount input[type=range]{flex:1;min-width:200px;accent-color:#00a676}
.fg-tabs{display:flex;gap:8px;margin-bottom:12px}.fg-tabs button{padding:8px 14px;border:1px solid #e3e5ea;border-radius:999px;background:#fff;font:700 13px inherit;cursor:pointer}.fg-tabs button.on{border-color:#44475b;background:#f0f1f4}
.fg-rank{width:100%;border-collapse:collapse;border:1px solid #eef0f3;border-radius:12px;overflow:hidden}.fg-rank th,.fg-rank td{padding:14px;border-bottom:1px solid #eef0f3;text-align:right}.fg-rank th{background:#f7f8fa;color:#44475b}
.fg-rank th:first-child,.fg-rank td:first-child{text-align:left}
.fg-ratios{display:grid;grid-template-columns:1fr 1fr;gap:0 40px}.fg-ratios div{display:flex;justify-content:space-between;padding:10px 0;color:#44475b}.fg-ratios b{color:#1b1d29}
@media(max-width:640px){.fg-ratios{grid-template-columns:1fr}}
.fg-faq details{border-bottom:1px solid #eef0f3;padding:16px 0}.fg-faq summary{cursor:pointer;font-size:16px;color:#1b1d29;list-style:none;display:flex;justify-content:space-between}
.fg-faq summary:after{content:'⌄';color:#7c7e8c}.fg-faq details[open] summary:after{content:'⌃'}.fg-faq p{margin:10px 0 0;color:#44475b;line-height:1.6}
</style>

<section class="dt-card" id="fg-perf">
    <div class="fg-head"><b class="{{ ($head ?? 0) < 0 ? 'down' : '' }}">{{ $fmt($head, 2) }}</b><span>{{ $headLabel }}</span>
        @if($chg !== null)<div class="{{ $chg >= 0 ? 'up' : 'down' }}" style="font-weight:700;margin-top:4px">{{ $chg >= 0 ? '+' : '' }}{{ $chg }}% <span style="margin:0">1D</span></div>@endif</div>
    <p class="dt-muted" id="fg-range-label" style="margin:6px 0 0"></p>
    <div class="fg-chart"><canvas id="fg-nav" aria-label="NAV history"></canvas></div>
    <div class="fg-ranges" id="fg-ranges">@foreach(['1M' => 30, '6M' => 182, '1Y' => 365, '3Y' => 1095, '5Y' => 1826, 'All' => 0] as $l => $d)<button type="button" data-days="{{ $d }}" class="{{ $l === '3Y' ? 'on' : '' }}">{{ $l }}</button>@endforeach</div>
</section>

<section class="dt-card" id="fg-sip-card">
    <h2>Return calculator</h2>
    <div class="fg-amount"><span>Monthly SIP</span><input type="range" id="fg-sip-amt" min="100" max="100000" step="100" value="1000" aria-label="Monthly SIP amount"><b id="fg-sip-label">₹1,000</b></div>
    <table class="fg-sip"><thead><tr><th>Over the past</th><th>Total investment</th><th>Would've become</th><th class="bars">Historic returns</th><th>Returns</th></tr></thead><tbody id="fg-sip-rows"><tr><td colspan="5" class="dt-muted">Loading NAV history…</td></tr></tbody></table>
    <p class="dt-muted" style="margin:10px 0 0">A SIP on the 1st of every month at that day's NAV. Past returns do not guarantee future returns.</p>
</section>

<section class="dt-card">
    <h2>Returns and rankings</h2>
    <div class="fg-tabs"><button type="button" class="on" data-mode="ann">Annualised returns</button><button type="button" data-mode="abs">Absolute returns</button></div>
    <table class="fg-rank">
        <thead><tr><th>Name</th>@foreach(['return_6m' => '6M', 'return_1y' => '1Y', 'return_3y' => '3Y', 'return_5y' => '5Y'] as $k => $l)<th>{{ $l }}</th>@endforeach</tr></thead>
        <tbody>
        @php
            $years = ['return_6m' => .5, 'return_1y' => 1, 'return_3y' => 3, 'return_5y' => 5];
            $abs = fn ($v, $y) => $v === null ? null : ($y <= 1 ? $v : (pow(1 + $v / 100, $y) - 1) * 100);
            $cell = fn ($v, $y) => '<td data-ann="' . e($fmt($v)) . '" data-abs="' . e($fmt($abs($v, $y))) . '" class="' . ($v === null ? '' : ($v >= 0 ? 'up' : 'down')) . '">' . $fmt($v) . '</td>';
        @endphp
        <tr><td>Fund returns</td>@foreach($years as $k => $y){!! $cell($scheme->{$k}, $y) !!}@endforeach</tr>
        <tr><td>Category average ({{ $scheme->sub_category }})</td>@foreach($years as $k => $y){!! $cell($ranks[$k]['avg'] ?? null, $y) !!}@endforeach</tr>
        <tr><td>Rank ({{ $scheme->sub_category }})</td>@foreach($years as $k => $y)<td>{{ isset($ranks[$k]) ? $ranks[$k]['rank'] . ' / ' . $ranks[$k]['of'] : '—' }}</td>@endforeach</tr>
        </tbody>
    </table>
    <p class="dt-muted" style="margin:10px 0 0">Ranked among {{ $scheme->sub_category }} funds, {{ $scheme->plan }} plan, {{ $scheme->option }} option. 3Y and 5Y are annualised.</p>
</section>

@if($ratios)
<section class="dt-card">
    <h2>Advanced ratios</h2>
    <div class="fg-ratios">
        <div><span>Volatility (annualised)</span><b>{{ number_format($ratios['volatility'], 2) }}%</b></div>
        <div><span>Sharpe</span><b>{{ $ratios['sharpe'] !== null ? number_format($ratios['sharpe'], 2) : '—' }}</b></div>
        <div><span>Maximum drawdown</span><b class="down">{{ number_format($ratios['max_drawdown'], 2) }}%</b></div>
        <div><span>Sortino</span><b>{{ $ratios['sortino'] !== null ? number_format($ratios['sortino'], 2) : '—' }}</b></div>
    </div>
    <p class="dt-muted" style="margin:8px 0 0">Calculated from daily NAVs over {{ $ratios['period'] }}, with a 6.5% risk-free rate.</p>
</section>
@endif

<section class="dt-card">
    <h2>Minimum investments</h2>
    <div class="fg-ratios">
        <div><span>Min. for 1st investment</span><b>₹{{ number_format(max(100, (float) $scheme->min_purchase)) }}</b></div>
        <div><span>Min. for SIP</span><b>₹{{ number_format(max(100, (float) $scheme->min_purchase)) }}</b></div>
        <div><span>Min. for 2nd investment</span><b>₹{{ number_format(max(100, (float) ($scheme->min_additional ?: $scheme->min_purchase))) }}</b></div>
    </div>
</section>

<section class="dt-card">
    <h2>Exit load, stamp duty and tax</h2>
    <h3 style="margin:6px 0 2px">Exit load</h3><p class="dt-muted" style="margin:0">{{ $scheme->exit_load ?: 'Not published in the AMFI data for this scheme. See the scheme information document on the fund house website.' }}</p>
    <h3 style="margin:14px 0 2px">Stamp duty on investment: 0.005% (from 1 July 2020)</h3>
    <h3 style="margin:14px 0 2px">Tax implication</h3><p class="dt-muted" style="margin:0">{{ $taxText }}</p>
</section>

<section class="dt-card fg-faq">
    <h2>FAQs</h2>
    <details><summary>How to invest in {{ $name }}?</summary><p>Open your Do Trading account (KYC), choose Monthly SIP or One-time in the Invest panel, enter the amount and confirm the order with the OTP sent to your email.</p></details>
    <details><summary>What kind of returns does {{ $name }} provide?</summary><p>{{ collect(['6M' => $scheme->return_6m, '1Y' => $scheme->return_1y, '3Y (annualised)' => $scheme->return_3y, '5Y (annualised)' => $scheme->return_5y])->filter(fn ($v) => $v !== null)->map(fn ($v, $k) => $k . ': ' . $fmt($v, 2))->implode(' · ') ?: 'Returns appear once enough NAV history is available.' }}</p></details>
    <details><summary>What is the NAV of {{ $name }}?</summary><p>₹{{ number_format($scheme->nav, 4) }} as on {{ $scheme->nav_date?->format('d M Y') }}.</p></details>
    <details><summary>How risky is {{ $name }}?</summary><p>Its riskometer level is {{ $scheme->risk_level ?? 'not published' }}.@if($ratios) Over {{ $ratios['period'] }} its NAV moved {{ number_format($ratios['volatility'], 1) }}% a year (volatility) and fell at most {{ number_format(abs($ratios['max_drawdown']), 1) }}% from a peak.@endif</p></details>
    <details><summary>How to redeem {{ $name }}?</summary><p>In the Invest panel choose Redeem, enter the units (or redeem all) and confirm with the OTP. The amount reaches your bank account after the fund house processes it, usually 1–3 working days.</p></details>
    <details><summary>Can I invest in SIP and lump sum of {{ $name }}?</summary><p>Yes. The minimum is ₹{{ number_format(max(100, (float) $scheme->min_purchase)) }} for a one-time investment or a monthly SIP.</p></details>
    <details><summary>How is {{ $name }} taxed?</summary><p>{{ $taxText }}</p></details>
</section>

@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(() => {
    let points = [], chart;
    const inr = v => '₹' + Math.round(v).toLocaleString('en-IN');
    const canvas = document.getElementById('fg-nav'), label = document.getElementById('fg-range-label');
    const draw = days => {
        if (!points.length || !window.Chart) return;
        let set = points;
        if (days) { const from = new Date(points[points.length - 1][0]); from.setDate(from.getDate() - days); set = points.filter(p => new Date(p[0]) >= from); }
        const first = set[0][1], last = set[set.length - 1][1], ch = (last / first - 1) * 100, up = ch >= 0;
        label.innerHTML = `<b class="${up ? 'up' : 'down'}">${up ? '+' : ''}${ch.toFixed(2)}%</b> from ${new Date(set[0][0]).toLocaleDateString('en-IN', {day: 'numeric', month: 'short', year: 'numeric'})}`;
        chart?.destroy();
        chart = new Chart(canvas, {type: 'line', data: {labels: set.map(p => p[0]), datasets: [{data: set.map(p => p[1]), borderColor: up ? '#00b386' : '#e5484d', borderWidth: 2, pointRadius: 0, tension: .1, fill: false}]},
            options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
                plugins: {legend: {display: false}, tooltip: {callbacks: {title: i => new Date(i[0].label).toLocaleDateString('en-IN', {day: 'numeric', month: 'short', year: 'numeric'}), label: c => ' NAV ₹' + c.raw.toFixed(4)}}},
                scales: {x: {display: false}, y: {display: false}}}});
    };
    document.querySelectorAll('#fg-ranges button').forEach(b => b.addEventListener('click', () => { document.querySelectorAll('#fg-ranges button').forEach(x => x.classList.toggle('on', x === b)); draw(+b.dataset.days); }));

    // SIP on the 1st NAV date of each month for the last N months, valued at the latest NAV.
    const sipRows = () => {
        if (!points.length) return;
        const amt = +document.getElementById('fg-sip-amt').value, last = points[points.length - 1];
        document.getElementById('fg-sip-label').textContent = inr(amt);
        const firstOfMonth = {}; points.forEach(p => { const k = p[0].slice(0, 7); if (!firstOfMonth[k]) firstOfMonth[k] = p; });
        const months = Object.keys(firstOfMonth).sort();
        const rows = [[3, '3 months'], [6, '6 months'], [12, '1 year'], [36, '3 years'], [60, '5 years']].filter(([m]) => months.length > m).map(([m, text]) => {
            const used = months.slice(-m); let units = 0; used.forEach(k => units += amt / firstOfMonth[k][1]);
            const invested = amt * m, value = units * last[1]; return {text, invested, value, ret: (value / invested - 1) * 100};
        });
        const max = Math.max(...rows.map(r => Math.max(r.invested, r.value)), 1);
        document.getElementById('fg-sip-rows').innerHTML = rows.map(r => `<tr><td>${r.text}</td><td>${inr(r.invested)}</td><td class="become">${inr(r.value)}</td>
            <td class="bars"><i class="inv" style="width:${r.invested / max * 100}%"></i><i class="val" style="width:${r.value / max * 100}%"></i></td>
            <td class="${r.ret >= 0 ? 'up' : 'down'}" style="font-weight:700">${r.ret >= 0 ? '+' : ''}${r.ret.toFixed(2)}%</td></tr>`).join('') || '<tr><td colspan="5" class="dt-muted">Not enough NAV history yet.</td></tr>';
    };
    document.getElementById('fg-sip-amt').addEventListener('input', sipRows);

    document.querySelectorAll('.fg-tabs button').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('.fg-tabs button').forEach(x => x.classList.toggle('on', x === b));
        document.querySelectorAll('.fg-rank td[data-ann]').forEach(td => td.textContent = td.dataset[b.dataset.mode]);
    }));

    fetch(@json(route('trading.fund.history', $scheme))).then(r => r.json()).then(d => {
        points = d.points || [];
        if (!points.length) { canvas.parentElement.innerHTML = '<p class="dt-muted">NAV history is not available right now.</p>'; return; }
        draw(1095); sipRows();
    }).catch(() => {});
})();
</script>
@endpush
