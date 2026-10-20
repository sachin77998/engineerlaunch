@extends('layouts.site')
@section('title', $item['title'] . ' Calculator')
@push('styles')@include('calculators.styles')@endpush
@section('content')
<section class="cx-hero">
    <div class="cx-wrap">
        <div class="cx-crumb"><a href="{{ route('calculators.index') }}">Calculators</a> › {{ config('calculators.groups.' . $item['group']) }}</div>
        <h1>{{ $item['title'] }} Calculator</h1>
        <p>{{ $item['text'] }}.</p>
    </div>
</section>
<main class="cx-wrap cx-main">
    @if(isset($item['metal']) && !$metal)
        <p class="cx-card cx-note">No {{ $item['metal'] }} prices loaded yet. Enter today's price below; the trend chart appears after the daily price import runs.</p>
    @endif
    <section class="cx-card">
        <div class="cx-calc">
            <form class="cx-inputs" id="cx-form" onsubmit="return false"></form>
            <div>
                <div class="cx-donut"><canvas id="cx-donut" aria-label="Breakdown of the result"></canvas></div>
                <div class="cx-results" id="cx-results" aria-live="polite"></div>
            </div>
        </div>
    </section>

    @if($metal)
        <section class="cx-card" id="cx-metal">
            <div class="cx-head">
                <h2>{{ ucfirst($item['metal']) }} price trend (₹ per {{ $metal['unit'] }})</h2>
                <div class="cx-ranges">@foreach(['1M' => 30, '6M' => 182, '1Y' => 365, '3Y' => 1095, '5Y' => 1826] as $label => $days)<button type="button" data-days="{{ $days }}" class="{{ $label === '1Y' ? 'on' : '' }}">{{ $label }}</button>@endforeach</div>
            </div>
            <div class="cx-stats" id="cx-metal-stats"></div>
            <div class="cx-chart"><canvas id="cx-metal-chart" aria-label="{{ $item['metal'] }} price history"></canvas></div>
            <p class="cx-note">{{ $metal['source'] }}. Latest {{ \Carbon\Carbon::parse($metal['latest_on'])->format('d M Y') }}. Prices in Indian markets (MCX, jewellers) also include import duty and local premiums.</p>
        </section>
    @endif

    <section class="cx-card" id="cx-chart-card" hidden>
        <div class="cx-head"><h2 id="cx-chart-title"></h2></div>
        <div class="cx-chart"><canvas id="cx-chart"></canvas></div>
    </section>
    <section class="cx-card" id="cx-table-card" hidden>
        <div class="cx-head"><h2>Breakdown</h2></div>
        <div class="cx-scroll"><table class="cx-table" id="cx-table"></table></div>
    </section>

    @if($related->isNotEmpty())
        <section class="cx-card">
            <div class="cx-head"><h2>More {{ strtolower(config('calculators.groups.' . $item['group'])) }} calculators</h2><a href="{{ route('calculators.index') }}">All calculators ›</a></div>
            <div class="cx-related">@foreach($related as $s => $r)<a href="{{ route('calculators.show', $s) }}">{{ $r['icon'] }} {{ $r['title'] }}</a>@endforeach</div>
        </section>
    @endif
</main>
@endsection

