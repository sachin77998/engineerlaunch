<style>
/* Insights dashboard & explore databases */
.db-page{background:#f3f5f9;min-height:70vh}
.db-layout{display:grid;grid-template-columns:270px minmax(0,1fr) 300px;gap:20px;width:min(1440px,100%);margin:0 auto;padding:20px 16px 40px;box-sizing:border-box;align-items:start}
.db-layout.two{grid-template-columns:270px minmax(0,1fr)}
@media(max-width:1180px){.db-layout{grid-template-columns:240px minmax(0,1fr)}.db-right{grid-column:1/-1}}
@media(max-width:800px){.db-layout,.db-layout.two{grid-template-columns:1fr}}
.db-card{background:#fff;border:1px solid #e3e8f0;border-radius:12px;padding:16px;margin-bottom:16px}
.db-card h2{margin:0 0 12px;font-size:18px;color:#0b2545}.db-card h3{margin:0 0 10px;font-size:15px;color:#0b2545}
.db-user{display:flex;gap:12px;align-items:center}.db-avatar{display:grid;place-items:center;width:44px;height:44px;border-radius:50%;background:#0b2545;color:#fff;font-weight:800}
.db-user b{display:block;color:#0b2545}.db-user small{color:#5b6b82;display:block}
.db-nav{display:flex;flex-direction:column}.db-nav a{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:9px 8px;border-radius:8px;color:#26364f;text-decoration:none;font-size:14.5px}
.db-nav a:hover,.db-nav a.on{background:#eef3fa;color:#0b2545}.db-nav a small{color:#7b8aa0;font-size:12px}.db-nav .db-sep{margin:8px 0 4px;padding:0 8px;color:#7b8aa0;font-size:12px;text-transform:uppercase;letter-spacing:.06em}
.db-head{display:flex;justify-content:space-between;align-items:baseline;gap:10px;flex-wrap:wrap;margin:4px 0 10px}.db-head h2{margin:0;font-size:19px;color:#0b2545}.db-head small{color:#5b6b82;font-style:italic}
.db-snap{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}@media(max-width:900px){.db-snap{grid-template-columns:repeat(2,minmax(0,1fr))}}
.db-snap a{display:block;padding:14px;border:1px solid #e3e8f0;border-radius:10px;background:#fff;color:#0b2545;text-decoration:none}.db-snap a:hover{border-color:#f5b800}
.db-snap span{font-weight:700;font-size:14px}.db-snap b{display:block;font-size:22px;margin:4px 0}.db-snap small{color:#5b6b82}
.db-idx{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:12px}
.db-idx div{padding:12px;border:1px solid #e3e8f0;border-radius:10px;background:#fff}.db-idx span{color:#425066;font-size:13px}.db-idx b{display:block;font-size:19px;color:#0b2545;margin:2px 0}
.db-up{color:#087443}.db-down{color:#b42318}
.db-movers{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:18px}
.db-movers .db-card{margin:0}.db-movers .db-row{display:flex;justify-content:space-between;padding:6px 0;font-size:14px;border-bottom:1px solid #f1f4f8}.db-movers .db-row:last-child{border:0}
.db-breadth{height:8px;border-radius:99px;background:#e0464f;overflow:hidden;margin-top:8px}.db-breadth span{display:block;height:100%;background:#22a06b}
.db-cta{background:#fff}.db-cta ul{list-style:none;margin:10px 0 14px;padding:0}.db-cta li{padding:4px 0 4px 22px;position:relative;color:#26364f;font-size:14px}.db-cta li:before{content:'✓';position:absolute;left:0;color:#087443;font-weight:800}
.db-btn{display:block;text-align:center;padding:11px;border-radius:9px;background:#1d5fd1;color:#fff;font-weight:800;text-decoration:none}.db-btn.alt{background:#fff;color:#1d5fd1;border:1px solid #c9d8f2;margin-top:8px}
.db-recent a{display:flex;gap:10px;align-items:center;padding:8px 0;color:#0b2545;text-decoration:none;border-bottom:1px solid #f1f4f8}.db-recent img,.db-logo{width:34px;height:34px;border-radius:8px;object-fit:contain;background:#fff;border:1px solid #eef1f5;flex:0 0 34px}
.db-logo.txt{display:grid;place-items:center;background:#0b2545;color:#fff;font-weight:800;font-size:14px}
.db-recent small{display:block;color:#5b6b82;font-size:12px}
/* explore table */
.ex-top{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;flex-wrap:wrap;background:#fff;border:1px solid #e3e8f0;border-radius:12px;padding:14px 16px;margin-bottom:12px}
.ex-top small{color:#5b6b82}.ex-top h1{margin:2px 0 0;font-size:21px;color:#0b2545}.ex-top h1 span{font-size:14px;color:#5b6b82;font-weight:500;margin-left:10px}
.ex-filters{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px}.ex-filters input,.ex-filters select{padding:8px 10px;border:1px solid #d5deea;border-radius:8px;font:inherit;background:#fff}
.ex-filters input{min-width:220px;flex:1}.ex-filters button{padding:8px 16px;border:0;border-radius:8px;background:#0b2545;color:#fff;font-weight:700;cursor:pointer}
.ex-table-wrap{overflow-x:auto;background:#fff;border:1px solid #e3e8f0;border-radius:12px}
.ex-table{width:100%;border-collapse:collapse;font-size:14px}.ex-table th{position:sticky;top:0;background:#f6f8fb;color:#26364f;text-align:left;padding:12px;white-space:nowrap;border-bottom:1px solid #e3e8f0}
.ex-table td{padding:12px;border-bottom:1px solid #f0f3f7;vertical-align:middle}.ex-table td.num{white-space:nowrap}
.ex-co{display:flex;gap:10px;align-items:center;min-width:230px}.ex-co a{color:#0b2545;font-weight:700;text-decoration:none}.ex-co small{display:block;color:#5b6b82}
.ex-badge{display:inline-block;margin-left:6px;padding:1px 6px;border:1px solid #c9d6ea;border-radius:5px;font-size:11px;color:#1d4f86;font-weight:700}
.ex-stage{display:inline-block;padding:2px 8px;border-radius:6px;font-size:12px;font-weight:700;background:#e9f2fd;color:#1d4f86}.ex-stage.pvt{background:#f1f3f6;color:#425066}
.ex-desc{max-width:280px;color:#425066;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.ex-gate{display:grid;grid-template-columns:1.2fr 1.3fr .9fr;gap:0;background:#fff;border:1px solid #f0d58a;border-radius:12px;margin-top:14px;overflow:hidden}
@media(max-width:1000px){.ex-gate{grid-template-columns:1fr}}
.ex-gate-bar{grid-column:1/-1;display:flex;gap:14px;align-items:center;flex-wrap:wrap;padding:12px 18px;background:#fff8e6;color:#5a4400}
.ex-gate-bar .bar{width:240px;height:6px;border-radius:99px;background:#f0e2b5;overflow:hidden}.ex-gate-bar .bar span{display:block;height:100%;background:#f5b800}
.ex-gate>div{padding:18px}.ex-gate h4{margin:0 0 4px;font-size:12px;letter-spacing:.06em;color:#5b6b82;text-transform:uppercase}.ex-gate h3{margin:0 0 12px;color:#0b2545}
.ex-gate ul{list-style:none;margin:0;padding:0}.ex-gate li{padding:6px 0 6px 26px;position:relative}.ex-gate li:before{content:'✓';position:absolute;left:0;top:6px;width:18px;height:18px;border-radius:50%;background:#22a06b;color:#fff;font-size:11px;display:grid;place-items:center}
.ex-dbs{display:grid;grid-template-columns:1fr 1fr;gap:8px}.ex-dbs a{display:flex;justify-content:space-between;padding:10px 12px;border:1px solid #e3e8f0;border-radius:8px;color:#26364f;text-decoration:none}.ex-dbs a.on{background:#eef3fa;color:#1d4f86;font-weight:700}
.ex-price{border-left:1px solid #f0e2b5}.ex-price b{font-size:28px;color:#0b2545}
</style>
