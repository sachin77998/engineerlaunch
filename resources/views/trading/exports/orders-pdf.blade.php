<!doctype html>
<html><head><meta charset="utf-8"><title>{{ $title }}</title>
<style>
body{font-family:DejaVu Sans,sans-serif;font-size:8px;color:#0b2545}
h1{font-size:14px;margin:0 0 4px}.muted{color:#5b6b82}
table{width:100%;border-collapse:collapse;margin-top:8px}
th{background:#0b2545;color:#fff;text-align:left;padding:4px 3px;font-size:7.5px}
td{border-bottom:1px solid #dfe6ef;padding:3px;vertical-align:top}
.sum{margin-top:6px}.sum span{margin-right:14px}
</style></head><body>
<h1>{{ $title }}</h1>
<div class="muted">Generated {{ now('Asia/Kolkata')->format('d M Y, H:i') }} IST · {{ count($rows) }} orders</div>
@if($summary)<div class="sum">@foreach($summary as $k => $v)<span><b>{{ $k }}:</b> {{ $v }}</span>@endforeach</div>@endif
<table>
    <thead><tr>@foreach(['order_no', 'created_at', 'client_code', 'scheme', 'side', 'status', 'amount', 'units', 'nav', 'applicable_nav_date', 'allotted_units', 'total_fees', 'net_amount'] as $c)<th>{{ $columns[$c] }}</th>@endforeach</tr></thead>
    <tbody>
    @foreach($rows as $r)
        <tr>@foreach(['order_no', 'created_at', 'client_code', 'scheme', 'side', 'status', 'amount', 'units', 'nav', 'applicable_nav_date', 'allotted_units', 'total_fees', 'net_amount'] as $c)<td>{{ is_float($r[$c]) ? number_format($r[$c], in_array($c, ['units', 'allotted_units', 'nav'], true) ? 4 : 2) : $r[$c] }}</td>@endforeach</tr>
    @endforeach
    </tbody>
</table>
</body></html>
