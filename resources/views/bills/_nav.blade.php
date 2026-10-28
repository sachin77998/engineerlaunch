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
.bp-modal{position:fixed;inset:0;z-index:1500;background:rgba(15,23,42,.55);display:grid;place-items:start center;overflow:auto;padding:40px 16px}
.bp-modal[hidden]{display:none}
.bp-modal-box{position:relative;width:min(1100px,100%);background:#fff;border-radius:16px;padding:34px 40px}
.bp-modal-box h2{margin:18px 0 10px;font-size:22px}
.bp-close{position:absolute;top:18px;right:18px;width:38px;height:38px;border:0;border-radius:50%;background:#111827;color:#fff;font-size:22px;cursor:pointer}
@media(max-width:820px){.bp-two{grid-template-columns:1fr}.bp-cats a{min-width:86px}}
</style>@endpush
<div class="bp-band"><div class="container"><nav class="bp-cats" aria-label="Bill categories">
    <a href="{{ route('bills.index') }}" class="{{ request()->routeIs('bills.index') ? 'on' : '' }}"><span>🧾</span>All bills</a>
    @foreach(\App\Http\Controllers\BillPaymentController::PRIMARY as $pk) @continue(!isset($categories[$pk])) @php($c = $categories[$pk])<a href="{{ route('bills.category', $c['key']) }}" class="{{ ($key ?? null) === $c['key'] ? 'on' : '' }}"><span>{{ $c['icon'] }}</span>{{ $c['name'] }}</a>@endforeach
    @php($moreOn = isset($key) && !in_array($key, \App\Http\Controllers\BillPaymentController::PRIMARY, true))<a href="{{ route('bills.index') }}#more" class="{{ $moreOn ? 'on' : '' }}" data-bp-more><span>•••</span>{{ $moreOn ? $categories[$key]['name'] : 'More' }}</a>
</nav></div></div>
<div class="bp-modal" id="bp-more" hidden role="dialog" aria-modal="true" aria-label="All bill and recharge services">
    <div class="bp-modal-box">
        <button type="button" class="bp-close" aria-label="Close" data-bp-close>×</button>
        @foreach(\App\Http\Controllers\BillPaymentController::GROUPS as $gk => $gname)
            <h2>{{ $gname }}</h2>
            <div class="bp-grid">@foreach($categories as $c) @continue(($c['group'] ?? 'bills') !== $gk)<a class="bp-tile" href="{{ route('bills.category', $c['key']) }}"><span class="bp-mono" style="background:#eef4ff;font-size:30px">{{ $c['icon'] }}</span>{{ $c['name'] }}</a>@endforeach
                @if($gk === 'more')<a class="bp-tile" href="{{ route('bills.index') }}#cities"><span class="bp-mono" style="background:#eef4ff;font-size:30px">🏙️</span>City municipal services</a>@endif</div>
        @endforeach
    </div>
</div>
@push('scripts')<script>
(function () {
    var m = document.getElementById('bp-more'); if (!m) return;
    var open = function (e) { e.preventDefault(); m.hidden = false; document.body.style.overflow = 'hidden'; };
    var close = function () { m.hidden = true; document.body.style.overflow = ''; };
    document.querySelectorAll('[data-bp-more]').forEach(function (a) { a.addEventListener('click', open); });
    m.addEventListener('click', function (e) { if (e.target === m || e.target.hasAttribute('data-bp-close')) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !m.hidden) close(); });
})();
</script>@endpush
