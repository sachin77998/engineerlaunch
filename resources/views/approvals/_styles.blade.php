@push('styles')
<style>
    .sa-wrap{width:min(1200px,calc(100% - 32px));margin:24px auto 48px}
    .sa-wrap h1{font-size:clamp(26px,4vw,38px);margin:6px 0 14px;color:#10213e}
    .sa-crumbs{font-size:13.5px;color:#526176}.sa-crumbs a{color:#1d4ed8}
    .sa-card{background:#fff;border:1px solid #dbe4f0;border-radius:12px;padding:18px;margin:16px 0;overflow-wrap:anywhere}
    .sa-card h2{font-size:19px;margin:0 0 12px;color:#10213e}
    .sa-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px}
    .sa-state{display:block;background:#fff;border:1px solid #dbe4f0;border-radius:12px;padding:14px 16px;text-decoration:none;color:#10213e}
    .sa-state:hover{border-color:#1d4ed8}.sa-state b{display:block;font-size:16px}.sa-state small{color:#526176;display:block;margin-top:4px;line-height:1.5}
    .sa-pill{display:inline-block;background:#eef6ff;color:#1e40af;border-radius:999px;padding:3px 10px;font-size:12px;margin:2px 4px 2px 0;text-decoration:none}
    .sa-pill.on{background:#1d4ed8;color:#fff}.sa-pill.ok{background:#dcfce7;color:#14532d}.sa-pill.warn{background:#fef3c7;color:#92400e}.sa-pill.grey{background:#f1f5f9;color:#475569}
    .sa-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;align-items:end}
    .sa-filters label{display:block;font-size:12.5px;font-weight:700;margin-bottom:4px;color:#334155}
    .sa-filters select,.sa-filters input{width:100%;min-width:0}
    .sa-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:14px 0}.sa-tabs a{padding:8px 14px;border-radius:8px;background:#e5efff;color:#173f80;text-decoration:none;font-weight:600}.sa-tabs a.on{background:#173f80;color:#fff}
    .sa-list{list-style:none;margin:0;padding:0}.sa-list li{border-top:1px solid #e5ecf5;padding:12px 2px;display:flex;gap:12px;justify-content:space-between;align-items:flex-start}
    .sa-list li:first-child{border-top:0}.sa-list a.sa-name{font-weight:650;color:#10213e;text-decoration:none}.sa-list a.sa-name:hover{color:#1d4ed8;text-decoration:underline}
    .sa-sub{color:#526176;font-size:13px;margin-top:3px}.sa-right{flex:none;text-align:right;font-size:12.5px;color:#526176;white-space:nowrap}
    .sa-table{overflow-x:auto}.sa-table table{width:100%;border-collapse:collapse;font-size:14px}.sa-table th,.sa-table td{text-align:left;padding:9px 10px;border-top:1px solid #e5ecf5;vertical-align:top}
    .sa-table th{font-size:12px;color:#526176;border-top:0;white-space:nowrap}
    .sa-req{font-weight:700;white-space:nowrap}.sa-req.y{color:#15803d}.sa-req.n{color:#64748b}.sa-req.c{color:#b45309}
    .sa-links{display:flex;flex-wrap:wrap;gap:8px}.sa-links a{border:1px solid #c7d6ea;border-radius:8px;padding:7px 12px;text-decoration:none;color:#173f80;font-size:13.5px;background:#f8fbff}
    .sa-kv{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px 18px;font-size:14px}.sa-kv span{display:block;font-size:12px;color:#526176}
    .sa-group{margin:14px 0 6px;font-weight:700;font-size:14px;color:#334155}
    .sa-pages{display:flex;justify-content:space-between;gap:10px;margin-top:14px}.sa-pages a{color:#1d4ed8}
    @media(max-width:640px){.sa-list li{flex-direction:column}.sa-right{text-align:left;white-space:normal}.sa-card{padding:14px}}
</style>
@endpush
