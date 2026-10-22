@push('styles')
<style>
.mp{--mp-ink:#10213e;--mp-muted:#667085;--mp-line:#e3e9f2;--mp-card:#fff;--mp-soft:#f6f9ff;--mp-blue:#2563eb;--mp-green:#0b7a43;--mp-red:#b42318;--mp-amber:#b45309;color:var(--mp-ink)}
.mp-hero{background:linear-gradient(120deg,#0b1730,#1d4ed8);color:#fff;padding:34px 0 64px}
.mp-wrap{width:min(1200px,calc(100% - 32px));margin:auto}
.mp-hero h1{margin:0 0 6px;font-size:clamp(26px,4vw,38px)}.mp-hero p{margin:0;opacity:.85}
.mp-free{display:inline-block;margin-top:12px;background:#facc15;color:#10213e;font-weight:800;font-size:12px;border-radius:999px;padding:5px 12px;letter-spacing:.03em}
.mp-nav{display:flex;gap:6px;flex-wrap:wrap;margin-top:-30px;margin-bottom:20px;background:var(--mp-card);border:1px solid var(--mp-line);border-radius:16px;padding:6px;box-shadow:0 10px 30px rgba(16,33,62,.08)}
.mp-nav a{flex:1;min-width:150px;text-align:center;padding:12px 14px;border-radius:11px;font-weight:700;color:var(--mp-muted);text-decoration:none}
.mp-nav a.on{background:#10213e;color:#fff}.mp-nav a:not(.on):hover{background:var(--mp-soft);color:var(--mp-ink)}
.mp-card{background:var(--mp-card);border:1px solid var(--mp-line);border-radius:18px;padding:20px;box-shadow:0 10px 30px rgba(16,33,62,.05);margin-bottom:18px}
.mp-card h2{margin:0 0 4px;font-size:18px}.mp-card .sub{color:var(--mp-muted);font-size:13px;margin:0 0 14px}
.mp-grid{display:grid;grid-template-columns:minmax(300px,400px) 1fr;gap:18px;align-items:start}.mp-two{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.mp-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:18px}
.mp-kpi{background:var(--mp-soft);border:1px solid var(--mp-line);border-radius:14px;padding:14px 16px}.mp-kpi span{font-size:11.5px;font-weight:750;color:var(--mp-muted);text-transform:uppercase;letter-spacing:.04em}
.mp-kpi b{display:block;font-size:22px;margin-top:6px}.mp-kpi small{color:var(--mp-muted);font-size:12px}
.mp-kpi.hl{background:linear-gradient(135deg,#10213e,#1d4ed8);border:0;color:#fff}.mp-kpi.hl span,.mp-kpi.hl small{color:#c7d7ff}.mp-kpi.ok b{color:var(--mp-green)}.mp-kpi.bad b{color:var(--mp-red)}
.mp-f{margin-bottom:12px}.mp-f label{display:block;font-size:12.5px;font-weight:700;color:#475467;margin-bottom:5px}
.mp-in,.mp-f input,.mp-f select{width:100%;padding:10px 11px;border:1px solid #d5deeb;border-radius:10px;font:inherit;background:#fff;color:inherit}
.mp-row{display:grid;grid-template-columns:1fr 1fr;gap:10px}.mp-row3{display:grid;grid-template-columns:1.6fr 1fr .8fr .8fr auto;gap:6px;align-items:center;margin-bottom:6px}
.mp-row3 input,.mp-row3 select{padding:8px;border:1px solid #d5deeb;border-radius:8px;font:inherit;width:100%;background:#fff;color:inherit}
.mp-x{border:0;background:transparent;color:var(--mp-red);font-size:16px;cursor:pointer}
.mp-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:11px 18px;border-radius:10px;border:0;background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;font-weight:750;cursor:pointer;text-decoration:none;font:inherit;font-weight:750}
.mp-btn.ghost{background:#fff;color:var(--mp-blue);border:1px solid #bfd3f5}.mp-btn.gold{background:#facc15;color:#10213e}
.mp-table{width:100%;border-collapse:collapse;font-size:14px}.mp-table th{text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.04em;color:var(--mp-muted);padding:9px 8px;border-bottom:1px solid var(--mp-line)}
.mp-table td{padding:10px 8px;border-bottom:1px solid #eef2f7;vertical-align:top}.mp-table .num{text-align:right;white-space:nowrap}.mp-scroll{overflow-x:auto}
.mp-pill{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:750;white-space:nowrap}
.mp-pill.buy{background:#dcfce7;color:#166534}.mp-pill.hold{background:#e0ecff;color:#1d4ed8}.mp-pill.watch{background:#f2f4f7;color:#475467}.mp-pill.book_profit{background:#fef3c7;color:#92400e}.mp-pill.exit{background:#fee2e2;color:#991b1b}.mp-pill.pending{background:#f2f4f7;color:#667085}
.mp-note{background:#f0fdf4;border:1px solid #c6f0d5;color:#14532d;border-radius:12px;padding:12px 14px;font-size:14px;line-height:1.5;margin:10px 0}
.mp-warn{background:#fffbeb;border:1px solid #fde68a;color:#78350f;border-radius:12px;padding:12px 14px;font-size:14px;line-height:1.5;margin:10px 0}
.mp-chart{position:relative;height:280px}.mp-donut{position:relative;height:220px}
.mp-opt{border:1px solid var(--mp-line);border-radius:14px;padding:14px 16px;margin-bottom:10px;display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center}
.mp-opt.best{border-color:#86efac;background:#f0fdf4}.mp-opt h3{margin:0 0 4px;font-size:15px}.mp-opt p{margin:0;color:#475467;font-size:13.5px;line-height:1.5}
.mp-opt .tax{text-align:right}.mp-opt .tax b{display:block;font-size:18px}.mp-opt .tax small{color:var(--mp-green);font-weight:750}
.mp-sec{display:inline-block;background:#eef4ff;color:#1d4ed8;border-radius:6px;padding:1px 7px;font-size:12px;font-weight:750;margin-left:6px}
.mp-disc{color:var(--mp-muted);font-size:12px;margin:18px 0 0}
@media(max-width:960px){.mp-grid,.mp-two{grid-template-columns:1fr}.mp-row3{grid-template-columns:1fr 1fr}}
@media (prefers-color-scheme: dark){:root:not([data-theme="light"]) .mp{--mp-ink:#e6edf8;--mp-muted:#9aa8bd;--mp-line:#22324f;--mp-card:#0f1b31;--mp-soft:#13223d}:root:not([data-theme="light"]) .mp-in,:root:not([data-theme="light"]) .mp-f input,:root:not([data-theme="light"]) .mp-f select,:root:not([data-theme="light"]) .mp-row3 input,:root:not([data-theme="light"]) .mp-row3 select{background:#13223d;border-color:#2b3d5f}:root:not([data-theme="light"]) .mp-f label{color:#cbd5e1}}
</style>
@endpush
@php
    $mpTabs = ['money.index' => 'Retirement plan', 'money.tax' => 'Tax saver', 'money.income' => 'Retirement income & insurance', 'money.funds' => 'Fund picks & signals', 'trading.calculators' => 'Calculators'];
@endphp
<section class="mp-hero"><div class="mp-wrap"><h1>{{ $mpTitle }}</h1><p>{{ $mpSub }}</p><span class="mp-free">FREE · NO SUBSCRIPTION · NO UPFRONT FEE</span></div></section>
<div class="mp-wrap"><nav class="mp-nav" aria-label="Money planner">@foreach($mpTabs as $route => $label)<a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'on' : '' }}">{{ $label }}</a>@endforeach</nav></div>
