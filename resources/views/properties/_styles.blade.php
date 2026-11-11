@push('styles')<style>
:root{--pr-ink:#111827;--pr-mute:#6b7280;--pr-line:#e5e7eb;--pr-bg:#f6f7fb;--pr-accent:#4338ca;--pr-accent-h:#3730a3;--pr-soft:#eef2ff;--pr-amber:#f59e0b;--pr-ok:#047857}
.pr{background:var(--pr-bg);min-height:70vh;padding-bottom:40px;color:var(--pr-ink)}
.pr-wrap{width:min(1240px,calc(100% - 32px));margin:0 auto}
.pr-sub{background:#fff;border-bottom:1px solid var(--pr-line)}
.pr-sub .pr-wrap{display:flex;align-items:center;gap:14px;flex-wrap:wrap;min-height:60px}
.pr-brand{display:flex;align-items:center;gap:9px;font:800 18px Inter,system-ui,sans-serif;color:var(--pr-ink);text-decoration:none}
.pr-brand i{display:grid;place-items:center;width:38px;height:38px;border-radius:11px;background:var(--pr-accent);color:#fff;font-style:normal;font-size:20px}
.pr-sub nav{display:flex;gap:4px;flex-wrap:wrap}
.pr-sub nav a{padding:8px 12px;border-radius:9px;color:#374151;text-decoration:none;font-weight:600;font-size:14px}
.pr-sub nav a:hover,.pr-sub nav a.on{background:var(--pr-soft);color:var(--pr-accent)}
.pr-post{margin-left:auto;padding:10px 16px;border-radius:10px;background:var(--pr-amber);color:#111827;font-weight:800;text-decoration:none;font-size:14px;white-space:nowrap}
.pr-post:hover{background:#fbbf24}
.pr-card-box{background:#fff;border:1px solid var(--pr-line);border-radius:16px;padding:18px}
.pr h1{font-size:clamp(22px,3vw,32px);margin:0 0 6px}.pr h2{font-size:19px;margin:0 0 12px}.pr h3{font-size:15px;margin:0 0 10px}
.pr-mute{color:var(--pr-mute)}
.pr-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:11px 18px;border-radius:10px;border:0;background:var(--pr-accent);color:#fff;font:700 14px Inter,system-ui,sans-serif;cursor:pointer;text-decoration:none}
.pr-btn:hover{background:var(--pr-accent-h);color:#fff}
.pr-btn.ghost{background:#fff;color:var(--pr-accent);border:1px solid #c7d2fe}.pr-btn.ghost:hover{background:var(--pr-soft)}
.pr-btn.grey{background:#6b7280}.pr-btn.danger{background:#b91c1c}.pr-btn.sm{padding:7px 12px;font-size:13px}
.pr-flash{padding:11px 14px;border-radius:10px;margin:14px 0;font-weight:600}.pr-flash.ok{background:#ecfdf5;color:var(--pr-ok)}.pr-flash.warn{background:#fffbeb;color:#92400e}.pr-flash.err{background:#fef2f2;color:#991b1b}
/* form controls */
.pr label.f{display:block;font-weight:700;font-size:14px;margin:0 0 6px}.pr label.f em{color:#dc2626;font-style:normal}
.pr input[type=text],.pr input[type=number],.pr input[type=date],.pr input[type=time],.pr input[type=tel],.pr input[type=email],.pr input[type=search],.pr input[type=datetime-local],.pr select,.pr textarea{width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid #d1d5db;border-radius:10px;font:15px Inter,system-ui,sans-serif;background:#fff;color:var(--pr-ink)}
.pr input:focus,.pr select:focus,.pr textarea:focus{outline:2px solid #a5b4fc;border-color:var(--pr-accent)}
.pr-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 22px}.pr-grid.three{grid-template-columns:repeat(3,minmax(0,1fr))}.pr-grid .full{grid-column:1/-1}
.pr-input-addon{position:relative}.pr-input-addon span{position:absolute;top:50%;transform:translateY(-50%);color:var(--pr-mute);font-size:14px}.pr-input-addon span.l{left:12px}.pr-input-addon span.r{right:12px}
.pr-input-addon input{padding-left:30px!important}.pr-input-addon.rr input{padding-right:70px!important}
.pr-check{display:inline-flex;align-items:center;gap:8px;font-size:14px;margin-top:8px;cursor:pointer}
.pr-chips{display:flex;flex-wrap:wrap;gap:8px}
.pr-chip{position:relative;display:inline-flex;align-items:center;gap:6px;padding:8px 13px;border:1px solid #d1d5db;border-radius:999px;background:#fff;font-size:13.5px;cursor:pointer;user-select:none}
.pr-chip[hidden]{display:none}
.pr-chip input{position:absolute;opacity:0;pointer-events:none}
.pr-chip:has(input:checked){background:var(--pr-soft);border-color:var(--pr-accent);color:var(--pr-accent);font-weight:700}
.pr-chip:has(input:focus-visible){outline:2px solid #a5b4fc}
.pr-err{color:#b91c1c;font-size:13px;margin-top:5px}
/* listing card */
.pr-list{display:grid;gap:14px}
.pr-item{display:grid;grid-template-columns:260px minmax(0,1fr);background:#fff;border:1px solid var(--pr-line);border-radius:16px;overflow:hidden}
.pr-item:hover{box-shadow:0 10px 26px rgba(17,24,39,.08)}
.pr-thumb{position:relative;background:#e0e7ff;min-height:190px;display:grid;place-items:center;font-size:44px;color:#818cf8}
.pr-thumb img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.pr-thumb small{position:absolute;left:10px;bottom:10px;background:rgba(17,24,39,.75);color:#fff;font-size:12px;padding:3px 8px;border-radius:6px}
.pr-body{padding:14px 16px;display:flex;flex-direction:column;gap:8px;min-width:0}
.pr-body h3 a{color:var(--pr-ink);text-decoration:none}.pr-body h3 a:hover{color:var(--pr-accent)}
.pr-price{font-size:20px;font-weight:800}
.pr-facts{display:flex;flex-wrap:wrap;gap:6px 16px;font-size:13.5px;color:#374151}.pr-facts b{display:block;font-size:11.5px;color:var(--pr-mute);font-weight:600;text-transform:uppercase;letter-spacing:.03em}
.pr-tag{display:inline-block;padding:3px 9px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:12px;font-weight:600}
.pr-tag.ok{background:#ecfdf5;color:var(--pr-ok)}.pr-tag.amber{background:#fffbeb;color:#92400e}.pr-tag.blue{background:var(--pr-soft);color:var(--pr-accent)}
.pr-heart{border:1px solid var(--pr-line);background:#fff;border-radius:9px;padding:6px 10px;cursor:pointer;font-size:14px}.pr-heart.on{color:#be123c;border-color:#fecdd3;background:#fff1f2}
@media(max-width:760px){.pr-item{grid-template-columns:1fr}.pr-grid,.pr-grid.three{grid-template-columns:1fr}}
</style>@endpush
