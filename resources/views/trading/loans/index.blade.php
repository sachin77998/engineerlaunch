@extends('layouts.site')
@section('title', 'Loans and amortization schedules — Do Trading')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'Loans', 'subtitle' => 'Amortization schedules for EMI, bullet, interest-only and quarterly structures, with moratorium and day-count basis'])
<main class="dt-wrap dt-main">
    <div class="dt-two">
        <section class="dt-card">
            <h2>New loan</h2>
            <form method="post" action="{{ route('trading.loans.store') }}" id="dt-loan" class="dt-form">@csrf
                <label>Borrower name<input name="borrower_name" value="{{ old('borrower_name') }}" required maxlength="120"></label>
                <label>Account number<input name="account_number" value="{{ old('account_number') }}" maxlength="40"></label>
                <label>Loan type<select name="loan_type">@foreach(['term' => 'Term loan', 'home' => 'Home loan', 'vehicle' => 'Vehicle loan', 'personal' => 'Personal loan', 'business' => 'Business loan', 'education' => 'Education loan'] as $k => $l)<option value="{{ $k }}" @selected(old('loan_type') === $k)>{{ $l }}</option>@endforeach</select></label>
                <label>Loan amount (₹)<input type="number" name="principal" value="{{ old('principal', 1000000) }}" min="1" step="0.01" required></label>
                <label>Interest rate (% a year)<input type="number" name="interest_rate" value="{{ old('interest_rate', 10) }}" min="0" max="60" step="0.0001" required></label>
                <label>Issue / disbursal date<input type="date" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" required></label>
                <label>Maturity date<input type="date" name="maturity_date" value="{{ old('maturity_date', now()->addYears(2)->toDateString()) }}" required></label>
                <label>First due date (optional)<input type="date" name="first_due_date" value="{{ old('first_due_date') }}"></label>
                <label class="wide">Repayment structure<select name="payment_frequency">@foreach($frequencies as $k => $l)<option value="{{ $k }}" @selected(old('payment_frequency', 'monthly') === $k)>{{ $l }}</option>@endforeach</select></label>
                <label>Interest basis<select name="day_count">@foreach($dayCounts as $k => $l)<option value="{{ $k }}" @selected(old('day_count', 'actual_365') === $k)>{{ $l }}</option>@endforeach</select></label>
                <label>Moratorium (months)<input type="number" name="moratorium_months" value="{{ old('moratorium_months', 0) }}" min="0" max="120"></label>
                <label>Moratorium type<select name="moratorium_type"><option value="principal_only">Principal only (interest is paid)</option><option value="principal_and_interest" @selected(old('moratorium_type') === 'principal_and_interest')>Principal and interest (interest is added to the loan)</option></select></label>
                <div class="wide" style="display:flex;gap:8px"><button class="dt-btn light" type="button" id="dt-loan-preview">Preview schedule</button><button class="dt-btn gold" type="submit">Save loan</button></div>
            </form>
            <div id="dt-loan-out"></div>
        </section>
        <aside class="dt-card dt-sticky">
            <h2>Your loans</h2>
            @forelse($loans as $l)
                <a href="{{ route('trading.loans.show', $l) }}" style="display:block;padding:12px 0;border-bottom:1px solid #f1f1f3;text-decoration:none;color:#1b2437">
                    <b>{{ $l->borrower_name }}</b> <span class="dt-muted">· {{ $l->loan_no }}</span><br>
                    <small class="dt-muted">₹{{ \App\Services\Insights\CompanyInsights::indian($l->principal) }} at {{ rtrim(rtrim(number_format($l->interest_rate, 4), '0'), '.') }}% · {{ $frequencies[$l->payment_frequency] ?? $l->payment_frequency }} · {{ $l->installments }} instalments</small>
                </a>
            @empty
                <p class="dt-muted">No loans yet. Create one to see its full schedule.</p>
            @endforelse
            <div class="dt-pager" style="margin-top:10px">{{ $loans->links() }}</div>
        </aside>
    </div>
</main>
@endsection
@push('scripts')
<script>
(() => {
  const form = document.getElementById('dt-loan'), out = document.getElementById('dt-loan-out');
  const inr = v => '₹' + Number(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const stat = (l, v) => '<div class="dt-stat"><span>' + l + '</span><b>' + v + '</b></div>';
  document.getElementById('dt-loan-preview').addEventListener('click', async () => {
    const body = Object.fromEntries([...new FormData(form)].filter(([k, v]) => v !== '' && k !== '_token'));
    const r = await fetch('{{ route('trading.loans.preview') }}', { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(body) });
    const j = await r.json();
    if (!r.ok) { out.innerHTML = '<p class="dt-flash bad" style="margin-top:14px">' + (j.message || 'Check the inputs') + '</p>'; return; }
    const d = j.data;
    out.innerHTML = '<div class="dt-result">' + stat('Instalment', d.emi ? inr(d.emi) : 'Varies') + stat('Instalments', d.installments) + stat('Total interest', inr(d.totals.interest)) + stat('Total payment', inr(d.totals.payment)) + '</div>'
      + '<div class="dt-table-wrap" style="margin-top:14px;max-height:460px;overflow:auto"><table class="dt-table"><thead><tr><th>No</th><th>Due date</th><th class="num">Days</th><th class="num">Opening</th><th class="num">Principal</th><th class="num">Interest</th><th class="num">Payment</th><th class="num">Closing</th></tr></thead><tbody>'
      + d.rows.map(x => '<tr' + (x.is_moratorium ? ' style="background:#fff8e6"' : '') + '><td>' + x.installment_no + '</td><td>' + x.due_date + '</td><td class="num">' + x.days + '</td><td class="num">' + inr(x.opening_balance) + '</td><td class="num">' + inr(x.principal) + '</td><td class="num">' + inr(x.interest) + '</td><td class="num"><b>' + inr(x.total_payment) + '</b></td><td class="num">' + inr(x.closing_balance) + '</td></tr>').join('')
      + '</tbody></table></div>';
  });
})();
</script>
@endpush
