<!doctype html>
<html><head><meta charset="utf-8"><title>{{ $loan->loan_no }}</title>
<style>
body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#1b2437}
h1{font-size:15px;margin:0 0 4px}.muted{color:#7c7e8c}
.sum span{display:inline-block;margin:6px 16px 0 0}
table{width:100%;border-collapse:collapse;margin-top:10px}
th{background:#0b2545;color:#fff;text-align:right;padding:5px 4px}th:first-child,th:nth-child(2){text-align:left}
td{border-bottom:1px solid #e3e6ec;padding:4px;text-align:right}td:first-child,td:nth-child(2){text-align:left}
tr.m td{background:#fff8e6}tfoot td{font-weight:bold;border-top:2px solid #0b2545}
</style></head><body>
<h1>Amortization schedule · {{ $loan->loan_no }}</h1>
<div class="muted">{{ $loan->borrower_name }}{{ $loan->account_number ? ' · A/c ' . $loan->account_number : '' }} · generated {{ now('Asia/Kolkata')->format('d M Y, H:i') }} IST</div>
<div class="sum">
    <span><b>Amount:</b> ₹{{ number_format($loan->principal, 2) }}</span><span><b>Rate:</b> {{ rtrim(rtrim(number_format($loan->interest_rate, 4), '0'), '.') }}%</span>
    <span><b>Structure:</b> {{ $frequencies[$loan->payment_frequency] ?? $loan->payment_frequency }}</span><span><b>Basis:</b> {{ str_replace('_', '/', $loan->day_count) }}</span>
    <span><b>Term:</b> {{ $loan->issue_date->format('d M Y') }} – {{ $loan->maturity_date->format('d M Y') }}</span>
    @if($loan->moratorium_months)<span><b>Moratorium:</b> {{ $loan->moratorium_months }} months, {{ str_replace('_', ' ', $loan->moratorium_type) }}</span>@endif
</div>
<table>
    <thead><tr><th>No</th><th>Due date</th><th>Days</th><th>Opening</th><th>Principal</th><th>Interest</th><th>Payment</th><th>Closing</th></tr></thead>
    <tbody>
    @foreach($loan->schedule as $r)
        <tr class="{{ $r->is_moratorium ? 'm' : '' }}"><td>{{ $r->installment_no }}</td><td>{{ $r->due_date->format('d-m-Y') }}</td><td>{{ $r->days }}</td><td>{{ number_format($r->opening_balance, 2) }}</td><td>{{ number_format($r->principal, 2) }}</td><td>{{ number_format($r->interest, 2) }}</td><td>{{ number_format($r->total_payment, 2) }}</td><td>{{ number_format($r->closing_balance, 2) }}</td></tr>
    @endforeach
    </tbody>
    <tfoot><tr><td colspan="4">Total</td><td>{{ number_format($loan->schedule->sum('principal'), 2) }}</td><td>{{ number_format($loan->schedule->sum('interest'), 2) }}</td><td>{{ number_format($loan->schedule->sum('total_payment'), 2) }}</td><td></td></tr></tfoot>
</table>
</body></html>
