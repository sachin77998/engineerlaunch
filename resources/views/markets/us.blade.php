@extends('layouts.site')
@section('title', 'US Stock Market — NYSE & Nasdaq stocks, prices and market cap')
@push('styles')@include('calculators.styles')@include('insights.partials.styles')
<style>
.us-filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}.us-filters input,.us-filters select{padding:9px 10px;border:1px solid #d5deea;border-radius:8px;font:inherit;background:#fff}
.us-filters input{flex:1;min-width:200px}.us-filters button{padding:9px 16px;border:0;border-radius:8px;background:#0b2545;color:#fff;font-weight:700;cursor:pointer}
.us-movers{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-bottom:18px}
.us-box{background:#fff;border:1px solid #e3e9f2;border-radius:14px;padding:14px 16px}.us-box h3{margin:0 0 8px;font-size:14px;color:#0b2545}
.us-box div{display:flex;justify-content:space-between;padding:5px 0;font-size:14px}.us-box a{color:#0b2545;text-decoration:none;font-weight:700}
.us-bar{height:8px;border-radius:99px;background:#b42318;overflow:hidden;margin-top:8px}.us-bar span{display:block;height:100%;background:#087443}
.cx-table a{color:#1d4f86;font-weight:700;text-decoration:none}.us-tk{display:inline-block;margin-left:6px;padding:1px 6px;border:1px solid #c9d6ea;border-radius:5px;font-size:11px;color:#1d4f86}
.cx-table th a{color:#fff}
</style>@endpush
@section('content')
<section class="cx-hero">
    <div class="cx-wrap">
        <h1>US Stock Market</h1>
        <p>Every stock on the NYSE, Nasdaq and NYSE American: price, day change, volume and market cap, with SEC annual results on each company page.{{ $movers['price_date'] ? ' Prices as of ' . \Carbon\Carbon::parse($movers['price_date'])->format('d M Y') . '.' : '' }}</p>
    </div>
</section>
<main class="cx-wrap cx-main">
    <div class="us-movers">
        <div class="us-box"><h3>Market breadth</h3><div><span class="cx-up">{{ number_format($movers['breadth']['up']) }} up</span><span class="cx-down">{{ number_format($movers['breadth']['down']) }} down</span></div>
            @php $total = max(1, $movers['breadth']['up'] + $movers['breadth']['down']); @endphp
            <div class="us-bar"><span style="width:{{ round($movers['breadth']['up'] / $total * 100) }}%"></span></div></div>
        @foreach(['gainers' => 'Top gainers (≥ $2B cap)', 'losers' => 'Top losers (≥ $2B cap)', 'active' => 'Most active (volume)'] as $key => $title)
            <div class="us-box"><h3>{{ $title }}</h3>
                @foreach($movers[$key] as $s)
                    <div><a href="{{ $s->company ? route('insights.show', $s->company->slug) : '#' }}">{{ $s->ticker }}</a>
                        <span>{{ $key === 'active' ? number_format($s->volume) : '' }} <b class="{{ $s->change_pct >= 0 ? 'cx-up' : 'cx-down' }}">{{ $s->change_pct >= 0 ? '+' : '' }}{{ number_format($s->change_pct, 2) }}%</b></span></div>
                @endforeach
            </div>
        @endforeach
    </div>

    <form class="us-filters" method="get">
        <input type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Search ticker or company (AAPL, Microsoft, Oracle…)" aria-label="Search">
        <select name="exchange" aria-label="Exchange"><option value="">All exchanges</option>@foreach($facets['exchanges'] as $e)<option @selected(($f['exchange'] ?? '') === $e)>{{ $e }}</option>@endforeach</select>
        <select name="sector" aria-label="Sector"><option value="">All sectors</option>@foreach($facets['sectors'] as $s)<option @selected(($f['sector'] ?? '') === $s)>{{ $s }}</option>@endforeach</select>
        <button type="submit">Filter</button>
    </form>

    @php $sortUrl = fn ($key) => request()->fullUrlWithQuery(['sort' => $key, 'dir' => $sort === $key && $dir === 'desc' ? 'asc' : 'desc', 'page' => null]); @endphp
    <div class="cx-scroll" style="max-height:none"><table class="cx-table">
        <thead><tr><th>#</th><th><a href="{{ $sortUrl('name') }}">Company</a></th><th>Exchange</th><th><a href="{{ $sortUrl('last_price') }}">Price</a></th><th><a href="{{ $sortUrl('change_pct') }}">Change</a></th>
            <th><a href="{{ $sortUrl('volume') }}">Volume</a></th><th><a href="{{ $sortUrl('market_cap') }}">Market cap {{ $sort === 'market_cap' ? ($dir === 'desc' ? '↓' : '↑') : '' }}</a></th><th>Sector</th><th>Country</th></tr></thead>
        <tbody>
        @forelse($stocks as $i => $s)
            <tr>
                <td>{{ $stocks->firstItem() + $i }}</td>
                <td style="text-align:left;white-space:normal">@if($s->company)<a href="{{ route('insights.show', $s->company->slug) }}">{{ $s->name }}</a>@else{{ $s->name }}@endif<span class="us-tk">{{ $s->ticker }}</span></td>
                <td>{{ $s->exchange ?? '—' }}</td>
                <td>{{ $s->last_price !== null ? '$' . number_format($s->last_price, 2) : '—' }}</td>
                <td class="{{ $s->change_pct >= 0 ? 'cx-up' : 'cx-down' }}">{{ $s->change_pct !== null ? ($s->change_pct >= 0 ? '+' : '') . number_format($s->change_pct, 2) . '%' : '—' }}</td>
                <td>{{ $s->volume !== null ? number_format($s->volume) : '—' }}</td>
                <td>{{ \App\Services\Insights\UsMarketData::money($s->market_cap) }}</td>
                <td style="text-align:left">{{ $s->sector ?? '—' }}</td>
                <td style="text-align:left">{{ $s->country ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="9" style="text-align:center">No stocks match.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="ci-pager" style="margin-top:14px">{{ $stocks->links() }}</div>
</main>
@endsection
