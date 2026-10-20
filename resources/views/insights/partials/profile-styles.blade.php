<style>
/* Company profile layout */
.pf-head{background:#f3f5f9;padding:18px 0 4px}
.pf-card{display:flex;gap:18px;align-items:center;background:#fff;border:1px solid #e3e8f0;border-radius:14px;padding:18px 20px;box-sizing:border-box}
.pf-logo{display:grid;place-items:center;flex:0 0 84px;width:84px;height:84px;border:1px solid #e8edf4;border-radius:12px;background:#fff;font-size:34px;font-weight:800;color:#0b2545;overflow:hidden}
.pf-logo img{width:100%;height:100%;object-fit:contain}.pf-logo.sm{flex-basis:36px;width:36px;height:36px;font-size:16px;border-radius:8px}
.pf-main{flex:1;min-width:0}.pf-main h1{margin:0;font-size:28px;color:#0b2545}.pf-ext{font-size:18px;color:#7b8aa0;text-decoration:none;margin-left:4px}
.pf-about{margin:6px 0 10px;color:#425066;font-size:15.5px}
.pf-meta{display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center;color:#26364f;font-size:15px}
.pf-badge{padding:3px 9px;border-radius:6px;background:#eef2f7;color:#0b2545;font-weight:700;font-size:13px}.pf-badge.ok{background:#e7f6ee;color:#087443}.pf-badge.light{font-weight:600;color:#425066}
.pf-actions{display:flex;flex-direction:column;gap:8px}.pf-btn{padding:9px 14px;border:1px solid #d5deea;border-radius:9px;background:#fff;color:#0b2545;font:700 14px inherit;cursor:pointer;text-decoration:none;text-align:center;white-space:nowrap}
.pf-btn.light{font-weight:600;color:#425066}
@media(max-width:760px){.pf-card{flex-wrap:wrap}.pf-actions{flex-direction:row}}
.pf-sticky{position:fixed;top:0;left:0;right:0;z-index:60;background:#fff;border-bottom:1px solid #e3e8f0;box-shadow:0 4px 14px rgba(11,37,69,.08);transform:translateY(-110%);transition:transform .18s}
.pf-sticky.on{transform:none}.pf-sticky .ci-wrap{display:flex;gap:12px;align-items:center;padding:8px 16px}.pf-sticky b{flex:1;color:#0b2545;font-size:17px}
.pf-nav .pf-nav-title{display:block;padding:8px 12px;margin-bottom:4px;border-radius:8px;background:#eef2f7;color:#0b2545}
.pf-nav a.active{background:#eaf1fb!important;color:#1d4f86;font-weight:700}
.pf-nav a.active:before{content:'';display:inline-block;width:6px;height:6px;border-radius:50%;background:#1d4f86;margin-right:8px;vertical-align:middle}
.pf-nav .pf-jobs{order:999;margin-top:6px;border-top:1px solid #eef1f5!important;border-radius:0!important}
.pf-metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));border:1px solid #e8edf4;border-radius:10px;overflow:hidden}
.pf-metrics>div{padding:14px 16px;border-right:1px solid #eef1f5;border-bottom:1px solid #eef1f5;background:#fff}
.pf-metrics span{display:block;color:#5b6b82;font-size:13.5px}.pf-metrics b{display:block;font-size:22px;color:#0b2545;margin:4px 0}.pf-metrics small{display:block;color:#7b8aa0;font-size:12.5px}
.pf-text{color:#26364f;line-height:1.7;margin:0 0 10px}
.pf-tags{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}.pf-tags span{padding:6px 12px;border:1px solid #dbe3ef;border-radius:8px;background:#fff;color:#26364f;font-size:14px}
.pf-details{display:grid;grid-template-columns:170px minmax(0,1fr);gap:10px 16px;margin:0}.pf-details dt{color:#5b6b82}.pf-details dd{margin:0;color:#0b2545}
@media(max-width:600px){.pf-details{grid-template-columns:1fr}.pf-details dt{font-weight:700}}
.pf-crumb{display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:12px 14px;border:1px solid #e8edf4;border-radius:10px;margin-bottom:8px;background:#fbfcfe}.pf-crumb span{color:#7b8aa0}
.pf-co{display:inline-flex;gap:8px;align-items:center}.pf-co img{width:26px;height:26px;border-radius:6px;object-fit:contain;border:1px solid #eef1f5}
.pf-tools{float:right;display:flex;gap:6px;margin-top:-4px}.pf-tools button{width:34px;height:34px;border:1px solid #dbe3ef;border-radius:8px;background:#fff;cursor:pointer;color:#425066}
.ci-card.pf-collapsed>*:not(h2):not(.pf-tools){display:none!important}
.pf-help{display:flex;justify-content:center;align-items:center;gap:10px;margin:16px -6px -4px;padding-top:12px;border-top:1px solid #eef1f5;color:#425066;font-size:14px}
.pf-help button{padding:6px 12px;border:1px solid #dbe3ef;border-radius:8px;background:#fff;cursor:pointer;font:inherit}.pf-help button.on{background:#eaf1fb;border-color:#9cb6d3}
.pf-ptabs{display:flex;gap:4px;flex-wrap:wrap;align-items:center;margin:8px 0 10px}.pf-ptabs button{padding:7px 14px;border:0;border-radius:8px;background:#f1f4f8;color:#425066;font-weight:600;cursor:pointer}
.pf-ptabs button.on{background:#fff;color:#0b2545;box-shadow:0 0 0 1px #dbe3ef}.pf-ptabs input{margin-left:auto;padding:8px 12px;border:1px solid #dbe3ef;border-radius:8px;min-width:220px;font:inherit}
.pf-avatar{display:inline-grid;place-items:center;width:32px;height:32px;border-radius:50%;background:#eef2f7;color:#425066;font-size:12px;font-weight:800;margin-right:8px}
@media print{.pf-sticky,.pf-actions,.pf-nav,.pf-tools,.pf-help,header,footer,.site-footer,.sitebar{display:none!important}.ci-main.ci-layout{display:block}}
</style>
