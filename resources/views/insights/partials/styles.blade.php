<style>
.ci-wrap{width:min(1200px,calc(100% - 32px));margin:0 auto}
.ci-hero{background:linear-gradient(135deg,#0b2545,#13315c);color:#fff;padding:34px 0 30px;border-bottom:3px solid #f4b400}
.ci-hero h1{margin:0 0 6px;font-size:clamp(26px,4vw,38px);color:#fff!important}.ci-hero p{margin:0 0 16px;color:#d6e2f2;max-width:780px;line-height:1.6}
.ci-search{display:flex;gap:8px;flex-wrap:wrap;max-width:820px}.ci-search input{flex:1 1 280px;min-width:0;padding:13px 16px;border-radius:12px;border:0;font:inherit;font-size:16px}
.ci-hero-stats{display:flex;flex-wrap:wrap;gap:18px;margin-top:14px;font-size:13.5px;color:#c9d6ea}.ci-hero-stats b{color:#f4b400}
.ci-btn{padding:11px 18px;border-radius:10px;border:1px solid #0b2545;background:#0b2545;color:#fff;font:inherit;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}
.ci-btn.gold{background:#f4b400;border-color:#f4b400;color:#0b2545}.ci-btn.light{background:#fff;color:#0b2545}
.ci-main{padding:24px 0 60px}.ci-h2{font-size:20px;color:#0b2545;margin:18px 0 12px}
.ci-card{background:#fff;border:1px solid #dfe6f1;border-radius:14px;padding:18px;margin-bottom:18px}.ci-card h2{margin:0 0 12px;font-size:19px;color:#0b2545}
.ci-muted{color:#5b6b82;line-height:1.6}.ci-flash{padding:11px 14px;border-radius:10px;font-weight:600}.ci-flash.ok{background:#e8f7ef;color:#087443}.ci-flash.warn{background:#fff6dc;color:#7a5600}
.ci-facts{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px}.ci-facts div{padding:10px 12px;border:1px solid #e8edf5;border-radius:10px;background:#f9fbfe}
.ci-facts span{display:block;font-size:12px;color:#5b6b82}.ci-facts b{color:#0b2545;font-size:14.5px;word-break:break-word}
.ci-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px}
.ci-company{display:flex;gap:12px;padding:14px;background:#fff;border:1px solid #dfe6f1;border-radius:12px;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
.ci-company:hover{border-color:#f4b400;box-shadow:0 6px 18px rgba(11,37,69,.08)}
.ci-logo{flex:0 0 44px;height:44px;border-radius:10px;background:#0b2545;color:#f4b400;display:grid;place-items:center;font-weight:800;overflow:hidden}.ci-logo img{width:100%;height:100%;object-fit:contain;background:#fff}
.ci-company-body{display:grid;gap:2px;min-width:0}.ci-company-body strong{color:#0b2545}.ci-company-body small{color:#5b6b82;font-size:12.5px}.ci-company-body em{font-style:normal;font-size:12px;font-weight:700;color:#087443}
.ci-inline{display:flex;flex-wrap:wrap;gap:8px;align-items:center}.ci-inline select,.ci-inline input[type=text]{padding:9px 10px;border:1px solid #dfe6f1;border-radius:9px;font:inherit}
.ci-head{display:flex;flex-wrap:wrap;gap:16px;align-items:center}.ci-head .ci-logo{flex-basis:64px;height:64px;font-size:26px}
.ci-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}.ci-chips span{padding:4px 10px;border-radius:999px;background:rgba(255,255,255,.12);font-size:12.5px;color:#fff}.ci-chips span.ok{background:#16a34a}.ci-chips span.bad{background:#dc2626}
.ci-metrics{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px}
.ci-metric{padding:14px;border-radius:12px;border:1px solid #dfe6f1;background:#fff}.ci-metric span{display:block;font-size:12.5px;color:#5b6b82}.ci-metric b{display:block;font-size:20px;color:#0b2545;margin-top:3px}.ci-metric small{color:#5b6b82;font-size:11.5px}
.up{color:#087443}.down{color:#b42318}
.ci-table-wrap{overflow-x:auto;border:1px solid #e8edf5;border-radius:10px}
.ci-table{width:100%;border-collapse:collapse;font-size:13.5px}.ci-table th,.ci-table td{padding:8px 11px;border-bottom:1px solid #eef2f7;text-align:right;white-space:nowrap}
.ci-table th:first-child,.ci-table td:first-child{text-align:left;position:sticky;left:0;background:#fff}.ci-table thead th{background:#0b2545;color:#fff}.ci-table thead th:first-child{background:#0b2545}
.ci-table tr.ci-sub td{font-weight:700;background:#f7f9fc}.ci-table tr.ci-sub td:first-child{background:#f7f9fc}
.ci-bars{display:flex;align-items:flex-end;gap:10px;height:170px;padding:10px 4px 0;border-bottom:1px solid #dfe6f1;overflow-x:auto}
.ci-bar{flex:1 0 54px;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:4px;font-size:11.5px;color:#5b6b82}
.ci-bar i{display:block;width:100%;max-width:46px;border-radius:6px 6px 0 0;background:linear-gradient(#f4b400,#d99a00)}.ci-bar i.pat{background:linear-gradient(#1d4ed8,#0b2545)}.ci-bar i.neg{background:#dc2626}
.ci-tabs{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 14px}.ci-tabs a{padding:7px 13px;border-radius:999px;border:1px solid #dfe6f1;background:#fff;color:#0b2545;text-decoration:none;font-weight:600;font-size:13.5px}.ci-tabs a:hover{border-color:#f4b400}
.ci-two{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px}
.ci-form-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:8px}.ci-form-grid label{display:grid;gap:3px;font-size:12.5px;color:#334155}.ci-form-grid input,.ci-form-grid select{padding:8px 9px;border:1px solid #dfe6f1;border-radius:8px;font:inherit}
.ci-metric small{display:block;margin-top:4px;font-size:12px}
.ci-health{display:grid;grid-template-columns:minmax(200px,260px) 1fr;gap:16px}@media(max-width:700px){.ci-health{grid-template-columns:1fr}}
.ci-health-score{padding:16px;border-radius:12px;background:#f4f6fb}.ci-health-score span{font-size:12px;color:#5b6b82}.ci-health-score b{display:block;font-size:44px;color:#0b2545;line-height:1.1}.ci-health-score b small{font-size:16px;color:#5b6b82}.ci-health-score em{font-style:normal;font-weight:800;color:#0b2545;font-size:18px}
.ci-gauge{height:7px;border-radius:4px;background:#e5ecf4;overflow:hidden;margin-top:8px}.ci-gauge i{display:block;height:100%;background:linear-gradient(90deg,#0b2545,#f4b400)}
.ci-health-parts{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px}.ci-health-parts>div{padding:12px;border:1px solid #e8edf5;border-radius:10px}.ci-health-parts strong{display:flex;justify-content:space-between;color:#0b2545}.ci-health-parts small{color:#5b6b82;font-size:12px}
.ci-risk{display:flex;gap:10px;align-items:center;margin:0 0 6px;padding:9px 11px;border:1px solid #eef2f7;border-radius:9px;font-size:13.5px}.ci-risk .lvl{flex:0 0 auto;padding:2px 8px;border-radius:999px;font-size:11.5px;font-weight:700;background:#eef2f7;color:#334155}
.ci-risk .lvl.high{background:#fee2e2;color:#b42318}.ci-risk .lvl.moderate{background:#fff6dc;color:#7a5600}.ci-risk .lvl.low{background:#eef2f7;color:#334155}.ci-risk .lvl.ok{background:#e8f7ef;color:#087443}
.ci-donut{width:150px;height:150px;border-radius:50%;margin:auto;position:relative}.ci-donut:after{content:'';position:absolute;inset:32px;border-radius:50%;background:#fff}
/* Company page: section menu becomes a sticky sidebar on wide screens. */
@media(min-width:1000px){
  .ci-main.ci-layout{display:grid;grid-template-columns:210px minmax(0,1fr);column-gap:22px;align-items:start}
  .ci-main.ci-layout>*{grid-column:2;min-width:0}
  .ci-main.ci-layout>.ci-tabs{grid-column:1;grid-row:1 / span 60;position:sticky;top:12px;flex-direction:column;flex-wrap:nowrap;gap:2px;padding:10px;background:#fff;border:1px solid #dfe6f1;border-radius:14px}
  .ci-main.ci-layout>.ci-tabs a{border:0;border-radius:8px;padding:9px 12px;background:transparent}
  .ci-main.ci-layout>.ci-tabs a:hover{background:#fff6dc}
}
details.ci-more summary{cursor:pointer;font-weight:700;color:#0b2545}
</style>
