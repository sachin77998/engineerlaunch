@extends('layouts.site')
@php
    $o = config('properties');
    $modeCfg = $o['modes'][$mode];
    $isRent = $modeCfg['listing'] === 'rent';
    $homes = $modeCfg['segment'] === 'residential';
    $kind = $f['kind'] ?? null;
    $types = $f['type'] ?? [];
    $where = $cityName ? ' in ' . $cityName : '';
    // Heading: "Plots for sale in Hoshiarpur", "Independent houses for sale in …", "Shop for rent in …".
    $kindLabel = $kind ? ($o['kinds'][$modeCfg['segment']][$kind] ?? null) : null;
    $what = count($types) === 1 ? $o['home_types'][$types[0]][1] : ($kindLabel ?: ($homes ? ($isRent ? 'Homes' : 'Homes and plots') : 'Commercial property'));
    $heading = $what . ($isRent ? ' for rent' : ' for sale') . $where;
    $budgetSteps = $o['budgets'][($isRent ? 'rent' : 'sale') . ($currency === 'INR' ? '_inr' : '')];
    $budget = function ($n) use ($currency, $isRent) {
        if ($currency !== 'INR') return \App\Services\Property\PropertyFormat::short($n, $currency);
        $t = fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
        if ($n >= 1e7) return '₹' . $t($n / 1e7) . ' Cr';
        if ($n >= 1e5 && !$isRent) return '₹' . $t($n / 1e5) . ' Lacs';
        return \App\Services\Property\PropertyFormat::full($n, 'INR');
    };
    $url = fn (array $params = []) => route('properties.search', array_filter([$mode, $city])) . ($params ? '?' . http_build_query($params) : '');
    $checked = fn ($key, $value) => in_array((string) $value, array_map('strval', $f[$key] ?? []), true);
    // Applied filters, each with a link that removes it.
    $applied = collect();
    $without = function ($key, $value = null) use ($f) {
        $next = $f;
        if ($value === null) unset($next[$key]); else $next[$key] = array_values(array_filter($next[$key] ?? [], fn ($v) => (string) $v !== (string) $value));
        return array_filter($next, fn ($v) => $v !== [] && $v !== null && $v !== '');
    };
    if (isset($f['min'])) $applied->push(['Min ' . $budget($f['min']), $without('min')]);
    if (isset($f['max'])) $applied->push(['Max ' . $budget($f['max']), $without('max')]);
    foreach ([['type', fn ($v) => $o['home_types'][$v][0] ?? $v], ['bhk', fn ($v) => $o['bhk_filter'][$v] ?? $o['bhk_search'][$v] ?? $o['bhk'][$v] ?? $v],
              ['possession', fn ($v) => ['new_launch' => 'New launch', 'under_construction' => 'Under construction', 'ready' => 'Ready to move'][$v] ?? $v],
              ['posted_by', fn ($v) => $o['posted_by'][$v] ?? $v], ['loc', fn ($v) => $v], ['project', fn ($v) => $v], ['amen', fn ($v) => $o['amenities'][$v] ?? $v],
              ['furnishing', fn ($v) => $o['furnishing'][$v] ?? $v]] as [$key, $label]) {
        foreach ($f[$key] ?? [] as $v) $applied->push([$label($v), $without($key, $v)]);
    }
    if (isset($f['area_min']) || isset($f['area_max'])) $applied->push(['Area ' . ($f['area_min'] ?? 0) . '–' . ($f['area_max'] ?? '∞') . ' ' . $o['area_units'][$f['area_unit'] ?? 'sqft'][0], array_filter(array_diff_key($f, array_flip(['area_min', 'area_max', 'area_unit'])), fn ($v) => $v !== [] && $v !== null && $v !== '')]);
    $quick = $homes ? ($isRent
        ? [['Flats', ['type' => ['apartment']]], ['Independent houses', ['type' => ['independent_house']]], ['Builder floors', ['type' => ['builder_floor']]], ['PG / hostel', ['kind' => 'pg']], ['Flatmates', ['kind' => 'flatmate']]]
        : [['Buy a home', ['kind' => 'full_house']], ['Independent houses', ['type' => ['independent_house']]], ['Plots' . $where, ['type' => ['plot']]], ['Farm houses', ['type' => ['farm_house']]], ['Flats', ['type' => ['apartment']]], ['New launches', ['possession' => ['new_launch']]]])
        : collect($o['kinds']['commercial'])->map(fn ($label, $k) => [$label, ['kind' => $k]])->values()->all();
    $showMore = 5;
    // "Clear" link on a filter section when something in it is ticked.
    $clear = fn (string ...$keys) => collect($keys)->contains(fn ($k) => !empty($f[$k])) ? '<a class="pf-clear" href="' . e($url(array_filter(array_diff_key($f, array_flip($keys)), fn ($v) => $v !== [] && $v !== null && $v !== ''))) . '">Clear</a>' : '';
    // Pills beside Sort: each toggles one filter on or off.
    $pill = function (string $key, $value = '1') use ($f, $url) {
        $list = is_array($f[$key] ?? null);
        $on = $list ? in_array($value, $f[$key], true) : !empty($f[$key]);
        $next = $f;
        if (in_array($key, ['posted_by', 'possession'], true)) $next[$key] = $on ? array_values(array_diff($f[$key] ?? [], [$value])) : array_merge($f[$key] ?? [], [$value]);
        elseif ($on) unset($next[$key]); else $next[$key] = $value;
        return [$on, $url(array_filter($next, fn ($v) => $v !== [] && $v !== null && $v !== ''))];
    };
    $pills = array_filter([
        'Owner' => $pill('posted_by', 'owner'), 'Verified' => $pill('verified'),
        'New homes' => !$isRent ? $pill('possession', 'new_launch') : null, 'Under construction' => !$isRent ? $pill('possession', 'under_construction') : null,
        'Ready to move' => !$isRent ? $pill('possession', 'ready') : null, 'With photos' => $pill('photos'), 'With videos' => $pill('videos'),
    ]);
    // Area dropdown steps in the chosen unit.
    $areaUnit = $f['area_unit'] ?? 'sqft';
    $areaSteps = ['sqft' => [300, 500, 750, 1000, 1250, 1500, 2000, 2500, 3000, 4000, 5000, 7500, 10000, 20000], 'sqyd' => [50, 100, 150, 200, 300, 500, 1000, 2000],
        'sqm' => [30, 50, 75, 100, 150, 200, 300, 500, 1000, 2000], 'marla' => [2, 3, 4, 5, 6, 8, 10, 15, 20], 'kanal' => [1, 2, 3, 4, 5, 8, 10, 20]][$areaUnit] ?? [1, 2, 3, 5, 10, 20, 50, 100];
