{{-- Search box: Buy / Rent / Commercial, city, up to 3 localities, and each tab's own options. Expects $cities; optional $mode, $city, $areas, $f. --}}
@php
    $o = config('properties');
    $mode = $mode ?? 'buy';
    $tab = str_starts_with($mode, 'commercial') ? 'commercial' : $mode;
    $f = $f ?? [];
    $citySlug = $city ?? '';
    $byCountry = collect($o['cities'])->map(fn ($list) => collect($list));
    $extra = collect($cities)->reject(fn ($c) => $byCountry->flatten()->contains($c));
@endphp
@push('styles')<style>
.ps{background:#fff;border-radius:18px;box-shadow:0 18px 40px rgba(17,24,39,.12);overflow:hidden}
.ps-tabs{display:flex;border-bottom:1px solid var(--pr-line)}
.ps-tabs button{flex:1;padding:14px;border:0;background:#fff;font:700 15px Inter,system-ui,sans-serif;color:#4b5563;cursor:pointer;border-bottom:3px solid transparent}
.ps-tabs button.on{color:var(--pr-accent);border-bottom-color:var(--pr-accent);background:var(--pr-soft)}
.ps-main{display:grid;grid-template-columns:200px minmax(0,1fr) auto;gap:10px;padding:16px}
.ps-areas{display:flex;flex-wrap:wrap;align-items:center;gap:6px;padding:6px 10px;border:1px solid #d1d5db;border-radius:10px;background:#fff;position:relative;min-height:46px;box-sizing:border-box}
.ps-areas input{flex:1;min-width:160px;border:0!important;outline:0!important;padding:6px 2px!important}
.ps-pill{display:inline-flex;align-items:center;gap:6px;background:var(--pr-soft);color:var(--pr-accent);border-radius:999px;padding:4px 10px;font-size:13px;font-weight:700}
.ps-pill button{border:0;background:none;color:inherit;cursor:pointer;font-size:15px;line-height:1;padding:0}
.ps-suggest{position:absolute;left:0;right:0;top:100%;margin-top:4px;background:#fff;border:1px solid var(--pr-line);border-radius:10px;box-shadow:0 12px 26px rgba(0,0,0,.1);z-index:20;display:none;max-height:260px;overflow:auto}
.ps-suggest button{display:block;width:100%;text-align:left;padding:9px 12px;border:0;background:#fff;cursor:pointer;font-size:14px}.ps-suggest button:hover{background:var(--pr-soft)}
.ps-opts{padding:0 16px 16px;display:none;gap:14px 26px;flex-wrap:wrap;align-items:center}.ps-opts.on{display:flex}
.ps-opts .grp{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.ps-opts .grp>b{font-size:13px;color:#4b5563;margin-right:2px}
.ps-radio{display:inline-flex;align-items:center;gap:6px;font-size:14px;cursor:pointer}
@media(max-width:760px){.ps-main{grid-template-columns:1fr}}
</style>@endpush
<form class="ps" id="prSearch" method="get" action="{{ route('properties.search', 'buy') }}" data-base="{{ url('/properties') }}" data-suggest="{{ route('properties.suggest') }}">
    <div class="ps-tabs" role="tablist">
        @foreach(['buy' => 'Buy', 'rent' => 'Rent', 'commercial' => 'Commercial'] as $t => $label)
            <button type="button" role="tab" data-tab="{{ $t }}" class="{{ $tab === $t ? 'on' : '' }}" aria-selected="{{ $tab === $t ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
    </div>
    <div class="ps-main">
        <select name="_city" aria-label="City">
            <option value="">All cities</option>
            @foreach($byCountry as $country => $list)
                <optgroup label="{{ $country }}">@foreach($list as $c)<option value="{{ Str::slug($c) }}" @selected($citySlug === Str::slug($c))>{{ $c }}</option>@endforeach</optgroup>
            @endforeach
            @if($extra->isNotEmpty())<optgroup label="More cities">@foreach($extra as $c)<option value="{{ Str::slug($c) }}" @selected($citySlug === Str::slug($c))>{{ $c }}</option>@endforeach</optgroup>@endif
        </select>
        <div class="ps-areas" id="psAreas">
            @foreach(($areas ?? collect()) as $a)<span class="ps-pill">{{ $a }}<input type="hidden" name="q[]" value="{{ $a }}"><button type="button" aria-label="Remove">×</button></span>@endforeach
            <input type="search" id="psInput" placeholder="Search up to 3 localities, societies or landmarks" aria-label="Locality, society or landmark" autocomplete="off">
            <div class="ps-suggest" id="psSuggest"></div>
        </div>
        <button class="pr-btn" type="submit">Search</button>
    </div>

    <div class="ps-opts {{ $tab === 'buy' ? 'on' : '' }}" data-panel="buy">
        <div class="grp">@foreach(['full_house' => 'Full house', 'land' => 'Land / plot'] as $k => $label)<label class="ps-radio"><input type="radio" name="_kbuy" value="{{ $k }}" @checked($tab === 'buy' ? ($f['kind'] ?? 'full_house') === $k : $k === 'full_house')> {{ $label }}</label>@endforeach</div>
        <div class="grp" data-bhk><b>BHK</b>@foreach($o['bhk_search'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="bhk[]" value="{{ $k }}" @checked(in_array((string) $k, $f['bhk'] ?? [], true) && $tab === 'buy')>{{ $label }}</label>@endforeach</div>
        <div class="grp"><b>Status</b>@foreach($o['possession'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="possession[]" value="{{ $k }}" @checked(in_array($k, $f['possession'] ?? [], true))>{{ $label }}</label>@endforeach</div>
        <label class="ps-radio"><input type="checkbox" name="new_projects" value="1" @checked(!empty($f['new_projects']))> New builder projects</label>
    </div>
    <div class="ps-opts {{ $tab === 'rent' ? 'on' : '' }}" data-panel="rent">
        <div class="grp">@foreach(['full_house' => 'Full house', 'pg' => 'PG / hostel', 'flatmate' => 'Flatmates'] as $k => $label)<label class="ps-radio"><input type="radio" name="_krent" value="{{ $k }}" @checked($tab === 'rent' ? ($f['kind'] ?? 'full_house') === $k : $k === 'full_house')> {{ $label }}</label>@endforeach</div>
        <div class="grp" data-bhk><b>BHK</b>@foreach($o['bhk_search'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="bhk[]" value="{{ $k }}" @checked(in_array((string) $k, $f['bhk'] ?? [], true) && $tab === 'rent')>{{ $label }}</label>@endforeach</div>
        <div class="grp" data-gender><b>For</b>@foreach(['male' => 'Men', 'female' => 'Women'] as $k => $label)<label class="pr-chip"><input type="radio" name="gender" value="{{ $k }}" @checked(($f['gender'] ?? '') === $k)>{{ $label }}</label>@endforeach</div>
    </div>
    <div class="ps-opts {{ $tab === 'commercial' ? 'on' : '' }}" data-panel="commercial">
        <div class="grp">@foreach(['commercial-rent' => 'Rent', 'commercial-buy' => 'Buy'] as $k => $label)<label class="ps-radio"><input type="radio" name="_cmode" value="{{ $k }}" @checked($mode === $k || ($k === 'commercial-rent' && !str_starts_with($mode, 'commercial')))> {{ $label }}</label>@endforeach</div>
        <div class="grp"><b>Property type</b><select name="kind" aria-label="Property type" style="width:auto"><option value="">All types</option>@foreach($o['kinds']['commercial'] as $k => $label)<option value="{{ $k }}" @selected(($f['kind'] ?? '') === $k && $tab === 'commercial')>{{ $label }}</option>@endforeach</select></div>
    </div>
</form>
@push('scripts')<script>
(function () {
    var form = document.getElementById('prSearch'); if (!form) return;
    var tab = @json($tab), areas = document.getElementById('psAreas'), input = document.getElementById('psInput'), box = document.getElementById('psSuggest');
    function panels() { form.querySelectorAll('[data-panel]').forEach(function (p) { p.classList.toggle('on', p.dataset.panel === tab); }); }
    function kindRules() {
        var rent = form.querySelector('input[name=_krent]:checked'), buy = form.querySelector('input[name=_kbuy]:checked');
        form.querySelector('[data-panel=buy] [data-bhk]').style.display = buy && buy.value === 'land' ? 'none' : '';
        form.querySelector('[data-panel=rent] [data-bhk]').style.display = rent && rent.value === 'pg' ? 'none' : '';
        form.querySelector('[data-panel=rent] [data-gender]').style.display = rent && rent.value !== 'full_house' ? '' : 'none';
    }
    form.querySelectorAll('.ps-tabs button').forEach(function (b) {
        b.addEventListener('click', function () { tab = b.dataset.tab; form.querySelectorAll('.ps-tabs button').forEach(function (x) { x.classList.toggle('on', x === b); x.setAttribute('aria-selected', x === b); }); panels(); });
    });
    form.addEventListener('change', kindRules); kindRules();
    function count() { return areas.querySelectorAll('.ps-pill').length; }
    function add(name) {
        name = name.trim(); if (!name || count() >= 3) return;
        var pill = document.createElement('span'); pill.className = 'ps-pill'; pill.textContent = name;
        var h = document.createElement('input'); h.type = 'hidden'; h.name = 'q[]'; h.value = name; pill.appendChild(h);
        var x = document.createElement('button'); x.type = 'button'; x.textContent = '×'; x.setAttribute('aria-label', 'Remove'); pill.appendChild(x);
        areas.insertBefore(pill, input); input.value = ''; box.style.display = 'none'; input.placeholder = count() >= 3 ? 'Up to 3 localities' : 'Add another locality';
    }
    areas.addEventListener('click', function (e) { if (e.target.matches('.ps-pill button')) e.target.parentNode.remove(); });
    var timer;
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter' && input.value.trim()) { e.preventDefault(); add(input.value); } });
    input.addEventListener('input', function () {
        clearTimeout(timer); var q = input.value.trim(); if (q.length < 2) { box.style.display = 'none'; return; }
        timer = setTimeout(function () {
            var url = form.dataset.suggest + '?q=' + encodeURIComponent(q) + '&city=' + encodeURIComponent(form._city.value);
            fetch(url, {headers: {Accept: 'application/json'}}).then(function (r) { return r.ok ? r.json() : []; }).then(function (list) {
                box.innerHTML = ''; list.forEach(function (n) { var b = document.createElement('button'); b.type = 'button'; b.textContent = n; b.onclick = function () { add(n); }; box.appendChild(b); });
                box.style.display = list.length ? 'block' : 'none';
            });
        }, 200);
    });
    document.addEventListener('click', function (e) { if (!areas.contains(e.target)) box.style.display = 'none'; });
    form.addEventListener('submit', function () {
        if (input.value.trim()) add(input.value);
        var mode = tab === 'commercial' ? (form.querySelector('input[name=_cmode]:checked') || {value: 'commercial-rent'}).value : tab;
        form.action = form.dataset.base + '/' + mode + (form._city.value ? '/' + form._city.value : '');
        // Only the open tab's options are sent; the city and commercial rent/buy travel in the address instead.
        form.querySelectorAll('[data-panel]').forEach(function (p) { p.querySelectorAll('input,select').forEach(function (el) { el.disabled = p.dataset.panel !== tab; }); });
        // Options hidden for the chosen kind (BHK for a plot, men/women for a full house) are not sent either.
        form.querySelectorAll('[data-bhk],[data-gender]').forEach(function (g) { if (g.style.display === 'none') g.querySelectorAll('input').forEach(function (el) { el.disabled = true; }); });
        var k = form.querySelector('input[name=_k' + tab + ']:checked'), hidden = form.querySelector('input[name=kind][type=hidden]');
        if (!hidden) { hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'kind'; form.appendChild(hidden); }
        hidden.value = k ? k.value : ''; hidden.disabled = !k;
        form.querySelectorAll('[name=_city],[name=_cmode],[name=_kbuy],[name=_krent]').forEach(function (el) { el.disabled = true; });
        form.querySelectorAll('select[name=kind]').forEach(function (el) { if (!el.value) el.disabled = true; });
        setTimeout(function () { form.querySelectorAll('[disabled]').forEach(function (el) { el.disabled = false; }); }, 0);
    });
    panels();
})();
</script>@endpush
