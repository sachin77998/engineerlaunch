@push('styles')<style>
.hs-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px;flex-wrap:wrap}.hs-head h1{margin:0 0 4px;font-size:26px}.muted{color:var(--muted)}
.hs-card{background:linear-gradient(145deg,#fff,#f5f9ff);border:1px solid var(--line);border-radius:14px;box-shadow:0 8px 25px rgba(37,99,235,.08);padding:18px;margin-bottom:16px}
.hs-card h2{margin:0 0 12px;font-size:17px}.hs-grid{display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(170px,1fr))}
.hs-grid label,.hs-field label{display:block;font-size:12px;font-weight:700;color:var(--muted);margin-bottom:4px}
.hs-in,.hs-grid input,.hs-grid select,.hs-field input,.hs-field select,.hs-field textarea{width:100%;padding:9px 10px;border:1px solid var(--line);border-radius:8px;background:#fff;font:inherit;color:inherit}
.hs-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.hs-mini{border:1px solid var(--line);background:#fff;border-radius:7px;padding:6px 10px;cursor:pointer;font:inherit;font-size:13px;display:inline-flex;align-items:center;gap:4px}
.hs-mini.primary{background:var(--blue);color:#fff;border-color:var(--blue)}.hs-mini.danger{color:#b42318;border-color:#f4c7c3}
.hs-table{width:100%;border-collapse:collapse}.hs-table th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);padding:9px 8px;border-bottom:1px solid var(--line);white-space:nowrap}
.hs-table td{padding:10px 8px;border-bottom:1px solid #e6eefa;vertical-align:top}.hs-table tr:hover td{background:#f7faff}.hs-scroll{overflow-x:auto}
.pill{display:inline-block;padding:3px 9px;border-radius:30px;font-size:12px;font-weight:700;background:#eef4ff;color:#2457bd;white-space:nowrap}
.pill.new{background:#eef4ff;color:#2457bd}.pill.screening{background:#fef6e7;color:#9a5b00}.pill.shortlisted{background:#e8f7ef;color:#0b7a43}.pill.interview{background:#f0ebff;color:#5b3fc4}
.pill.offered{background:#e6f6fb;color:#0b6a85}.pill.hired{background:#0b7a43;color:#fff}.pill.rejected{background:#fdecea;color:#b42318}.pill.withdrawn{background:#f2f4f7;color:#667085}
.pill.ok{background:#e8f7ef;color:#0b7a43}.pill.bad{background:#fdecea;color:#b42318}.pill.wait{background:#fef6e7;color:#9a5b00}
.score{display:inline-grid;place-items:center;min-width:40px;height:26px;border-radius:7px;font-weight:800;font-size:13px}.score.hi{background:#e8f7ef;color:#0b7a43}.score.mid{background:#fef6e7;color:#9a5b00}.score.lo{background:#fdecea;color:#b42318}
.kpis{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:16px}.kpi{background:#fff;border:1px solid var(--line);border-radius:12px;padding:14px}.kpi span{font-size:12px;color:var(--muted);font-weight:700}.kpi b{display:block;font-size:24px;margin-top:6px}.kpi small{color:var(--muted)}
.bulkbar{position:sticky;bottom:12px;z-index:5;display:none;background:var(--navy);color:#fff;border-radius:12px;padding:10px 14px;gap:8px;align-items:center;flex-wrap:wrap;box-shadow:0 10px 30px rgba(16,33,62,.3)}.bulkbar.on{display:flex}.bulkbar select,.bulkbar input{padding:7px;border-radius:7px;border:0;font:inherit}
.tag{display:inline-block;background:#f2f4f7;border-radius:6px;padding:2px 7px;font-size:12px;margin:2px 2px 0 0}
.chips a{display:inline-block;padding:6px 11px;border:1px solid var(--line);border-radius:30px;margin:0 6px 6px 0;background:#fff;font-size:13px}.chips a.on{background:var(--blue);border-color:var(--blue);color:#fff}
details.hs-card>summary{cursor:pointer;font-weight:800;font-size:16px}details.hs-card[open]>summary{margin-bottom:12px}
@media(max-width:700px){.hs-head h1{font-size:22px}.hs-grid{grid-template-columns:1fr 1fr}}
</style>@endpush
