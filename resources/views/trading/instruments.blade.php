@extends('layouts.site')
@section('title', $title . ' — prices and trading | Do Trading')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@php
    $route = $type === 'ETF' ? 'trading.etfs' : 'trading.stocks';
    $url = fn (array $c) => route($route, array_filter(array_merge(request()->query(), $c, ['page' => null]), fn ($v) => $v !== null && $v !== ''));
    $sortLink = fn ($col) => $url(['sort' => $col, 'dir' => $sort === $col && $dir === 'desc' ? 'asc' : 'desc']);
    $arrow = fn ($col) => $sort === $col ? ($dir === 'desc' ? ' ↓' : ' ↑') : '';
@endphp
@include('trading.partials.hero', ['title' => $title, 'subtitle' => number_format($movers['counts']['NSE'] ?? 0) . ' on NSE' . (($movers['counts']['BSE'] ?? 0) ? ' · ' . number_format($movers['counts']['BSE']) . ' only on BSE' : '') . ' · End-of-day prices ' . ($movers['price_date'] ? \Carbon\Carbon::parse($movers['price_date'])->format('d M Y') : '—') . ($connected ? ' · live ticks from Zerodha' : '')])
<main class="dt-wrap dt-main">
    <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between">
        <p class="dt-flash {{ $status['open'] ? 'ok' : 'warn' }}" style="flex:1">{{ $status['message'] }} @if($connected)<b data-ws-status>Connecting…</b>@endif</p>
        @if(!$connected)<a class="dt-btn gold" href="{{ route('trading.broker.connect') }}">Connect Zerodha for live prices &amp; trading</a>@endif
    </div>

    <section class="dt-cards">
        @foreach(['gainers' => 'Top gainers', 'losers' => 'Top losers', 'active' => 'Most active (turnover)'] as $k => $label)
            <div class="dt-card" style="padding:16px">
                <h2 style="font-size:16px">{{ $label }}</h2>
                @foreach($movers[$k] as $m)
                    <a href="{{ route('trading.instrument', [$m->exchange, $m->symbol]) }}" data-token="{{ $m->kite_token }}" style="display:flex;justify-content:space-between;gap:8px;padding:8px 0;border-bottom:1px solid #f1f1f3;text-decoration:none;color:#1b2437">
                        <span><b>{{ $m->symbol }}</b><br><small class="dt-muted">{{ \Illuminate\Support\Str::limit($m->name, 28) }}</small></span>
                        <span style="text-align:right">₹<span data-ltp>{{ number_format($m->last_price, 2) }}</span><br><small data-chg class="{{ ($m->change_pct ?? 0) >= 0 ? 'up' : 'down' }}">{{ ($m->change_pct ?? 0) >= 0 ? '+' : '' }}{{ number_format($m->change_pct, 2) }}%</small></span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </section>

    <section class="dt-card" style="padding:0;overflow:hidden">
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:16px 20px">
            <form method="get" class="dt-chips" style="flex:1">
                <input type="search" name="q" value="{{ request('q') }}" class="dt-input" placeholder="Search symbol or company" style="width:240px">
                @foreach(request()->except(['q', 'page']) as $k => $v)@if(!is_array($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
                <button class="dt-btn sm" type="submit">Search</button>
                <span class="dt-seg">@foreach(['NSE', 'BSE'] as $ex)<a href="{{ $url(['exchange' => $ex]) }}" class="{{ $exchange === $ex ? 'on' : '' }}">{{ $ex }}</a>@endforeach</span>
                @if($type === 'ETF')
                    <a class="dt-chip {{ !request('class') ? 'on' : '' }}" href="{{ $url(['class' => null]) }}">All</a>
                    @foreach($movers['classes'] as $class => $n)<a class="dt-chip {{ request('class') === $class ? 'on' : '' }}" href="{{ $url(['class' => $class]) }}">{{ ucfirst(strtolower($class)) }} <span class="dt-muted">{{ $n }}</span></a>@endforeach
                @endif
            </form>
            <b>{{ number_format($instruments->total()) }} {{ strtolower($title) }}</b>
        </div>
        <div class="dt-table-wrap">
            <table class="dt-table">
                <thead><tr>
                    <th><a href="{{ $sortLink('symbol') }}" class="{{ $sort === 'symbol' ? 'on' : '' }}">Company{{ $arrow('symbol') }}</a></th>
                    @if($type === 'ETF')<th>Tracks</th>@endif
                    <th class="num"><a href="{{ $sortLink('last_price') }}" class="{{ $sort === 'last_price' ? 'on' : '' }}">Price{{ $arrow('last_price') }}</a></th>
                    <th class="num"><a href="{{ $sortLink('change_pct') }}" class="{{ $sort === 'change_pct' ? 'on' : '' }}">Change{{ $arrow('change_pct') }}</a></th>
                    <th class="num">Open</th><th class="num">High</th><th class="num">Low</th>
                    <th class="num"><a href="{{ $sortLink('volume') }}" class="{{ $sort === 'volume' ? 'on' : '' }}">Volume{{ $arrow('volume') }}</a></th>
                    <th class="num"><a href="{{ $sortLink('turnover_lakhs') }}" class="{{ $sort === 'turnover_lakhs' ? 'on' : '' }}">Turnover{{ $arrow('turnover_lakhs') }}</a></th>
                    <th class="num">Margin</th>
                </tr></thead>
                <tbody>
                @forelse($instruments as $i)
                    <tr data-token="{{ $i->kite_token }}">
                        <td><a class="dt-name" href="{{ route('trading.instrument', [$i->exchange, $i->symbol]) }}">@include('trading.partials.logo', ['domain' => $i->logo_domain, 'name' => $i->name ?: $i->symbol])<span><b>{{ $i->symbol }}</b><small>{{ \Illuminate\Support\Str::limit($i->name, 44) }} · {{ $i->exchange }}</small></span></a></td>
                        @if($type === 'ETF')<td><small>{{ $i->underlying }}</small></td>@endif
                        <td class="num">@if($i->last_price !== null)₹<span data-ltp>{{ number_format($i->last_price, 2) }}</span>@else<span data-ltp class="dt-muted">—</span>@endif</td>
                        <td class="num"><span data-chg class="{{ ($i->change_pct ?? 0) >= 0 ? 'up' : 'down' }}">{{ $i->change_pct !== null ? (($i->change_pct >= 0 ? '+' : '') . number_format($i->change_pct, 2) . '%') : '—' }}</span></td>
                        <td class="num" data-open>{{ $i->open ? number_format($i->open, 2) : '—' }}</td><td class="num" data-high>{{ $i->high ? number_format($i->high, 2) : '—' }}</td><td class="num" data-low>{{ $i->low ? number_format($i->low, 2) : '—' }}</td>
                        <td class="num" data-vol>{{ $i->volume ? number_format($i->volume) : '—' }}</td>
                        <td class="num">{{ $i->turnover_lakhs ? '₹' . \App\Services\Insights\CompanyInsights::indian($i->turnover_lakhs / 100, 2) . ' Cr' : '—' }}</td>
                        <td class="num">{{ $i->margin_pct ? number_format($i->margin_pct, 1) . '%' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="dt-empty">{{ request('q') ? 'No matches.' : 'The instrument list is loading; refresh in a few minutes.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="dt-pager" style="padding:14px 20px">{{ $instruments->links() }}</div>
    </section>
@include('trading.partials.ticker')
</main>
@endsection
