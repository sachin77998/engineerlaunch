@extends('layouts.site')
@section('title', $loan->loan_no . ' — amortization schedule')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => $loan->borrower_name . ' · ' . $loan->loan_no, 'subtitle' => ($frequencies[$loan->payment_frequency] ?? $loan->payment_frequency) . ' · ' . ($dayCounts[$loan->day_count] ?? $loan->day_count) . ($loan->moratorium_months ? ' · ' . $loan->moratorium_months . '-month moratorium (' . str_replace('_', ' ', $loan->moratorium_type) . ')' : '')])
<main class="dt-wrap dt-main">
    <section class="dt-stats">
        <div class="dt-stat"><span>Loan amount</span><b>₹{{ \App\Services\Insights\CompanyInsights::indian($loan->principal, 2) }}</b></div>
        <div class="dt-stat"><span>Interest rate</span><b>{{ rtrim(rtrim(number_format($loan->interest_rate, 4), '0'), '.') }}%</b></div>
        <div class="dt-stat"><span>Instalment</span><b>{{ $loan->emi ? '₹' . \App\Services\Insights\CompanyInsights::indian($loan->emi, 2) : 'Varies' }}</b></div>
        <div class="dt-stat"><span>Total interest</span><b>₹{{ \App\Services\Insights\CompanyInsights::indian($loan->total_interest, 2) }}</b></div>
        <div class="dt-stat"><span>Total payment</span><b>₹{{ \App\Services\Insights\CompanyInsights::indian($loan->total_payment, 2) }}</b></div>
        <div class="dt-stat"><span>Term</span><b style="font-size:15px">{{ $loan->issue_date->format('d M Y') }} → {{ $loan->maturity_date->format('d M Y') }}</b></div>
    </section>
    <section class="dt-card" style="padding:0;overflow:hidden">
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;align-items:center;padding:16px 20px">
            <h2 style="margin:0">Amortization schedule ({{ $loan->installments }} instalments)</h2>
            <span class="dt-chips">
                <a class="dt-btn light sm" href="{{ route('trading.loans.export', [$loan, 'xlsx']) }}">Excel</a>
                <a class="dt-btn light sm" href="{{ route('trading.loans.export', [$loan, 'pdf']) }}">PDF</a>
                <form method="post" action="{{ route('trading.loans.destroy', $loan) }}" onsubmit="return confirm('Delete this loan?')">@csrf @method('DELETE')<button class="dt-btn light sm" type="submit">Delete</button></form>
            </span>
        </div>
        <div class="dt-table-wrap"><table class="dt-table">
            <thead><tr><th>No</th><th>Due date</th><th class="num">Days</th><th class="num">Opening balance</th><th class="num">Principal</th><th class="num">Interest</th><th class="num">Payment</th><th class="num">Closing balance</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($loan->schedule as $r)
                <tr @if($r->is_moratorium) style="background:#fff8e6" @endif>
                    <td>{{ $r->installment_no }}</td><td>{{ $r->due_date->format('d M Y') }}</td><td class="num">{{ $r->days }}</td>
                    <td class="num">₹{{ number_format($r->opening_balance, 2) }}</td><td class="num">₹{{ number_format($r->principal, 2) }}</td><td class="num">₹{{ number_format($r->interest, 2) }}</td>
                    <td class="num"><b>₹{{ number_format($r->total_payment, 2) }}</b></td><td class="num">₹{{ number_format($r->closing_balance, 2) }}</td>
                    <td><span class="dt-pill {{ $r->status }}">{{ $r->is_moratorium ? 'moratorium' : $r->status }}</span></td>
                </tr>
            @endforeach
            </tbody>
            <tfoot><tr><td colspan="4"><b>Total</b></td><td class="num"><b>₹{{ number_format($loan->schedule->sum('principal'), 2) }}</b></td><td class="num"><b>₹{{ number_format($loan->schedule->sum('interest'), 2) }}</b></td><td class="num"><b>₹{{ number_format($loan->schedule->sum('total_payment'), 2) }}</b></td><td colspan="2"></td></tr></tfoot>
        </table></div>
    </section>
</main>
@endsection
