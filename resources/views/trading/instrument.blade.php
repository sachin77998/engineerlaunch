@extends('layouts.site')
@section('title', $instrument->symbol . ' — ' . $instrument->name . ' share price | Do Trading')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => $instrument->name ?: $instrument->symbol, 'subtitle' => $instrument->exchange . ': ' . $instrument->symbol . ($instrument->instrument_type === 'ETF' ? ' · ETF tracking ' . $instrument->underlying : '') . ($instrument->isin ? ' · ISIN ' . $instrument->isin : '')])
<main class="dt-wrap dt-main">
    <div class="dt-two">
        <div style="display:grid;gap:18px;min-width:0">
            <section class="dt-card" data-token="{{ $instrument->kite_token }}">
                <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
                    @include('trading.partials.logo', ['name' => $instrument->name ?: $instrument->symbol, 'size' => 'lg'])
                    <div style="flex:1">
                        <div class="dt-ltp">₹<span data-ltp>{{ $instrument->last_price !== null ? number_format($instrument->last_price, 2) : '—' }}</span></div>
                        <div>
                            <span data-chg-abs class="{{ ($instrument->change_pct ?? 0) >= 0 ? 'up' : 'down' }}">{{ $instrument->last_price && $instrument->prev_close ? (($instrument->last_price - $instrument->prev_close >= 0 ? '+' : '') . number_format($instrument->last_price - $instrument->prev_close, 2)) : '' }}</span>
                            <span data-chg class="{{ ($instrument->change_pct ?? 0) >= 0 ? 'up' : 'down' }}">({{ $instrument->change_pct !== null ? (($instrument->change_pct >= 0 ? '+' : '') . number_format($instrument->change_pct, 2) . '%') : '—' }})</span>
                            <span class="dt-muted">@if($connected)<b data-ws-status>Connecting…</b>@else close {{ $instrument->price_date?->format('d M Y') }}@endif</span>
                        </div>
                    </div>
                    <span class="dt-pill {{ $status['open'] ? 'complete' : 'pending' }}">{{ $status['open'] ? 'Market open' : 'Market closed' }}</span>
                </div>
                <div class="dt-kv" style="margin-top:12px">
                    <div><span>Open</span><b data-open>{{ $instrument->open ? number_format($instrument->open, 2) : '—' }}</b></div>
                    <div><span>High</span><b data-high>{{ $instrument->high ? number_format($instrument->high, 2) : '—' }}</b></div>
                    <div><span>Low</span><b data-low>{{ $instrument->low ? number_format($instrument->low, 2) : '—' }}</b></div>
                    <div><span>Previous close</span><b data-close>{{ $instrument->prev_close ? number_format($instrument->prev_close, 2) : '—' }}</b></div>
                    <div><span>Volume</span><b data-vol>{{ $instrument->volume ? number_format($instrument->volume) : '—' }}</b></div>
                    <div><span>Turnover</span><b>{{ $instrument->turnover_lakhs ? '₹' . \App\Services\Insights\CompanyInsights::indian($instrument->turnover_lakhs / 100, 2) . ' Cr' : '—' }}</b></div>
                </div>
            </section>
            <section class="dt-card">
                <h2>About</h2>
                <div class="dt-kv">
                    @foreach(['Exchange' => $instrument->exchange, 'Symbol' => $instrument->symbol, 'Type' => $instrument->instrument_type === 'ETF' ? 'Exchange traded fund' : 'Equity share',
                              'Series' => $instrument->series ?? '—', 'ISIN' => $instrument->isin ?? '—', 'Listed on' => $instrument->listed_on?->format('d M Y') ?? '—',
                              'Face value' => $instrument->face_value ? '₹' . rtrim(rtrim(number_format($instrument->face_value, 2), '0'), '.') : '—', 'Lot size' => $instrument->lot_size,
                              'Tick size' => '₹' . $instrument->tick_size, 'Intraday margin (VaR + ELM)' => $instrument->margin_pct ? number_format($instrument->margin_pct, 2) . '% (' . number_format(100 / max(20, $instrument->margin_pct), 1) . '× leverage)' : '—',
                              'Underlying' => $instrument->underlying ?? '—'] as $k => $v)
                        <div><span>{{ $k }}</span><b>{{ $v }}</b></div>
                    @endforeach
                </div>
                @if($instrument->company)<p style="margin-top:14px"><a class="dt-btn light sm" href="{{ route('insights.show', $instrument->company) }}">Company financials &amp; ratios →</a> <a class="dt-btn light sm" href="{{ route('companies.show', $instrument->company) }}">Jobs at {{ \Illuminate\Support\Str::limit($instrument->company->name, 30) }} →</a></p>@endif
            </section>
            @if($myOrders->isNotEmpty())
                <section class="dt-card">
                    <h2>Your orders</h2>
                    <div class="dt-table-wrap"><table class="dt-table"><thead><tr><th>Order</th><th>Side</th><th class="num">Qty</th><th class="num">Price</th><th>Status</th></tr></thead><tbody>
                        @foreach($myOrders as $o)<tr data-order="{{ $o->broker_order_id }}"><td>{{ $o->order_no }}<br><small class="dt-muted">{{ $o->created_at->timezone('Asia/Kolkata')->format('d M H:i') }}</small></td><td>{{ $o->side }} · {{ $o->product }}</td><td class="num">{{ $o->filled_quantity }}/{{ $o->quantity }}</td><td class="num">{{ $o->average_price ? number_format($o->average_price, 2) : ($o->price ? number_format($o->price, 2) : 'MKT') }}</td><td><span class="dt-pill {{ $o->status }}" data-order-status>{{ $o->status }}</span></td></tr>@endforeach
                    </tbody></table></div>
                </section>
            @endif
        </div>

        <aside class="dt-card dt-sticky">
            <div class="dt-seg" id="dt-side" style="margin-bottom:14px;width:100%"><button type="button" class="on" data-side="BUY" style="flex:1">Buy</button><button type="button" data-side="SELL" style="flex:1">Sell</button></div>
            @if(!auth()->check())
                <p class="dt-muted">Log in and connect your Zerodha account to trade.</p><a class="dt-btn gold block" href="{{ route('login') }}">Log in</a>
            @elseif(!$kiteReady)
                <p class="dt-muted">Stock trading is being switched on. Prices update every evening.</p>
            @elseif(!$connected)
                <p class="dt-muted">Trades are placed in your own Zerodha account. Connect once a day (Zerodha sessions end at 6 AM).</p><a class="dt-btn gold block" href="{{ route('trading.broker.connect') }}">Connect Zerodha</a>
            @else
                <form id="dt-stock-order" class="dt-form" style="grid-template-columns:1fr" onsubmit="return false">
                    <div class="dt-seg" style="width:100%"><button type="button" class="on" data-product="CNC" style="flex:1">Delivery</button><button type="button" data-product="MIS" style="flex:1">Intraday</button></div>
                    <label>Order type<select name="order_type"><option value="MARKET">Market</option><option value="LIMIT">Limit</option><option value="SL">Stop-loss limit</option><option value="SL-M">Stop-loss market</option></select></label>
                    <label>Quantity<input type="number" name="quantity" min="{{ $instrument->lot_size }}" step="{{ $instrument->lot_size }}" value="{{ $instrument->lot_size }}"></label>
                    <label data-price hidden>Price (₹)<input type="number" name="price" step="{{ $instrument->tick_size }}" value="{{ $instrument->last_price }}"></label>
                    <label data-trigger hidden>Trigger price (₹)<input type="number" name="trigger_price" step="{{ $instrument->tick_size }}"></label>
                    <div id="dt-charges" class="dt-muted" style="font-size:13px;line-height:1.6"></div>
                    @unless($status['open'])<p class="dt-flash warn" style="font-size:13px">{{ $status['message'] }}</p>@endunless
                    <button class="dt-btn green block" id="dt-stock-place" type="button" @disabled($status['session'] === 'closed')>Buy {{ $instrument->symbol }}</button>
                    <p class="dt-muted" id="dt-stock-msg" style="margin:0"></p>
                </form>
                <form method="post" action="{{ route('trading.broker.disconnect') }}" style="margin-top:12px">@csrf<button class="dt-btn light sm" type="submit">Disconnect Zerodha ({{ $connected->broker_user_id }})</button></form>
            @endif
        </aside>
    </div>
