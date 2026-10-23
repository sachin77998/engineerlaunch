<!doctype html>
<html><head><meta charset="utf-8"><style>
@page{margin:34px 38px}body{font-family:DejaVu Sans,sans-serif;font-size:9.5px;color:#1d2939;line-height:1.4}
h1{font-size:18px;margin:0;color:#0b2545}h2{font-size:12.5px;margin:16px 0 6px;color:#0b2545;border-bottom:1.5px solid #0b2545;padding-bottom:3px}
.muted{color:#667085}.facts{width:100%;border-collapse:collapse;margin:6px 0}.facts td{padding:4px 6px;border:1px solid #e4e7ec;width:25%}.facts td b{display:block;font-size:10.5px}
table.t{width:100%;border-collapse:collapse;margin:4px 0}table.t th{background:#0b2545;color:#fff;font-weight:bold;padding:4px 5px;text-align:left;font-size:8.5px}table.t td{padding:3.5px 5px;border-bottom:1px solid #eaecf0;vertical-align:top}
.num{text-align:right;white-space:nowrap}.box{border:1px solid #d0d5dd;border-radius:6px;padding:8px 10px;margin:6px 0}.up{color:#067647}.down{color:#b42318}
</style></head><body>
@php
    $cr = fn ($v) => $v === null ? '—' : \App\Services\Insights\CompanyInsights::indian((float) $v / 1e7, 2);
    $crd = fn ($v) => $v === null ? '—' : \App\Services\Insights\CompanyInsights::indian((float) $v, $v >= 100 ? 0 : 2);
    $r = $registry;
@endphp
<h1>{{ $company->name }}</h1>
<div class="muted">{{ collect([$r?->cin ?? $company->registry_cin, $company->industry, $r?->city, $company->registered_state])->filter()->implode(' · ') }}</div>
<div class="muted">Company insights report · generated {{ $generated->format('d M Y, h:i A') }} · amounts in ₹ crore</div>

<table class="facts"><tr>
    <td><span class="muted">Status</span><b>{{ $r?->company_status ?? $company->registry_status ?? '—' }}</b></td>
    <td><span class="muted">Incorporated</span><b>{{ $r?->incorporation_date?->format('d M Y') ?? '—' }}</b></td>
    <td><span class="muted">Paid-up capital</span><b>{{ $r?->paid_up_capital ? '₹' . $cr($r->paid_up_capital) . ' cr' : '—' }}</b></td>
    <td><span class="muted">Website</span><b>{{ $company->website ?: '—' }}</b></td>
</tr></table>

@if($years->isNotEmpty())
    <h2>Financials (audited, standalone)</h2>
    <table class="t"><thead><tr><th>₹ crore</th>@foreach($years as $y)<th class="num">{{ $y->financial_year }}</th>@endforeach</tr></thead><tbody>
    @foreach(['revenue' => 'Revenue', 'ebitda' => 'EBITDA', 'finance_cost' => 'Finance cost', 'profit_after_tax' => 'Net profit', 'net_worth' => 'Net worth', 'long_term_borrowings' => 'Long-term borrowings', 'short_term_borrowings' => 'Short-term borrowings', 'total_debt' => 'Total debt', 'cash_and_bank' => 'Cash & bank'] as $k => $label)
        <tr><td>{{ $label }}</td>@foreach($years as $y)<td class="num">{{ $cr($y->$k) }}</td>@endforeach</tr>
    @endforeach
    </tbody></table>
@elseif(!$full)
    <p class="muted">Audited financials, lenders and bank charges are included for Premium members.</p>
@endif

@if($assessment)
    @php $a = $assessment; @endphp
    <h2>Credit assessment – {{ $a['sector'] }}</h2>
    <div class="muted">{{ $a['criteria'] }} · {{ ucfirst($a['basis']) }} accounts{{ $a['year'] ? ' · FY ' . $a['year'] : '' }}</div>
    <table class="facts"><tr><td><span class="muted">Indicative long-term</span><b>{{ $a['indicative'] ?? '—' }}</b></td><td><span class="muted">Indicative short-term</span><b>{{ $a['short_term'] ?? '—' }}</b></td><td><span class="muted">Liquidity</span><b>{{ $a['liquidity']['level'] ?? '—' }}</b></td><td><span class="muted">Direction</span><b>{{ $a['outlook']['watch'] ? 'Rating watch' : $a['outlook']['direction'] }}</b></td></tr></table>
    @if($a['adjustments'])<ul style="margin:4px 0;padding-left:14px">@foreach($a['adjustments'] as $x)<li>{{ $x['from'] }} → {{ $x['to'] }}: {{ $x['why'] }}</li>@endforeach</ul>@endif
    @if($a['rows'])<table class="t"><thead><tr><th>Ratio</th><th class="num">Company</th><th class="num">Sector median</th><th class="num">Score</th></tr></thead><tbody>@foreach($a['rows'] as $r)<tr><td>{{ $r['label'] }}</td><td class="num">{{ $r['value'] ?? '—' }}</td><td class="num">{{ $r['peer'] ?? '—' }}</td><td class="num">{{ $r['score'] ?? '—' }}/5</td></tr>@endforeach</tbody></table>@endif
    @if($a['checks'])<table class="t"><tbody>@foreach($a['checks'] as $x)<tr><td>{{ $x['label'] }}</td><td>{{ $x['value'] }}</td><td>{{ ucfirst($x['status']) }}</td></tr>@endforeach</tbody></table>@endif
@endif

@if($funding)
    @php $c = $funding['capacity']; @endphp
    <h2>Borrowing capacity</h2>
    <div class="box"><b class="{{ str_contains($c['verdict'], 'likely') ? 'up' : (str_contains($c['verdict'], 'difficult') ? 'down' : '') }}" style="font-size:12px">{{ $c['verdict'] }}</b>
        @if($c['outlook'])<div>Probability the next rating action is stable or better: <b>{{ round($c['outlook']['probability'] * 100) }}%</b> ({{ $c['outlook']['label'] }})</div>@endif
        <table class="facts"><tr><td><span class="muted">Rated limits</span><b>{{ $crd($c['rated_total']) }}</b></td><td><span class="muted">Not yet allocated</span><b>{{ $crd($c['unallocated'] ?: null) }}</b></td>
            <td><span class="muted">Extra debt within {{ $c['debt_cap_multiple'] }}× EBITDA</span><b>{{ $crd($c['room_crore']) }}</b></td><td><span class="muted">Interest coverage</span><b>{{ $c['interest_coverage'] !== null ? number_format($c['interest_coverage'], 2) . '×' : '—' }}</b></td></tr></table>
        <ul style="margin:4px 0 0;padding-left:14px">@foreach($c['why'] as $w)<li>{{ $w }}</li>@endforeach</ul></div>

    @if($funding['stories'])
        <h2>Funding story</h2>
        @foreach($funding['stories'] as $s)<div class="box"><b>{{ $s['title'] }}</b><ol style="margin:4px 0 0;padding-left:16px">@foreach($s['steps'] as $st)<li>{{ $st['date']->format('M Y') }} — {{ $st['text'] }}</li>@endforeach</ol></div>@endforeach
    @endif

    <h2>Borrowing &amp; funding timeline</h2>
    <table class="t"><thead><tr><th style="width:62px">Date</th><th>Event</th><th class="num" style="width:62px">₹ crore</th><th style="width:90px">Source</th></tr></thead><tbody>
    @foreach($funding['timeline']->take(60) as $e)<tr><td>{{ $e['date']->format('d M Y') }}</td><td>{{ $e['title'] }}@if(!empty($e['evidence']))<div class="muted">{{ \Illuminate\Support\Str::limit($e['evidence'], 220) }}</div>@endif</td><td class="num">{{ $e['amount'] !== null ? $crd($e['amount']) : '' }}</td><td>{{ $e['source'] }}</td></tr>@endforeach
    </tbody></table>

    @if($funding['lenders']->isNotEmpty())
        <h2>Lenders (MCA charges)</h2>
        <table class="t"><thead><tr><th>Lender</th><th class="num">Charges</th><th class="num">Open</th><th class="num">Open ₹ cr</th><th>First loan</th><th>Last closed</th></tr></thead><tbody>
        @foreach($funding['lenders'] as $l)<tr><td>{{ $l['lender'] }}</td><td class="num">{{ $l['charges'] }}</td><td class="num">{{ $l['open'] }}</td><td class="num">{{ $crd($l['amount_open'] ?: null) }}</td><td>{{ $l['first'] ? \Carbon\Carbon::parse($l['first'])->format('M Y') : '—' }}</td><td>{{ $l['last_closed'] ? \Carbon\Carbon::parse($l['last_closed'])->format('M Y') : '—' }}</td></tr>@endforeach
        </tbody></table>
    @endif
@endif

@if($credit)
    <h2>Credit rating history (ICRA / CARE)</h2>
    <table class="t"><thead><tr><th>Date</th><th>Agency</th><th>Action</th><th>Long-term</th><th>Short-term</th><th>Outlook</th><th class="num">Rated ₹ cr</th><th>Liquidity</th></tr></thead><tbody>
    @foreach($credit['history']->take(30) as $x)<tr><td>{{ $x->published_on?->format('d M Y') }}</td><td>{{ $x->agency }}</td><td>{{ ucfirst((string) $x->action) }}</td><td>{{ $x->rating_long ?? '—' }}</td><td>{{ $x->rating_short ?? '—' }}</td><td>{{ $x->outlook ?? '—' }}</td><td class="num">{{ $crd($x->total_rated_crore) }}</td><td>{{ $x->liquidity ?? '—' }}</td></tr>@endforeach
    </tbody></table>
    @php $latest = $credit['history']->first(fn ($x) => !empty($x->instruments)); @endphp
    @if($latest)
        <h2>Rated facilities ({{ $latest->agency }}, {{ $latest->published_on?->format('d M Y') }})</h2>
        <table class="t"><thead><tr><th>Facility</th><th class="num">Previous ₹ cr</th><th class="num">Current ₹ cr</th><th>Rating</th><th>Action</th></tr></thead><tbody>
        @foreach($latest->instruments as $i)<tr><td>{{ $i['instrument'] ?? '' }}</td><td class="num">{{ $crd($i['previous'] ?? null) }}</td><td class="num">{{ $crd($i['current'] ?? null) }}</td><td>{{ trim(($i['rating'] ?? '') . ' ' . ($i['outlook'] ?? '')) }}</td><td>{{ ucfirst($i['action'] ?? '') }}</td></tr>@endforeach
        </tbody></table>
    @endif
@endif

@if($directors->isNotEmpty())
    <h2>Directors</h2>
    <table class="t"><thead><tr><th>Name</th><th>Designation</th><th>Appointed</th></tr></thead><tbody>
    @foreach($directors as $o)<tr><td>{{ $o->name }}</td><td>{{ $o->designation }}</td><td>{{ $o->appointed_on?->format('d M Y') }}</td></tr>@endforeach
    </tbody></table>
@endif
<p class="muted" style="margin-top:14px">Sources: MCA filings and charges, NSE/BSE results, ICRA and CARE rating reports. The borrowing view is an estimate built from public data, not a lender's credit decision or a rating.</p>
</body></html>
