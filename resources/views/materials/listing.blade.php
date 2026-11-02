@extends('layouts.site')
@section('title', $title . ' — prices, bulk prices and stock')
@section('meta_description', $title . ': live prices, MRP, discounts, bulk prices and stock for construction and interior materials.')
@section('content')
@include('materials._nav')
@push('styles')<style>
.mt-fbar{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:10px 0 26px}
.mt-fbar>span{color:#a8a29e;font-size:16px;margin-right:6px}
.mt-fbar details{position:relative}
.mt-fbar summary{display:flex;align-items:center;gap:8px;padding:8px 12px;border-radius:9px;font-size:16px;color:#292524;cursor:pointer;list-style:none}
.mt-fbar summary::-webkit-details-marker{display:none}
.mt-fbar summary:after{content:'';width:7px;height:7px;border-right:2px solid currentColor;border-bottom:2px solid currentColor;transform:rotate(45deg);margin:-4px 0 0 2px}
.mt-fbar summary.act{color:var(--mt-accent);font-weight:600}
.mt-fbar summary:hover{background:#fafaf9}
.mt-fpanel{position:absolute;top:calc(100% + 6px);left:0;z-index:30;width:270px;max-height:340px;overflow:auto;padding:12px;border:1px solid var(--mt-line);border-radius:14px;background:#fff;box-shadow:0 18px 40px rgba(28,25,23,.14)}
.mt-fpanel label{display:flex;gap:10px;align-items:center;padding:7px 4px;font-size:14.5px;cursor:pointer}
.mt-fpanel label span{margin-left:auto;color:#a8a29e;font-size:12.5px}
.mt-fpanel a{display:block;padding:8px 6px;border-radius:8px;color:#292524;text-decoration:none;font-size:14.5px}.mt-fpanel a:hover{background:#fff7ed;color:var(--mt-accent)}
.mt-fright{margin-left:auto;display:flex;align-items:center;gap:12px;color:#a8a29e;font-size:16px}
.mt-fright select{padding:11px 14px;min-width:200px;border:1px solid #ddd6ce;border-radius:10px;font:inherit;font-size:15px;background:#fff;color:#292524}
.mt-chips{display:flex;gap:8px;flex-wrap:wrap;margin:-14px 0 22px}
.mt-chips a{padding:5px 12px;border-radius:16px;background:#fff7ed;color:#9a3412;font-size:13px;text-decoration:none}.mt-chips a:not(.pl):after{content:' ✕'}
@media(max-width:860px){.mt-fright{margin-left:0;width:100%}.mt-fright select{flex:1;min-width:0}.mt-fpanel{position:fixed;left:16px;right:16px;width:auto;top:30%}}
</style>@endpush
@php
    $catSlug = $context['category'] ?? null;
    $catGroup = $context['group'] ?? null;
    $without = fn ($key, $value = null) => request()->fullUrlWithQuery([$key => $value === null ? null : array_values(array_diff((array) request($key), [$value])), 'page' => null]);
@endphp
<div class="container">
    <nav class="mt-crumbs" aria-label="Breadcrumb"><a href="{{ route('materials.index') }}">Home</a> ›
        @if($catGroup)<a href="{{ route('materials.categories') }}#{{ $catGroup }}">{{ config("materials.groups.$catGroup") }}</a> ›@endif <b>{{ $title }}</b></nav>
    <h1 class="mt-h1">{{ $title }}</h1>
    <form method="get" class="mt-fbar" id="mt-filter">
        @if(isset($context['q']))<input type="hidden" name="q" value="{{ $context['q'] }}">@endif
        <span>Filter:</span>
        <details><summary>Category</summary><div class="mt-fpanel">
            @foreach($allCollections as $slug => $c)<a href="{{ route('materials.category', $slug) }}" @if(($context['category'] ?? null) === $slug) style="color:var(--mt-accent);font-weight:700" @endif>{{ $c['title'] }} <span class="mt-muted">{{ $c['count'] }}</span></a>@endforeach
        </div></details>
        @if($brands->isNotEmpty())
        <details><summary class="{{ !empty($f['brand']) ? 'act' : '' }}">Brand{{ !empty($f['brand']) ? ' (' . count($f['brand']) . ')' : '' }}</summary><div class="mt-fpanel">
            @foreach($brands as $slug => [$name, $n])<label><input type="checkbox" name="brand[]" value="{{ $slug }}" @checked(in_array($slug, $f['brand'] ?? [], true))> {{ $name }}<span>{{ $n }}</span></label>@endforeach
        </div></details>
        @endif
        @if($types->count() > 1)
        <details><summary class="{{ !empty($f['type']) ? 'act' : '' }}">Type</summary><div class="mt-fpanel">
            <label><input type="radio" name="type" value="" @checked(empty($f['type']))> All types</label>
            @foreach($types as $t => $n)<label><input type="radio" name="type" value="{{ $t }}" @checked(($f['type'] ?? '') === $t)> {{ $t }}<span>{{ $n }}</span></label>@endforeach
        </div></details>
        @endif
        <details><summary class="{{ isset($f['min']) || isset($f['max']) ? 'act' : '' }}">Price</summary><div class="mt-fpanel">
            <div style="display:flex;gap:6px"><input class="mt-in" name="min" type="number" min="0" placeholder="Min ₹" value="{{ $f['min'] ?? '' }}"><input class="mt-in" name="max" type="number" min="0" placeholder="Max ₹" value="{{ $f['max'] ?? '' }}"></div>
            <button class="mt-btn sm" style="width:100%;margin-top:8px">Apply</button>
        </div></details>
        <details><summary class="{{ ($f['stock'] ?? false) || ($f['bulk'] ?? false) ? 'act' : '' }}">Availability</summary><div class="mt-fpanel">
            <label><input type="checkbox" name="stock" value="1" @checked($f['stock'] ?? false)> In stock only</label>
            <label><input type="checkbox" name="bulk" value="1" @checked($f['bulk'] ?? false)> Has bulk prices</label>
        </div></details>
        <div class="mt-fright">{{ number_format($items->total()) }} products
            <label for="sort" style="white-space:nowrap">Sort by:</label>
            <select name="sort" id="sort">@foreach($sorts as $k => $label)<option value="{{ $k }}" @selected(($f['sort'] ?? 'popular') === $k)>{{ $label }}</option>@endforeach</select>
        </div>
    </form>
    <div class="mt-chips">
        @foreach($f['brand'] ?? [] as $b)<a href="{{ $without('brand', $b) }}">{{ $brands[$b][0] ?? $b }}</a>@endforeach
        @if(!empty($f['type']))<a href="{{ $without('type') }}">{{ $f['type'] }}</a>@endif
        @if(isset($f['min']) || isset($f['max']))<a href="{{ request()->fullUrlWithQuery(['min' => null, 'max' => null, 'page' => null]) }}">₹{{ $f['min'] ?? 0 }}–{{ $f['max'] ?? '∞' }}</a>@endif
        @if($f['stock'] ?? false)<a href="{{ $without('stock') }}">In stock</a>@endif
        @if($f['bulk'] ?? false)<a href="{{ $without('bulk') }}">Bulk prices</a>@endif
        @if($catSlug)<a class="pl" href="{{ route('materials.price-list', $catSlug) }}" style="background:#ecfdf5;color:#166534">📋 {{ $title }} price list</a>@endif
    </div>
    <div class="mt-wrap" style="padding-top:0">
        @if($items->isEmpty())
            <p class="mt-muted">No products match. Try fewer filters or another word.</p>
        @else
            <div class="mt-grid">@foreach($items as $m)@include('materials._card')@endforeach</div>
            <div>{{ $items->links() }}</div>
        @endif
    </div>
</div>
@push('scripts')<script>
(function () {
    var form = document.getElementById('mt-filter');
    form.querySelectorAll('select, input[type=checkbox], input[type=radio]').forEach(function (el) { el.addEventListener('change', function () { form.submit(); }); });
    var drops = form.querySelectorAll('details');
    drops.forEach(function (d) { d.addEventListener('toggle', function () { if (d.open) drops.forEach(function (o) { if (o !== d) o.open = false; }); }); });
    document.addEventListener('click', function (e) { if (!e.target.closest('#mt-filter details')) drops.forEach(function (d) { d.open = false; }); });
})();
</script>@endpush
@endsection
