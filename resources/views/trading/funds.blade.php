@extends('layouts.site')
@section('title', $meta['title'] . ' — mutual funds | Do Trading')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@php
    $sel = fn ($key) => array_values(array_filter((array) ($filters[$key] ?? []), fn ($v) => $v !== null && $v !== ''));
    $plan = $filters['plan'] ?? 'Direct'; $option = $filters['option'] ?? 'Growth';
    $sort = $filters['sort'] ?? 'return_3y'; $dir = $filters['dir'] ?? 'desc';
    $base = request()->url();
    $url = function (array $change) use ($base) {
        $q = array_merge(request()->query(), $change);
        unset($q['page']);
        $q = array_filter($q, fn ($v) => $v !== null && $v !== '' && $v !== []);
        return $base . ($q ? '?' . http_build_query($q) : '');
    };
    $toggleList = function (string $key, string $value) use ($sel, $url) {
        $now = $sel($key);
        return $url([$key => in_array($value, $now, true) ? array_values(array_diff($now, [$value])) : array_merge($now, [$value])]);
    };
    $sortLink = fn ($col) => $url(['sort' => $col, 'dir' => $sort === $col && $dir === 'desc' ? 'asc' : 'desc']);
    $arrow = fn ($col) => $sort === $col ? ($dir === 'desc' ? ' ↓' : ' ↑') : '';
    $catCount = count($sel('sub_category')) + count($sel('asset_class'));
