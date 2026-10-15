@extends('layouts.site')
@section('title', $scheme->name . ' — NAV, returns and invest')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => $scheme->name, 'subtitle' => $scheme->amc . ' · ' . $scheme->category . ' · ' . $scheme->plan . ' · ' . $scheme->option])
@php
    $chg = $scheme->dayChange();
    $buyable = $scheme->scheme_type === 'Open Ended' && $scheme->asset_class !== 'ETF' && $scheme->is_active;
@endphp
<main class="dt-wrap dt-main">
    <div class="dt-two">
        <div style="display:grid;gap:16px">
            <section class="dt-card">
                <div class="dt-stats">
                    <div class="dt-stat"><span>NAV · {{ $scheme->nav_date?->format('d M Y') }}</span><b>₹{{ number_format($scheme->nav, 4) }}</b>@if($chg !== null)<small class="{{ $chg >= 0 ? 'up' : 'down' }}">{{ $chg >= 0 ? '▲' : '▼' }} {{ abs($chg) }}% today</small>@endif</div>
                    @foreach(['return_1y' => '1-year return', 'return_3y' => '3-year CAGR', 'return_5y' => '5-year CAGR'] as $k => $l)
                        <div class="dt-stat"><span>{{ $l }}</span><b class="{{ ($scheme->{$k} ?? 0) >= 0 ? 'up' : 'down' }}">{{ $scheme->{$k} !== null ? number_format($scheme->{$k}, 2) . '%' : '—' }}</b></div>
                    @endforeach
                    <div class="dt-stat"><span>Asset class</span><b style="font-size:15px">{{ $scheme->asset_class ?? '—' }}</b></div>
                    <div class="dt-stat"><span>Scheme code · ISIN</span><b style="font-size:14px">{{ $scheme->scheme_code }}<br>{{ $scheme->isin_growth ?? '—' }}</b></div>
                </div>
                @if($history->count() > 2)
                    @php
                        $w = 720; $h = 200; $pad = 30;
                        $min = $history->min('nav'); $max = $history->max('nav'); $span = max(0.0001, $max - $min);
                        $pts = $history->values()->map(fn ($r, $i) => round($pad + $i * ($w - 2 * $pad) / max(1, $history->count() - 1), 1) . ',' . round($h - $pad - ($r->nav - $min) / $span * ($h - 2 * $pad), 1))->implode(' ');
                    @endphp
                    <h3>NAV history</h3>
                    <svg class="dt-chart" viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="NAV from {{ $history->first()->nav_date }} to {{ $history->last()->nav_date }}">
                        <line x1="{{ $pad }}" x2="{{ $w - $pad }}" y1="{{ $h - $pad }}" y2="{{ $h - $pad }}" stroke="#e3eaf3"/>
                        <text x="2" y="{{ $pad }}" font-size="11" fill="#5b6b82">₹{{ number_format($max, 2) }}</text><text x="2" y="{{ $h - $pad }}" font-size="11" fill="#5b6b82">₹{{ number_format($min, 2) }}</text>
                        <polyline points="{{ $pts }}" fill="none" stroke="#0b2545" stroke-width="2"/>
                        <text x="{{ $pad }}" y="{{ $h - 8 }}" font-size="11" fill="#5b6b82">{{ \Carbon\Carbon::parse($history->first()->nav_date)->format('d M Y') }}</text>
                        <text x="{{ $w - $pad }}" y="{{ $h - 8 }}" font-size="11" fill="#5b6b82" text-anchor="end">{{ \Carbon\Carbon::parse($history->last()->nav_date)->format('d M Y') }}</text>
                    </svg>
                @endif
                @if($variants->isNotEmpty())
                    <h3>Other plans of this fund</h3>
                    <div class="dt-filters">@foreach($variants as $v)<a class="dt-btn light sm" href="{{ route('trading.fund', $v) }}">{{ $v->plan }} · {{ $v->option }} · ₹{{ number_format($v->nav, 2) }}</a>@endforeach</div>
                @endif
            </section>

            @foreach(['same_amc' => 'More from ' . $scheme->amc, 'same_category' => 'Top ' . $scheme->category . ' funds from other fund houses', 'also_bought' => 'Investors who bought this also bought'] as $key => $label)
                @if($recommended[$key]->isNotEmpty())
                    <section class="dt-card">
                        <h2>{{ $label }}</h2>
                        <div class="dt-grid">
                            @foreach($recommended[$key] as $f)
                                <a class="dt-fund" href="{{ route('trading.fund', $f) }}"><strong>{{ $f->name }}</strong><small class="dt-muted">{{ $f->category }}</small>
                                    <span class="meta"><span>NAV ₹{{ number_format($f->nav, 2) }}</span><span>3Y {!! $f->return_3y !== null ? '<b class="' . ($f->return_3y >= 0 ? 'up' : 'down') . '">' . number_format($f->return_3y, 1) . '%</b>' : '—' !!}</span></span></a>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>

        <aside class="dt-card" id="invest">
            <h2>Invest</h2>
            @if(!$buyable)
                <p class="dt-muted">This scheme cannot be bought here{{ $scheme->asset_class === 'ETF' ? ' (ETFs trade on the stock exchange)' : '' }}.</p>
            @elseif(!auth()->check())
                <p class="dt-muted">Log in and complete account opening to invest.</p><a class="dt-btn gold" href="{{ route('login') }}">Log in</a>
            @elseif(!$client || !$client->isVerified())
                <p class="dt-muted">{{ $client && $client->status === 'submitted' ? 'Your account is being verified. You can invest once it is approved.' : 'Complete account opening (KYC) to invest.' }}</p>
                <a class="dt-btn gold" href="{{ route('trading.onboarding') }}">{{ $client ? 'Continue account opening' : 'Open account' }}</a>
            @else
                @if($marketClosed)<p class="dt-flash warn">{{ $marketClosed }}</p>@endif
                <div class="dt-tabs" role="tablist"><button type="button" class="on" data-side="buy">Buy</button>@if($holding && $holding->units > 0)<button type="button" data-side="sell">Redeem</button>@endif</div>
                <form id="dt-order" class="dt-form" style="grid-template-columns:1fr" onsubmit="return false">
                    <label data-for="buy">Amount (₹)<input type="number" name="amount" min="{{ max(100, (float) $scheme->min_purchase) }}" step="1" value="5000" inputmode="numeric"></label>
                    <label data-for="sell" hidden>Units (you hold {{ number_format($holding->units ?? 0, 4) }})<input type="number" name="units" min="0.0001" step="0.0001" max="{{ $holding->units ?? 0 }}"></label>
                    <label data-for="sell" hidden class="check"><input type="checkbox" name="all_units" value="1"> Redeem all units</label>
                    <label data-for="buy">Pay by<select name="payment_mode"><option value="netbanking">Net banking</option><option value="upi">UPI</option><option value="mandate">Bank mandate</option></select></label>
                    <div id="dt-fees" class="dt-muted" style="font-size:13px"></div>
                    <p class="dt-muted" style="margin:0">NAV date if placed now: <b>{{ $navDate->format('d M Y') }}</b> (cut-off applies).</p>
                    <button class="dt-btn gold" id="dt-place" type="button" @disabled($marketClosed)>Place order</button>
                </form>
                @if($holding && $holding->units > 0)<p class="dt-muted" style="margin-top:12px">You hold <b>{{ number_format($holding->units, 4) }}</b> units · invested ₹{{ number_format($holding->invested, 2) }} · value ₹{{ number_format($holding->units * $scheme->nav, 2) }}</p>@endif
            @endif
        </aside>
    </div>
