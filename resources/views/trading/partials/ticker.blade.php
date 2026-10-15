{{-- Live prices over Zerodha's Kite WebSocket. Elements carry data-token (instrument token); price cells use
     data-ltp, data-chg, data-chg-abs, data-open/high/low/vol/close. Order updates arrive as 'kite:order' events. --}}
@if(auth()->check() && app(\App\Services\Trading\KiteService::class)->connection(auth()->user()))
@push('scripts')
<script>
(() => {
  const tokens = [...new Set([...document.querySelectorAll('[data-token]')].map(e => Number(e.dataset.token)).filter(Boolean))];
  if (!tokens.length || !window.WebSocket) return;
  const status = document.querySelectorAll('[data-ws-status]');
  const setStatus = (text, live) => status.forEach(s => { s.textContent = text; s.classList.toggle('up', live); });
  const fmt = v => Number(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const fmtInt = v => Number(v).toLocaleString('en-IN');
  let ws, retry = 1000;

  const parse = buf => {
    const dv = new DataView(buf), out = [];
    if (buf.byteLength < 2) return out;           // 1-byte heartbeat
    const count = dv.getInt16(0); let off = 2;
    for (let i = 0; i < count; i++) {
      const len = dv.getInt16(off); off += 2;
      const p = new DataView(buf, off, len); off += len;
      const token = p.getInt32(0), segment = token & 0xff, div = segment === 3 ? 10000000 : 100;   // CDS uses a different divisor
      const t = { token, ltp: p.getInt32(4) / div };
      if (len === 28 || len === 32) {               // index quote/full
        Object.assign(t, { high: p.getInt32(8) / div, low: p.getInt32(12) / div, open: p.getInt32(16) / div, close: p.getInt32(20) / div });
      } else if (len >= 44) {                        // quote (44) / full (184)
        Object.assign(t, { avg: p.getInt32(12) / div, volume: p.getInt32(16), buyQty: p.getInt32(20), sellQty: p.getInt32(24),
          open: p.getInt32(28) / div, high: p.getInt32(32) / div, low: p.getInt32(36) / div, close: p.getInt32(40) / div });
      }
      out.push(t);
    }
    return out;
  };

  const apply = t => {
    const set = (attr, v) => document.querySelectorAll('[data-token="' + t.token + '"] [' + attr + '], [' + attr + '][data-token="' + t.token + '"]').forEach(el => {
      const txt = typeof v === 'function' ? v(el) : v; if (el.textContent !== txt) { el.textContent = txt; el.classList.remove('dt-flash-tick'); void el.offsetWidth; el.classList.add('dt-flash-tick'); }
    });
    set('data-ltp', fmt(t.ltp));
    if (t.close) {
      const chg = (t.ltp - t.close) / t.close * 100, abs = t.ltp - t.close;
      document.querySelectorAll('[data-token="' + t.token + '"] [data-chg], [data-chg][data-token="' + t.token + '"]').forEach(el => { el.textContent = (chg >= 0 ? '+' : '') + chg.toFixed(2) + '%'; el.className = chg >= 0 ? 'up' : 'down'; });
      document.querySelectorAll('[data-token="' + t.token + '"] [data-chg-abs], [data-chg-abs][data-token="' + t.token + '"]').forEach(el => { el.textContent = (abs >= 0 ? '+' : '') + fmt(abs); el.className = abs >= 0 ? 'up' : 'down'; });
      set('data-close', fmt(t.close));
    }
    if (t.open) { set('data-open', fmt(t.open)); set('data-high', fmt(t.high)); set('data-low', fmt(t.low)); }
    if (t.volume !== undefined) set('data-vol', fmtInt(t.volume));
    document.dispatchEvent(new CustomEvent('kite:tick', { detail: t }));
  };

  const connect = async () => {
    try {
      const r = await fetch('{{ route('trading.broker.ticker') }}', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
      if (!r.ok) { setStatus('Live prices unavailable', false); return; }
      ws = new WebSocket((await r.json()).data.url);
      ws.binaryType = 'arraybuffer';
      ws.onopen = () => {
        retry = 1000; setStatus('Live', true);
        for (let i = 0; i < tokens.length; i += 500) {
          const chunk = tokens.slice(i, i + 500);
          ws.send(JSON.stringify({ a: 'subscribe', v: chunk }));
          ws.send(JSON.stringify({ a: 'mode', v: ['quote', chunk] }));
        }
      };
      ws.onmessage = e => {
        if (typeof e.data === 'string') {
          try { const m = JSON.parse(e.data); if (m.type === 'order') document.dispatchEvent(new CustomEvent('kite:order', { detail: m.data })); if (m.type === 'error') setStatus(m.data, false); } catch (_) {}
          return;
        }
        parse(e.data).forEach(apply);
      };
      ws.onclose = () => { setStatus('Reconnecting…', false); setTimeout(connect, retry = Math.min(retry * 2, 30000)); };
      ws.onerror = () => ws.close();
    } catch (_) { setTimeout(connect, retry = Math.min(retry * 2, 30000)); }
  };
  connect();
  window.addEventListener('beforeunload', () => { if (ws) { ws.onclose = null; ws.close(); } });
})();
</script>
@endpush
@endif
