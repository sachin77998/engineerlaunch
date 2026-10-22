@extends('layouts.site')
@section('title', $scheme->displayName() . ' — NAV, returns, invest | Do Trading')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@php
    $chg = $scheme->dayChange();
    $buyable = $scheme->scheme_type === 'Open Ended' && $scheme->asset_class !== 'ETF' && $scheme->is_active;
    $house = $scheme->fundHouse;
@endphp
@include('trading.partials.hero', ['title' => $scheme->displayName(), 'subtitle' => ($house->short_name ?? $scheme->amc) . ' Mutual Fund · ' . $scheme->asset_class . ' ' . $scheme->sub_category])
<main class="dt-wrap dt-main">
    <div class="dt-two">
        <div style="display:grid;gap:18px;min-width:0">
            <section class="dt-card">
                <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
                    @include('trading.partials.logo', ['name' => $scheme->amc, 'color' => $house?->color, 'size' => 'lg', 'amc' => true])
                    <div style="flex:1;min-width:220px">
                        <div class="dt-chips">
                            <a class="dt-chip" href="{{ $scheme->categoryRow ? route('trading.category', $scheme->categoryRow->slug) : route('trading.funds') }}">{{ $scheme->asset_class }} · {{ $scheme->sub_category }}</a>
                            <span class="dt-chip">{{ $scheme->risk_level }} risk</span>
                            <span class="dt-chip">@include('trading.partials.stars', ['rating' => $scheme->rating])</span>
                            @if($house)<a class="dt-chip" href="{{ route('trading.amc', $house) }}">{{ $house->short_name }}</a>@endif
                        </div>
                    </div>
                    <div style="text-align:right">
                        <div class="dt-ltp">₹{{ number_format($scheme->nav, 4) }}</div>
                        <div class="dt-muted">NAV · {{ $scheme->nav_date?->format('d M Y') }} @if($chg !== null)<span class="{{ $chg >= 0 ? 'up' : 'down' }}">{{ $chg >= 0 ? '+' : '' }}{{ $chg }}% (1D)</span>@endif</div>
                    </div>
                </div>
            </section>

            @include('trading.partials.fund-insights')

            <section class="dt-card">
                <h2>Fund details</h2>
                <div class="dt-kv">
                    @foreach(['Fund house' => ($house->short_name ?? $scheme->amc) . ' Mutual Fund', 'Category' => $scheme->category, 'Plan · Option' => $scheme->plan . ' · ' . $scheme->option,
                              'Risk (riskometer)' => $scheme->risk_level, 'Scheme type' => $scheme->scheme_type, 'Scheme code' => $scheme->scheme_code, 'ISIN' => $scheme->isin_growth ?? '—',
                              'Minimum investment' => '₹' . number_format(max(100, (float) $scheme->min_purchase)), 'NAV date' => $scheme->nav_date?->format('d M Y')] as $k => $v)
                        <div><span>{{ $k }}</span><b>{{ $v }}</b></div>
                    @endforeach
                </div>
                @if($variants->isNotEmpty())
                    <h3>Other plans of this fund</h3>
                    <div class="dt-chips">@foreach($variants as $v)<a class="dt-chip" href="{{ route('trading.fund', $v) }}">{{ $v->plan }} · {{ $v->option }} · ₹{{ number_format($v->nav, 2) }}</a>@endforeach</div>
                @endif
            </section>

            @foreach(['same_amc' => 'More from ' . ($house->short_name ?? $scheme->amc), 'same_category' => 'Similar ' . $scheme->sub_category . ' funds', 'also_bought' => 'Investors also bought'] as $key => $label)
                @if($recommended[$key]->isNotEmpty())
                    <section class="dt-card">
                        <h2>{{ $label }}</h2>
                        <div class="dt-cards">
                            @foreach($recommended[$key] as $f)
                                <a class="dt-fcard" href="{{ route('trading.fund', $f) }}">
                                    <span style="display:flex;gap:10px;align-items:center">@include('trading.partials.logo', ['name' => $f->amc, 'amc' => true])<small class="dt-muted">{{ $f->sub_category ?? $f->category }}</small></span>
                                    <b>{{ $f->prettyName() }}</b>
                                    <span class="row"><span class="ret {{ ($f->return_3y ?? 0) >= 0 ? 'up' : 'down' }}">{{ $f->return_3y !== null ? number_format($f->return_3y, 2) . '%' : '—' }}</span><span class="dt-muted">3Y</span></span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        </div>

        <aside class="dt-card dt-sticky" id="invest">
            <h2>Invest</h2>
            @if(!$buyable)
                <p class="dt-muted">This scheme cannot be bought here{{ $scheme->asset_class === 'ETF' ? ' (ETFs trade on the stock exchange; see ETFs)' : '' }}.</p>
            @elseif(!auth()->check())
                <p class="dt-muted">Log in and complete account opening to invest.</p><a class="dt-btn gold block" href="{{ route('login') }}">Log in to invest</a>
            @elseif(!$client || !$client->isVerified())
                <p class="dt-muted">{{ $client && $client->status === 'submitted' ? 'Your account is being verified. You can invest once it is approved.' : 'Complete account opening (KYC) to invest.' }}</p>
                <a class="dt-btn gold block" href="{{ route('trading.onboarding') }}">{{ $client ? 'Continue account opening' : 'Open account' }}</a>
            @else
                @if($marketClosed)<p class="dt-flash warn">{{ $marketClosed }}</p>@endif
                <div class="dt-seg" role="tablist" style="margin-bottom:14px"><button type="button" class="on" data-side="buy">Buy</button>@if($holding && $holding->units > 0)<button type="button" data-side="sell">Redeem</button>@endif</div>
                <form id="dt-order" class="dt-form" style="grid-template-columns:1fr" onsubmit="return false">
                    <label data-for="buy">Amount (₹)<input type="number" name="amount" min="{{ max(100, (float) $scheme->min_purchase) }}" step="1" value="5000" inputmode="numeric"></label>
                    <div data-for="buy" class="dt-chips">@foreach([1000, 5000, 10000, 25000] as $a)<button type="button" class="dt-chip" data-amount="{{ $a }}">+₹{{ number_format($a) }}</button>@endforeach</div>
                    <label data-for="sell" hidden>Units (you hold {{ number_format($holding->units ?? 0, 4) }})<input type="number" name="units" min="0.0001" step="0.0001" max="{{ $holding->units ?? 0 }}"></label>
                    <label data-for="sell" hidden class="check"><input type="checkbox" name="all_units" value="1"> Redeem all units</label>
                    <label data-for="buy">Pay by<select name="payment_mode"><option value="upi">UPI</option><option value="netbanking">Net banking</option><option value="mandate">Bank mandate</option></select></label>
                    <div id="dt-fees" class="dt-muted" style="font-size:13px;line-height:1.6"></div>
                    <p class="dt-muted" style="margin:0">NAV applicable: <b>{{ $navDate->format('d M Y') }}</b></p>
                    <button class="dt-btn green block" id="dt-place" type="button" @disabled($marketClosed)>Invest now</button>
                </form>
                @if($holding && $holding->units > 0)<p class="dt-muted" style="margin-top:14px">You hold <b>{{ number_format($holding->units, 4) }}</b> units · invested ₹{{ number_format($holding->invested, 2) }} · current ₹{{ number_format($holding->units * $scheme->nav, 2) }}</p>@endif
            @endif
        </aside>
    </div>
