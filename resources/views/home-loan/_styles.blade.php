@push('styles')<style>
.hl-hero{background:linear-gradient(135deg,#312e81,#5b21b6 60%,#6d28d9);color:#fff;padding:34px 0 40px}
.hl-hero .pr-wrap{display:grid;grid-template-columns:minmax(0,1fr) 400px;gap:36px;align-items:start}
.hl-hero h1{font-size:clamp(28px,4vw,44px);line-height:1.15;margin:8px 0 12px;color:#fff}.hl-hero h1 em{font-style:normal;color:#fda4af}
.hl-chips{display:flex;flex-wrap:wrap;gap:10px;margin:16px 0}.hl-chips span{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);border-radius:999px;padding:7px 14px;font-weight:600;font-size:14px}
.hl-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.07);border-radius:16px;margin-top:20px}
.hl-stats div{padding:16px 18px;border-right:1px solid rgba(255,255,255,.15)}.hl-stats div:last-child{border:0}.hl-stats b{display:block;font-size:24px}.hl-stats small{font-size:11px;letter-spacing:.06em;text-transform:uppercase;opacity:.75}
.hl-form{background:#fff;color:var(--pr-ink);border-radius:16px;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,.25)}
.hl-form header{background:#e11d48;color:#fff;padding:14px 18px}.hl-form header b{font-size:18px}.hl-form header small{display:block;opacity:.9}
.hl-form form{padding:16px 18px;display:grid;gap:12px}.hl-form label.f{font-size:11.5px;letter-spacing:.06em;text-transform:uppercase;margin-bottom:4px}
.hl-dial{display:flex}.hl-dial span{display:grid;place-items:center;padding:0 12px;border:1px solid #d1d5db;border-right:0;border-radius:10px 0 0 10px;background:#f9fafb;font-weight:700}.hl-dial input{border-radius:0 10px 10px 0!important}
.hl-two{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.hl-seg{display:grid;grid-template-columns:1fr 1fr;gap:10px}.hl-seg input{position:absolute;opacity:0}.hl-seg span{display:block;text-align:center;padding:10px;border:1px solid #d1d5db;border-radius:10px;cursor:pointer;font-weight:600;font-size:14px}
.hl-seg input:checked+span{border-color:#e11d48;background:#fff1f2;color:#be123c}.hl-seg input:focus-visible+span{outline:2px solid #fda4af}
.hl-red{background:#e11d48!important}.hl-red:hover{background:#be123c!important}
.hl-sec{background:#fff;border:1px solid var(--pr-line);border-radius:18px;padding:24px;margin-top:22px}
.hl-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0 16px}.hl-tabs button{padding:8px 16px;border-radius:999px;border:1px solid var(--pr-line);background:#fff;cursor:pointer;font-weight:600;color:#6b7280}.hl-tabs button.on{border-color:#e11d48;color:#e11d48}
.hl-table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--pr-line);border-radius:12px;overflow:hidden}
.hl-table th{background:#111827;color:#fff;font-size:11.5px;letter-spacing:.06em;text-transform:uppercase;text-align:left;padding:13px 16px}
.hl-table td{padding:14px 16px;border-top:1px solid var(--pr-line);vertical-align:middle;font-size:14px}.hl-table tr[hidden]{display:none}
.hl-bank{display:flex;gap:12px;align-items:center}.hl-bank i{font-style:normal;width:40px;height:40px;border-radius:10px;display:grid;place-items:center;color:#fff;font-weight:800;font-size:12px;flex:none}.hl-bank small{display:block;color:var(--pr-mute);font-size:12px}
.hl-calc{display:grid;grid-template-columns:1fr 1fr;gap:20px}.hl-card{border:1px solid var(--pr-line);border-radius:16px;padding:20px;display:flex;flex-direction:column;gap:14px}
.hl-card h3{display:flex;gap:12px;align-items:center;margin:0;font-size:18px}.hl-card h3 i{font-style:normal;width:42px;height:42px;border-radius:11px;display:grid;place-items:center;color:#fff;background:#5b21b6}
.hl-field{display:grid;gap:6px}.hl-field label{font-weight:700;font-size:14px}.hl-in{display:flex;border:1px solid #d1d5db;border-radius:10px;overflow:hidden}.hl-in span{display:grid;place-items:center;width:44px;background:#f3f4f6;color:#9ca3af;font-weight:700}.hl-in input{border:0!important;border-radius:0!important;outline:0}
.hl-field input[type=range]{width:100%;accent-color:#e11d48}
.hl-out{background:linear-gradient(135deg,#4c1d95,#6d28d9);color:#fff;border-radius:14px;padding:18px}.hl-out small{font-size:11.5px;letter-spacing:.06em;text-transform:uppercase;opacity:.85}.hl-out b{display:block;font-size:28px;margin:4px 0}.hl-out p{margin:0;font-size:12px;opacity:.8}
.hl-split{height:10px;border-radius:9px;background:#f43f5e;overflow:hidden}.hl-split i{display:block;height:100%;background:#5b21b6}
.hl-legend{display:flex;justify-content:space-between;font-size:12.5px;color:#374151}
.hl-mini{display:grid;grid-template-columns:1fr 1fr;gap:10px}.hl-mini div{border:1px solid var(--pr-line);border-radius:12px;padding:12px}.hl-mini small{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--pr-mute)}.hl-mini b{display:block;font-size:17px;margin-top:2px}
.hl-note{border-radius:10px;padding:10px 12px;font-size:13px}.hl-note.ok{background:#fff7ed;color:#9a3412}.hl-note.no{background:#fef2f2;color:#991b1b}
/* application steps */
.hl-app{display:grid;grid-template-columns:320px minmax(0,1fr);min-height:calc(100vh - 140px);background:#fff}
.hl-side{background:linear-gradient(180deg,#1e1b4b,#4338ca);color:#fff;padding:30px 26px}.hl-side h2{color:#fff;font-size:24px;margin:0 0 26px}
.hl-side ol{list-style:none;margin:0;padding:0}.hl-side li{display:grid;grid-template-columns:28px 1fr;gap:12px;position:relative;padding-bottom:22px}
.hl-side li:not(:last-child)::after{content:"";position:absolute;left:13px;top:30px;bottom:2px;width:2px;background:rgba(255,255,255,.35)}
.hl-side li>span{width:28px;height:28px;border-radius:50%;border:2px solid #fff;display:grid;place-items:center;font-size:13px;font-weight:800}.hl-side li.on>span,.hl-side li.done>span{background:#fff;color:#4338ca}
.hl-side li small{display:block;font-size:12px;opacity:.75}.hl-side li a,.hl-side li b{color:#fff;text-decoration:none;font-size:17px;font-weight:700}.hl-side li:not(.on):not(.done) b{opacity:.7}
.hl-main{padding:30px clamp(16px,5vw,80px) 110px;max-width:640px;width:100%;box-sizing:border-box;margin:0 auto}
.hl-main h1{display:flex;gap:12px;align-items:center;font-size:24px}.hl-main h1 a{text-decoration:none;color:var(--pr-ink)}
.hl-emp{display:flex;gap:14px}.hl-emp label{position:relative}.hl-emp input{position:absolute;opacity:0}.hl-emp span{display:grid;place-items:center;gap:4px;width:130px;height:80px;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;font-size:14px;color:#374151}.hl-emp span i{font-style:normal;font-size:24px}
.hl-emp input:checked+span{border:2px solid #4338ca;background:#eef2ff}.hl-emp input:checked+span::after{content:"✓";position:absolute;top:-9px;right:-9px;width:22px;height:22px;border-radius:50%;background:#4338ca;color:#fff;display:grid;place-items:center;font-size:12px}
.hl-pills{display:flex;gap:10px;flex-wrap:wrap}.hl-pills label{position:relative}.hl-pills input{position:absolute;opacity:0}.hl-pills span{display:block;padding:8px 16px;border:1px solid #d1d5db;border-radius:999px;cursor:pointer;font-size:14px}.hl-pills input:checked+span{background:#4338ca;border-color:#4338ca;color:#fff}.hl-pills input:focus-visible+span{outline:2px solid #a5b4fc}
.hl-hint{font-size:12.5px;color:#059669;font-weight:700;text-align:right;margin-top:4px}
.hl-bar{position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:1px solid var(--pr-line);padding:12px clamp(16px,6vw,110px);display:flex;justify-content:flex-end;gap:10px;z-index:20}
.hl-bar .pr-btn{background:#0f766e;min-width:200px}.hl-bar .pr-btn:hover{background:#115e59}
.hl-offer{display:grid;grid-template-columns:auto minmax(0,1fr) auto;gap:14px;align-items:center;border:1px solid var(--pr-line);border-radius:12px;padding:14px;cursor:pointer;margin-bottom:10px}
.hl-offer:has(input:checked){border:2px solid #4338ca;background:#f5f7ff}.hl-offer.dim{opacity:.6}
.hl-offer .r{text-align:right}.hl-offer .r b{font-size:18px;display:block}.hl-offer small{color:var(--pr-mute)}
.hl-doc{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:center;border:1px solid var(--pr-line);border-radius:12px;padding:12px 14px;margin-bottom:10px}
.hl-doc ul{margin:6px 0 0;padding:0;list-style:none;font-size:13px}.hl-doc li{display:flex;gap:8px;align-items:center}
@media(max-width:900px){.hl-hero .pr-wrap,.hl-calc,.hl-app{grid-template-columns:1fr}.hl-stats{grid-template-columns:1fr 1fr}.hl-side{padding:18px}.hl-side ol{display:flex;gap:10px;overflow-x:auto}.hl-side li{padding:0;grid-template-columns:28px auto}.hl-side li::after{display:none}.hl-side h2{margin-bottom:12px;font-size:20px}}
@media(max-width:640px){.hl-table thead{display:none}.hl-table tr{display:grid;grid-template-columns:1fr 1fr;border-top:1px solid var(--pr-line)}.hl-table td{border:0;padding:8px 14px}.hl-table td:first-child,.hl-table td:last-child{grid-column:1/-1}.hl-two{grid-template-columns:1fr}}
</style>@endpush
