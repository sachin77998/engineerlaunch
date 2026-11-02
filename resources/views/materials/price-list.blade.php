@extends('layouts.site')
@section('title', $title . ' price list ' . config('materials.source.city') . ' — ' . now()->format('F Y'))
@section('meta_description', 'Latest ' . $title . ' prices in ' . config('materials.source.city') . ': ' . $items->count() . ' products with MRP, discount, price per unit, bulk price and weekly change.')
@section('content')
@include('materials._nav')
<div class="container mt-wrap">
    <section class="mt-card">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:baseline">
            <h1>{{ $title }} price list, {{ config('materials.source.city') }}</h1>
            <span class="mt-muted">{{ $items->count() }} products · updated {{ \Illuminate\Support\Carbon::parse($updated)->format('d M Y, H:i') }}
                · <a href="{{ $isCategory ? route('materials.category', $slug) : route('materials.search', ['brand' => [$slug]]) }}" style="color:#9a3412">Browse with filters</a></span>
        </div>
        <input class="mt-in" id="mt-find" placeholder="Filter this list…" style="max-width:340px;margin-bottom:10px" aria-label="Filter price list">
        <div class="mt-scroll">
            <table class="mt-table" id="mt-pl">
                <thead><tr><th data-k="0">Product</th><th data-k="1">Type</th><th class="n" data-k="2">Price</th><th class="n" data-k="3">MRP</th><th class="n" data-k="4">Per unit</th><th class="n" data-k="5">Best bulk</th><th class="n" data-k="6">7-day change</th><th></th></tr></thead>
                <tbody>
                @foreach($items as $m)@php($old = $then[$m->id] ?? null)
                    <tr>
                        <td data-v="{{ $m->title }}"><a href="{{ route('materials.show', $m) }}" style="color:#1c1917;font-weight:600;text-decoration:none">{{ $m->title }}</a>@unless($m->in_stock) <span class="mt-tag out">Out of stock</span>@endunless</td>
                        <td data-v="{{ $m->product_type }}" class="mt-muted">{{ $m->product_type }}</td>
                        <td class="n" data-v="{{ $m->price }}"><b>₹{{ number_format($m->price) }}</b>@if(count($m->variants ?? []) > 1)<div class="mt-muted">to ₹{{ number_format(collect($m->variants)->max('price')) }}</div>@endif</td>
                        <td class="n" data-v="{{ $m->mrp ?? 0 }}">@if($m->mrp > $m->price)<s class="mt-muted">₹{{ number_format($m->mrp) }}</s> <span class="mt-off">{{ $m->discountPercent() }}%</span>@else — @endif</td>
                        <td class="n" data-v="{{ $m->unitPrice() ?? 0 }}">{{ $m->unitPrice() ? '₹' . number_format($m->unitPrice(), 1) . '/' . $m->unit : '—' }}</td>
                        <td class="n" data-v="{{ collect($m->bulk_tiers)->min('price') ?? 0 }}">@if($m->bulk_tiers)@php($t = collect($m->bulk_tiers)->sortBy('price')->first())₹{{ number_format($t['price']) }} <span class="mt-muted">({{ $t['qty'] }}+)</span>@else — @endif</td>
                        <td class="n" data-v="{{ $old ? $m->price - $old : 0 }}">@if($old && $old != $m->price)<span class="{{ $m->price > $old ? 'mt-up' : 'mt-down' }}">{{ $m->price > $old ? '▲' : '▼' }} ₹{{ number_format(abs($m->price - $old)) }}</span>@elseif($old) no change @else <span class="mt-muted">new</span> @endif</td>
                        <td><a class="mt-btn sm ghost" href="{{ $m->storeUrl() }}" target="_blank" rel="noopener">Buy ↗</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@push('scripts')<script>
(function () {
    var table = document.getElementById('mt-pl'), body = table.tBodies[0], dir = {};
    document.getElementById('mt-find').addEventListener('input', function () {
        var q = this.value.toLowerCase();
        Array.from(body.rows).forEach(function (r) { r.hidden = q && r.textContent.toLowerCase().indexOf(q) < 0; });
    });
    table.querySelectorAll('th[data-k]').forEach(function (th) {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            var k = +th.dataset.k, d = dir[k] = -(dir[k] || -1);
            Array.from(body.rows).sort(function (a, b) {
                var x = a.cells[k].dataset.v, y = b.cells[k].dataset.v, nx = parseFloat(x), ny = parseFloat(y);
                return (isNaN(nx) || isNaN(ny) ? x.localeCompare(y) : nx - ny) * d;
            }).forEach(function (r) { body.appendChild(r); });
        });
    });
})();
</script>@endpush
@endsection