@endphp
@include('trading.partials.hero', ['title' => $meta['title'], 'subtitle' => $meta['description'] ?? 'Filter and compare every mutual fund in India'])
<main class="dt-wrap dt-main">
    <section class="dt-card" style="padding:16px">
        <form method="get" id="dt-screener" class="dt-chips">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="dt-input" placeholder="Search funds" aria-label="Search funds" style="width:220px">
            <details class="dt-dd">
                <summary class="dt-chip {{ $catCount ? 'on' : '' }}">Categories @if($catCount)<span class="n">{{ $catCount }}</span>@endif</summary>
                <div class="dt-panel" style="width:340px">
                    <div class="dt-panel-body">
                        <label class="dt-opt"><input type="checkbox" name="is_index" value="1" @checked(!empty($filters['is_index']))> Index funds only</label>
                        @foreach($facets['categories'] as $asset => $cats)
                            <details class="dt-group" @if(in_array($asset, $sel('asset_class'), true) || $cats->pluck('sub_category')->intersect($sel('sub_category'))->isNotEmpty()) open @endif>
                                <summary><input type="checkbox" name="asset_class[]" value="{{ $asset }}" @checked(in_array($asset, $sel('asset_class'), true)) onclick="event.stopPropagation()"> {{ $asset }} <small class="dt-muted" style="margin-left:auto">{{ $cats->sum('scheme_count') }}</small></summary>
                                @foreach($cats as $c)<label class="dt-opt"><input type="checkbox" name="sub_category[]" value="{{ $c->sub_category }}" @checked(in_array($c->sub_category, $sel('sub_category'), true))> {{ $c->sub_category }} <small>{{ $c->scheme_count }}</small></label>@endforeach
                            </details>
                        @endforeach
                    </div>
                    <div class="dt-panel-foot"><a class="dt-btn light sm" href="{{ $url(['asset_class' => null, 'sub_category' => null, 'is_index' => null]) }}">Clear all</a><button class="dt-btn sm" type="submit">Apply</button></div>
                </div>
            </details>
            <details class="dt-dd">
                <summary class="dt-chip {{ $sel('risk') ? 'on' : '' }}">Risk @if($sel('risk'))<span class="n">{{ count($sel('risk')) }}</span>@endif</summary>
                <div class="dt-panel"><div class="dt-panel-body">
                    @foreach($risks as $r)<label class="dt-opt"><input type="checkbox" name="risk[]" value="{{ $r }}" @checked(in_array($r, $sel('risk'), true))> {{ $r }}</label>@endforeach
                </div><div class="dt-panel-foot"><a class="dt-btn light sm" href="{{ $url(['risk' => null]) }}">Clear all</a><button class="dt-btn sm" type="submit">Apply</button></div></div>
            </details>
            <details class="dt-dd">
                <summary class="dt-chip {{ !empty($filters['min_rating']) ? 'on' : '' }}">Ratings @if(!empty($filters['min_rating']))<span class="n">{{ $filters['min_rating'] }}+★</span>@endif</summary>
                <div class="dt-panel" style="width:220px"><div class="dt-panel-body">
                    @foreach([5, 4, 3, 2, 1] as $r)<label class="dt-opt"><input type="radio" name="min_rating" value="{{ $r }}" @checked((int) ($filters['min_rating'] ?? 0) === $r)> {{ $r }}{{ $r < 5 ? '+' : '' }} <span class="dt-stars">★</span></label>@endforeach
                </div><div class="dt-panel-foot"><a class="dt-btn light sm" href="{{ $url(['min_rating' => null]) }}">Clear all</a><button class="dt-btn sm" type="submit">Apply</button></div></div>
            </details>
            <details class="dt-dd">
                <summary class="dt-chip {{ $sel('amc') ? 'on' : '' }}">Fund house @if($sel('amc'))<span class="n">{{ count($sel('amc')) }}</span>@endif</summary>
                <div class="dt-panel" style="width:320px">
                    <div style="padding:12px 14px 0"><input type="search" class="dt-input" placeholder="Search fund house" style="width:100%" oninput="const q=this.value.toLowerCase();this.closest('.dt-panel').querySelectorAll('[data-amc]').forEach(l=>l.hidden=!l.dataset.amc.includes(q))"></div>
                    <div class="dt-panel-body">
                        @foreach($facets['amcs'] as $a)<label class="dt-opt" data-amc="{{ strtolower($a->short_name) }}"><input type="checkbox" name="amc[]" value="{{ $a->name }}" @checked(in_array($a->name, $sel('amc'), true))> {{ $a->short_name }} Mutual Fund <small>{{ $a->scheme_count }}</small></label>@endforeach
                    </div>
                    <div class="dt-panel-foot"><a class="dt-btn light sm" href="{{ $url(['amc' => null]) }}">Clear all</a><button class="dt-btn sm" type="submit">Apply</button></div>
                </div>
            </details>
            <details class="dt-dd">
                <summary class="dt-chip on">{{ $plan === 'all' ? 'All plans' : $plan }} · {{ $option === 'all' ? 'All options' : $option }}</summary>
                <div class="dt-panel" style="width:240px"><div class="dt-panel-body">
                    <b class="dt-muted">Plan</b>
                    @foreach(['Direct' => 'Direct', 'Regular' => 'Regular', 'all' => 'All plans'] as $v => $l)<label class="dt-opt"><input type="radio" name="plan" value="{{ $v }}" @checked($plan === $v)> {{ $l }}</label>@endforeach
                    <b class="dt-muted">Option</b>
                    @foreach(['Growth' => 'Growth', 'IDCW' => 'IDCW (dividend)', 'all' => 'All options'] as $v => $l)<label class="dt-opt"><input type="radio" name="option" value="{{ $v }}" @checked($option === $v)> {{ $l }}</label>@endforeach
                </div><div class="dt-panel-foot"><span></span><button class="dt-btn sm" type="submit">Apply</button></div></div>
            </details>
            <span style="width:1px;height:26px;background:#e9e9eb;margin:0 4px"></span>
            <a class="dt-chip {{ !empty($filters['is_index']) ? 'on' : '' }}" href="{{ $url(['is_index' => empty($filters['is_index']) ? 1 : null]) }}">Index only</a>
            @foreach(['Flexi Cap', 'Large Cap', 'Mid Cap', 'Small Cap', 'Sectoral / Thematic', 'ELSS'] as $chip)
                <a class="dt-chip {{ in_array($chip, $sel('sub_category'), true) ? 'on' : '' }}" href="{{ $toggleList('sub_category', $chip) }}">{{ $chip === 'Sectoral / Thematic' ? 'Sectoral' : $chip }}</a>
            @endforeach
            <a class="dt-chip {{ (int) ($filters['min_rating'] ?? 0) === 4 ? 'on' : '' }}" href="{{ $url(['min_rating' => (int) ($filters['min_rating'] ?? 0) === 4 ? null : 4]) }}">4+ ★</a>
            <input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="dir" value="{{ $dir }}">
        </form>
        <div class="dt-chips" style="margin-top:12px">
            <span class="dt-muted">Collections:</span>
            @foreach($facets['collections'] as $c)<a class="dt-chip {{ ($meta['collection']->slug ?? null) === $c->slug ? 'on' : '' }}" href="{{ route('trading.collection', $c->slug) }}">{{ $c->title }}</a>@endforeach
        </div>
    </section>

    <section class="dt-card" style="padding:0;overflow:hidden">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px">
            <b>{{ number_format($funds->total()) }} results</b>
            @if(request()->query())<a class="dt-btn light sm" href="{{ $base }}">Reset filters</a>@endif
        </div>
        <div class="dt-table-wrap">
            <table class="dt-table">
                <thead><tr>
                    <th><a href="{{ $sortLink('name') }}" class="{{ $sort === 'name' ? 'on' : '' }}">Fund name{{ $arrow('name') }}</a></th>
                    <th>Category</th>
                    <th class="num"><a href="{{ $sortLink('return_1y') }}" class="{{ $sort === 'return_1y' ? 'on' : '' }}">1Y{{ $arrow('return_1y') }}</a></th>
                    <th class="num"><a href="{{ $sortLink('return_3y') }}" class="{{ $sort === 'return_3y' ? 'on' : '' }}">3Y{{ $arrow('return_3y') }}</a></th>
                    <th class="num"><a href="{{ $sortLink('return_5y') }}" class="{{ $sort === 'return_5y' ? 'on' : '' }}">5Y{{ $arrow('return_5y') }}</a></th>
                    <th class="num"><a href="{{ $sortLink('rating') }}" class="{{ $sort === 'rating' ? 'on' : '' }}">Rating{{ $arrow('rating') }}</a></th>
                    <th class="num"><a href="{{ $sortLink('nav') }}" class="{{ $sort === 'nav' ? 'on' : '' }}">NAV{{ $arrow('nav') }}</a></th>
                </tr></thead>
                <tbody>
                @forelse($funds as $f)
                    <tr>
                        <td><a class="dt-name" href="{{ route('trading.fund', $f) }}">@include('trading.partials.logo', ['name' => $f->amc, 'color' => $f->fundHouse?->color])<span><b>{{ $f->displayName() }}</b><small>{{ $f->fundHouse?->short_name ?? $f->amc }} · {{ $f->risk_level }} risk</small></span></a></td>
                        <td>{{ $f->asset_class }} {{ $f->sub_category }}</td>
                        @foreach(['return_1y', 'return_3y', 'return_5y'] as $r)<td class="num">@if($f->{$r} !== null)<span class="{{ $f->{$r} >= 0 ? 'up' : 'down' }}">{{ $f->{$r} >= 0 ? '+' : '' }}{{ number_format($f->{$r}, 2) }}%</span>@else<span class="dt-muted">—</span>@endif</td>@endforeach
                        <td class="num">@include('trading.partials.stars', ['rating' => $f->rating])</td>
                        <td class="num">₹{{ number_format($f->nav, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="dt-empty">No funds match these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="dt-pager" style="padding:14px 20px">{{ $funds->links() }}</div>
    </section>
</main>
@endsection
@push('scripts')
<script>
document.querySelectorAll('.dt-dd').forEach(d => d.addEventListener('toggle', () => { if (d.open) document.querySelectorAll('.dt-dd').forEach(o => { if (o !== d) o.open = false; }); }));
document.addEventListener('click', e => { if (!e.target.closest('.dt-dd')) document.querySelectorAll('.dt-dd[open]').forEach(d => d.open = false); });
</script>
@endpush
