@php($listCount = count(session('materials.list', [])))
@php($navGroups = app(\App\Services\Materials\MaterialCollections::class)->byGroup())
@push('styles')<style>
:root{--mt-ink:#1c1917;--mt-accent:#ea580c;--mt-green:#2f7d1e;--mt-green-h:#256418;--mt-teal:#0f8b8d;--mt-yellow:#fcd34d;--mt-line:#ece7e1}
.page{background:#fff}
.mt-head{position:sticky;top:0;z-index:150;background:#fff;border-bottom:1px solid var(--mt-line);box-shadow:0 2px 10px rgba(28,25,23,.04)}
.mt-bar{display:flex;align-items:center;gap:18px;padding:14px 0}
.mt-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--mt-ink);font:800 18px Inter,system-ui,sans-serif;flex:0 0 auto}
.mt-brand i{display:grid;place-items:center;width:44px;height:44px;border-radius:12px;background:var(--mt-accent);color:#fff;font-style:normal;font-size:24px}
.mt-brand small{display:block;font:600 11px Inter,system-ui,sans-serif;color:#78716c;letter-spacing:.04em}
.mt-deliver{display:flex;align-items:center;gap:10px;flex:0 0 auto;border-left:1px solid var(--mt-line);padding-left:18px;cursor:pointer;background:none;border-top:0;border-right:0;border-bottom:0;font:inherit;text-align:left}
.mt-deliver b{display:grid;place-items:center;width:44px;height:44px;border-radius:10px;background:var(--mt-green);color:#fff;font:800 11px/1.05 Inter,system-ui,sans-serif;text-align:center}
.mt-deliver span{font-size:12.5px;color:#78716c}.mt-deliver strong{display:block;font-size:16px;color:var(--mt-ink)}
.mt-search{flex:1;display:flex;align-items:center;gap:10px;border:1px solid #ddd6ce;border-radius:14px;padding:0 16px;height:52px;background:#fff}
.mt-search:focus-within{border-color:var(--mt-accent);box-shadow:0 0 0 3px rgba(234,88,12,.12)}
.mt-search svg{flex:0 0 auto;color:#57534e}
.mt-search input{flex:1;min-width:0;border:0!important;box-shadow:none!important;outline:0;font:inherit;font-size:16px;background:transparent;height:100%}
.mt-iconbtn{position:relative;display:flex;align-items:center;gap:8px;color:var(--mt-ink);text-decoration:none;font-weight:600;flex:0 0 auto}
.mt-iconbtn em{position:absolute;top:-6px;left:16px;min-width:18px;height:18px;padding:0 5px;border-radius:9px;background:var(--mt-accent);color:#fff;font:700 11px/18px Inter,system-ui,sans-serif;font-style:normal;text-align:center}
.mt-menu{display:flex;justify-content:center;gap:4px;flex-wrap:wrap;padding-bottom:6px}
.mt-menu>a,.mt-menu summary{display:flex;align-items:center;gap:6px;padding:10px 14px;border-radius:9px;color:#292524;text-decoration:none;font:500 15.5px Inter,system-ui,sans-serif;cursor:pointer;list-style:none;white-space:nowrap}
.mt-menu summary::-webkit-details-marker{display:none}
.mt-menu summary:after{content:'';width:7px;height:7px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg);margin:-4px 0 0 4px}
.mt-menu details[open] summary:after{transform:rotate(-135deg);margin-top:3px}
.mt-menu>a:hover,.mt-menu summary:hover,.mt-menu .on{color:var(--mt-accent)}
.mt-menu details{position:relative}
.mt-drop{position:absolute;top:calc(100% + 6px);left:0;z-index:20;min-width:270px;padding:10px;border:1px solid var(--mt-line);border-radius:16px;background:#fff;box-shadow:0 18px 40px rgba(28,25,23,.14)}
.mt-drop a{display:flex;align-items:center;gap:12px;padding:9px 12px;border-radius:10px;color:#292524;text-decoration:none;font-size:15px}
.mt-drop a:hover{background:#fff7ed;color:var(--mt-accent)}
.mt-drop a span{width:30px;height:30px;display:grid;place-items:center;border-radius:8px;background:#fafaf9;font-size:17px}
.mt-pinbox{display:none;align-items:center;gap:8px;flex-wrap:wrap;padding:0 0 12px}
.mt-pinbox.open{display:flex}
.mt-pinbox input{width:140px;padding:10px 12px;border:1px solid #ddd6ce;border-radius:10px;font:inherit}
.mt-pin-out{font-size:13.5px;color:#57534e}
.mt-pin-out.fast:before{content:'⚡ ';}
.mt-search{position:relative}
.mt-sugg{position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:40;padding:8px;border:1px solid var(--mt-line);border-radius:14px;background:#fff;box-shadow:0 18px 40px rgba(28,25,23,.14)}
.mt-sugg a{display:flex;align-items:center;gap:12px;padding:8px;border-radius:10px;color:#292524;text-decoration:none;font-size:14px}
.mt-sugg a:hover,.mt-sugg a.on{background:#fff7ed}
.mt-sugg img{width:40px;height:40px;object-fit:contain;border-radius:8px;background:#fafaf9}
.mt-sugg small{margin-left:auto;color:#78716c;white-space:nowrap}
.mt-sugg h6{margin:6px 8px 4px;font-size:11px;letter-spacing:.06em;color:#a8a29e;text-transform:uppercase}
.mt-drop.wide{display:grid;grid-template-columns:repeat(2,minmax(230px,1fr));min-width:520px;max-height:70vh;overflow:auto}
.mt-drop a.all{grid-column:1/-1;justify-content:center;color:var(--mt-accent);font-weight:700}
.mt-acct{display:flex;align-items:center;gap:8px;color:var(--mt-ink);text-decoration:none;font-weight:600;flex:0 0 auto}
@media(max-width:860px){.mt-drop.wide{grid-template-columns:1fr;min-width:0}}
.mt-crumbs{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:22px 0 4px;font-size:15px;color:#a8a29e}
.mt-crumbs a{color:#a8a29e;text-decoration:none}.mt-crumbs a:hover{color:var(--mt-accent)}.mt-crumbs b{color:#44403c;font-weight:500}
.mt-wrap{display:grid;gap:22px;padding:18px 0 48px}
.mt-card{background:#fff;border:1px solid var(--mt-line);border-radius:18px;padding:24px;min-width:0}
.mt-card h1,.mt-card h2{margin:0 0 16px;font-size:22px;color:var(--mt-ink)}
.mt-h1{margin:6px 0 8px;font-size:clamp(26px,3.4vw,38px);font-weight:800;color:var(--mt-ink)}
.mt-sec-h{display:flex;justify-content:space-between;align-items:baseline;gap:10px;margin-bottom:16px}
.mt-sec-h h2{margin:0;font-size:24px}.mt-sec-h a{color:var(--mt-accent);font-weight:700;text-decoration:none}
.mt-muted{color:#78716c;font-size:13px}
.mt-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:11px 18px;border:0;border-radius:10px;background:var(--mt-accent);color:#fff;font:700 15px Inter,system-ui,sans-serif;cursor:pointer;text-decoration:none;text-align:center}
.mt-btn:hover{background:#c2410c}
.mt-btn.ghost{background:#fff;color:#9a3412;border:1px solid #fed7aa}.mt-btn.ghost:hover{background:#fff7ed}
.mt-btn.green{background:var(--mt-green)}.mt-btn.green:hover{background:var(--mt-green-h)}
.mt-btn.sm{padding:7px 12px;font-size:13px;border-radius:8px}
/* Product cards */
.mt-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:34px 22px}
.mt-p{position:relative;display:flex;flex-direction:column;min-width:0}
.mt-p .mt-img{position:relative;display:block;aspect-ratio:1;border-radius:14px;background:#fff;overflow:hidden}
.mt-p .mt-img img{width:100%;height:100%;object-fit:contain;transition:transform .25s}
.mt-p:hover .mt-img img{transform:scale(1.04)}
.mt-p .mt-noimg{width:100%;height:100%;display:grid;place-items:center;font-size:64px;background:#fafaf9}
.mt-badge{position:absolute;top:0;left:0;z-index:1;padding:3px 10px;border-radius:12px;background:var(--mt-yellow);color:#1c1917;font:600 14px Inter,system-ui,sans-serif}
.mt-badge.out{background:#fee2e2;color:#991b1b;left:auto;right:0}
.mt-feats{display:flex;gap:14px;flex-wrap:wrap;margin-top:14px;font-size:13px;font-weight:600;color:#292524}
.mt-feats span{display:inline-flex;align-items:center;gap:5px}
.mt-feats svg{width:15px;height:15px}
.mt-ribbon{display:inline-flex;align-items:center;gap:6px;margin-top:9px;font-size:13px;color:var(--mt-teal);flex-wrap:wrap}
.mt-ribbon b{display:inline-flex;align-items:center;gap:4px;padding:2px 7px;border-radius:4px;background:var(--mt-teal);color:#fff;font-weight:600}
.mt-p h3{margin:16px 0 10px;font-size:17px;line-height:1.4;font-weight:600;min-height:48px}
.mt-p h3 a{color:var(--mt-ink);text-decoration:none}.mt-p h3 a:hover{color:var(--mt-accent)}
.mt-price{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;margin-top:auto}
.mt-price b{font-size:19px;font-weight:700}.mt-price s{color:#a8a29e;font-size:15px}.mt-off{color:#15803d;font-weight:700;font-size:13px}
.mt-strip{display:flex;align-items:center;gap:10px;margin-top:8px;padding:6px 10px;border-radius:6px;background:linear-gradient(90deg,#fef3c7,#fffbeb);font-size:12.5px;color:#44403c;min-height:40px}
.mt-strip strong{font-size:20px;color:#b45309;line-height:1}
.mt-unlock{display:inline-block;margin-top:10px;color:#2563eb;font-size:14px;text-decoration:none;border-bottom:1px dashed #2563eb;align-self:flex-start}
.mt-p form{margin-top:12px}
.mt-add{width:100%;padding:13px;border:0;border-radius:8px;background:var(--mt-green);color:#fff;font:700 17px Inter,system-ui,sans-serif;cursor:pointer}
.mt-add:hover{background:var(--mt-green-h)}
.mt-opts{position:relative;margin-top:12px}.mt-opts summary{display:flex;justify-content:center;gap:6px;list-style:none}.mt-opts summary::-webkit-details-marker{display:none}
.mt-opts-list{position:absolute;bottom:calc(100% + 6px);left:0;right:0;z-index:25;max-height:320px;overflow:auto;padding:8px;border:1px solid var(--mt-line);border-radius:12px;background:#fff;box-shadow:0 -12px 30px rgba(28,25,23,.16)}
.mt-opts-list form{display:flex;align-items:center;justify-content:space-between;gap:8px;margin:0;padding:8px 6px;border-bottom:1px solid #f5f0ea;font-size:13px}
.mt-opts-list form:last-child{border:0}.mt-add:disabled{background:#d6d3d1;cursor:not-allowed}
/* Tiles */
.mt-tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px}
.mt-tile{display:grid;justify-items:center;align-content:start;gap:8px;padding:12px 8px;border-radius:16px;text-align:center;color:#292524;text-decoration:none;font-size:14.5px;font-weight:600;border:1px solid transparent}
.mt-tile:hover{border-color:#fed7aa;background:#fffaf5}
.mt-tile .mt-ico{width:100%;aspect-ratio:1;max-width:130px;border-radius:50%;display:grid;place-items:center;overflow:hidden;background:#fff7ed;font-size:42px}
.mt-tile .mt-ico img{width:78%;height:78%;object-fit:contain}
.mt-tile .mt-muted{font-weight:400}
.mt-tag{padding:2px 8px;border-radius:12px;background:#fff7ed;color:#9a3412;font-size:11.5px;font-weight:650}
.mt-tag.out{background:#fef2f2;color:#b91c1c}.mt-tag.ok{background:#f0fdf4;color:#15803d}
.mt-tags{display:flex;gap:5px;flex-wrap:wrap;margin:6px 0}
.mt-table{width:100%;border-collapse:collapse;font-size:14px}
.mt-table th,.mt-table td{padding:11px 8px;border-bottom:1px solid #f1ece6;text-align:left;vertical-align:top}
.mt-table th{font-size:12.5px;color:#78716c;font-weight:700;background:#fafaf9}
.mt-table td.n,.mt-table th.n{text-align:right;white-space:nowrap}
.mt-scroll{overflow-x:auto}
.mt-in{width:100%;padding:10px 12px;border:1px solid #e7d8c9;border-radius:9px;font:inherit;background:#fff}
.mt-label{display:block;font-weight:700;font-size:13px;margin:10px 0 5px}
.mt-up{color:#b91c1c}.mt-down{color:#15803d}
@media(max-width:1100px){.mt-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:860px){.mt-bar{flex-wrap:wrap;gap:12px}.mt-search{order:5;flex-basis:100%}.mt-deliver{padding-left:12px}.mt-menu{justify-content:flex-start;flex-wrap:nowrap;overflow-x:auto}.mt-drop{position:fixed;left:16px;right:16px;top:auto}.mt-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:24px 14px}.mt-p h3{font-size:15px;min-height:0}.mt-card{padding:16px}}
@media(max-width:420px){.mt-brand span{display:none}.mt-iconbtn span{display:none}}
</style>@endpush
@php($svg = ['search' => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
    'list' => '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14l-1.2 12.2a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8L5 7Z"/><path d="M9 7V5a3 3 0 0 1 6 0v2"/></svg>',
    'pin' => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z"/></svg>'])
<header class="mt-head">
    <div class="container">
        <div class="mt-bar">
            <a class="mt-brand" href="{{ route('materials.index') }}"><i>🏗️</i><span>Home Building<small>MATERIALS · PRICES · ESTIMATES</small></span></a>
            <button type="button" class="mt-deliver" id="mt-deliver" aria-expanded="false" aria-controls="mt-pinbox"><b id="mt-pin-badge">PIN<br>check</b><span>Deliver to<strong>{!! $svg['pin'] !!} <span id="mt-pin-label" style="font-size:16px;color:#1c1917">Enter PIN</span> ▾</strong></span></button>
            <form class="mt-search" method="get" action="{{ route('materials.search') }}" role="search">
                {!! $svg['search'] !!}
                <input name="q" id="mt-q-top" value="{{ request()->routeIs('materials.search') ? request('q') : '' }}" placeholder="Search for Cement" aria-label="Search materials" autocomplete="off" role="combobox" aria-controls="mt-sugg" aria-expanded="false">
                <div class="mt-sugg" id="mt-sugg" hidden></div>
            </form>
            @auth<a class="mt-acct" href="{{ route('materials.orders') }}"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><span>My orders</span></a>
            @else<a class="mt-acct" href="{{ route('login') }}"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg><span>Login</span></a>@endauth
            <a class="mt-iconbtn" href="{{ route('materials.list') }}" aria-label="Cart">{!! $svg['list'] !!}@if($listCount)<em>{{ $listCount }}</em>@endif<span>Cart</span></a>
        </div>
        <div class="mt-pinbox" id="mt-pinbox">
            <input id="mt-pin" inputmode="numeric" maxlength="6" placeholder="6-digit PIN code" aria-label="Delivery PIN code">
            <button type="button" class="mt-btn sm green" id="mt-pin-go">Check</button>
            <span class="mt-pin-out" id="mt-pin-out" aria-live="polite"></span>
        </div>
        <nav class="mt-menu" aria-label="Material categories">
            <a class="{{ request()->routeIs('materials.index') ? 'on' : '' }}" href="{{ route('materials.index') }}">Home</a>
            @foreach(config('materials.groups') as $gk => $gname) @continue(empty($navGroups[$gk]))
                <details>
                    <summary>{{ $gname }}</summary>
                    <div class="mt-drop {{ count($navGroups[$gk]) > 8 ? 'wide' : '' }}">
                        @foreach($navGroups[$gk] as $slug => $c)
                            <a href="{{ route('materials.category', $slug) }}"><span>@if($c['image'])<img src="{{ $c['image'] }}" alt="" style="width:26px;height:26px;object-fit:contain" loading="lazy">@else{{ $c['icon'] }}@endif</span>{{ $c['title'] }}</a>
                        @endforeach
                        <a class="all" href="{{ route('materials.categories') }}#{{ $gk }}">All {{ $gname }} →</a>
                    </div>
                </details>
            @endforeach
            <details>
                <summary>Calculators</summary>
                <div class="mt-drop">
                    <a href="{{ route('materials.calculator') }}"><span>🏠</span>Construction cost calculator</a>
                    @foreach(config('materials.estimators') as $k => [$name, $icon])<a href="{{ route('materials.estimator', $k) }}"><span>{{ $icon }}</span>{{ $name }}</a>@endforeach
                </div>
            </details>
            <a class="{{ request()->routeIs('materials.price-list*') ? 'on' : '' }}" href="{{ route('materials.price-lists') }}">Price lists</a>
            <a href="{{ route('materials.search', ['sort' => 'new']) }}">New launches</a>
            <a class="{{ request()->routeIs('materials.categories') ? 'on' : '' }}" href="{{ route('materials.categories') }}">All categories</a>
        </nav>
    </div>
</header>
@push('scripts')<script>
(function () {
    var input = document.getElementById('mt-pin'), out = document.getElementById('mt-pin-out'), box = document.getElementById('mt-pinbox'), toggle = document.getElementById('mt-deliver');
    toggle.addEventListener('click', function () { var open = box.classList.toggle('open'); toggle.setAttribute('aria-expanded', open); if (open) input.focus(); });
    // One open dropdown at a time; clicking elsewhere closes it.
    var drops = document.querySelectorAll('.mt-menu details');
    drops.forEach(function (d) { d.addEventListener('toggle', function () { if (d.open) drops.forEach(function (o) { if (o !== d) o.open = false; }); }); });
    document.addEventListener('click', function (e) { if (!e.target.closest('.mt-menu details')) drops.forEach(function (d) { d.open = false; }); });
    // Rotating search hint.
    var q = document.getElementById('mt-q-top'), hints = ['Cement', 'Plywood', 'Fevicol', 'Waterproofing', 'Painting', 'Wires', 'Hinges', 'Tile adhesive'], h = 0;
    setInterval(function () { if (document.activeElement !== q && !q.value) q.placeholder = 'Search for ' + hints[h = (h + 1) % hints.length]; }, 2200);
    function show(d) {
        document.getElementById('mt-pin-label').textContent = d.pin;
        document.getElementById('mt-pin-badge').innerHTML = d.fast ? '60<br>Mins' : 'PIN<br>' + (d.pin.slice(0, 3));
        out.className = 'mt-pin-out' + (d.fast ? ' fast' : '');
        out.textContent = (d.district ? d.district + (d.state ? ', ' + d.state : '') + ' · ' : '') + d.message;
    }
    function check(silent) {
        var pin = input.value.trim();
        if (!/^[1-9]\d{5}$/.test(pin)) { if (!silent) out.textContent = 'Enter a 6-digit PIN code.'; return; }
        if (!silent) out.textContent = 'Checking…';
        fetch('{{ route('materials.pincode') }}?pin=' + pin, {headers: {Accept: 'application/json'}}).then(function (r) { return r.json(); }).then(function (d) {
            if (d.error) { out.textContent = d.error; return; }
            try { localStorage.setItem('mt-pin', pin); } catch (e) {}
            show(d);
            document.dispatchEvent(new CustomEvent('mt-pin', {detail: d}));
        }).catch(function () { out.textContent = 'Could not check this PIN code right now.'; });
    }
    document.getElementById('mt-pin-go').addEventListener('click', function () { check(false); });
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); check(false); } });
    try { var saved = localStorage.getItem('mt-pin'); if (saved) { input.value = saved; check(true); } } catch (e) {}
    // Search suggestions: products with photo and price, and matching categories.
    var sugg = document.getElementById('mt-sugg'), timer, seq = 0;
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); };
    function close() { sugg.hidden = true; q.setAttribute('aria-expanded', 'false'); }
    q.addEventListener('input', function () {
        clearTimeout(timer);
        var term = q.value.trim();
        if (term.length < 2) return close();
        timer = setTimeout(function () {
            var mine = ++seq;
            fetch('{{ route('materials.suggest') }}?q=' + encodeURIComponent(term), {headers: {Accept: 'application/json'}}).then(function (r) { return r.json(); }).then(function (d) {
                if (mine !== seq) return;
                var html = '';
                if (d.categories.length) html += '<h6>Categories</h6>' + d.categories.map(function (c) { return '<a href="' + esc(c.url) + '">🗂️ ' + esc(c.title) + '<small>' + c.count + ' products</small></a>'; }).join('');
                if (d.products.length) html += '<h6>Products</h6>' + d.products.map(function (p) { return '<a href="' + esc(p.url) + '">' + (p.image ? '<img src="' + esc(p.image) + '" alt="">' : '') + esc(p.title) + '<small>₹' + Math.round(p.price).toLocaleString('en-IN') + '</small></a>'; }).join('');
                if (!html) html = '<a href="{{ route('materials.search') }}?q=' + encodeURIComponent(term) + '">Search for “' + esc(term) + '”</a>';
                sugg.innerHTML = html; sugg.hidden = false; q.setAttribute('aria-expanded', 'true');
            }).catch(close);
        }, 180);
    });
    q.addEventListener('keydown', function (e) {
        var links = Array.from(sugg.querySelectorAll('a')), i = links.findIndex(function (a) { return a.classList.contains('on'); });
        if (e.key === 'Escape') return close();
        if (sugg.hidden || !links.length || (e.key !== 'ArrowDown' && e.key !== 'ArrowUp' && e.key !== 'Enter')) return;
        if (e.key === 'Enter') { if (i >= 0) { e.preventDefault(); location.href = links[i].href; } return; }
        e.preventDefault();
        links.forEach(function (a) { a.classList.remove('on'); });
        links[(i + (e.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length].classList.add('on');
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('.mt-search')) close(); });
})();
</script>@endpush
