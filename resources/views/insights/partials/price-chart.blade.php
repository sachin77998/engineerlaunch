{{-- Share price chart: 1D / 2D / 5D intraday (5-minute prices, change from the previous close) and 1M–5Y daily closes.
     Expects $pcId (unique id), $pcTitle, $pcUrl (history endpoint), $pcCur (currency sign), $pcZone (exchange time zone label). --}}
<div class="ci-head-row" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:18px">
    <h3 class="ci-h3" style="margin:0">{{ $pcTitle }}</h3>
    <div class="ci-tabs" id="{{ $pcId }}-ranges" style="margin:0;flex-wrap:wrap">@foreach(['1D' => 1, '2D' => 2, '5D' => 5] as $label => $n)<button type="button" data-sessions="{{ $n }}">{{ $label }}</button>@endforeach @foreach(['1M' => 30, '6M' => 182, '1Y' => 365, '3Y' => 1095, '5Y' => 1826] as $label => $days)<button type="button" data-days="{{ $days }}" class="{{ $label === '1Y' ? 'active' : '' }}">{{ $label }}</button>@endforeach</div>
</div>
<p class="ci-muted" id="{{ $pcId }}-stats" style="margin:6px 0"></p>
<div class="ci-chart"><canvas id="{{ $pcId }}-chart" aria-label="{{ $pcTitle }}"></canvas></div>
@push('scripts')
<script>
(() => {
    let chart, points = [], intraday = null;
    const cur = @json($pcCur), zone = @json($pcZone), url = @json($pcUrl);
    const canvas = document.getElementById(@json($pcId . '-chart')), stats = document.getElementById(@json($pcId . '-stats'));
    const money = v => cur + v.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const draw = (set, base, label, period) => {
        if (!set.length || !window.Chart) return;
        canvas.parentElement.hidden = false;
        const last = set[set.length - 1][1], ch = (last / base - 1) * 100, diff = last - base;
        stats.innerHTML = `${money(last)} · <b class="${ch >= 0 ? 'up' : 'down'}">${ch >= 0 ? '▲' : '▼'} ${money(Math.abs(diff))} (${Math.abs(ch).toFixed(2)}%)</b> ${period} · high ${money(Math.max(...set.map(p => p[1])))} · low ${money(Math.min(...set.map(p => p[1])))}`;
        chart?.destroy();
        chart = new Chart(canvas, {type: 'line', data: {labels: set.map(label), datasets: [{label: 'Price', data: set.map(p => p[1]), borderColor: ch >= 0 ? '#087443' : '#b42318',
            backgroundColor: ch >= 0 ? 'rgba(8,116,67,.1)' : 'rgba(180,35,24,.08)', fill: true, pointRadius: 0, borderWidth: 2, tension: .15}]},
            options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false}, plugins: {legend: {display: false}}, scales: {x: {ticks: {maxTicksLimit: 8}}}}});
    };
    const daily = days => {
        if (!points.length) return;
        const from = new Date(points[points.length - 1][0]); from.setDate(from.getDate() - days);
        const set = points.filter(p => new Date(p[0]) >= from);
        draw(set, set[0][1], p => p[0], 'over this period');
    };
    // Last N trading sessions; the change is measured from the close before the window (yesterday's close for 1D).
    const sessions = n => {
        const run = d => {
            const days = [...new Set(d.points.map(p => p[0].slice(0, 10)))], keep = days.slice(-n);
            const set = d.points.filter(p => keep.includes(p[0].slice(0, 10)));
            if (!set.length) { stats.textContent = 'Intraday prices are not available right now.'; return; }
            const before = d.points.filter(p => p[0].slice(0, 10) < keep[0]);
            const base = before.length ? before[before.length - 1][1] : (d.previous_close || set[0][1]);
            draw(set, base, p => n === 1 ? p[0].slice(11) : new Date(p[0].slice(0, 10)).toLocaleDateString('en-IN', {day: '2-digit', month: 'short'}) + ' ' + p[0].slice(11),
                n === 1 ? 'on ' + new Date(keep[0]).toLocaleDateString('en-IN', {day: '2-digit', month: 'short'}) + ' (' + zone + ')' : 'over ' + n + ' sessions');
        };
        if (intraday) return run(intraday);
        stats.textContent = 'Loading intraday prices…';
        fetch(url + '?intraday=1').then(r => r.json()).then(d => { intraday = d; run(d); }).catch(() => { stats.textContent = 'Intraday prices are not available right now.'; });
    };
    document.querySelectorAll(@json('#' . $pcId . '-ranges button')).forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll(@json('#' . $pcId . '-ranges button')).forEach(x => x.classList.toggle('active', x === b));
        b.dataset.sessions ? sessions(+b.dataset.sessions) : daily(+b.dataset.days);
    }));
    fetch(url).then(r => r.json()).then(d => {
        points = d.points || [];
        if (!points.length) { canvas.parentElement.hidden = true; stats.textContent = 'Price history is not available right now.'; return; }
        daily(365);
    }).catch(() => { canvas.parentElement.hidden = true; });
})();
</script>
@endpush
