@extends('layouts.site')
@section('title', $meta[0] . ' — quantity and cost with live prices')
@section('meta_description', $meta[2])
@section('content')
@include('materials._nav')
@push('styles')<style>
.mt-calc{display:grid;grid-template-columns:360px 1fr;gap:18px;align-items:start}
.mt-rooms .rm{display:grid;grid-template-columns:repeat(5,1fr) auto;gap:6px;align-items:end;margin-bottom:8px}
.mt-rooms .rm label{font-size:11.5px;color:#78716c}.mt-rooms .rm input{padding:7px 6px}
.mt-x{border:0;background:none;color:#b91c1c;font-size:18px;cursor:pointer}
.mt-total{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin-bottom:14px}
.mt-total div{padding:12px 14px;border-radius:12px;background:#fff7ed}.mt-total b{display:block;font-size:21px;color:#9a3412}
@media(max-width:900px){.mt-calc{grid-template-columns:1fr}}
</style>@endpush
<div class="container mt-wrap">
    <div class="mt-calc">
        <section class="mt-card" id="e-form">
            <h1 style="font-size:19px">{{ $meta[0] }}</h1>
            @if($type === 'paint')
                <div class="mt-label">Rooms (feet)</div>
                <div class="mt-rooms" id="e-rooms"></div>
                <button type="button" class="mt-btn sm ghost" id="e-room">+ Add room</button>
                <label class="mt-label" for="e-coats">Coats of paint</label>
                <select class="mt-in" id="e-coats"><option>1</option><option selected>2</option><option>3</option></select>
                <label style="display:flex;gap:8px;margin-top:10px;font-size:14px"><input type="checkbox" id="e-ceiling" checked> Paint ceilings</label>
                <label style="display:flex;gap:8px;font-size:14px"><input type="checkbox" id="e-useputty" checked> Putty (2 coats)</label>
                <label style="display:flex;gap:8px;font-size:14px"><input type="checkbox" id="e-useprimer" checked> Primer (1 coat)</label>
            @elseif($type === 'tiles')
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <div><label class="mt-label" for="e-l">Length (ft)</label><input class="mt-in" id="e-l" type="number" min="1" step="0.5" value="12"></div>
                    <div><label class="mt-label" for="e-w">Width / height (ft)</label><input class="mt-in" id="e-w" type="number" min="1" step="0.5" value="10"></div>
                </div>
                <label class="mt-label" for="e-size">Tile size (mm)</label>
                <select class="mt-in" id="e-size">@foreach($cfg['sizes'] as $k => [$a, $b, $box])<option value="{{ $k }}" data-a="{{ $a }}" data-b="{{ $b }}" data-box="{{ $box }}" @selected($k === '600x600')>{{ str_replace('x', ' × ', $k) }}</option>@endforeach</select>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <div><label class="mt-label" for="e-box">Tiles per box</label><input class="mt-in" id="e-box" type="number" min="1" value="4"></div>
                    <div><label class="mt-label" for="e-waste">Wastage %</label><input class="mt-in" id="e-waste" type="number" min="0" max="30" value="8"></div>
                </div>
                <label class="mt-label" for="e-rate">Tile price (₹ per sq ft)</label>
                <input class="mt-in" id="e-rate" type="number" min="0" value="60">
                <label style="display:flex;gap:8px;margin-top:10px;font-size:14px"><input type="checkbox" id="e-usegrout" checked> Grout</label>
            @else
                <label class="mt-label" for="e-area">Area (sq ft)</label>
                <input class="mt-in" id="e-area" type="number" min="1" value="1000">
                <label class="mt-label" for="e-surface">Surface</label>
                <select class="mt-in" id="e-surface">@foreach($cfg['surfaces'] as $k => [$label, $coats, $rate])<option value="{{ $k }}" data-coats="{{ $coats }}" data-rate="{{ $rate }}">{{ $label }}</option>@endforeach</select>
                <label class="mt-label" for="e-coats">Coats</label>
                <select class="mt-in" id="e-coats"><option>1</option><option selected>2</option><option>3</option></select>
            @endif
            @foreach($packs as $k => $list)
                <label class="mt-label" for="p-{{ $k }}">{{ ucfirst($k) }} product</label>
                <select class="mt-in" id="p-{{ $k }}">@forelse($list as $i => $p)<option value="{{ $i }}">{{ $p['title'] }} · ₹{{ number_format($p['rate'], 1) }}/{{ $k === 'paint' || $k === 'primer' || $k === 'coating' ? 'L' : 'kg' }}</option>@empty<option value="">No store products loaded yet</option>@endforelse</select>
            @endforeach
            <button type="button" class="mt-btn" id="e-add" style="width:100%;margin-top:16px">Add to my list</button>
            <p class="mt-muted" id="e-msg" aria-live="polite"></p>
        </section>
        <section class="mt-card">
            <div class="mt-total" id="e-totals"></div>
            <div class="mt-scroll"><table class="mt-table"><thead><tr><th>Item</th><th class="n">Needed</th><th>Buy</th><th class="n">Cost</th></tr></thead><tbody id="e-body"></tbody></table></div>
        </section>
    </div>
</div>
@push('scripts')<script>
(function () {
    var type = @json($type), cfg = @json($cfg), packs = @json($packs);
    var $ = function (id) { return document.getElementById(id); }, num = function (id) { return parseFloat(($(id) || {}).value) || 0; };
    var inr = function (n) { return '₹' + Math.round(n).toLocaleString('en-IN'); };
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); };
    var cart = [];

    // Cheapest mix of packs covering at least `need` (unbounded knapsack over 0.1-unit steps).
    function mix(product, need) {
        if (!product || need <= 0) return null;
        var sizes = product.packs.map(function (p) { return Math.max(1, Math.round(p.size * 10)); }), goal = Math.ceil(need * 10), cap = goal + Math.max.apply(null, sizes);
        var cost = new Float64Array(cap + 1).fill(Infinity), from = new Int32Array(cap + 1).fill(-1); cost[0] = 0;
        for (var q = 1; q <= cap; q++) product.packs.forEach(function (p, i) { var prev = q - sizes[i]; if (prev >= 0 && cost[prev] + p.price < cost[q]) { cost[q] = cost[prev] + p.price; from[q] = i; } });
        var best = -1; for (q = goal; q <= cap; q++) if (cost[q] < Infinity && (best < 0 || cost[q] < cost[best])) best = q;
        if (best < 0) return null;
        var count = {}; for (q = best; q > 0; q -= sizes[from[q]]) count[from[q]] = (count[from[q]] || 0) + 1;
        var items = Object.keys(count).map(function (i) { return {pack: product.packs[i], n: count[i]}; });
        return {cost: cost[best], items: items, label: items.map(function (x) { return x.n + ' × ' + esc(x.pack.label || (x.pack.size + '')); }).join(' + ')};
    }
    function pick(k) { var s = $('p-' + k); return s && s.value !== '' ? packs[k][+s.value] : null; }
    function line(rows, name, need, unit, k) {
        var p = pick(k), m = mix(p, need);
        rows.push({name: name, need: need, unit: unit, product: p, mix: m, cost: m ? m.cost : 0});
        if (m) m.items.forEach(function (x) { cart.push({handle: p.handle, variant: x.pack.index, qty: x.n}); });
    }
    function compute() {
        var rows = [], totals = [];
        cart = [];
        if (type === 'paint') {
            var wall = 0, ceil = 0;
            document.querySelectorAll('#e-rooms .rm').forEach(function (r) {
                var v = function (n) { return parseFloat(r.querySelector('[data-f=' + n + ']').value) || 0; };
                wall += Math.max(0, 2 * (v('l') + v('w')) * v('h') - v('d') * cfg.door_sqft - v('win') * cfg.window_sqft);
                ceil += v('l') * v('w');
            });
            var area = wall + ($('e-ceiling').checked ? ceil : 0), coats = num('e-coats');
            totals.push(['Paintable area', Math.round(area).toLocaleString('en-IN') + ' sq ft']);
            line(rows, 'Paint (' + coats + ' coats)', area * coats / cfg.coverage_sqft_per_l, 'L', 'paint');
            if ($('e-useprimer').checked) line(rows, 'Primer', area / cfg.primer_sqft_per_l, 'L', 'primer');
            if ($('e-useputty').checked) line(rows, 'Wall putty', wall / cfg.putty_sqft_per_kg, 'kg', 'putty');
        } else if (type === 'tiles') {
            var o = $('e-size').selectedOptions[0], tileSqft = (+o.dataset.a / 304.8) * (+o.dataset.b / 304.8), a = num('e-l') * num('e-w');
            var tiles = Math.ceil(a * (1 + num('e-waste') / 100) / tileSqft), boxes = Math.ceil(tiles / Math.max(1, num('e-box'))), buySqft = boxes * num('e-box') * tileSqft;
            totals.push(['Area', Math.round(a).toLocaleString('en-IN') + ' sq ft'], ['Tiles', tiles + ' (' + boxes + ' boxes)']);
            rows.push({name: 'Tiles ' + o.value, need: tiles, unit: 'tiles', mix: {label: boxes + ' boxes (' + Math.round(buySqft) + ' sq ft)'}, cost: buySqft * num('e-rate')});
            line(rows, 'Tile adhesive', a * cfg.adhesive_kg_per_sqft, 'kg', 'adhesive');
            if ($('e-usegrout').checked) line(rows, 'Grout', a * cfg.grout_kg_per_sqft, 'kg', 'grout');
        } else {
            var s = $('e-surface').selectedOptions[0], need = num('e-area') * num('e-coats') * +s.dataset.rate;
            totals.push(['Area', num('e-area').toLocaleString('en-IN') + ' sq ft']);
            line(rows, 'Waterproof coating (' + num('e-coats') + ' coats)', need, 'L', 'coating');
        }
        return {rows: rows, totals: totals};
    }
    function render() {
        var r = compute(), total = r.rows.reduce(function (s, x) { return s + x.cost; }, 0);
        $('e-totals').innerHTML = r.totals.concat([['Total cost', inr(total)]]).map(function (t) { return '<div>' + t[0] + '<b>' + t[1] + '</b></div>'; }).join('');
        $('e-body').innerHTML = r.rows.map(function (x) {
            var buy = x.mix ? x.mix.label + (x.product ? '<div class="mt-muted"><a href="' + esc(x.product.url) + '" style="color:#9a3412">' + esc(x.product.title) + '</a></div>' : '') : '<span class="mt-muted">No store product</span>';
            return '<tr><td><b>' + esc(x.name) + '</b></td><td class="n">' + (Math.round(x.need * 10) / 10).toLocaleString('en-IN') + ' ' + x.unit + '</td><td>' + buy + '</td><td class="n"><b>' + inr(x.cost) + '</b></td></tr>';
        }).join('');
    }
    function addRoom(d) {
        d = d || {l: 12, w: 10, h: 10, d: 1, win: 1};
        var el = document.createElement('div'); el.className = 'rm';
        el.innerHTML = [['l', 'Length'], ['w', 'Width'], ['h', 'Height'], ['d', 'Doors'], ['win', 'Windows']].map(function (f) { return '<div><label>' + f[1] + '</label><input class="mt-in" type="number" min="0" step="0.5" data-f="' + f[0] + '" value="' + d[f[0]] + '"></div>'; }).join('') + '<button type="button" class="mt-x" aria-label="Remove room">×</button>';
        el.querySelector('.mt-x').addEventListener('click', function () { if (document.querySelectorAll('#e-rooms .rm').length > 1) { el.remove(); render(); } });
        $('e-rooms').appendChild(el);
    }
    if (type === 'paint') { addRoom(); $('e-room').addEventListener('click', function () { addRoom(); render(); }); }
    if (type === 'tiles') $('e-size').addEventListener('change', function () { $('e-box').value = this.selectedOptions[0].dataset.box; });
    $('e-form').addEventListener('input', render); $('e-form').addEventListener('change', render);
    $('e-add').addEventListener('click', function () {
        compute();
        if (!cart.length) { $('e-msg').textContent = 'Nothing from the store to add.'; return; }
        fetch(@json(route('materials.list.add')), {method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify({items: cart})})
            .then(function (r) { if (!r.ok) throw r; return r.json(); }).then(function () { $('e-msg').innerHTML = 'Added. <a href="' + @json(route('materials.list')) + '" style="color:#9a3412">Open my list →</a>'; })
            .catch(function () { $('e-msg').textContent = 'Could not add the items. Try again.'; });
    });
    render();
})();
</script>@endpush
@endsection
