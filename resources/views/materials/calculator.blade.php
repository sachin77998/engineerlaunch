@extends('layouts.site')
@section('title', 'Construction cost calculator ' . now()->year . ' — materials, quantities and cost by city')
@section('meta_description', 'House construction cost by city and built-up area: cement, steel, bricks, sand, tiles, paint, wires, plumbing, doors, windows, kitchen and labour, with live material prices.')
@section('content')
@include('materials._nav')
@push('styles')<style>
.mt-calc{display:grid;grid-template-columns:320px 1fr;gap:18px;align-items:start}
.mt-seg{display:inline-flex;border:1px solid #e7d8c9;border-radius:9px;overflow:hidden}
.mt-seg button{white-space:nowrap;border:0;background:#fff;padding:9px 14px;font:600 13px Inter,system-ui,sans-serif;cursor:pointer}.mt-seg button.on{background:#f97316;color:#fff}
.mt-total{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:14px}
.mt-total div{padding:12px 14px;border-radius:12px;background:#fff7ed}.mt-total b{display:block;font-size:22px;color:#9a3412}
.mt-table select{padding:5px 6px;border:1px solid #e7d8c9;border-radius:7px;font:inherit;font-size:13px;background:#fff}
.mt-src{display:flex;gap:8px;align-items:flex-start;font-size:12px}.mt-src img{flex:0 0 40px;width:40px;height:40px;object-fit:contain;border-radius:8px;border:1px solid #f1ece6;background:#fff}
.mt-li{display:flex;gap:10px;align-items:center}.mt-li i{flex:0 0 34px;height:34px;border-radius:10px;display:grid;place-items:center;background:#fff7ed;font-style:normal;font-size:18px}.mt-src a{color:#9a3412}
@media(max-width:900px){.mt-calc{grid-template-columns:1fr}}
</style>@endpush
<div class="container mt-wrap">
    <div class="mt-calc">
        <section class="mt-card">
            <h1 style="font-size:19px">Construction cost calculator</h1>
            <label class="mt-label" for="c-state">State</label>
            <select class="mt-in" id="c-state"><option value="">Select state</option>@foreach($states as $s)<option @selected(($in['state'] ?? 'Karnataka') === $s)>{{ $s }}</option>@endforeach</select>
            <label class="mt-label" for="c-city">City</label>
            <input class="mt-in" id="c-city" list="c-cities" value="{{ $in['city'] ?? 'Bengaluru' }}" placeholder="Type or pick a city">
            <datalist id="c-cities"></datalist>
            <label class="mt-label" for="c-area">Built-up area</label>
            <div style="display:flex;gap:8px;align-items:center">
                <input class="mt-in" id="c-area" type="number" min="100" step="10" value="{{ $in['area'] ?? 1000 }}">
                <div class="mt-seg" id="c-unit"><button type="button" data-u="sqft" class="{{ ($in['unit'] ?? 'sqft') === 'sqft' ? 'on' : '' }}">sq ft</button><button type="button" data-u="sqm" class="{{ ($in['unit'] ?? '') === 'sqm' ? 'on' : '' }}">sq m</button></div>
            </div>
            <label class="mt-label">Quality for every item</label>
            <div class="mt-seg" id="c-all">@foreach(config('materials.tiers') as $k => $label)<button type="button" data-t="{{ $k }}" class="{{ $k === 'medium' ? 'on' : '' }}">{{ $label }}</button>@endforeach</div>
            <p class="mt-muted" id="c-factor" style="margin-top:12px"></p>
            <div style="display:grid;gap:8px;margin-top:14px">
                <button type="button" class="mt-btn" id="c-add">Add store items to my list</button>
                <button type="button" class="mt-btn ghost" id="c-csv">Download estimate (CSV)</button>
                <button type="button" class="mt-btn ghost" id="c-reset">Reset</button>
            </div>
            <p class="mt-muted" id="c-msg" aria-live="polite"></p>
        </section>
        <section class="mt-card">
            <div class="mt-total">
                <div>Total cost<b id="t-total">—</b></div>
                <div>Per sq ft<b id="t-sqft">—</b></div>
                <div>Materials<b id="t-mat">—</b></div>
                <div>Labour<b id="t-lab">—</b></div>
            </div>
            <div class="mt-scroll">
                <table class="mt-table">
                    <thead><tr><th>Item</th><th>Quality</th><th class="n">Quantity</th><th class="n">Rate</th><th class="n">Cost</th><th>Price from</th></tr></thead>
                    <tbody id="c-body"></tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@push('scripts')<script>
(function () {
    var lines = @json(array_values($lines)), cities = @json($cities), factors = @json($factors), tiers = @json(config('materials.tiers'));
    var icons = {cement: '🧱', steel: '🔩', bricks: '🧱', sand: '⛱️', aggregate: '🪨', tiles: '🔲', tile_adhesive: '🪣', putty: '🪣', primer: '🖌️', paint: '🎨', waterproofing: '💧', wires: '🔌', electrical: '💡', plumbing: '🚰', doors: '🚪', windows: '🪟', kitchen: '🍳', labour: '👷'};
    var showUrl = @json(route('materials.show', '__H__')), catUrl = @json(route('materials.category', '__C__'));
    var $ = function (id) { return document.getElementById(id); }, unit = '{{ $in['unit'] ?? 'sqft' }}', choice = {};
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); };
    var inr = function (n) { return '₹' + Math.round(n).toLocaleString('en-IN'); };
    var fmt = function (n) { return n >= 100 ? Math.round(n).toLocaleString('en-IN') : (Math.round(n * 10) / 10).toLocaleString('en-IN'); };
    lines.forEach(function (l) { choice[l.key] = 'medium'; });

    function fillCities() {
        var list = cities[$('c-state').value] || [];
        $('c-cities').innerHTML = list.map(function (c) { return '<option value="' + esc(c) + '">'; }).join('');
    }
    function factor() {
        var c = $('c-city').value.trim().toLowerCase(), f = factors.other.factor, name = 'other cities';
        Object.keys(factors).forEach(function (k) { if (factors[k].cities.some(function (x) { return x.toLowerCase() === c; })) { f = factors[k].factor; name = k === 'metro' ? 'metro' : 'tier-2 city'; } });
        return {f: f, name: name};
    }
    function sqft() { var a = parseFloat($('c-area').value) || 0; return unit === 'sqm' ? a * 10.7639 : a; }
    function compute() {
        var area = sqft(), fc = factor(), rows = [];
        lines.forEach(function (l) {
            var t = choice[l.key], rate = l.rates[t] * (l.local ? fc.f : 1), qty = Math.max(l.qty * area, l.min || 0);
            if (l.unit === 'nos' || l.unit === 'bag') qty = Math.ceil(qty);
            rows.push({l: l, tier: t, qty: qty, rate: rate, cost: qty * rate, pick: l.picks ? l.picks[t] : null});
        });
        return {area: area, fc: fc, rows: rows};
    }
    function render() {
        var r = compute(), total = 0, labour = 0;
        $('c-body').innerHTML = r.rows.map(function (x) {
            total += x.cost; if (x.l.key === 'labour') labour += x.cost;
            var src = x.l.live
                ? '<span class="mt-src">' + (x.pick.image ? '<img src="' + esc(x.pick.image) + '" alt="" loading="lazy">' : '') + '<span>Live · ' + x.l.count + ' offers<br><a href="' + showUrl.replace('__H__', encodeURIComponent(x.pick.handle)) + '">' + esc(x.pick.title) + (x.pick.variant ? ' (' + esc(x.pick.variant) + ')' : '') + '</a> ' + inr(x.pick.pack_price) + '</span></span>'
                : '<span class="mt-src mt-muted">Standard rate' + (x.l.local ? ' · ' + r.fc.name : '') + '</span>';
            return '<tr><td><span class="mt-li"><i>' + (icons[x.l.key] || '•') + '</i><b>' + x.l.label + '</b></span></td><td><select data-k="' + x.l.key + '">' + Object.keys(tiers).map(function (k) { return '<option value="' + k + '"' + (k === x.tier ? ' selected' : '') + '>' + tiers[k] + '</option>'; }).join('') + '</select></td>'
                + '<td class="n">' + fmt(x.qty) + ' ' + x.l.unit + '</td><td class="n">' + inr(x.rate) + '/' + x.l.unit + '</td><td class="n"><b>' + inr(x.cost) + '</b></td><td>' + src + '</td></tr>';
        }).join('');
        $('t-total').textContent = inr(total);
        $('t-sqft').textContent = r.area ? inr(total / r.area) : '—';
        $('t-mat').textContent = inr(total - labour);
        $('t-lab').textContent = inr(labour);
        $('c-factor').textContent = 'Local costs (labour, sand, aggregate, bricks) × ' + r.fc.f + ' for ' + ($('c-city').value || 'your city') + ' (' + r.fc.name + '). Store prices: {{ config('materials.source.city') }}.';
        $('c-body').querySelectorAll('select').forEach(function (s) { s.addEventListener('change', function () { choice[s.dataset.k] = s.value; render(); }); });
        try { history.replaceState(null, '', '?' + new URLSearchParams({state: $('c-state').value, city: $('c-city').value, area: $('c-area').value, unit: unit})); } catch (e) {}
    }
    function packs(x) { return x.l.per === 'pack' ? Math.ceil(x.qty) : Math.ceil(x.qty / (x.pick.pack_size || 1)); }

    $('c-state').addEventListener('change', function () { fillCities(); render(); });
    ['c-city', 'c-area'].forEach(function (id) { $(id).addEventListener('input', render); });
    $('c-unit').querySelectorAll('button').forEach(function (b) { b.addEventListener('click', function () {
        if (b.dataset.u === unit) return;
        var a = parseFloat($('c-area').value) || 0;
        $('c-area').value = Math.round(b.dataset.u === 'sqm' ? a / 10.7639 : a * 10.7639);
        unit = b.dataset.u; $('c-unit').querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); }); render();
    }); });
    $('c-all').querySelectorAll('button').forEach(function (b) { b.addEventListener('click', function () {
        Object.keys(choice).forEach(function (k) { choice[k] = b.dataset.t; });
        $('c-all').querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); }); render();
    }); });
    $('c-reset').addEventListener('click', function () { $('c-area').value = 1000; $('c-state').value = 'Karnataka'; $('c-city').value = 'Bengaluru'; unit = 'sqft';
        $('c-unit').querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x.dataset.u === 'sqft'); });
        $('c-all').querySelector('[data-t=medium]').click(); fillCities(); });
    $('c-add').addEventListener('click', function () {
        var items = compute().rows.filter(function (x) { return x.pick; }).map(function (x) { return {handle: x.pick.handle, variant: x.pick.variant_index, qty: packs(x)}; });
        if (!items.length) { $('c-msg').textContent = 'No store prices loaded yet.'; return; }
        fetch(@json(route('materials.list.add')), {method: 'POST', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify({items: items})})
            .then(function (r) { return r.json(); }).then(function () { $('c-msg').innerHTML = items.length + ' items added. <a href="' + @json(route('materials.list')) + '" style="color:#9a3412">Open my list →</a>'; })
            .catch(function () { $('c-msg').textContent = 'Could not add the items. Try again.'; });
    });
    $('c-csv').addEventListener('click', function () {
        var r = compute(), csv = [['Item', 'Quality', 'Quantity', 'Unit', 'Rate (Rs)', 'Cost (Rs)', 'Price from']];
        r.rows.forEach(function (x) { csv.push([x.l.label, tiers[x.tier], Math.round(x.qty * 10) / 10, x.l.unit, Math.round(x.rate), Math.round(x.cost), x.pick ? x.pick.title : 'Standard rate']); });
        csv.push(['Total', '', '', '', '', Math.round(r.rows.reduce(function (s, x) { return s + x.cost; }, 0)), '']);
        var blob = new Blob([csv.map(function (row) { return row.map(function (c) { return '"' + String(c).replace(/"/g, '""') + '"'; }).join(','); }).join('\n')], {type: 'text/csv'});
        var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'construction-estimate.csv'; a.click();
    });
    document.addEventListener('mt-pin', function (e) {
        if (e.detail.state) { var opt = Array.from($('c-state').options).find(function (o) { return o.value.toLowerCase() === e.detail.state.toLowerCase(); }); if (opt) { $('c-state').value = opt.value; fillCities(); } }
        if (e.detail.district) $('c-city').value = e.detail.district;
        render();
    });
    fillCities(); render();
})();
</script>@endpush
@endsection
