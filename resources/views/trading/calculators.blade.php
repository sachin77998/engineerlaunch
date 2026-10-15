@extends('layouts.site')
@section('title', 'Calculators — mutual fund returns, SIP, EMI, loan amortization and margin')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'Calculators', 'subtitle' => 'Mutual fund returns (lumpsum and SIP), EMI, loan amortization schedule and equity margin.'])
<main class="dt-wrap dt-main">
    <section class="dt-card">
        <div class="dt-tabs" role="tablist">
            @foreach(['lumpsum' => 'Lumpsum return', 'sip' => 'SIP return', 'emi' => 'EMI', 'amortization' => 'Loan amortization', 'margin' => 'Margin'] as $k => $l)
                <button type="button" data-calc="{{ $k }}" class="{{ $loop->first ? 'on' : '' }}">{{ $l }}</button>
            @endforeach
        </div>
        <form class="dt-form" data-form="lumpsum">
            <label>Investment (₹)<input type="number" name="amount" value="100000" min="1" required></label>
            <label>Expected return (% a year)<input type="number" name="rate" value="12" step="0.1" required></label>
            <label>Years<input type="number" name="years" value="10" step="0.5" min="0.1" required></label>
        </form>
        <form class="dt-form" data-form="sip" hidden>
            <label>Monthly SIP (₹)<input type="number" name="monthly" value="10000" min="1" required></label>
            <label>Expected return (% a year)<input type="number" name="rate" value="12" step="0.1" required></label>
            <label>Years<input type="number" name="years" value="15" step="0.5" min="0.1" required></label>
            <label>Yearly step-up (%)<input type="number" name="step_up" value="0" step="1" min="0"></label>
        </form>
        <form class="dt-form" data-form="emi" hidden>
            <label>Loan amount (₹)<input type="number" name="principal" value="2500000" min="1" required></label>
            <label>Interest rate (% a year)<input type="number" name="rate" value="8.5" step="0.05" min="0" required></label>
            <label>Tenure (months)<input type="number" name="months" value="240" min="1" max="600" required></label>
        </form>
        <form class="dt-form" data-form="amortization" hidden>
            <label>Loan amount (₹)<input type="number" name="principal" value="2500000" min="1" required></label>
            <label>Interest rate (% a year)<input type="number" name="rate" value="8.5" step="0.05" min="0" required></label>
            <label>Tenure (months)<input type="number" name="months" value="240" min="1" max="600" required></label>
            <label>One-time prepayment (₹)<input type="number" name="prepayment" value="0" min="0"></label>
            <label>Prepayment in month<input type="number" name="prepay_month" value="0" min="0" max="600"></label>
        </form>
        <form class="dt-form" data-form="margin" hidden>
            <label>NSE symbol<input type="text" name="symbol" value="RELIANCE" maxlength="30" style="text-transform:uppercase"></label>
            <label>Price (₹)<input type="number" name="price" value="1200" step="0.05" min="0.01" required></label>
            <label>Quantity<input type="number" name="quantity" value="100" min="1" required></label>
            <label>Product<select name="product"><option value="intraday">Intraday (MIS)</option><option value="delivery">Delivery (CNC)</option></select></label>
            <label>Margin % (blank = exchange rate)<input type="number" name="margin_pct" step="0.01" min="1" max="100"></label>
        </form>
        <div style="margin-top:12px"><button class="dt-btn gold" id="dt-calc" type="button">Calculate</button></div>
        <div id="dt-out"></div>
    </section>
</main>
@endsection
@push('scripts')
<script>
(() => {
  let type = 'lumpsum';
  const out = document.getElementById('dt-out');
  const inr = v => v === null || v === undefined ? '—' : '₹' + Number(v).toLocaleString('en-IN', { maximumFractionDigits: 2 });
  const stat = (l, v) => '<div class="dt-stat"><span>' + l + '</span><b>' + v + '</b></div>';
  const table = (cols, rows) => '<div class="dt-table-wrap" style="margin-top:12px;max-height:420px;overflow:auto"><table class="dt-table"><thead><tr>' + cols.map(c => '<th class="num">' + c[1] + '</th>').join('') + '</tr></thead><tbody>'
      + rows.map(r => '<tr>' + cols.map(c => '<td class="num">' + (c[2] ? c[2](r[c[0]]) : r[c[0]]) + '</td>').join('') + '</tr>').join('') + '</tbody></table></div>';
  const render = {
    lumpsum: d => '<div class="dt-result">' + stat('Invested', inr(d.invested)) + stat('Value', inr(d.value)) + stat('Gain', inr(d.gain)) + stat('Absolute return', d.absolute_return_pct + '%') + '</div>' + table([['year', 'Year'], ['value', 'Value', inr]], d.yearly),
    sip: d => '<div class="dt-result">' + stat('Invested', inr(d.invested)) + stat('Value', inr(d.value)) + stat('Gain', inr(d.gain)) + stat('Instalments', d.months) + '</div>' + table([['year', 'Year'], ['invested', 'Invested', inr], ['value', 'Value', inr]], d.yearly),
    emi: d => '<div class="dt-result">' + stat('Monthly EMI', inr(d.emi)) + stat('Total interest', inr(d.total_interest)) + stat('Total payment', inr(d.total_payment)) + stat('Months', d.months) + '</div>',
    amortization: d => '<div class="dt-result">' + stat('Monthly EMI', inr(d.emi)) + stat('Total interest', inr(d.total_interest)) + stat('Months taken', d.months_taken) + stat('Months saved', d.months_saved) + '</div>'
      + table([['month', 'Month'], ['emi', 'EMI', inr], ['principal', 'Principal', inr], ['interest', 'Interest', inr], ['prepayment', 'Prepayment', inr], ['balance', 'Balance', inr]], d.schedule),
    margin: d => '<div class="dt-result">' + stat('Trade value', inr(d.trade_value)) + stat('Margin required', inr(d.margin_required)) + stat('Margin %', d.margin_pct + '%') + stat('Leverage', d.leverage + '×')
      + (d.exchange_margin !== null ? stat('NSE VaR + ELM', d.var_margin + '% + ' + d.elm + '% = ' + d.exchange_margin + '%') + stat('Rates as of', d.as_of) : '') + '</div>',
  };
  document.querySelectorAll('[data-calc]').forEach(b => b.addEventListener('click', () => {
    type = b.dataset.calc; document.querySelectorAll('[data-calc]').forEach(x => x.classList.toggle('on', x === b));
    document.querySelectorAll('[data-form]').forEach(f => f.hidden = f.dataset.form !== type); out.innerHTML = '';
  }));
  document.getElementById('dt-calc').addEventListener('click', () => {
    const form = document.querySelector('[data-form="' + type + '"]');
    const body = Object.fromEntries([...new FormData(form)].filter(([k, v]) => v !== ''));
    fetch('{{ url('trading/api/calculators') }}/' + type, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(body) })
      .then(async r => { const j = await r.json(); if (!r.ok) throw new Error(j.message || 'Check the inputs'); out.innerHTML = render[type](j.data); })
      .catch(e => out.innerHTML = '<p class="dt-flash bad" style="margin-top:12px">' + e.message + '</p>');
  });
})();
</script>
@endpush