@endphp
@section('title', $heading . ' — ' . config('properties.brand'))
@section('meta_description', $results->total() . ' ' . strtolower($heading) . '. Prices, photos and owner contact details.')
@section('content')
@include('properties._nav')
@push('styles')<style>
.pr-res{display:grid;grid-template-columns:290px minmax(0,1fr);gap:20px;align-items:start;margin-top:18px}
.pr-filters{position:sticky;top:12px;max-height:calc(100vh - 24px);overflow:auto;padding:0!important}
.pf-sec{border-bottom:1px solid var(--pr-line);padding:14px 16px}.pf-sec:last-child{border-bottom:0}
.pf-sec>summary{list-style:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-weight:800;font-size:14.5px}
.pf-sec>summary::-webkit-details-marker{display:none}.pf-sec>summary:after{content:'⌄';color:var(--pr-mute)}.pf-sec[open]>summary:after{content:'⌃'}
.pf-body{margin-top:10px}
.pf-row2{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.pf-plus .pr-chip:before{content:'+';font-weight:800;color:var(--pr-mute)}
.pf-plus .pr-chip:has(input:checked):before{content:'✓';color:var(--pr-accent)}
.pf-list label{display:flex;align-items:center;gap:8px;padding:5px 0;font-size:14px;cursor:pointer}.pf-list label span{margin-left:auto;color:var(--pr-mute);font-size:12.5px}
.pf-list label.more{display:none}.pf-list.all label.more{display:flex}
.pf-list button{border:0;background:none;color:var(--pr-accent);font-weight:700;cursor:pointer;padding:6px 0}
.pf-applied{display:flex;flex-wrap:wrap;gap:6px}.pf-applied a{display:inline-flex;gap:6px;align-items:center;padding:4px 10px;border-radius:999px;background:var(--pr-soft);color:var(--pr-accent);font-size:12.5px;font-weight:700;text-decoration:none}
.pr-quick{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0 4px}.pr-quick a{padding:8px 14px;border-radius:999px;border:1px solid #c7d2fe;background:#fff;color:var(--pr-accent);font-weight:700;font-size:13.5px;text-decoration:none}
.pr-quick a.on,.pr-quick a:hover{background:var(--pr-accent);color:#fff;border-color:var(--pr-accent)}
.pr-top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:6px 0 12px}
.pr-crumb{font-size:13px;color:var(--pr-mute);margin:12px 0 0}.pr-crumb a{color:var(--pr-mute)}
.pr-count{font-size:22px;margin:0 0 10px}.pr-count b{font-weight:800}.pr-count span{color:var(--pr-mute);margin:0 8px}
.pr-pills{display:flex;gap:8px;align-items:center;overflow-x:auto;padding-bottom:4px;margin-bottom:12px}
.pr-pills a{flex:0 0 auto;padding:7px 14px;border-radius:999px;border:1px solid var(--pr-line);background:#fff;color:#374151;font-size:13.5px;text-decoration:none}
.pr-pills a.on{background:var(--pr-soft);border-color:var(--pr-accent);color:var(--pr-accent);font-weight:700}
.pr-pills select{flex:0 0 auto;width:auto;margin-left:auto}
.pf-clear{font-size:13px;font-weight:700;margin-left:auto;margin-right:8px}
.pf-more{border:0;background:none;color:var(--pr-accent);font-weight:700;cursor:pointer;padding:4px 2px;font-size:13.5px}
.pf-switch{display:flex;justify-content:space-between;align-items:center;cursor:pointer;font-weight:800;font-size:14.5px}
.pf-switch input{appearance:none;width:38px;height:22px;border-radius:999px;background:#d1d5db;position:relative;cursor:pointer;transition:background .15s;flex:0 0 auto}
.pf-switch input:before{content:'';position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:left .15s}
.pf-switch input:checked{background:var(--pr-accent)}.pf-switch input:checked:before{left:19px}
.pf-switch small{display:block;font-weight:500;color:var(--pr-mute);font-size:12px}
@media(max-width:900px){.pr-res{grid-template-columns:1fr}.pr-filters{position:static;max-height:none}}
</style>@endpush
<div class="pr"><div class="pr-wrap">
    <div style="margin-top:18px">@include('properties._search', ['cities' => $cities, 'mode' => $mode, 'city' => $city, 'areas' => $areas, 'f' => $f])</div>
    <nav class="pr-crumb" aria-label="Breadcrumb"><a href="{{ route('properties.index') }}">Home</a> › <a href="{{ route('properties.search', $mode) }}">{{ $modeCfg['label'] }}</a>@if($cityName) › <a href="{{ route('properties.search', [$mode, $city]) }}">Property in {{ $cityName }}</a>@endif</nav>
    <nav class="pr-quick" aria-label="Quick links">
        @foreach($quick as [$label, $params])
            @php($on = collect($params)->every(fn ($v, $k) => is_array($v) ? ($f[$k] ?? []) == $v : ($f[$k] ?? null) === $v))
            <a href="{{ $url($params) }}" class="{{ $on ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="pr-res">
        <form class="pr-card-box pr-filters" method="get" id="prFilters" aria-label="Filters">
            @foreach($areas as $a)<input type="hidden" name="q[]" value="{{ $a }}">@endforeach
            @if($kind)<input type="hidden" name="kind" value="{{ $kind }}">@endif
            <input type="hidden" name="sort" value="{{ $f['sort'] ?? 'newest' }}">
            <div class="pf-sec" style="display:flex;justify-content:space-between;align-items:center"><b>Applied filters</b>@if($applied->isNotEmpty() || $kind || !empty($f['verified']) || !empty($f['photos']) || !empty($f['videos']) || !empty($f['rera']))<a href="{{ $url() }}" style="font-size:13px;font-weight:700">Clear all</a>@endif</div>
            @if($applied->isNotEmpty())
                <div class="pf-sec"><div class="pf-applied">@foreach($applied as [$label, $params])<a href="{{ $url($params) }}" aria-label="Remove {{ $label }}">{{ $label }} ✕</a>@endforeach</div></div>
            @endif
            <div class="pf-sec"><label class="pf-switch"><span>Verified properties<small>Checked by our team</small></span><input type="checkbox" name="verified" value="1" @checked(!empty($f['verified']))></label></div>

            <details class="pf-sec" open><summary>Budget{{ $isRent ? ' (per month)' : '' }}</summary><div class="pf-body pf-row2">
                <select name="min" aria-label="Minimum budget"><option value="">No min</option>@foreach($budgetSteps as $n)<option value="{{ $n }}" @selected((string) ($f['min'] ?? '') === (string) $n)>{{ $budget($n) }}</option>@endforeach</select>
                <select name="max" aria-label="Maximum budget"><option value="">No max</option>@foreach($budgetSteps as $n)<option value="{{ $n }}" @selected((string) ($f['max'] ?? '') === (string) $n)>{{ $budget($n) }}</option>@endforeach</select>
            </div></details>

            @if($homes && !in_array($kind, ['pg', 'flatmate'], true))
                <details class="pf-sec" open><summary>Type of property{!! $clear('type') !!}</summary><div class="pf-body pr-chips pf-plus" data-collapse="5">
                    @foreach($o['home_types'] as $k => [$label])
                        @continue($isRent && $k === 'plot')
                        <label class="pr-chip"><input type="checkbox" name="type[]" value="{{ $k }}" @checked($checked('type', $k))>{{ $label }}</label>
                    @endforeach
                </div></details>
                @if($types !== ['plot'] && $kind !== 'land')
                    <details class="pf-sec" open><summary>No. of bedrooms{!! $clear('bhk') !!}</summary><div class="pf-body pr-chips pf-plus" data-collapse="5">
                        @foreach($o['bhk_filter'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="bhk[]" value="{{ $k }}" @checked($checked('bhk', $k))>{{ $label }}</label>@endforeach
                    </div></details>
                @endif
            @endif

            @unless($isRent)
                <details class="pf-sec" open><summary>Possession status{!! $clear('possession') !!}</summary><div class="pf-body pr-chips pf-plus">
                    @foreach(['new_launch' => 'New launch', 'under_construction' => 'Under construction', 'ready' => 'Ready to move'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="possession[]" value="{{ $k }}" @checked($checked('possession', $k))>{{ $label }}</label>@endforeach
                </div></details>
            @endunless

            <details class="pf-sec" open><summary>Posted by{!! $clear('posted_by') !!}</summary><div class="pf-body pr-chips pf-plus">
                @foreach($o['posted_by'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="posted_by[]" value="{{ $k }}" @checked($checked('posted_by', $k))>{{ $label }}</label>@endforeach
            </div></details>

            @if($kind !== 'pg')
                <details class="pf-sec" open><summary>Area{!! $clear('area_min', 'area_max') !!}</summary><div class="pf-body">
                    <select name="area_unit" aria-label="Area unit" style="margin-bottom:8px;width:auto;padding:6px 10px;font-size:13.5px">@foreach($o['area_units'] as $k => [$label])<option value="{{ $k }}" @selected($areaUnit === $k)>{{ $label }}</option>@endforeach</select>
                    <div class="pf-row2">
                        <select name="area_min" aria-label="Minimum area"><option value="">No min</option>@foreach($areaSteps as $n)<option value="{{ $n }}" @selected((string) ($f['area_min'] ?? '') === (string) $n)>{{ number_format($n) }} {{ $o['area_units'][$areaUnit][0] }}</option>@endforeach
                            @if(isset($f['area_min']) && !in_array((float) $f['area_min'], $areaSteps))<option value="{{ $f['area_min'] }}" selected>{{ $f['area_min'] }} {{ $o['area_units'][$areaUnit][0] }}</option>@endif</select>
                        <select name="area_max" aria-label="Maximum area"><option value="">No max</option>@foreach($areaSteps as $n)<option value="{{ $n }}" @selected((string) ($f['area_max'] ?? '') === (string) $n)>{{ number_format($n) }} {{ $o['area_units'][$areaUnit][0] }}</option>@endforeach
                            @if(isset($f['area_max']) && !in_array((float) $f['area_max'], $areaSteps))<option value="{{ $f['area_max'] }}" selected>{{ $f['area_max'] }} {{ $o['area_units'][$areaUnit][0] }}</option>@endif</select>
                    </div>
                </div></details>
            @endif

            @if($localities->isNotEmpty())
                <details class="pf-sec" open><summary>Localities{{ $cityName ? ' in ' . $cityName : '' }}{!! $clear('loc') !!}</summary><div class="pf-body pf-list" data-list>
                    @foreach($localities as $name => $n)
                        <label class="{{ $loop->index >= $showMore && !$checked('loc', $name) ? 'more' : '' }}"><input type="checkbox" name="loc[]" value="{{ $name }}" @checked($checked('loc', $name))>{{ $name }}@if($r = $localityRatings[\App\Models\Property\LocalityReview::key($name)] ?? null)<em class="pr-tag amber" style="font-style:normal;padding:1px 6px" title="Rated {{ $r }} by residents">{{ number_format($r, 1) }} ★</em>@endif<span>{{ $n ?: '' }}</span></label>
                    @endforeach
                    @if($localities->count() > $showMore)<button type="button" data-more>⌕ More localities ({{ $localities->count() - $showMore }})</button>
                        <input type="search" placeholder="Search locality" aria-label="Search locality" data-find hidden style="margin-top:6px;padding:8px 10px">@endif
                </div></details>
            @endif

            @if($projects->isNotEmpty())
                <details class="pf-sec" @if(!empty($f['project'])) open @endif><summary>New projects / societies{!! $clear('project') !!}</summary><div class="pf-body pf-list" data-list>
                    @foreach($projects as $name => $n)
                        <label class="{{ $loop->index >= $showMore && !$checked('project', $name) ? 'more' : '' }}"><input type="checkbox" name="project[]" value="{{ $name }}" @checked($checked('project', $name))>{{ $name }}<span>{{ $n }}</span></label>
                    @endforeach
                    @if($projects->count() > $showMore)<button type="button" data-more>Show all {{ $projects->count() }}</button>@endif
                </div></details>
            @endif

            @if($homes && $kind !== 'land' && $types !== ['plot'])
                <details class="pf-sec" open><summary>Amenities{!! $clear('amen') !!}</summary><div class="pf-body pr-chips pf-plus" data-collapse="5">
                    @foreach($o['amenity_filters'] as $k)<label class="pr-chip"><input type="checkbox" name="amen[]" value="{{ $k }}" @checked($checked('amen', $k))>{{ $o['amenities'][$k] }}</label>@endforeach
                </div></details>
            @endif
            <div class="pf-sec"><label class="pf-switch"><span>Properties with photos</span><input type="checkbox" name="photos" value="1" @checked(!empty($f['photos']))></label></div>
            <div class="pf-sec"><label class="pf-switch"><span>Properties with videos</span><input type="checkbox" name="videos" value="1" @checked(!empty($f['videos']))></label></div>
            @if($homes && $kind !== 'land' && $types !== ['plot'])
                <details class="pf-sec" @if(!empty($f['furnishing'])) open @endif><summary>Furnishing status{!! $clear('furnishing') !!}</summary><div class="pf-body pr-chips pf-plus">
                    @foreach($o['furnishing'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="furnishing[]" value="{{ $k }}" @checked($checked('furnishing', $k))>{{ $label }}</label>@endforeach
                </div></details>
            @endif
            @if($mode === 'rent' && (!$kind || $kind === 'full_house'))
                <details class="pf-sec" @if(!empty($f['tenant'])) open @endif><summary>Tenants</summary><div class="pf-body"><select name="tenant" aria-label="Preferred tenants"><option value="">Anyone</option>@foreach($o['preferred_tenants'] as $k => $label)@continue($k === 'any')<option value="{{ $k }}" @selected(($f['tenant'] ?? '') === $k)>{{ $label }}</option>@endforeach</select></div></details>
            @endif
            @if(in_array($kind, ['pg', 'flatmate'], true))
                <details class="pf-sec" open><summary>For</summary><div class="pf-body"><select name="gender" aria-label="For"><option value="">Anyone</option><option value="male" @selected(($f['gender'] ?? '') === 'male')>Men</option><option value="female" @selected(($f['gender'] ?? '') === 'female')>Women</option></select></div></details>
            @endif
            @unless($isRent)
                <div class="pf-sec"><label class="pf-switch"><span>RERA approved<small>Posted with a RERA registration number</small></span><input type="checkbox" name="rera" value="1" @checked(!empty($f['rera']))></label></div>
            @endunless
            <noscript><div class="pf-sec"><button class="pr-btn" type="submit" style="width:100%">Apply</button></div></noscript>
        </form>

        <main style="min-width:0">
            <h1 class="pr-count"><b>{{ number_format($results->total()) }} {{ $results->total() === 1 ? 'result' : 'results' }}</b><span>|</span>{{ $heading }}@if($areas->isNotEmpty()) <small class="pr-mute" style="font-size:15px">· {{ $areas->implode(', ') }}</small>@endif</h1>
            <div class="pr-pills">
                @foreach($pills as $label => [$on, $href])<a href="{{ $href }}" class="{{ $on ? 'on' : '' }}">{{ $on ? '✓ ' : '' }}{{ $label }}</a>@endforeach
                <select aria-label="Sort by" onchange="var f=document.getElementById('prFilters');f.sort.value=this.value;f.submit()">
                    @foreach(\App\Http\Controllers\PropertyController::SORTS as $k => $label)<option value="{{ $k }}" @selected(($f['sort'] ?? 'newest') === $k)>Sort: {{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="pr-list">
                @forelse($results as $p)
                    @include('properties._card', ['p' => $p, 'shortlisted' => $shortlisted])
                @empty
                    <div class="pr-card-box" style="text-align:center;padding:36px">
                        <h2>No listings match</h2>
                        <p class="pr-mute">Try another locality, widen the budget or remove a filter.</p>
                        <a class="pr-btn ghost" href="{{ $url() }}">Clear filters</a>
                    </div>
                @endforelse
            </div>
            <div style="margin-top:16px">{{ $results->links() }}</div>
        </main>
    </div>
</div></div>
@push('scripts')<script>
(function () {
    var form = document.getElementById('prFilters'); if (!form) return;
    // Every change applies at once; empty fields are left out of the address.
    function submit() { form.querySelectorAll('input,select').forEach(function (el) { if (!el.value && el.type !== 'checkbox') el.disabled = true; }); form.submit(); }
    form.addEventListener('change', function (e) {
        if (e.target.matches('[data-find]')) return;
        // Min / max were in the old unit, so a new unit starts without them.
        if (e.target.name === 'area_unit') form.querySelectorAll('[name=area_min],[name=area_max]').forEach(function (s) { s.value = ''; });
        submit();
    });
    form.querySelectorAll('[data-more]').forEach(function (b) {
        b.addEventListener('click', function () { var list = b.closest('[data-list]'), find = list.querySelector('[data-find]'); list.classList.add('all'); if (find) { find.hidden = false; find.focus(); } b.remove(); });
    });
    // Chip groups show the first few options (plus any ticked) and a "+N more" button.
    form.querySelectorAll('[data-collapse]').forEach(function (g) {
        var n = parseInt(g.dataset.collapse, 10), extra = [].slice.call(g.querySelectorAll('.pr-chip')).filter(function (c, i) { return i >= n && !c.querySelector('input').checked; });
        if (!extra.length) return;
        extra.forEach(function (c) { c.hidden = true; });
        var b = document.createElement('button'); b.type = 'button'; b.className = 'pf-more'; b.textContent = '+ ' + extra.length + ' more';
        b.addEventListener('click', function () { extra.forEach(function (c) { c.hidden = false; }); b.remove(); });
        g.appendChild(b);
    });
    form.querySelectorAll('[data-find]').forEach(function (i) {
        i.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
        i.addEventListener('input', function () {
            var list = i.closest('[data-list]'), q = i.value.trim().toLowerCase(); list.classList.add('all');
            list.querySelectorAll('label').forEach(function (l) { l.style.display = !q || l.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none'; });
        });
    });
})();
</script>@endpush
@include('partials.property-services')
@endsection
