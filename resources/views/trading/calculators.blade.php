@extends('layouts.site')
@section('title', 'Calculators — SIP, lumpsum, FD, RD, EMI, loan amortization and margin')
@push('styles')@include('trading.partials.styles')
<style>
.cz{display:grid;grid-template-columns:minmax(300px,420px) 1fr;gap:22px;align-items:start}
.cz-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}
.cz-tabs button{border:1px solid #dbe3ef;background:#fff;color:#1f2a44;border-radius:999px;padding:9px 16px;font:inherit;font-weight:650;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:.15s}
.cz-tabs button:hover{border-color:#94a3b8}.cz-tabs button.on{background:#10213e;border-color:#10213e;color:#fff;box-shadow:0 6px 16px rgba(16,33,62,.18)}
.cz-card{background:#fff;border:1px solid #e3e9f2;border-radius:18px;padding:22px;box-shadow:0 10px 30px rgba(16,33,62,.06)}
.cz-form[hidden]{display:none}.cz-field{margin-bottom:20px}
.cz-row{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}.cz-row label{font-weight:650;color:#344054}
.cz-num{display:flex;align-items:center;background:#eef4ff;border:1px solid #d5e3fb;border-radius:9px;padding:0 10px;min-width:130px}
.cz-num span{color:#2563eb;font-weight:700}.cz-num input{border:0;background:transparent;width:100%;padding:8px 4px;font:inherit;font-weight:750;color:#10213e;text-align:right;outline:0}
.cz-num select{border:0;background:transparent;font:inherit;font-weight:700;padding:8px 0;outline:0}
input[type=range].cz-range{width:100%;accent-color:#2563eb;height:6px;cursor:pointer}
.cz-hint{font-size:12px;color:#667085;display:flex;justify-content:space-between;margin-top:4px}
.cz-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px}
.cz-kpi{border-radius:14px;padding:14px 16px;background:#f6f9ff;border:1px solid #e3ebf8}.cz-kpi span{font-size:12px;font-weight:700;color:#667085;text-transform:uppercase;letter-spacing:.04em}
.cz-kpi b{display:block;font-size:22px;margin-top:6px;color:#10213e}.cz-kpi.hl{background:linear-gradient(135deg,#10213e,#1d4ed8);border:0}.cz-kpi.hl span{color:#bfd3ff}.cz-kpi.hl b{color:#fff;font-size:26px}
.cz-kpi.green b{color:#0b7a43}.cz-kpi.red b{color:#b42318}
.cz-charts{display:grid;grid-template-columns:260px 1fr;gap:18px;align-items:center}
.cz-donut{position:relative;height:240px}.cz-donut .mid{position:absolute;inset:0;display:grid;place-items:center;text-align:center;pointer-events:none}.cz-donut .mid b{font-size:20px;display:block}.cz-donut .mid small{color:#667085}
.cz-bar{height:280px}.cz-legend{display:flex;gap:16px;flex-wrap:wrap;margin-top:10px;font-size:13px;color:#475467}.cz-legend i{display:inline-block;width:11px;height:11px;border-radius:3px;margin-right:6px;vertical-align:-1px}
.cz-sentence{background:#f0fdf4;border:1px solid #c6f0d5;color:#14532d;border-radius:12px;padding:12px 14px;margin:14px 0 0;font-size:14px;line-height:1.5}
details.cz-table{margin-top:16px}details.cz-table summary{cursor:pointer;font-weight:700;color:#1d4ed8}
.cz-err{background:#fef3f2;color:#b42318;border-radius:10px;padding:10px 12px}
.cz-plan{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:16px;background:linear-gradient(135deg,#fff7e6,#fff);border:1px solid #fde4a8;border-radius:14px;padding:14px 16px}
@media(max-width:980px){.cz{grid-template-columns:1fr}.cz-charts{grid-template-columns:1fr}}
@media (prefers-color-scheme: dark){:root:not([data-theme="light"]) .cz-card{background:#0f1b31;border-color:#22324f}:root:not([data-theme="light"]) .cz-kpi{background:#13223d;border-color:#22324f}:root:not([data-theme="light"]) .cz-kpi b,:root:not([data-theme="light"]) .cz-num input{color:#e6edf8}:root:not([data-theme="light"]) .cz-row label{color:#cbd5e1}:root:not([data-theme="light"]) .cz-num{background:#13223d;border-color:#2b3d5f}:root:not([data-theme="light"]) .cz-tabs button{background:#13223d;color:#e6edf8;border-color:#2b3d5f}}
</style>
@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'Calculators', 'subtitle' => 'See how much you invest, how much you earn and what it pays every month.'])
@php
    // [name, label, min, max, step, default, prefix/suffix]
    $calcs = [
        'sip' => ['SIP', '📈', [['monthly', 'Monthly investment', 500, 200000, 500, 10000, '₹'], ['rate', 'Expected return (% a year)', 1, 30, 0.5, 12, '%'], ['years', 'Time period (years)', 1, 40, 1, 15, 'Yr'], ['step_up', 'Yearly step-up', 0, 30, 1, 0, '%']]],
        'lumpsum' => ['Lumpsum', '💰', [['amount', 'Total investment', 1000, 10000000, 1000, 100000, '₹'], ['rate', 'Expected return (% a year)', 1, 30, 0.5, 12, '%'], ['years', 'Time period (years)', 1, 40, 1, 10, 'Yr']]],
        'fd' => ['Fixed deposit', '🏦', [['amount', 'Deposit amount', 1000, 10000000, 1000, 100000, '₹'], ['rate', 'Interest rate (% a year)', 1, 12, 0.05, 7, '%'], ['years', 'Time period (years)', 1, 10, 1, 5, 'Yr']]],
        'rd' => ['Recurring deposit', '🗓️', [['monthly', 'Monthly deposit', 500, 200000, 500, 5000, '₹'], ['rate', 'Interest rate (% a year)', 1, 12, 0.05, 6.5, '%'], ['years', 'Time period (years)', 1, 10, 1, 5, 'Yr']]],
        'emi' => ['Loan EMI', '🏠', [['principal', 'Loan amount', 50000, 50000000, 50000, 2500000, '₹'], ['rate', 'Interest rate (% a year)', 5, 20, 0.05, 8.5, '%'], ['months', 'Tenure (months)', 6, 360, 6, 240, 'Mo']]],
        'amortization' => ['Prepayment', '📉', [['principal', 'Loan amount', 50000, 50000000, 50000, 2500000, '₹'], ['rate', 'Interest rate (% a year)', 5, 20, 0.05, 8.5, '%'], ['months', 'Tenure (months)', 6, 360, 6, 240, 'Mo'], ['prepayment', 'One-time prepayment', 0, 5000000, 10000, 200000, '₹'], ['prepay_month', 'Prepay in month', 0, 360, 1, 24, 'Mo']]],
        'margin' => ['Margin', '⚖️', [['price', 'Share price', 1, 100000, 0.05, 1200, '₹'], ['quantity', 'Quantity', 1, 100000, 1, 100, 'Qty']]],
    ];
@endphp
<main class="dt-wrap dt-main">
    <div class="cz-tabs" role="tablist">
        @foreach($calcs as $k => [$label, $icon])<button type="button" role="tab" data-calc="{{ $k }}" class="{{ $loop->first ? 'on' : '' }}"><span aria-hidden="true">{{ $icon }}</span>{{ $label }}</button>@endforeach
    </div>
    <div class="cz">
        <section class="cz-card" aria-label="Inputs">
            @foreach($calcs as $k => [$label, $icon, $fields])
                <form class="cz-form" data-form="{{ $k }}" @unless($loop->first) hidden @endunless onsubmit="return false">
                    @if($k === 'margin')
                        <div class="cz-field"><div class="cz-row"><label for="m-symbol">NSE symbol</label><div class="cz-num"><input id="m-symbol" name="symbol" value="RELIANCE" maxlength="30" style="text-transform:uppercase;text-align:left"></div></div></div>
                        <div class="cz-field"><div class="cz-row"><label for="m-product">Product</label><div class="cz-num"><select id="m-product" name="product"><option value="intraday">Intraday (MIS)</option><option value="delivery">Delivery (CNC)</option></select></div></div></div>
                    @endif
                    @if($k === 'fd')
                        <div class="cz-field"><div class="cz-row"><label for="fd-comp">Compounding</label><div class="cz-num"><select id="fd-comp" name="compounding"><option value="4">Quarterly</option><option value="12">Monthly</option><option value="2">Half-yearly</option><option value="1">Yearly</option></select></div></div></div>
                    @endif
                    @foreach($fields as [$name, $flabel, $min, $max, $step, $default, $unit])
                        <div class="cz-field">
                            <div class="cz-row"><label for="{{ $k }}-{{ $name }}">{{ $flabel }}</label>
                                <div class="cz-num">@if($unit === '₹')<span>₹</span>@endif<input type="number" id="{{ $k }}-{{ $name }}" name="{{ $name }}" value="{{ $default }}" min="{{ $min }}" step="{{ $step }}" data-pair>@if($unit !== '₹')<span>{{ $unit }}</span>@endif</div></div>
                            <input class="cz-range" type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" value="{{ $default }}" aria-label="{{ $flabel }}" data-for="{{ $name }}">
                            <div class="cz-hint"><span>{{ $unit === '₹' ? '₹'.number_format($min) : $min }}</span><span>{{ $unit === '₹' ? '₹'.number_format($max) : $max }}</span></div>
                        </div>
                    @endforeach
                    @if($k === 'margin')<div class="cz-field"><div class="cz-row"><label for="m-pct">Margin % (blank = exchange rate)</label><div class="cz-num"><input id="m-pct" type="number" name="margin_pct" step="0.01" min="1" max="100"><span>%</span></div></div></div>@endif
                </form>
            @endforeach
        </section>
        <section class="cz-card" aria-live="polite">
            <div class="cz-kpis" id="cz-kpis"></div>
            <div class="cz-charts" id="cz-charts">
                <div><div class="cz-donut"><canvas id="cz-donut" aria-label="Split of the final amount"></canvas><div class="mid" id="cz-mid"></div></div><div class="cz-legend" id="cz-legend"></div></div>
                <div><div class="cz-bar"><canvas id="cz-bar" aria-label="Year-by-year growth"></canvas></div></div>
            </div>
            <div id="cz-sentence"></div>
            <div id="cz-table"></div>
            <div class="cz-plan"><span><b>Want a full retirement plan?</b> Combine SIP, FD, RD and insurance and see the monthly income at retirement.</span><a class="dt-btn gold" href="{{ route('money.index') }}">Open money planner</a></div>
        </section>
    </div>
</main>
@endsection
@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(() => {
  const C = { principal: '#2563eb', gain: '#22c55e', interest: '#f97316', light: '#bfdbfe' };
  const inr = v => v == null ? '—' : '₹' + Math.round(v).toLocaleString('en-IN');
  const short = v => { const a = Math.abs(v); return a >= 1e7 ? '₹' + (v / 1e7).toFixed(2) + ' Cr' : a >= 1e5 ? '₹' + (v / 1e5).toFixed(2) + ' L' : inr(v); };
  const kpi = (l, v, cls = '') => `<div class="cz-kpi ${cls}"><span>${l}</span><b>${v}</b></div>`;
  const $ = id => document.getElementById(id);
  let type = 'sip', donut, bar, timer, seq = 0;

  const charts = (split, labels, series, stacked = true) => {
    donut?.destroy(); bar?.destroy();
    $('cz-charts').hidden = !split;
    if (!split) return;
    donut = new Chart($('cz-donut'), { type: 'doughnut', data: { labels: split.map(s => s[0]), datasets: [{ data: split.map(s => Math.max(0, s[1])), backgroundColor: split.map(s => s[2]), borderWidth: 0, hoverOffset: 6 }] },
      options: { cutout: '70%', plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + inr(c.raw) } } } } });
    const total = split.reduce((a, s) => a + Math.max(0, s[1]), 0);
    $('cz-mid').innerHTML = `<div><b>${short(total)}</b><small>total</small></div>`;
    $('cz-legend').innerHTML = split.map(s => `<span><i style="background:${s[2]}"></i>${s[0]} · ${total ? Math.round(s[1] / total * 100) : 0}%</span>`).join('');
    bar = new Chart($('cz-bar'), { type: 'bar', data: { labels, datasets: series.map(s => ({ label: s[0], data: s[1], backgroundColor: s[2], borderRadius: 4, stack: stacked ? 'a' : s[0] })) },
      options: { maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => ' ' + c.dataset.label + ': ' + inr(c.raw) } } },
        scales: { x: { stacked, grid: { display: false } }, y: { stacked, ticks: { callback: v => short(v) } } } } });
  };
  const table = (cols, rows) => `<details class="cz-table"><summary>Year-by-year table</summary><div class="dt-table-wrap" style="max-height:380px;overflow:auto;margin-top:10px"><table class="dt-table"><thead><tr>${cols.map(c => `<th class="num">${c[1]}</th>`).join('')}</tr></thead><tbody>${rows.map(r => `<tr>${cols.map(c => `<td class="num">${c[2] ? c[2](r[c[0]]) : r[c[0]]}</td>`).join('')}</tr>`).join('')}</tbody></table></div></details>`;
  const growth = (d, rate, monthlyLabel) => {
    const yr = d.yearly || [];
    const invested = yr.map(y => y.invested ?? d.invested);
    charts([['Invested', d.invested, C.principal], ['Returns', d.gain, C.gain]], yr.map(y => 'Yr ' + y.year), [['Invested', invested, C.principal], ['Returns', yr.map((y, i) => Math.max(0, y.value - invested[i])), C.gain]]);
    // Monthly income the final amount could pay at the same rate, without touching the principal.
    const monthly = d.value * rate / 100 / 12;
    $('cz-kpis').innerHTML = kpi('Invested', inr(d.invested)) + kpi('Est. returns', inr(d.gain), 'green') + kpi('Total value', short(d.value), 'hl') + kpi(monthlyLabel, inr(monthly));
    $('cz-sentence').innerHTML = `<p class="cz-sentence">You put in <b>${inr(d.invested)}</b> and earn <b>${inr(d.gain)}</b> — your money grows <b>${(d.value / d.invested).toFixed(2)}×</b>. Kept invested at ${rate}%, the final amount can pay about <b>${inr(monthly)} a month</b>.</p>`;
    $('cz-table').innerHTML = table([['year', 'Year'], ['invested', 'Invested', inr], ['value', 'Value', inr]], yr.map((y, i) => ({ ...y, invested: invested[i] })));
  };
  const loanYears = schedule => {
    const ys = {};
    schedule.forEach(r => { const y = Math.ceil(r.month / 12); ys[y] ??= { year: y, principal: 0, interest: 0, balance: 0 }; ys[y].principal += r.principal + (r.prepayment || 0); ys[y].interest += r.interest; ys[y].balance = r.balance; });
    return Object.values(ys);
  };
  const render = {
    sip: (d, f) => growth(d, +f.rate, 'Monthly income after'),
    lumpsum: (d, f) => growth(d, +f.rate, 'Monthly income after'),
    rd: (d, f) => growth(d, +f.rate, 'Monthly interest after'),
    fd: (d, f) => {
      growth(d, +f.rate, 'Monthly interest after');
      $('cz-kpis').innerHTML += kpi('Monthly payout FD', inr(d.monthly_payout)) + kpi('Effective yield', d.effective_rate + '%');
      $('cz-sentence').innerHTML += `<p class="cz-sentence">Prefer a monthly income instead? A monthly-payout FD of ${inr(d.invested)} pays about <b>${inr(d.monthly_payout)} every month</b> and returns the deposit at maturity.</p>`;
    },
    emi: (d, f) => {
      // Build the year-by-year split client-side from the EMI.
      const r = f.rate / 1200; let bal = d.principal; const rows = [];
      for (let m = 1; m <= d.months; m++) { const i = bal * r, p = d.emi - i; bal = Math.max(0, bal - p); rows.push({ month: m, principal: p, interest: i, balance: bal }); }
      const ys = loanYears(rows);
      charts([['Principal', d.principal, C.principal], ['Interest', d.total_interest, C.interest]], ys.map(y => 'Yr ' + y.year), [['Principal paid', ys.map(y => y.principal), C.principal], ['Interest paid', ys.map(y => y.interest), C.interest]]);
      const first = rows[0];
      $('cz-kpis').innerHTML = kpi('Monthly EMI', inr(d.emi), 'hl') + kpi('Principal', inr(d.principal)) + kpi('Total interest', inr(d.total_interest), 'red') + kpi('Total payment', short(d.total_payment));
      $('cz-sentence').innerHTML = `<p class="cz-sentence">Your first EMI of ${inr(d.emi)} is <b>${inr(first.interest)} interest</b> and only ${inr(first.principal)} principal. Over ${d.months / 12} years you pay <b>${inr(d.total_interest)}</b> in interest — ${Math.round(d.total_interest / d.principal * 100)}% of the loan.</p>`;
      $('cz-table').innerHTML = table([['year', 'Year'], ['principal', 'Principal', inr], ['interest', 'Interest', inr], ['balance', 'Balance', inr]], ys);
    },
    amortization: (d, f) => {
      const ys = loanYears(d.schedule);
      charts([['Principal', +f.principal, C.principal], ['Interest', d.total_interest, C.interest]], ys.map(y => 'Yr ' + y.year), [['Principal paid', ys.map(y => y.principal), C.principal], ['Interest paid', ys.map(y => y.interest), C.interest]]);
      $('cz-kpis').innerHTML = kpi('Monthly EMI', inr(d.emi), 'hl') + kpi('Total interest', inr(d.total_interest), 'red') + kpi('Loan closes in', d.months_taken + ' months') + kpi('Months saved', d.months_saved, 'green');
      $('cz-sentence').innerHTML = d.months_saved > 0 ? `<p class="cz-sentence">Prepaying ${inr(f.prepayment)} in month ${f.prepay_month} closes the loan <b>${d.months_saved} months early</b>.</p>` : '';
      $('cz-table').innerHTML = table([['year', 'Year'], ['principal', 'Principal', inr], ['interest', 'Interest', inr], ['balance', 'Balance', inr]], ys);
    },
    margin: d => {
      charts([['Your margin', d.margin_required, C.principal], ['Broker funded', d.trade_value - d.margin_required, C.light]], [], [], true);
      $('cz-kpis').innerHTML = kpi('Margin required', inr(d.margin_required), 'hl') + kpi('Trade value', inr(d.trade_value)) + kpi('Margin %', d.margin_pct + '%') + kpi('Leverage', d.leverage + '×')
        + (d.exchange_margin !== null ? kpi('NSE VaR + ELM', d.var_margin + '% + ' + d.elm + '%') : '');
      $('cz-sentence').innerHTML = ''; $('cz-table').innerHTML = '';
    },
  };
  const run = () => {
    const form = document.querySelector(`[data-form="${type}"]`);
    const body = Object.fromEntries([...new FormData(form)].filter(([, v]) => v !== ''));
    const my = ++seq;
    fetch('{{ url('trading/api/calculators') }}/' + type, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(body) })
      .then(async r => { const j = await r.json(); if (!r.ok) throw new Error(j.message || 'Check the inputs'); if (my === seq) render[type](j.data, body); })
      .catch(e => { if (my === seq) { charts(null); $('cz-kpis').innerHTML = `<div class="cz-err">${e.message}</div>`; $('cz-sentence').innerHTML = $('cz-table').innerHTML = ''; } });
  };
  const soon = () => { clearTimeout(timer); timer = setTimeout(run, 250); };
  document.querySelectorAll('.cz-form').forEach(form => {
    form.querySelectorAll('input[type=range]').forEach(rg => {
      const num = form.querySelector(`input[name="${rg.dataset.for}"]`);
      rg.addEventListener('input', () => { num.value = rg.value; soon(); });
      num.addEventListener('input', () => { rg.value = num.value; soon(); });
    });
    form.querySelectorAll('select, input:not([data-pair]):not([type=range])').forEach(el => el.addEventListener('input', soon));
  });
  document.querySelectorAll('[data-calc]').forEach(b => b.addEventListener('click', () => {
    type = b.dataset.calc;
    document.querySelectorAll('[data-calc]').forEach(x => x.classList.toggle('on', x === b));
    document.querySelectorAll('[data-form]').forEach(f => f.hidden = f.dataset.form !== type);
    run();
  }));
  run();
})();
</script>
@endpush
