{{-- Chart.js (served from public/vendor/chartjs). Each <canvas data-chart="{...}"> holds its own Chart.js config. --}}
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(() => {
    if (!window.Chart) return;
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = '#526175';
    Chart.defaults.borderColor = '#e8edf5';
    const thisYear = new Date().getFullYear();
    const num = v => v === null || v === undefined ? '—' : Number(v).toLocaleString('en-IN', {maximumFractionDigits: 2});
    const label = ctx => {
        const raw = ctx.raw, unit = ctx.dataset.unit ?? '';
        if (Array.isArray(raw)) return `${ctx.dataset.label}: ${Math.floor(raw[0])} – ${raw[1] >= thisYear ? 'today' : Math.floor(raw[1])}`;
        return `${ctx.dataset.label}: ${num(raw)}${unit}`;
    };
    document.querySelectorAll('canvas[data-chart]').forEach(canvas => {
        const cfg = JSON.parse(canvas.dataset.chart);
        const options = cfg.options || {};
        // Year axes: plain integers (2009, not 2,009).
        if (cfg.yearAxis) { const axis = (options.scales ||= {})[cfg.yearAxis] ||= {}; axis.ticks = {...(axis.ticks || {}), callback: v => String(Math.round(v))}; delete cfg.yearAxis; }
        cfg.options = {
            responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false}, ...options,
            plugins: {legend: {position: 'top', labels: {boxWidth: 12, usePointStyle: true}}, ...(options.plugins || {}), tooltip: {callbacks: {label}}},
        };
        new Chart(canvas, cfg);
    });
    // Tabs that filter table rows by data-status (charges: All / Active / Closed).
    document.querySelectorAll('[data-tabs]').forEach(group => {
        const table = document.getElementById(group.dataset.tabs);
        group.querySelectorAll('button').forEach(button => button.addEventListener('click', () => {
            group.querySelectorAll('button').forEach(b => b.classList.toggle('active', b === button));
            table.querySelectorAll('tbody tr').forEach(row => { row.hidden = button.dataset.status !== 'all' && row.dataset.status !== button.dataset.status; });
        }));
    });
})();
</script>