</main>

<div class="dt-modal" id="dt-otp" hidden>
    <div>
        <h2 style="margin:0 0 8px;font:800 18px Inter,system-ui,sans-serif;color:#0b2545">Confirm order</h2>
        <p class="dt-muted" id="dt-otp-msg">Enter the 6-digit OTP sent to your email.</p>
        <input id="dt-otp-code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" style="width:100%;font-size:22px;letter-spacing:6px;text-align:center;padding:10px;border:1px solid #cfd9e6;border-radius:10px">
        <p class="dt-flash bad" id="dt-otp-err" hidden style="margin-top:10px"></p>
        <div style="display:flex;gap:8px;margin-top:12px"><button class="dt-btn" id="dt-otp-ok" type="button">Verify and confirm</button><button class="dt-btn light" id="dt-otp-cancel" type="button">Cancel order</button></div>
    </div>
</div>
@endsection
@push('scripts')
<script>
(() => {
  const form = document.getElementById('dt-order');
  if (!form) return;
  const csrf = '{{ csrf_token() }}';
  const api = (url, method = 'GET', body = null, extra = {}) => fetch(url, { method, credentials: 'same-origin',
    headers: Object.assign({ 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, extra), body: body ? JSON.stringify(body) : null })
    .then(async r => { const j = await r.json().catch(() => ({})); if (!r.ok) throw new Error(j.message || Object.values(j.errors || {})[0]?.[0] || 'Request failed'); return j; });
  let side = 'buy', order = null, key = null;
  const fees = document.getElementById('dt-fees');
  const money = v => '₹' + Number(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const preview = () => {
    const amount = side === 'buy' ? Number(form.amount.value) : Number(form.units.value || 0) * {{ (float) $scheme->nav }};
    if (!amount) { fees.textContent = ''; return; }
    api('{{ route('trading.api.fees') }}?side=' + side + '&amount=' + amount).then(j => {
      const f = j.data;
      fees.innerHTML = (side === 'buy' ? 'Stamp duty ' + money(f.stamp_duty) + (f.transaction_charge ? ' · Txn charge ' + money(f.transaction_charge) : '') + (f.platform_fee ? ' · Platform fee ' + money(f.platform_fee) : '') + (f.gst ? ' · GST ' + money(f.gst) : '') + '<br>Invested amount <b>' + money(f.net_amount) + '</b>' : 'Estimated value <b>' + money(f.net_amount) + '</b> (final value at the applicable NAV)');
    }).catch(() => {});
  };
  let t; form.addEventListener('input', () => { clearTimeout(t); t = setTimeout(preview, 300); }); preview();
  document.querySelectorAll('[data-side]').forEach(b => b.addEventListener('click', () => {
    side = b.dataset.side; document.querySelectorAll('[data-side]').forEach(x => x.classList.toggle('on', x === b));
    form.querySelectorAll('[data-for]').forEach(l => l.hidden = l.dataset.for !== side); key = null; preview();
  }));
  const modal = document.getElementById('dt-otp'), err = document.getElementById('dt-otp-err');
  document.getElementById('dt-place').addEventListener('click', e => {
    const btn = e.currentTarget; btn.disabled = true;
    key = key || (crypto.randomUUID ? crypto.randomUUID().replace(/-/g, '') : String(Date.now()) + Math.random().toString(36).slice(2));
    const body = { scheme_code: {{ $scheme->scheme_code }}, side, payment_mode: form.payment_mode.value };
    if (side === 'buy') body.amount = Number(form.amount.value); else { body.units = Number(form.units.value || 0); body.all_units = form.all_units.checked; }
    api('{{ route('trading.api.orders.place') }}', 'POST', body, { 'Idempotency-Key': key }).then(j => {
      order = j.data; document.getElementById('dt-otp-msg').textContent = j.message + ' Order ' + order.order_no + '.'; err.hidden = true; modal.hidden = false; document.getElementById('dt-otp-code').focus();
    }).catch(x => alert(x.message)).finally(() => btn.disabled = false);
  });
  document.getElementById('dt-otp-ok').addEventListener('click', () => {
    api('{{ url('trading/api/orders') }}/' + order.order_no + '/verify', 'POST', { otp: document.getElementById('dt-otp-code').value.trim() })
      .then(j => { modal.hidden = true; key = null; location.href = '{{ route('trading.orders') }}?placed=' + encodeURIComponent(order.order_no); })
      .catch(x => { err.textContent = x.message; err.hidden = false; });
  });
  document.getElementById('dt-otp-cancel').addEventListener('click', () => {
    api('{{ url('trading/api/orders') }}/' + order.order_no + '/cancel', 'POST').finally(() => { modal.hidden = true; key = null; });
  });
})();
</script>
@endpush
