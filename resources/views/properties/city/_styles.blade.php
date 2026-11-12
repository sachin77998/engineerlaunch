@include('properties._styles')
@push('styles')<style>
.cp-hero{background:linear-gradient(135deg,#1e1b4b 0%,#3730a3 60%,#4f46e5 100%);padding:28px 0 26px;color:#fff}
.cp-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}.cp-head h1{color:#fff!important;margin:0}
.cp-zone{display:flex;align-items:center;gap:8px}.cp-zone label{color:#c7d2fe}.cp-zone select{padding:8px 10px;border-radius:10px;border:0}
.cp-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}.cp-tabs a{padding:7px 14px;border-radius:999px;background:rgba(255,255,255,.14);color:#fff;text-decoration:none;font-weight:600;font-size:14px}.cp-tabs a:hover{background:rgba(255,255,255,.26)}
.cp-recent{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;align-items:center}.cp-recent span{color:#c7d2fe;font-size:13px}.cp-recent a{background:#fff;color:#3730a3;border-radius:999px;padding:5px 12px;font-size:13px;text-decoration:none;font-weight:600;max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cp-layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:24px;margin-top:24px}
.cp-side{display:grid;gap:16px;align-content:start;position:sticky;top:80px}
.cp-sec{margin-bottom:32px}.cp-sec h2{margin:0}.cp-sec .sub{color:#6b7280;font-size:14px}
.cp-sec-head{display:flex;justify-content:space-between;align-items:baseline;gap:10px;margin-bottom:12px}
.cp-rail-wrap{position:relative}
.cp-rail{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(240px,1fr);gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;padding-bottom:6px;scrollbar-width:thin}
.cp-rail>*{scroll-snap-align:start}
.cp-arrow{position:absolute;top:40%;width:34px;height:34px;border-radius:50%;border:1px solid var(--pr-line);background:#fff;box-shadow:0 4px 10px rgba(0,0,0,.1);cursor:pointer;font-size:18px;z-index:2}
.cp-arrow.l{left:-12px}.cp-arrow.r{right:-12px}
.cp-card{background:#fff;border:1px solid var(--pr-line);border-radius:14px;overflow:hidden;text-decoration:none;color:var(--pr-ink);display:block}
.cp-card:hover{box-shadow:0 10px 24px rgba(17,24,39,.08)}
.cp-card .img{height:150px;background:#eef2ff center/cover no-repeat;display:flex;align-items:center;justify-content:center;font-size:40px;color:#a5b4fc}
.cp-card .t{padding:12px 14px;display:grid;gap:3px}.cp-card b{font-size:16px}.cp-card small{color:#6b7280;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}
.cp-tile{background:#fff;border:1px solid var(--pr-line);border-radius:14px;padding:16px;text-decoration:none;color:var(--pr-ink);display:grid;gap:4px}
.cp-tile:hover{border-color:#a5b4fc}.cp-tile .ic{font-size:28px}.cp-tile small{color:#6b7280}
.cp-band{background:#fff7ed;border-radius:18px;padding:22px}
.cp-pill{display:inline-block;background:#eef2ff;color:#3730a3;border-radius:999px;padding:2px 9px;font-size:12px;font-weight:700;white-space:nowrap}
.cp-offer{display:flex;gap:8px;align-items:flex-start;background:#eff6ff;border-radius:10px;padding:8px 10px;font-size:13px;margin:0 14px 10px}
.cp-tabbar{display:flex;gap:18px;border-bottom:1px solid var(--pr-line);margin-bottom:14px;overflow-x:auto}
.cp-tabbar button{border:0;background:none;padding:8px 0;font:600 14px Inter,system-ui,sans-serif;color:#6b7280;cursor:pointer;border-bottom:2px solid transparent;white-space:nowrap}
.cp-tabbar button.on{color:#3730a3;border-bottom-color:#3730a3}
.cp-box{background:#fff;border:1px solid var(--pr-line);border-radius:14px;padding:16px}
.cp-bar{height:6px;border-radius:999px;background:#e5e7eb;overflow:hidden}.cp-bar i{display:block;height:100%;background:#6366f1}
.cp-logo{width:64px;height:64px;border-radius:50%;border:1px solid var(--pr-line);display:flex;align-items:center;justify-content:center;overflow:hidden;font-weight:800;color:#3730a3;background:#fff;flex:none}.cp-logo img{width:100%;height:100%;object-fit:contain}
.cp-stars{display:inline-flex;flex-direction:row-reverse;gap:4px}.cp-stars button{border:0;background:none;font-size:34px;color:#d1d5db;cursor:pointer;line-height:1;padding:0}
.cp-stars button:hover,.cp-stars button:hover~button{color:#f59e0b}
.cp-cta{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;background:linear-gradient(120deg,#ecfdf5,#e0e7ff);border-radius:18px;padding:26px 28px;margin-bottom:32px}
.cp-cta h2{margin:0 0 4px;font-size:26px}
.cp-empty{background:#fff;border:1px dashed var(--pr-line);border-radius:16px;padding:28px;text-align:center}
.cp-city{display:flex;gap:12px;align-items:center;text-decoration:none;color:var(--pr-ink)}.cp-city .ph{width:84px;height:84px;border-radius:12px;background:#eef2ff center/cover;flex:none;display:flex;align-items:center;justify-content:center;font-size:30px}
.cp-gallery{display:grid;grid-template-columns:2fr 1fr 1fr;grid-auto-rows:150px;gap:8px;border-radius:16px;overflow:hidden}
.cp-gallery a{background:#eef2ff center/cover;display:block;position:relative}.cp-gallery a:first-child{grid-row:span 2}
.cp-gallery span{position:absolute;left:8px;bottom:8px;background:rgba(0,0,0,.6);color:#fff;font-size:12px;border-radius:6px;padding:2px 7px}
.cp-facts{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px}.cp-facts div{background:#f8fafc;border-radius:12px;padding:12px}.cp-facts small{display:block;color:#6b7280;font-size:12.5px}.cp-facts b{font-size:15px}
.cp-head{justify-content:flex-start;gap:10px}.cp-head h1{font-size:24px}
.cp-place{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.35);color:#fff;border-radius:10px;padding:7px 14px;font:700 16px Inter,system-ui,sans-serif;cursor:pointer}
.cp-place:hover{background:rgba(255,255,255,.25)}
.cp-hero.has-banner{padding-top:16px}
.cp-banner{position:relative;max-width:1180px;margin:0 auto;border-radius:18px;overflow:hidden;min-height:300px}
.cp-slide{min-height:300px;background:#1e1b4b center/cover no-repeat;display:flex;justify-content:flex-end;align-items:center;padding:28px 70px}
.cp-slide-copy{max-width:470px;display:grid;gap:10px;color:#fff}.cp-slide-copy h2{color:#fde68a!important;margin:0;font-size:26px}.cp-slide-copy h2 small{color:#e2e8f0;font-size:15px;font-weight:600}
.cp-slide-copy p{margin:0;color:#f1f5f9}.cp-slide-btn{justify-self:start;border:1px solid #fff;color:#fff;text-decoration:none;padding:8px 16px;border-radius:6px;font-weight:700}.cp-slide-btn:hover{background:#fff;color:#1e1b4b}
.cp-bnav{position:absolute;top:50%;transform:translateY(-50%);width:40px;height:40px;border-radius:50%;border:0;background:#fff;font-size:22px;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.25)}.cp-bnav.l{left:14px}.cp-bnav.r{right:14px}
.cp-hero.has-banner .cp-hero-search{margin-top:-40px;position:relative;z-index:3}
.cp-search{background:#fff;border-radius:16px;box-shadow:0 18px 40px rgba(17,24,39,.16);color:var(--pr-ink)}
.cp-stabs{display:flex;align-items:center;border-bottom:1px solid var(--pr-line);overflow-x:auto;padding:0 10px}
.cp-stabs button{border:0;background:none;padding:15px 16px;font:600 15px Inter,system-ui,sans-serif;color:#4b5563;cursor:pointer;border-bottom:3px solid transparent;white-space:nowrap;position:relative}
.cp-stabs button.on{color:#1e3a8a;border-bottom-color:#2563eb;font-weight:800}.cp-stabs .dot{position:absolute;top:12px;right:8px;width:6px;height:6px;border-radius:50%;background:#ef4444}
.cp-stabs .post{margin-left:auto;padding:10px 14px;color:#111827;text-decoration:none;font-weight:700;white-space:nowrap;border-left:1px solid var(--pr-line)}.cp-stabs .post span{background:#16a34a;color:#fff;border-radius:4px;font-size:11px;padding:2px 5px;margin-left:4px}
.cp-sbar{display:flex;align-items:center;gap:8px;padding:12px 14px}
.cp-sbar select{width:auto;max-width:200px;border:0;font-weight:700;padding:10px 6px;border-right:1px solid var(--pr-line);border-radius:0;background:#fff}
.cp-sinput{flex:1;display:flex;align-items:center;gap:8px;position:relative;min-width:0}.cp-sinput input{border:0!important;box-shadow:none!important;padding:10px 4px!important;width:100%}
.cp-ibtn{width:40px;height:40px;border-radius:50%;border:0;background:#eff6ff;color:#2563eb;font-size:18px;cursor:pointer;flex:none}.cp-ibtn.on{background:#fee2e2;color:#dc2626}
.cp-snote{margin:0;padding:0 16px 12px;font-size:13px;color:#4b5563}
.cp-clist{position:absolute;left:0;right:0;top:100%;margin-top:6px;background:#fff;border:1px solid var(--pr-line);border-radius:10px;box-shadow:0 14px 30px rgba(0,0,0,.12);z-index:30;max-height:300px;overflow:auto;padding:6px}
.cp-clist small{display:block;padding:6px 10px;font-weight:700;color:#374151}
.cp-clist button{display:flex;justify-content:space-between;gap:10px;width:100%;text-align:left;padding:10px;border:0;border-bottom:1px solid #f1f5f9;background:#fff;cursor:pointer;font-size:15px;color:#111827}
.cp-clist button:hover{background:#eff6ff}.cp-clist em{font-style:normal;color:#9ca3af;font-size:13px}
.cp-recent{justify-content:center}.cp-recent a.all{background:none;color:#fff;text-decoration:underline}
.cp-trail-row{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:14px}.cp-trail-row>span{color:#c7d2fe;font-weight:700;font-size:14px;margin-right:4px}
.cp-trail-row a{background:#fff;color:#1f2937;border-radius:999px;padding:8px 14px;text-decoration:none;font-size:14px;font-weight:600;box-shadow:0 2px 6px rgba(0,0,0,.08)}
.cp-chooser-wrap{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:1000;display:flex;align-items:flex-start;justify-content:center;padding:60px 16px;overflow:auto}
.cp-chooser-wrap[hidden]{display:none}.cp-chooser-wrap.inline{position:static;background:none;padding:10px 0;z-index:auto}
.cp-chooser{position:relative;background:#fff;color:var(--pr-ink);border-radius:18px;width:min(680px,100%);padding:28px 30px 0;box-shadow:0 30px 60px rgba(0,0,0,.25)}
.cp-chooser h2{font-size:30px;margin:0 0 16px}.cp-x{position:absolute;top:12px;right:14px;border:0;background:none;font-size:28px;cursor:pointer;color:#6b7280}
.cp-ctabs{display:flex;gap:6px;border-bottom:1px solid var(--pr-line);overflow-x:auto}
.cp-ctabs button{border:0;background:none;padding:10px 14px;font:600 15px Inter,system-ui,sans-serif;color:#6b7280;cursor:pointer;border-bottom:3px solid transparent;white-space:nowrap}.cp-ctabs button.on{color:#111827;border-bottom-color:#2563eb}
.cp-cbar{display:flex;gap:8px;align-items:center;margin:16px 0 6px;border:1px solid var(--pr-line);border-radius:12px;padding:8px;box-shadow:0 6px 16px rgba(0,0,0,.06)}
.cp-cbar select{width:auto;border:0;font-weight:700;border-right:1px solid var(--pr-line);border-radius:0;padding:8px}
.cp-cinput{flex:1;display:flex;gap:8px;align-items:center;position:relative;min-width:0}.cp-cinput input{border:0!important;box-shadow:none!important;width:100%;padding:8px 2px!important}
.cp-cerr{color:#b91c1c;font-size:13px;margin:4px 0}
.cp-trail{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:14px 0}.cp-trail a{border:1px solid #bfdbfe;background:#eff6ff;color:#1e3a8a;border-radius:999px;padding:6px 12px;text-decoration:none;font-size:14px}
.cp-cfoot{display:flex;gap:4px;flex-wrap:wrap;align-items:center;background:#f8fafc;margin:16px -30px 0;padding:12px 30px;border-radius:0 0 18px 18px}
.cp-cfoot button{border:0;background:none;padding:6px 10px;border-radius:8px;cursor:pointer;color:#4b5563;font-size:14px}.cp-cfoot button.on{background:#e0e7ff;color:#1e3a8a;font-weight:700}.cp-cfoot a{font-weight:700;font-size:14px}
.cp-lcard{position:relative}.cp-lcard .t{text-decoration:none;color:inherit}.cp-lcard .meta{display:flex;justify-content:space-between;gap:8px;color:#6b7280;font-size:12.5px;margin-top:4px}
.cp-heart-f{position:absolute;top:10px;right:10px;margin:0}.cp-heart{display:grid;place-items:center;width:34px;height:34px;border-radius:50%;border:0;background:rgba(255,255,255,.92);font-size:18px;cursor:pointer;color:#111827;text-decoration:none}.cp-heart.on{color:#e11d48}
.cp-type{display:grid;grid-template-rows:auto 1fr;border-radius:16px;overflow:hidden;text-decoration:none;color:var(--pr-ink);min-height:300px;min-width:230px}
.cp-type .tx{padding:22px 22px 10px;display:grid;gap:6px}.cp-type b{font-size:24px;line-height:1.15}.cp-type small{color:#6b7280}
.cp-type .ph{background:center/cover no-repeat;min-height:170px;display:flex;align-items:center;justify-content:center;font-size:54px}
.cp-type:hover .ph{filter:brightness(1.05)}
@media(max-width:700px){.cp-slide{padding:24px 24px 70px}.cp-bnav{display:none}.cp-sbar{flex-wrap:wrap}.cp-sbar select{max-width:none;flex:1 1 100%;border-right:0;border-bottom:1px solid var(--pr-line)}.cp-chooser{padding:22px 18px 0}.cp-cfoot{margin:16px -18px 0;padding:12px 18px}.cp-cbar{flex-wrap:wrap}}
@media(max-width:960px){.cp-layout{grid-template-columns:1fr}.cp-side{position:static}.cp-arrow{display:none}.cp-gallery{grid-template-columns:1fr 1fr}.cp-gallery a:first-child{grid-column:span 2}}
</style>@endpush
