@php
    $kiteLive = auth()->check() ? app(\App\Services\Trading\KiteService::class)->connection(auth()->user()) : null;
    $tabs = [
        ['trading.index', 'Mutual Funds', ['trading.index', 'trading.funds', 'trading.collection', 'trading.amc', 'trading.category', 'trading.fund']],
        ['trading.stocks', 'Stocks', ['trading.stocks']],
        ['trading.etfs', 'ETFs', ['trading.etfs']],
        ['trading.calculators', 'Calculators', ['trading.calculators']],
        ['trading.loans', 'Loans', ['trading.loans', 'trading.loans.*']],
        ['trading.orders', 'Orders & holdings', ['trading.orders', 'trading.stock-orders']],
        ['trading.onboarding', 'Account', ['trading.onboarding']],
    ];
    if (auth()->user()?->isOwner()) $tabs[] = ['trading.admin.dashboard', 'Back office', ['trading.admin.*']];
@endphp
<div class="dt-bar">
    <div class="dt-wrap dt-bar-in">
        <nav class="dt-tabs-main" aria-label="Do Trading">
            @foreach($tabs as [$route, $label, $active])<a href="{{ route($route) }}" class="{{ request()->routeIs(...$active) ? 'on' : '' }}">{{ $label }}</a>@endforeach
        </nav>
        <div class="dt-search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" id="dt-global-search" placeholder="Search stocks, ETFs, mutual funds" autocomplete="off" aria-label="Search stocks, ETFs and mutual funds">
            <div class="dt-search-results" id="dt-global-results" hidden></div>
        </div>
        <a class="dt-broker" href="{{ route('trading.stock-orders') }}" title="{{ $kiteLive ? 'Zerodha connected' : 'Connect Zerodha to trade stocks' }}"><span class="dt-dot {{ $kiteLive ? 'live' : '' }}"></span>{{ $kiteLive ? 'Zerodha ' . $kiteLive->broker_user_id : 'Zerodha' }}</a>
    </div>
</div>
<section class="dt-head" style="box-shadow:0 0 0 100vmax #f7f8fa;clip-path:inset(0 -100vmax);background:#f7f8fa">
    <div class="dt-wrap">
        <h1>{{ $title }}</h1>
        @isset($subtitle)<p>{{ $subtitle }}</p>@endisset
        @if(session('success'))<p class="dt-flash ok" style="margin-top:12px">{{ session('success') }}</p>@endif
        @if($errors->any())<p class="dt-flash bad" style="margin-top:12px">{{ $errors->first() }}</p>@endif
    </div>
</section>
@once
@push('scripts')
<script>
(() => {
  const box = document.getElementById('dt-global-search'), out = document.getElementById('dt-global-results');
  if (!box) return;
  let t, ctl;
  const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  box.addEventListener('input', () => {
    clearTimeout(t);
    const q = box.value.trim();
    if (q.length < 2) { out.hidden = true; return; }
    t = setTimeout(async () => {
      ctl?.abort(); ctl = new AbortController();
      try {
        const [s, f] = await Promise.all([
          fetch('{{ route('trading.markets.search') }}?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' }, signal: ctl.signal }).then(r => r.json()),
          fetch('{{ route('trading.api.funds') }}?limit=6&q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' }, signal: ctl.signal }).then(r => r.json()),
        ]);
        const rows = (s.data || []).slice(0, 7).map(i => '<a href="{{ url('trading/markets') }}/' + i.exchange + '/' + encodeURIComponent(i.symbol) + '"><span><b>' + esc(i.symbol) + '</b> · ' + esc(i.name) + '</span><span class="dt-muted">' + i.exchange + ' ' + (i.instrument_type === 'ETF' ? 'ETF' : '') + '</span></a>')
          .concat((f.data || []).map(m => '<a href="{{ url('trading/funds') }}/' + m.scheme_code + '"><span>' + esc(m.name) + '</span><span class="dt-muted">Mutual fund</span></a>'));
        out.innerHTML = rows.length ? rows.join('') : '<a href="#" onclick="return false"><span class="dt-muted">No matches</span></a>';
        out.hidden = false;
      } catch (e) {}
    }, 220);
  });
  document.addEventListener('click', e => { if (!e.target.closest('.dt-search')) out.hidden = true; });
})();
</script>
@endpush
@endonce
