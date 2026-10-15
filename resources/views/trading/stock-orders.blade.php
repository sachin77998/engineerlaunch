@extends('layouts.site')
@section('title', 'Stock orders and portfolio — Do Trading')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'Stocks & ETFs portfolio', 'subtitle' => $connected ? 'Zerodha ' . $connected->broker_user_id . ' · ' . ($connected->profile['name'] ?? '') . ' · session until ' . $connected->expires_at->timezone('Asia/Kolkata')->format('d M, g:i A') : 'Connect your Zerodha account to trade and see your holdings'])
<main class="dt-wrap dt-main">
    <div class="dt-chips"><a class="dt-chip" href="{{ route('trading.orders') }}">Mutual funds</a><a class="dt-chip on" href="{{ route('trading.stock-orders') }}">Stocks &amp; ETFs</a></div>
    @if(!$connected)
        <section class="dt-card dt-empty">
            <b style="font-size:17px;color:#1b2437">Trade stocks and ETFs through your own Zerodha account</b>
            <p>Your orders, holdings and funds stay in your Zerodha account; we never hold your money. Zerodha asks you to log in once a day.</p>
            <a class="dt-btn gold" href="{{ route('trading.broker.connect') }}">Connect Zerodha</a>
        </section>
    @else
        @if($portfolio['error'])<p class="dt-flash bad">{{ $portfolio['error'] }}</p>@endif
        @php
            $eq = $portfolio['funds'] ?? [];
            $holdings = collect($portfolio['holdings']);
            $invested = $holdings->sum(fn ($h) => $h['average_price'] * $h['quantity']);
            $current = $holdings->sum(fn ($h) => $h['last_price'] * $h['quantity']);
        @endphp
        <section class="dt-stats">
            <div class="dt-stat"><span>Available cash</span><b>₹{{ number_format($eq['available']['live_balance'] ?? $eq['net'] ?? 0, 2) }}</b></div>
            <div class="dt-stat"><span>Margin used</span><b>₹{{ number_format($eq['utilised']['debits'] ?? 0, 2) }}</b></div>
            <div class="dt-stat"><span>Holdings invested</span><b>₹{{ number_format($invested, 2) }}</b></div>
            <div class="dt-stat"><span>Holdings value</span><b>₹{{ number_format($current, 2) }}</b></div>
            <div class="dt-stat"><span>Total P&amp;L</span><b class="{{ $current >= $invested ? 'up' : 'down' }}">₹{{ number_format($current - $invested, 2) }}</b></div>
        </section>
        <section class="dt-card" style="padding:0;overflow:hidden">
            <h2 style="padding:18px 20px 0">Holdings ({{ $holdings->count() }})</h2>
            <div class="dt-table-wrap"><table class="dt-table">
                <thead><tr><th>Instrument</th><th class="num">Qty</th><th class="num">Avg cost</th><th class="num">LTP</th><th class="num">Value</th><th class="num">P&amp;L</th><th class="num">Day</th></tr></thead>
                <tbody>
                @forelse($holdings as $h)
                    @php $pl = ($h['last_price'] - $h['average_price']) * $h['quantity']; @endphp
                    <tr data-token="{{ $h['instrument_token'] }}"><td><a class="dt-name" href="{{ route('trading.instrument', [$h['exchange'], $h['tradingsymbol']]) }}">@include('trading.partials.logo', ['name' => $h['tradingsymbol']])<span><b>{{ $h['tradingsymbol'] }}</b><small>{{ $h['exchange'] }}</small></span></a></td>
                        <td class="num">{{ $h['quantity'] }}</td><td class="num">{{ number_format($h['average_price'], 2) }}</td><td class="num" data-ltp>{{ number_format($h['last_price'], 2) }}</td>
                        <td class="num">₹{{ number_format($h['last_price'] * $h['quantity'], 2) }}</td><td class="num {{ $pl >= 0 ? 'up' : 'down' }}">₹{{ number_format($pl, 2) }}</td><td class="num"><span data-chg class="{{ ($h['day_change_percentage'] ?? 0) >= 0 ? 'up' : 'down' }}">{{ number_format($h['day_change_percentage'] ?? 0, 2) }}%</span></td></tr>
                @empty
                    <tr><td colspan="7" class="dt-empty">No holdings in your Zerodha account.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>
        @if(count($portfolio['positions']))
            <section class="dt-card" style="padding:0;overflow:hidden">
                <h2 style="padding:18px 20px 0">Positions</h2>
                <div class="dt-table-wrap"><table class="dt-table"><thead><tr><th>Instrument</th><th>Product</th><th class="num">Qty</th><th class="num">Avg</th><th class="num">LTP</th><th class="num">P&amp;L</th></tr></thead><tbody>
                    @foreach($portfolio['positions'] as $p)<tr data-token="{{ $p['instrument_token'] }}"><td><b>{{ $p['tradingsymbol'] }}</b> <small class="dt-muted">{{ $p['exchange'] }}</small></td><td>{{ $p['product'] }}</td><td class="num">{{ $p['quantity'] }}</td><td class="num">{{ number_format($p['average_price'], 2) }}</td><td class="num" data-ltp>{{ number_format($p['last_price'], 2) }}</td><td class="num {{ ($p['pnl'] ?? 0) >= 0 ? 'up' : 'down' }}">₹{{ number_format($p['pnl'] ?? 0, 2) }}</td></tr>@endforeach
                </tbody></table></div>
            </section>
        @endif
    @endif
    <section class="dt-card" style="padding:0;overflow:hidden">
        <h2 style="padding:18px 20px 0">Order book</h2>
        <div class="dt-table-wrap"><table class="dt-table">
            <thead><tr><th>Order</th><th>Instrument</th><th>Type</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Charges (est.)</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($orders as $o)
                <tr data-order="{{ $o->broker_order_id }}"><td><b>{{ $o->order_no }}</b><br><small class="dt-muted">{{ $o->created_at->timezone('Asia/Kolkata')->format('d M Y, H:i') }}@if($o->broker_order_id) · Zerodha {{ $o->broker_order_id }}@endif</small></td>
                    <td><a href="{{ route('trading.instrument', [$o->exchange, $o->symbol]) }}"><b>{{ $o->symbol }}</b></a> <small class="dt-muted">{{ $o->exchange }}</small></td>
                    <td><span class="{{ $o->side === 'BUY' ? 'up' : 'down' }}">{{ $o->side }}</span> · {{ $o->product }} · {{ $o->order_type }}</td>
                    <td class="num">{{ $o->filled_quantity }}/{{ $o->quantity }}</td>
                    <td class="num">{{ $o->average_price ? number_format($o->average_price, 2) : ($o->price ? number_format($o->price, 2) : 'Market') }}</td>
                    <td class="num">{{ $o->charges ? '₹' . number_format($o->charges['total'], 2) : '—' }}</td>
                    <td><span class="dt-pill {{ $o->status }}" data-order-status>{{ $o->status }}</span>@if($o->status_message)<br><small class="dt-muted">{{ \Illuminate\Support\Str::limit($o->status_message, 60) }}</small>@endif</td>
                    <td>@unless(in_array($o->status, \App\Services\Trading\StockOrderService::FINAL, true))<button class="dt-btn light sm" data-cancel="{{ $o->order_no }}">Cancel</button>@endunless</td></tr>
            @empty
                <tr><td colspan="8" class="dt-empty">No stock orders yet. <a href="{{ route('trading.stocks') }}">Explore stocks</a>.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div class="dt-pager" style="padding:14px 20px">{{ $orders->links() }}</div>
    </section>
@include('trading.partials.ticker')
</main>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-cancel]').forEach(b => b.addEventListener('click', async () => {
  b.disabled = true;
  const r = await fetch('{{ url('trading/stock-orders') }}/' + b.dataset.cancel + '/cancel', { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
  const j = await r.json().catch(() => ({}));
  if (!r.ok) { alert(j.message || 'Could not cancel'); b.disabled = false; return; }
  location.reload();
}));
document.addEventListener('kite:order', e => { const s = document.querySelector('[data-order="' + e.detail.order_id + '"] [data-order-status]'); if (s) s.textContent = String(e.detail.status).toLowerCase(); });
</script>
@endpush
