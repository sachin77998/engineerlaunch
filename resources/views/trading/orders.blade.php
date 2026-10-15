@extends('layouts.site')
@section('title', 'My orders and holdings — Do Trading')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'My orders & holdings', 'subtitle' => $client ? 'Client ' . $client->client_code . ' · ' . ucfirst($client->status) : 'Open an account to start investing.'])
<main class="dt-wrap dt-main">
    <div class="dt-chips"><a class="dt-chip on" href="{{ route('trading.orders') }}">Mutual funds</a><a class="dt-chip" href="{{ route('trading.stock-orders') }}">Stocks &amp; ETFs</a></div>
    @if(request('placed'))<p class="dt-flash ok">Order {{ request('placed') }} confirmed. Units are allotted at the NAV of the applicable date, usually by the next business day.</p>@endif
    @php
        $value = $holdings->sum(fn ($h) => $h->units * ($h->scheme->nav ?? 0));
        $invested = $holdings->sum('invested');
    @endphp
    <section class="dt-card">
        <h2>Holdings</h2>
        <div class="dt-stats" style="margin-bottom:12px">
            <div class="dt-stat"><span>Invested</span><b>₹{{ number_format($invested, 2) }}</b></div>
            <div class="dt-stat"><span>Current value</span><b>₹{{ number_format($value, 2) }}</b></div>
            <div class="dt-stat"><span>Gain</span><b class="{{ $value >= $invested ? 'up' : 'down' }}">₹{{ number_format($value - $invested, 2) }}</b></div>
            <div class="dt-stat"><span>Return</span><b class="{{ $value >= $invested ? 'up' : 'down' }}">{{ $invested > 0 ? number_format(($value / $invested - 1) * 100, 2) : '0.00' }}%</b></div>
        </div>
        <div class="dt-table-wrap"><table class="dt-table">
            <thead><tr><th>Fund</th><th class="num">Units</th><th class="num">Avg NAV</th><th class="num">NAV</th><th class="num">Invested</th><th class="num">Value</th><th class="num">Gain</th></tr></thead>
            <tbody>
            @forelse($holdings as $h)
                @php $v = $h->units * ($h->scheme->nav ?? 0); @endphp
                <tr><td><a href="{{ route('trading.fund', $h->scheme_code) }}" style="color:#0b2545;font-weight:700;text-decoration:none">{{ $h->scheme->name ?? $h->scheme_code }}</a><br><small class="dt-muted">{{ $h->scheme?->plan }} · {{ $h->scheme?->option }}</small></td>
                    <td class="num">{{ number_format($h->units, 4) }}</td><td class="num">₹{{ number_format($h->avg_nav, 4) }}</td><td class="num">₹{{ number_format($h->scheme->nav ?? 0, 4) }}</td>
                    <td class="num">₹{{ number_format($h->invested, 2) }}</td><td class="num">₹{{ number_format($v, 2) }}</td><td class="num {{ $v >= $h->invested ? 'up' : 'down' }}">₹{{ number_format($v - $h->invested, 2) }}</td></tr>
            @empty
                <tr><td colspan="7">No holdings yet. <a href="{{ route('trading.index') }}">Explore funds</a>.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <section class="dt-card">
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;align-items:center">
            <h2 style="margin:0">Orders</h2>
            <form method="get" action="{{ route('trading.orders.export', 'xlsx') }}" class="dt-filters">
                <input type="date" name="from" aria-label="From"> <input type="date" name="to" aria-label="To">
                <button class="dt-btn light sm" type="submit">Excel</button>
                <button class="dt-btn light sm" type="submit" formaction="{{ route('trading.orders.export', 'pdf') }}">PDF</button>
            </form>
        </div>
        <div class="dt-table-wrap" style="margin-top:12px"><table class="dt-table">
            <thead><tr><th>Order</th><th>Fund</th><th>Side</th><th class="num">Amount</th><th class="num">Units</th><th class="num">Fees</th><th>NAV date</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($orders as $o)
                <tr><td><b>{{ $o->order_no }}</b><br><small class="dt-muted">{{ $o->created_at->timezone('Asia/Kolkata')->format('d M Y, H:i') }}</small></td>
                    <td style="min-width:220px">{{ $o->scheme->name ?? $o->scheme_code }}<br><small class="dt-muted">{{ $o->scheme?->plan }} · {{ $o->scheme?->option }}</small></td>
                    <td>{{ strtoupper($o->side) }}</td><td class="num">₹{{ number_format($o->amount, 2) }}</td>
                    <td class="num">{{ $o->allotted_units !== null ? number_format($o->allotted_units, 4) : ($o->units !== null ? number_format($o->units, 4) : '—') }}@if($o->nav)<br><small class="dt-muted">@ ₹{{ number_format($o->nav, 4) }}</small>@endif</td>
                    <td class="num">₹{{ number_format($o->total_fees, 2) }}</td><td>{{ $o->applicable_nav_date?->format('d M Y') }}</td>
                    <td><span class="dt-pill {{ $o->status }}">{{ str_replace('_', ' ', $o->status) }}</span>@if($o->status_reason)<br><small class="dt-muted">{{ $o->status_reason }}</small>@endif</td></tr>
            @empty
                <tr><td colspan="8">No orders yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div style="margin-top:12px">{{ $orders->links() }}</div>
    </section>
</main>
@endsection
