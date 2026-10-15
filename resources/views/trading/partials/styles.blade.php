<style>
:root{--dt-ink:#1b2437;--dt-ink2:#44475b;--dt-mute:#7c7e8c;--dt-line:#e9e9eb;--dt-bg:#f7f8fa;--dt-card:#fff;--dt-up:#00a676;--dt-down:#e5484d;--dt-brand:#0b2545;--dt-gold:#f4b400;--dt-accent:#1f6feb;--dt-soft:#eef4ff}
.dt-page{background:var(--dt-bg);min-height:70vh;color:var(--dt-ink);font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
.dt-wrap{max-width:1240px;margin:0 auto;padding:0 20px}
.dt-bar{position:sticky;top:0;z-index:30;background:#fff;border-bottom:1px solid var(--dt-line)}
.dt-bar-in{display:flex;align-items:center;gap:6px;min-height:58px;flex-wrap:wrap}
.dt-tabs-main{display:flex;gap:2px;flex-wrap:wrap;flex:1 1 auto}
.dt-tabs-main a{padding:18px 12px 16px;font:600 14.5px Inter,system-ui,sans-serif;color:var(--dt-ink2);text-decoration:none;border-bottom:3px solid transparent;white-space:nowrap}
.dt-tabs-main a:hover{color:var(--dt-ink)}.dt-tabs-main a.on{color:var(--dt-brand);border-bottom-color:var(--dt-gold)}
.dt-search{position:relative;flex:0 1 320px}
.dt-search input{width:100%;border:1px solid var(--dt-line);background:var(--dt-bg);border-radius:10px;padding:10px 12px 10px 36px;font:14px Inter,system-ui,sans-serif;color:var(--dt-ink)}
.dt-search svg{position:absolute;left:11px;top:11px;color:var(--dt-mute)}
.dt-search-results{position:absolute;top:44px;left:0;right:0;background:#fff;border:1px solid var(--dt-line);border-radius:12px;box-shadow:0 12px 32px rgba(16,24,40,.12);max-height:380px;overflow:auto;z-index:40}
.dt-search-results a{display:flex;justify-content:space-between;gap:10px;padding:10px 12px;text-decoration:none;color:var(--dt-ink);font-size:13.5px;border-bottom:1px solid #f2f2f4}
.dt-search-results a:hover{background:var(--dt-bg)}
.dt-broker{display:inline-flex;align-items:center;gap:6px;font:600 12.5px Inter,system-ui,sans-serif;color:var(--dt-ink2);padding:7px 10px;border:1px solid var(--dt-line);border-radius:999px;text-decoration:none;background:#fff}
.dt-dot{width:8px;height:8px;border-radius:50%;background:#c4c4c4;display:inline-block}.dt-dot.live{background:var(--dt-up);box-shadow:0 0 0 3px rgba(0,166,118,.15)}
.dt-head{padding:26px 0 8px}
.dt-head h1{margin:0;font:700 26px/1.25 Inter,system-ui,sans-serif;color:var(--dt-ink)}
.dt-head p{margin:6px 0 0;color:var(--dt-mute);font-size:14px}
.dt-main{padding:14px 0 48px;display:grid;gap:18px;background:var(--dt-bg);box-shadow:0 0 0 100vmax var(--dt-bg);clip-path:inset(0 -100vmax)}
.dt-card{background:var(--dt-card);border:1px solid var(--dt-line);border-radius:16px;padding:20px}
.dt-card h2{margin:0 0 14px;font:700 18px Inter,system-ui,sans-serif;color:var(--dt-ink)}
.dt-card h3{margin:16px 0 10px;font:700 15px Inter,system-ui,sans-serif;color:var(--dt-ink)}
.dt-sec-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:14px}
.dt-sec-head h2{margin:0}.dt-sec-head a{font:600 13.5px Inter,system-ui,sans-serif;color:var(--dt-accent);text-decoration:none}
.dt-muted{color:var(--dt-mute);font-size:13.5px}
.dt-flash{padding:12px 14px;border-radius:12px;margin:0;font-size:14px}
.dt-flash.ok{background:#e9f8f2;color:#0b5c43;border:1px solid #bfe9d8}.dt-flash.warn{background:#fff7e0;color:#7a4b00;border:1px solid #f6dd9b}.dt-flash.bad{background:#fdeeee;color:#8a1c1c;border:1px solid #f6c9c9}
.dt-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:0;border-radius:10px;padding:11px 18px;background:var(--dt-brand);color:#fff;font:600 14px Inter,system-ui,sans-serif;cursor:pointer;text-decoration:none;white-space:nowrap}
.dt-btn.gold{background:var(--dt-gold);color:#1b2437}.dt-btn.light{background:#fff;color:var(--dt-ink);border:1px solid var(--dt-line)}.dt-btn.green{background:var(--dt-up)}.dt-btn.red{background:var(--dt-down)}
.dt-btn.sm{padding:7px 12px;font-size:13px;border-radius:9px}.dt-btn.block{width:100%}
.dt-btn:disabled{opacity:.5;cursor:not-allowed}
.up{color:var(--dt-up)!important;font-weight:600}.down{color:var(--dt-down)!important;font-weight:600}
.dt-chip{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border:1px solid var(--dt-line);border-radius:999px;background:#fff;font:600 13px Inter,system-ui,sans-serif;color:var(--dt-ink2);text-decoration:none;cursor:pointer;white-space:nowrap;list-style:none}
.dt-chip::-webkit-details-marker{display:none}.dt-chip:hover{border-color:#c9cbd4}
.dt-chip.on{background:var(--dt-soft);border-color:#b9d1ff;color:#0b4fd6}
.dt-chip .n{background:#0b4fd6;color:#fff;border-radius:999px;font-size:11px;padding:1px 6px}
.dt-chips{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.dt-dd{position:relative}
.dt-dd>summary{list-style:none}.dt-dd>summary::-webkit-details-marker{display:none}
.dt-dd>summary:after{content:'';width:6px;height:6px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg);margin:-3px 0 0 4px;opacity:.6}
.dt-panel{position:absolute;top:44px;left:0;z-index:35;background:#fff;border:1px solid var(--dt-line);border-radius:14px;box-shadow:0 16px 40px rgba(16,24,40,.14);width:300px;max-width:90vw}
.dt-panel-body{max-height:340px;overflow:auto;padding:10px 14px}
.dt-panel-foot{display:flex;justify-content:space-between;gap:8px;padding:10px 14px;border-top:1px solid var(--dt-line)}
.dt-opt{display:flex;align-items:center;gap:10px;padding:8px 2px;font-size:14px;color:var(--dt-ink2);cursor:pointer}
.dt-opt input{width:16px;height:16px;accent-color:#0b4fd6}
.dt-opt small{margin-left:auto;color:var(--dt-mute)}
.dt-group>summary{display:flex;align-items:center;gap:10px;padding:8px 2px;font:600 14px Inter,system-ui,sans-serif;color:var(--dt-ink);cursor:pointer;list-style:none}
.dt-group>summary::-webkit-details-marker{display:none}
.dt-group .dt-opt{padding-left:26px}
.dt-table-wrap{overflow-x:auto}
.dt-table{width:100%;border-collapse:collapse;font-size:14px}
.dt-table th{color:var(--dt-mute);text-align:left;font:600 12.5px Inter,system-ui,sans-serif;padding:12px 12px;border-bottom:1px solid var(--dt-line);white-space:nowrap;background:#fafafb}
.dt-table th a{color:inherit;text-decoration:none}.dt-table th a.on{color:var(--dt-ink)}
.dt-table td{padding:14px 12px;border-bottom:1px solid #f1f1f3;vertical-align:middle}
.dt-table tr:hover td{background:#fbfcfe}
.dt-table .num{text-align:right;white-space:nowrap}
.dt-name{display:flex;align-items:center;gap:12px;min-width:260px;color:var(--dt-ink);text-decoration:none}
.dt-name b{font-weight:600;font-size:14.5px;line-height:1.35}.dt-name small{display:block;color:var(--dt-mute);font-size:12.5px;margin-top:2px}
.dt-logo{flex:0 0 auto;width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font:800 13px Inter,system-ui,sans-serif;letter-spacing:.3px;box-shadow:inset 0 0 0 1px rgba(0,0,0,.06)}
.dt-logo.lg{width:56px;height:56px;border-radius:14px;font-size:18px}
.dt-stars{color:#f5a524;letter-spacing:1px;font-size:13px;white-space:nowrap}.dt-stars i{color:#d9d9de;font-style:normal}
.dt-pill{display:inline-block;padding:3px 10px;border-radius:999px;font:600 12px Inter,system-ui,sans-serif;background:#f1f2f5;color:var(--dt-ink2);white-space:nowrap}
.dt-pill.allotted,.dt-pill.verified,.dt-pill.passed,.dt-pill.complete{background:#e6f7f0;color:#0b5c43}
.dt-pill.submitted,.dt-pill.otp_pending,.dt-pill.pending,.dt-pill.draft,.dt-pill.sent,.dt-pill.open,.dt-pill.created{background:#fff4d6;color:#7a4b00}
.dt-pill.rejected,.dt-pill.failed,.dt-pill.cancelled,.dt-pill.suspended,.dt-pill.overdue{background:#fdecec;color:#8a1c1c}
.dt-collections{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px}
.dt-collection{display:grid;justify-items:center;gap:10px;text-align:center;text-decoration:none;color:var(--dt-ink);padding:18px 10px;border-radius:16px;background:#fff;border:1px solid var(--dt-line);transition:transform .12s,box-shadow .12s}
.dt-collection:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(16,24,40,.08)}
.dt-collection .ic{width:64px;height:64px;border-radius:16px;background:var(--dt-soft);display:flex;align-items:center;justify-content:center;color:#0b4fd6}
.dt-collection b{font-size:14px}.dt-collection small{color:var(--dt-mute);font-size:12px}
.dt-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px}
.dt-fcard{display:grid;gap:12px;padding:16px;border:1px solid var(--dt-line);border-radius:16px;background:#fff;text-decoration:none;color:var(--dt-ink);transition:box-shadow .12s}
.dt-fcard:hover{box-shadow:0 10px 24px rgba(16,24,40,.08)}
.dt-fcard b{font-size:14.5px;font-weight:600;line-height:1.35;min-height:39px}
.dt-fcard .row{display:flex;justify-content:space-between;align-items:baseline}
.dt-fcard .ret{font:700 18px Inter,system-ui,sans-serif}
.dt-amcs{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:12px}
.dt-amc{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--dt-line);border-radius:14px;text-decoration:none;color:var(--dt-ink);background:#fff}
.dt-amc:hover{border-color:#c9cbd4}.dt-amc b{font-size:14px;font-weight:600}.dt-amc small{display:block;color:var(--dt-mute);font-size:12px}
.dt-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px}
.dt-stat{background:#fff;border:1px solid var(--dt-line);border-radius:14px;padding:14px}
.dt-stat span{display:block;font-size:12.5px;color:var(--dt-mute)}.dt-stat b{display:block;font:700 20px Inter,system-ui,sans-serif;color:var(--dt-ink);margin-top:4px}
.dt-kv{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:0}
.dt-kv div{padding:12px 0;border-bottom:1px solid #f1f1f3}.dt-kv span{display:block;color:var(--dt-mute);font-size:12.5px}.dt-kv b{font-weight:600;font-size:14.5px}
.dt-two{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:18px;align-items:start}
.dt-sticky{position:sticky;top:76px}
.dt-form{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}
.dt-form label{display:grid;gap:6px;font:600 13px Inter,system-ui,sans-serif;color:var(--dt-ink2)}
.dt-form label.wide{grid-column:1/-1}.dt-form .check{display:flex;gap:8px;align-items:flex-start;font-weight:500}
.dt-form input,.dt-form select,.dt-form textarea,.dt-input{border:1px solid #dcdde3;border-radius:10px;padding:10px 12px;font:14px Inter,system-ui,sans-serif;background:#fff;color:var(--dt-ink);max-width:100%}
.dt-form input:focus,.dt-form select:focus,.dt-input:focus{outline:2px solid #b9d1ff;border-color:#7aa5ff}
.dt-seg{display:inline-flex;border:1px solid var(--dt-line);border-radius:10px;overflow:hidden;background:#fff}
.dt-seg button,.dt-seg a,.dt-seg label{padding:9px 16px;border:0;background:#fff;font:600 13.5px Inter,system-ui,sans-serif;color:var(--dt-ink2);cursor:pointer;text-decoration:none}
.dt-seg .on,.dt-seg input:checked+span{background:var(--dt-brand);color:#fff}
.dt-seg.buy .on{background:var(--dt-up)}.dt-seg.sell .on{background:var(--dt-down)}
.dt-steps{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px}
.dt-steps a{padding:7px 11px;border-radius:9px;font:600 12.5px Inter,system-ui,sans-serif;text-decoration:none;background:#f1f2f5;color:var(--dt-ink2)}
.dt-steps a.on{background:var(--dt-brand);color:#fff}.dt-steps a.done{background:#e6f7f0;color:#0b5c43}
.dt-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.dt-tabs button{border:1px solid var(--dt-line);background:#fff;border-radius:10px;padding:9px 14px;font:600 13.5px Inter,system-ui,sans-serif;color:var(--dt-ink2);cursor:pointer}
.dt-tabs button.on{background:var(--dt-brand);color:#fff;border-color:var(--dt-brand)}
.dt-result{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px;margin-top:14px}
.dt-modal{position:fixed;inset:0;background:rgba(16,24,40,.45);display:flex;align-items:center;justify-content:center;padding:16px;z-index:60}
.dt-modal>div{background:#fff;border-radius:18px;padding:22px;max-width:420px;width:100%}
.dt-chart{width:100%;height:auto;display:block}
.dt-bars{display:grid;gap:10px}
.dt-barrow{display:grid;grid-template-columns:70px 1fr 70px;align-items:center;gap:10px;font-size:13px}
.dt-bartrack{height:10px;background:#f1f2f5;border-radius:999px;overflow:hidden;position:relative}
.dt-bartrack i{position:absolute;top:0;bottom:0;left:0;border-radius:999px;background:var(--dt-up)}
.dt-bartrack i.cat{background:#b9c2d0;height:4px;top:3px}
.dt-ltp{font:700 32px Inter,system-ui,sans-serif;color:var(--dt-ink)}
.dt-flash-tick{animation:dtflash .6s}
@keyframes dtflash{from{background:#fff4d6}to{background:transparent}}
.dt-pager nav{display:flex;justify-content:center}
.dt-empty{padding:40px 10px;text-align:center;color:var(--dt-mute)}
@media(max-width:980px){.dt-two{grid-template-columns:1fr}.dt-sticky{position:static}.dt-search{flex:1 1 100%;order:3;margin-bottom:10px}}
</style>
