<style>
.dt-wrap{max-width:1200px;margin:0 auto;padding:0 18px}
.dt-hero{background:linear-gradient(135deg,#0b2545,#13315c);color:#fff;padding:26px 0 22px}
.dt-hero h1{margin:0 0 6px;font:800 28px/1.2 Inter,system-ui,sans-serif}
.dt-hero p{margin:0;color:#c9d6ea;font-size:14.5px}
.dt-nav{display:flex;flex-wrap:wrap;gap:6px;margin-top:16px}
.dt-nav a{padding:8px 14px;border-radius:999px;background:rgba(255,255,255,.1);color:#fff;text-decoration:none;font:600 13.5px Inter,system-ui,sans-serif}
.dt-nav a.on,.dt-nav a:hover{background:#f4b400;color:#0b2545}
.dt-main{padding:20px 0 40px;display:grid;gap:16px}
.dt-card{background:#fff;border:1px solid #e3eaf3;border-radius:14px;padding:18px;box-shadow:0 1px 2px rgba(11,37,69,.04)}
.dt-card h2{margin:0 0 12px;font:800 18px Inter,system-ui,sans-serif;color:#0b2545}
.dt-card h3{margin:14px 0 8px;font:700 15px Inter,system-ui,sans-serif;color:#0b2545}
.dt-muted{color:#5b6b82;font-size:13.5px}
.dt-flash{padding:11px 14px;border-radius:10px;margin:0;font-size:14px}
.dt-flash.ok{background:#e8f7ee;color:#14532d;border:1px solid #bfe5cc}
.dt-flash.warn{background:#fff6dc;color:#713f12;border:1px solid #f5dc8a}
.dt-flash.bad{background:#fdecec;color:#7f1d1d;border:1px solid #f5c2c2}
.dt-btn{display:inline-flex;align-items:center;gap:6px;border:0;border-radius:10px;padding:10px 16px;background:#0b2545;color:#fff;font:700 14px Inter,system-ui,sans-serif;cursor:pointer;text-decoration:none}
.dt-btn.gold{background:#f4b400;color:#0b2545}.dt-btn.light{background:#eef3f9;color:#0b2545}.dt-btn.red{background:#b91c1c}
.dt-btn:disabled{opacity:.5;cursor:not-allowed}
.dt-btn.sm{padding:6px 10px;font-size:12.5px;border-radius:8px}
.dt-filters{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.dt-filters input,.dt-filters select,.dt-form input,.dt-form select,.dt-form textarea{border:1px solid #cfd9e6;border-radius:9px;padding:9px 11px;font:14px Inter,system-ui,sans-serif;background:#fff;color:#0b2545;max-width:100%}
.dt-form{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px}
.dt-form label{display:grid;gap:5px;font:600 13px Inter,system-ui,sans-serif;color:#33465f}
.dt-form label.wide{grid-column:1/-1}
.dt-form .check{display:flex;gap:8px;align-items:flex-start;font-weight:500}
.dt-table-wrap{overflow-x:auto;border:1px solid #e3eaf3;border-radius:12px}
.dt-table{width:100%;border-collapse:collapse;font-size:13.5px}
.dt-table th{background:#f4f7fb;color:#33465f;text-align:left;font-weight:700;padding:9px 10px;white-space:nowrap}
.dt-table td{padding:9px 10px;border-top:1px solid #edf2f7;vertical-align:top}
.dt-table td.num,.dt-table th.num{text-align:right;white-space:nowrap}
.up{color:#15803d;font-weight:700}.down{color:#b91c1c;font-weight:700}
.dt-pill{display:inline-block;padding:2px 9px;border-radius:999px;font:700 11.5px Inter,system-ui,sans-serif;background:#eef3f9;color:#33465f;white-space:nowrap}
.dt-pill.allotted,.dt-pill.verified,.dt-pill.passed{background:#e8f7ee;color:#14532d}
.dt-pill.submitted,.dt-pill.otp_pending,.dt-pill.pending,.dt-pill.draft{background:#fff6dc;color:#713f12}
.dt-pill.rejected,.dt-pill.failed,.dt-pill.cancelled,.dt-pill.suspended{background:#fdecec;color:#7f1d1d}
.dt-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
.dt-fund{display:grid;gap:6px;border:1px solid #e3eaf3;border-radius:12px;padding:12px;text-decoration:none;color:#0b2545;background:#fff}
.dt-fund:hover{border-color:#f4b400;box-shadow:0 4px 14px rgba(11,37,69,.08)}
.dt-fund strong{font-size:14px;line-height:1.3}
.dt-fund .meta{display:flex;justify-content:space-between;font-size:12.5px;color:#5b6b82}
.dt-stats{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
.dt-stat{background:#f8fafc;border:1px solid #e3eaf3;border-radius:12px;padding:12px}
.dt-stat span{display:block;font-size:12px;color:#5b6b82}.dt-stat b{display:block;font:800 19px Inter,system-ui,sans-serif;color:#0b2545;margin-top:3px}
.dt-two{display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:16px;align-items:start}
.dt-steps{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
.dt-steps a{padding:6px 10px;border-radius:8px;font:600 12.5px Inter,system-ui,sans-serif;text-decoration:none;background:#eef3f9;color:#33465f}
.dt-steps a.on{background:#0b2545;color:#fff}.dt-steps a.done{background:#e8f7ee;color:#14532d}
.dt-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
.dt-tabs button{border:1px solid #cfd9e6;background:#fff;border-radius:9px;padding:8px 12px;font:700 13px Inter,system-ui,sans-serif;color:#33465f;cursor:pointer}
.dt-tabs button.on{background:#0b2545;color:#fff;border-color:#0b2545}
.dt-result{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-top:12px}
.dt-modal{position:fixed;inset:0;background:rgba(11,37,69,.45);display:flex;align-items:center;justify-content:center;padding:16px;z-index:60}
.dt-modal>div{background:#fff;border-radius:14px;padding:20px;max-width:420px;width:100%}
.dt-chart{width:100%;height:auto;display:block}
@media(max-width:860px){.dt-two{grid-template-columns:1fr}}
</style>