@push('scripts')
<script>window.METAL = @json($metal);</script>
@include('calculators.engine')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(() => {
    const calc = window.CALCS[@json($slug)];
    const form = document.getElementById('cx-form');
    const palette = ['#e8743b', '#f6c2a8', '#527fad', '#f5b800', '#43936c'];
    const charts = {};
    const inrTick = v => '₹' + (Math.abs(v) >= 1e7 ? (v / 1e7).toFixed(1) + ' Cr' : Math.abs(v) >= 1e5 ? (v / 1e5).toFixed(1) + ' L' : Number(v).toLocaleString('en-IN'));
    const draw = (id, config) => { if (!window.Chart) return; charts[id]?.destroy(); charts[id] = new Chart(document.getElementById(id), config); };

    // Inputs: a value box and a slider for numbers, a dropdown for choices.
    calc.inputs.forEach(f => {
        const wrap = document.createElement('div');
        wrap.className = 'cx-field';
        if (f.options) {
            wrap.innerHTML = `<div class="cx-top"><label for="f-${f.k}">${f.label}</label><span class="cx-box cx-select"><select id="f-${f.k}" name="${f.k}">${f.options.map(o => `<option${o === f.value ? ' selected' : ''}>${o}</option>`).join('')}</select></span></div>`;
        } else {
            wrap.innerHTML = `<div class="cx-top"><label for="f-${f.k}">${f.label}</label><span class="cx-box">${f.prefix || ''}<input id="f-${f.k}" name="${f.k}" type="number" inputmode="decimal" value="${f.value}" min="${f.min}" max="${f.max}" step="${f.step}">${f.suffix || ''}</span></div>
                <input type="range" aria-label="${f.label}" data-for="${f.k}" min="${f.min}" max="${f.max}" step="${f.step}" value="${f.value}">`;
        }
        form.appendChild(wrap);
    });
    form.addEventListener('input', e => {
        const t = e.target;
        if (t.type === 'range') form.elements[t.dataset.for].value = t.value;
        else if (t.type === 'number') { const r = form.querySelector(`[data-for="${t.name}"]`); if (r) r.value = t.value; }
        render();
    });

    const values = () => Object.fromEntries(calc.inputs.map(f => {
        const raw = form.elements[f.k].value;
        if (f.options) return [f.k, raw];
        const n = parseFloat(raw);
        return [f.k, isFinite(n) ? Math.min(Math.max(n, f.min), f.max) : f.value];
    }));

    function render() {
        const out = calc.compute(values());
        document.getElementById('cx-results').innerHTML = out.rows.map(([k, v]) => `<div><span>${k}</span><b>${v}</b></div>`).join('');
        draw('cx-donut', {type: 'doughnut', data: {labels: out.donut.labels, datasets: [{data: out.donut.values.map(v => Math.max(0, Math.round(v))), backgroundColor: palette, borderWidth: 0}]},
            options: {responsive: true, maintainAspectRatio: false, cutout: '55%', plugins: {legend: {position: 'bottom', labels: {usePointStyle: true, boxWidth: 10}},
                tooltip: {callbacks: {label: c => {
                    const total = c.dataset.data.reduce((a, b) => a + b, 0) || 1;
                    return ` ${c.label}: ₹${c.raw.toLocaleString('en-IN')} (${(c.raw / total * 100).toFixed(1)}%)`;
                }}}}}});
        const chartCard = document.getElementById('cx-chart-card');
        chartCard.hidden = !out.chart;
        if (out.chart) {
            document.getElementById('cx-chart-title').textContent = out.chart.title;
            draw('cx-chart', {type: out.chart.type, data: {labels: out.chart.labels, datasets: out.chart.datasets.map(d => ({
                label: d.label, data: d.data, type: d.type, stack: d.stack, yAxisID: d.axis || 'y', backgroundColor: d.color, borderColor: d.color, tension: .3, pointRadius: 2, borderRadius: d.type === 'line' ? 0 : 4, order: d.type === 'line' ? 0 : 1}))},
                options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
                    plugins: {legend: {position: 'top', labels: {usePointStyle: true, boxWidth: 10}}, tooltip: {callbacks: {label: c => ` ${c.dataset.label}: ₹${Number(c.raw).toLocaleString('en-IN')}`}}},
                    scales: {x: {stacked: out.chart.datasets.some(d => d.stack)}, y: {stacked: out.chart.datasets.some(d => d.stack), ticks: {callback: inrTick}},
                        ...(out.chart.datasets.some(d => d.axis === 'y2') ? {y2: {position: 'right', grid: {drawOnChartArea: false}, ticks: {callback: inrTick}}} : {})}}});
        }
        const tableCard = document.getElementById('cx-table-card');
        tableCard.hidden = !out.table;
        if (out.table) document.getElementById('cx-table').innerHTML = `<thead><tr>${out.table.head.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>${out.table.rows.map(r => `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`).join('')}</tbody>`;
    }
    render();

    // Metal price trend: range buttons, change, high/low, CAGR and what ₹1 lakh would have become.
    const m = window.METAL;
    if (m) {
        const dates = m.dates.map(d => new Date(d + 'T00:00:00'));
        const showRange = days => {
            const from = new Date(dates[dates.length - 1]); from.setDate(from.getDate() - days);
            const idx = dates.findIndex(d => d >= from), labels = m.dates.slice(idx), prices = m.prices.slice(idx);
            const first = prices[0], last = prices[prices.length - 1], change = (last / first - 1) * 100, yrs = days / 365.25;
            const cls = change >= 0 ? 'cx-up' : 'cx-down', inr = v => '₹' + Math.round(v).toLocaleString('en-IN');
            document.getElementById('cx-metal-stats').innerHTML = [
                ['Latest price', inr(last)], ['Change', `<span class="${cls}">${change >= 0 ? '▲' : '▼'} ${Math.abs(change).toFixed(2)}%</span>`],
                ['High', inr(Math.max(...prices))], ['Low', inr(Math.min(...prices))],
                ...(yrs >= 1 ? [['CAGR', `<span class="${cls}">${((Math.pow(last / first, 1 / yrs) - 1) * 100).toFixed(2)}%</span>`]] : []),
                ['₹1 lakh invested then', inr(100000 * last / first)],
            ].map(([k, v]) => `<div><span>${k}</span><b>${v}</b></div>`).join('');
            draw('cx-metal-chart', {type: 'line', data: {labels: labels.map(d => new Date(d + 'T00:00:00').toLocaleDateString('en-IN', {day: 'numeric', month: 'short', year: '2-digit'})),
                datasets: [{label: `₹ per ${m.unit}`, data: prices, borderColor: change >= 0 ? '#43936c' : '#b42318', backgroundColor: change >= 0 ? 'rgba(67,147,108,.12)' : 'rgba(180,35,24,.1)', fill: true, pointRadius: 0, tension: .2, borderWidth: 2}]},
                options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false}, plugins: {legend: {display: false},
                    tooltip: {callbacks: {label: c => ` ₹${Number(c.raw).toLocaleString('en-IN')} per ${m.unit}`}}},
                    scales: {x: {ticks: {maxTicksLimit: 8}}, y: {ticks: {callback: inrTick}}}}});
        };
        document.querySelectorAll('#cx-metal [data-days]').forEach(b => b.addEventListener('click', () => {
            document.querySelectorAll('#cx-metal [data-days]').forEach(x => x.classList.toggle('on', x === b));
            showRange(+b.dataset.days);
        }));
        showRange(365);
    }
})();
</script>
@endpush