@include('trading.partials.ticker')
</main>
@endsection
@push('scripts')
<script>
(() => {
  const form = document.getElementById('dt-stock-order');
  if (!form) return;
  let side = 'BUY', product = 'CNC', key = null, ltp = {{ (float) ($instrument->last_price ?? 0) }};
  const btn = document.getElementById('dt-stock-place'), msg = document.getElementById('dt-stock-msg'), charges = document.getElementById('dt-charges');
  const inr = v => '₹' + Number(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const sync = () => {
    const t = form.order_type.value;
    form.querySelector('[data-price]').hidden = !['LIMIT', 'SL'].includes(t);
    form.querySelector('[data-trigger]').hidden = !['SL', 'SL-M'].includes(t);
    btn.textContent = (side === 'BUY' ? 'Buy ' : 'Sell ') + '{{ $instrument->symbol }}';
    btn.className = 'dt-btn block ' + (side === 'BUY' ? 'green' : 'red');
    const price = ['LIMIT', 'SL'].includes(t) ? Number(form.price.value) : ltp, qty = Number(form.quantity.value);
    if (!price || !qty) { charges.textContent = ''; return; }
    fetch('{{ route('trading.markets.charges') }}?' + new URLSearchParams({ side, product, quantity: qty, price }), { headers: { Accept: 'application/json' } }).then(r => r.json()).then(j => {
      const c = j.data; if (!c) return;
      charges.innerHTML = 'Order value <b>' + inr(c.value) + '</b> · Charges ' + inr(c.total) + ' <span title="Brokerage ' + inr(c.brokerage) + ', STT ' + inr(c.stt) + ', exchange ' + inr(c.exchange) + ', SEBI ' + inr(c.sebi) + ', stamp ' + inr(c.stamp_duty) + ', GST ' + inr(c.gst) + '">ⓘ</span><br>' + (side === 'BUY' ? 'Total payable ' : 'You receive ') + '<b>' + inr(c.net) + '</b>';
    });
  };
  document.querySelectorAll('[data-side]').forEach(b => b.addEventListener('click', () => { side = b.dataset.side; key = null; document.querySelectorAll('[data-side]').forEach(x => x.classList.toggle('on', x === b)); document.getElementById('dt-side').className = 'dt-seg ' + (side === 'BUY' ? 'buy' : 'sell'); sync(); }));
  document.querySelectorAll('[data-product]').forEach(b => b.addEventListener('click', () => { product = b.dataset.product; key = null; document.querySelectorAll('[data-product]').forEach(x => x.classList.toggle('on', x === b)); sync(); }));
  let t; form.addEventListener('input', () => { key = null; clearTimeout(t); t = setTimeout(sync, 250); }); sync();
  document.addEventListener('kite:tick', e => { if (e.detail.token === {{ (int) $instrument->kite_token }}) ltp = e.detail.ltp; });
  document.addEventListener('kite:order', e => {
    const row = document.querySelector('[data-order="' + e.detail.order_id + '"] [data-order-status]');
    if (row) { row.textContent = String(e.detail.status).toLowerCase(); }
    msg.textContent = 'Zerodha: order ' + e.detail.order_id + ' ' + String(e.detail.status).toLowerCase() + (e.detail.status_message ? ' — ' + e.detail.status_message : '');
  });
  btn.addEventListener('click', async () => {
    btn.disabled = true; msg.textContent = 'Sending to Zerodha…';
    key = key || (crypto.randomUUID ? crypto.randomUUID().replace(/-/g, '') : String(Date.now()));
    const body = { side, product, order_type: form.order_type.value, quantity: Number(form.quantity.value) };
    if (['LIMIT', 'SL'].includes(body.order_type)) body.price = Number(form.price.value);
    if (['SL', 'SL-M'].includes(body.order_type)) body.trigger_price = Number(form.trigger_price.value);
    try {
      const r = await fetch('{{ route('trading.stock-orders.place', [$instrument->exchange, $instrument->symbol]) }}', { method: 'POST', credentials: 'same-origin',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Idempotency-Key': key }, body: JSON.stringify(body) });
      const j = await r.json();
      msg.innerHTML = r.ok ? '<span class="up">' + j.message + ' (' + j.data.order_no + ')</span>' : '<span class="down">' + (j.message || Object.values(j.errors || {})[0]?.[0] || 'Order failed') + '</span>';
      if (r.ok) { key = null; setTimeout(() => location.reload(), 1500); }
    } catch (e) { msg.textContent = 'Network error. Check your orders before retrying.'; }
    btn.disabled = false;
  });
})();
</script>
@endpush
