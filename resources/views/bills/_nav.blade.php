@push('styles')<style>
.bp-band{background:#0b2a66;color:#fff;padding:18px 0 70px}
.bp-cats{display:flex;gap:6px;overflow-x:auto;padding:4px 0;scrollbar-width:thin}
.bp-cats a{flex:0 0 auto;display:grid;justify-items:center;gap:6px;min-width:104px;padding:10px 8px;border-radius:12px;color:#fff;text-decoration:none;font:600 13.5px Inter,system-ui,sans-serif;text-align:center}
.bp-cats a span{font-size:26px;line-height:1}
.bp-cats a:hover,.bp-cats a.on{background:rgba(255,255,255,.12)}.bp-cats a.on{box-shadow:inset 0 -3px 0 #f4b400}
.bp-wrap{margin-top:-52px;display:grid;gap:18px;padding-bottom:40px}
.bp-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:24px;box-shadow:0 10px 30px rgba(16,33,62,.08)}
.bp-card h1,.bp-card h2{margin:0 0 14px;font-size:21px}
.bp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px}
.bp-tile{display:grid;justify-items:center;gap:8px;padding:14px 8px;border-radius:12px;text-align:center;color:#334155;text-decoration:none;font-size:14px;line-height:1.35}
.bp-tile:hover{background:#f1f6ff}
.bp-mono{width:72px;height:72px;border-radius:50%;display:grid;place-items:center;font:800 20px Inter,system-ui,sans-serif;color:#fff}
.bp-muted{color:var(--muted);font-size:13px}
.bp-in{width:100%;padding:12px 2px;border:0;border-bottom:1px solid #cbd5e1;font:inherit;font-size:16px;background:transparent}
.bp-in:focus{outline:0;border-color:#2563eb;box-shadow:none!important}
.bp-btn{display:block;width:100%;margin-top:18px;padding:14px;border:0;border-radius:10px;background:#00b9f5;color:#fff;font:700 17px Inter,system-ui,sans-serif;cursor:pointer;text-align:center;text-decoration:none}
.bp-btn:hover{background:#00a5db}
.bp-note{margin-top:12px;padding:10px 12px;border-radius:8px;background:#f1f5f9;color:#475569;font-size:13px}
.bp-two{display:grid;grid-template-columns:minmax(280px,420px) 1fr;gap:18px;align-items:start}
.bp-list a{display:flex;justify-content:space-between;gap:10px;padding:13px 4px;border-bottom:1px solid #eef2f7;color:inherit;text-decoration:none}
.bp-list a:hover{color:#1d4ed8}
.bp-chips{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}.bp-chips a{padding:6px 12px;border:1px solid var(--line);border-radius:20px;font-size:13px;color:#334155;text-decoration:none}.bp-chips a.on{background:#1d4ed8;color:#fff;border-color:#1d4ed8}
@media(max-width:820px){.bp-two{grid-template-columns:1fr}.bp-cats a{min-width:86px}}
</style>@endpush
<div class="bp-band"><div class="container"><nav class="bp-cats" aria-label="Bill categories">
    <a href="{{ route('bills.index') }}" class="{{ request()->routeIs('bills.index') ? 'on' : '' }}"><span>🧾</span>All bills</a>
    @foreach($categories as $c) @continue(!empty($c['more']))<a href="{{ route('bills.category', $c['key']) }}" class="{{ ($key ?? null) === $c['key'] ? 'on' : '' }}"><span>{{ $c['icon'] }}</span>{{ $c['name'] }}</a>@endforeach
    @php($moreOn = !empty($categories[$key ?? '']['more']))<a href="{{ route('bills.index') }}#more" class="{{ $moreOn ? 'on' : '' }}"><span>•••</span>{{ $moreOn ? $categories[$key]['name'] : 'More' }}</a>
</nav></div></div>
