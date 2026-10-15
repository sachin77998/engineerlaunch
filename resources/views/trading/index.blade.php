@extends('layouts.site')
@section('title', 'Do Trading — mutual funds, NAV and returns')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'Do Trading', 'subtitle' => number_format($facets['count']) . ' mutual fund schemes from ' . count($facets['amc']) . ' fund houses · NAV as of ' . ($facets['nav_date'] ? \Carbon\Carbon::parse($facets['nav_date'])->format('d M Y') : '—') . ' (AMFI)'])
<main class="dt-wrap dt-main">
    @if($marketClosed)<p class="dt-flash warn">{{ $marketClosed }} Orders open again on {{ $nextDay->format('l, d M Y') }}.</p>@endif
    <section class="dt-card">
        <form method="get" class="dt-filters">
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search fund or fund house (e.g. HDFC Flexi Cap)" aria-label="Search funds" style="flex:1 1 260px">
            <select name="amc" aria-label="Fund house"><option value="">All fund houses</option>@foreach($facets['amc'] as $v)<option @selected(($filters['amc'] ?? '') === $v)>{{ $v }}</option>@endforeach</select>
            <select name="asset_class" aria-label="Asset class"><option value="">All asset classes</option>@foreach($facets['asset_class'] as $v)<option @selected(($filters['asset_class'] ?? '') === $v)>{{ $v }}</option>@endforeach</select>
            <select name="category" aria-label="Category" style="max-width:260px"><option value="">All categories</option>@foreach($facets['category'] as $v)<option @selected(($filters['category'] ?? '') === $v)>{{ $v }}</option>@endforeach</select>
            <select name="plan" aria-label="Plan">@foreach(['Direct' => 'Direct plans', 'Regular' => 'Regular plans', 'all' => 'All plans'] as $v => $l)<option value="{{ $v }}" @selected(($filters['plan'] ?? 'Direct') === $v)>{{ $l }}</option>@endforeach</select>
            <select name="option" aria-label="Option">@foreach(['Growth' => 'Growth', 'IDCW' => 'IDCW (dividend)', 'all' => 'All options'] as $v => $l)<option value="{{ $v }}" @selected(($filters['option'] ?? 'Growth') === $v)>{{ $l }}</option>@endforeach</select>
            <select name="sort" aria-label="Sort">@foreach(['return_3y' => '3Y return', 'return_1y' => '1Y return', 'return_5y' => '5Y return', 'nav' => 'NAV', 'name' => 'Name'] as $v => $l)<option value="{{ $v }}" @selected(($filters['sort'] ?? 'return_3y') === $v)>Sort: {{ $l }}</option>@endforeach</select>
            <button class="dt-btn" type="submit">Search</button>
        </form>
    </section>
    <section class="dt-card">
        <h2>{{ number_format($funds->total()) }} funds</h2>
        <div class="dt-table-wrap">
            <table class="dt-table">
                <thead><tr><th>Fund</th><th>Category</th><th class="num">NAV</th><th class="num">1Y</th><th class="num">3Y CAGR</th><th class="num">5Y CAGR</th></tr></thead>
                <tbody>
                @forelse($funds as $f)
                    @php $chg = $f->dayChange(); @endphp
                    <tr>
                        <td style="min-width:260px"><a href="{{ route('trading.fund', $f) }}" style="color:#0b2545;font-weight:700;text-decoration:none">{{ $f->name }}</a><br><small class="dt-muted">{{ $f->amc }} · {{ $f->plan }} · {{ $f->option }}</small></td>
                        <td><small>{{ $f->category }}</small></td>
                        <td class="num">₹{{ number_format($f->nav, 4) }}@if($chg !== null)<br><small class="{{ $chg >= 0 ? 'up' : 'down' }}">{{ $chg >= 0 ? '▲' : '▼' }} {{ abs($chg) }}%</small>@endif</td>
                        @foreach(['return_1y', 'return_3y', 'return_5y'] as $r)<td class="num">@if($f->{$r} !== null)<span class="{{ $f->{$r} >= 0 ? 'up' : 'down' }}">{{ number_format($f->{$r}, 2) }}%</span>@else — @endif</td>@endforeach
                    </tr>
                @empty
                    <tr><td colspan="6">No funds match these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px">{{ $funds->links() }}</div>
    </section>
</main>
@endsection