</main>

<div class="dt-modal" id="dt-otp" hidden>
    <div>
        <h2 style="margin:0 0 8px;font:700 18px Inter,system-ui,sans-serif">Confirm with OTP</h2>
        <p class="dt-muted" id="dt-otp-msg">Enter the 6-digit OTP sent to your email.</p>
        <input id="dt-otp-code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="dt-input" style="width:100%;font-size:24px;letter-spacing:8px;text-align:center">
        <p class="dt-flash bad" id="dt-otp-err" hidden style="margin-top:10px"></p>
        <div style="display:flex;gap:8px;margin-top:14px"><button class="dt-btn green" id="dt-otp-ok" type="button">Verify &amp; confirm</button><button class="dt-btn light" id="dt-otp-cancel" type="button">Cancel order</button></div>
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
      fees.innerHTML = side === 'buy' ? 'Stamp duty ' + money(f.stamp_duty) + (f.transaction_charge ? ' · Txn charge ' + money(f.transaction_charge) : '') + (f.platform_fee ? ' · Platform fee ' + money(f.platform_fee) : '') + (f.gst ? ' · GST ' + money(f.gst) : '') + '<br>Amount invested <b>' + money(f.net_amount) + '</b>' : 'Estimated value <b>' + money(f.net_amount) + '</b> (final at the applicable NAV)';
    }).catch(() => {});
  };
  let t; form.addEventListener('input', () => { clearTimeout(t); t = setTimeout(preview, 300); }); preview();
  form.querySelectorAll('[data-amount]').forEach(b => b.addEventListener('click', () => { form.amount.value = Number(form.amount.value || 0) + Number(b.dataset.amount); preview(); }));
  document.querySelectorAll('[data-side]').forEach(b => b.addEventListener('click', () => {
    side = b.dataset.side; document.querySelectorAll('[data-side]').forEach(x => x.classList.toggle('on', x === b));
    form.querySelectorAll('[data-for]').forEach(l => l.hidden = l.dataset.for !== side); key = null; preview();
    document.getElementById('dt-place').textContent = side === 'buy' ? 'Invest now' : 'Redeem';
  }));
  const modal = document.getElementById('dt-otp'), err = document.getElementById('dt-otp-err');
  document.getElementById('dt-place').addEventListener('click', e => {
    const btn = e.currentTarget; btn.disabled = true;
    key = key || (crypto.randomUUID ? crypto.randomUUID().replace(/-/g, '') : String(Date.now()) + Math.random().toString(36).slice(2));
    const body = { scheme_code: {{ $scheme->scheme_code }}, side, payment_mode: form.payment_mode.value };
    if (side === 'buy') body.amount = Number(form.amount.value); else { body.units = Number(form.units.value || 0); body.all_units = form.all_units.checked; }
    api('{{ route('trading.api.orders.place') }}', 'POST', body, { 'Idempotency-Key': key }).then(j => {
      order = j.data; document.getElementById('dt-otp-msg').textContent = j.message + ' Order ' + order.order_no + '.'; err.hidden = true; modal.hidden = false; document.getElementById('dt-otp-code').focus();
    }).catch(x => { fees.innerHTML = '<span class="down">' + x.message + '</span>'; }).finally(() => btn.disabled = false);
  });
  document.getElementById('dt-otp-ok').addEventListener('click', () => {
    api('{{ url('trading/api/orders') }}/' + order.order_no + '/verify', 'POST', { otp: document.getElementById('dt-otp-code').value.trim() })
      .then(() => { modal.hidden = true; key = null; location.href = '{{ route('trading.orders') }}?placed=' + encodeURIComponent(order.order_no); })
      .catch(x => { err.textContent = x.message; err.hidden = false; });
  });
  document.getElementById('dt-otp-cancel').addEventListener('click', () => {
    api('{{ url('trading/api/orders') }}/' + order.order_no + '/cancel', 'POST').finally(() => { modal.hidden = true; key = null; });
  });
})();
</script>
@endpush
