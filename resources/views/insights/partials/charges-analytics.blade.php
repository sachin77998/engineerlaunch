@php $ch = $analytics['charges']; @endphp
<div class="ci-metrics">
    <div class="ci-metric"><span>Total charges</span><b>{{ $ch['total'] }}</b></div>
    <div class="ci-metric"><span>Active</span><b>{{ $ch['open'] }}</b><small>{{ $money($ch['open_amount']) }}</small></div>
    <div class="ci-metric"><span>Closed</span><b>{{ $ch['closed'] }}</b><small>{{ $money($ch['closed_amount']) }}</small></div>
    <div class="ci-metric"><span>Lenders</span><b>{{ $ch['lenders']->count() }}</b></div>
    <div class="ci-metric"><span>Repaid</span><b>{{ ($ch['open_amount'] + $ch['closed_amount']) > 0 ? number_format($ch['closed_amount'] / ($ch['open_amount'] + $ch['closed_amount']) * 100, 1) . '%' : '—' }}</b><small>of amount borrowed</small></div>
</div>
<div class="ci-two" style="margin-top:14px">
    <div style="min-width:0"><h3 class="ci-h3">Open vs closed</h3>
        <div class="ci-chart"><canvas aria-label="Active and closed charges" data-chart="{{ json_encode([
            'type' => 'doughnut',
            'data' => ['labels' => ['Active (' . $ch['open'] . ')', 'Closed (' . $ch['closed'] . ')'], 'datasets' => [['label' => 'Charges', 'data' => [$ch['open'], $ch['closed']], 'backgroundColor' => ['#527fad', '#94a3b8']]]],
            'options' => ['cutout' => '60%', 'interaction' => ['mode' => 'nearest']],
        ]) }}"></canvas></div></div>
    <div style="min-width:0"><h3 class="ci-h3">Borrowing timeline</h3>
        <div class="ci-chart"><canvas aria-label="Charges created and satisfied per year" data-chart="{{ json_encode([
            'type' => 'bar',
            'data' => ['labels' => $ch['timeline']['years'], 'datasets' => [
                ['label' => 'New', 'data' => $ch['timeline']['new'], 'backgroundColor' => '#527fad'],
                ['label' => 'Satisfied', 'data' => $ch['timeline']['closed'], 'backgroundColor' => '#94a3b8'],
            ]],
            'options' => ['scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]],
        ]) }}"></canvas></div></div>
</div>
@if($ch['lenders']->isNotEmpty())
    <h3 class="ci-h3" style="margin-top:14px">By lender</h3>
    <div class="ci-chart" style="height:{{ max(220, min(560, 46 * $ch['lenders']->take(12)->count() + 70)) }}px"><canvas aria-label="Amount borrowed, repaid and outstanding per lender, ₹ crore" data-chart="{{ json_encode([
        'type' => 'bar',
        'data' => ['labels' => $ch['lenders']->take(12)->map(fn ($l) => $l['name'] . ' (' . $l['count'] . ')'), 'datasets' => [
            ['label' => 'Closed', 'data' => $ch['lenders']->take(12)->pluck('closed'), 'backgroundColor' => '#94a3b8', 'stack' => 'a', 'unit' => ' Cr'],
            ['label' => 'Active', 'data' => $ch['lenders']->take(12)->pluck('active'), 'backgroundColor' => '#527fad', 'stack' => 'a', 'unit' => ' Cr'],
        ]],
        'options' => ['indexAxis' => 'y', 'scales' => ['x' => ['stacked' => true, 'title' => ['display' => true, 'text' => '₹ Cr']], 'y' => ['stacked' => true]]],
    ]) }}"></canvas></div>
@endif
<div class="ci-tabs" data-tabs="charges-table" style="margin-top:14px">
    <button type="button" class="active" data-status="all">All ({{ $ch['total'] }})</button>
    <button type="button" data-status="active">Active ({{ $ch['open'] }})</button>
    <button type="button" data-status="closed">Closed ({{ $ch['closed'] }})</button>
</div>
<div class="ci-table-wrap"><table class="ci-table" id="charges-table"><thead><tr><th>Charge ID</th><th>Lender</th><th>Amount</th><th>Status</th><th>Created</th><th>Closed</th></tr></thead><tbody>
    @foreach($ch['list']->sortByDesc('created_on') as $charge)
        @php $isClosed = (bool) $charge->satisfied_on || preg_match('/satisf|closed/i', (string) $charge->status); @endphp
        <tr data-status="{{ $isClosed ? 'closed' : 'active' }}"><td>{{ $charge->charge_id ?? '—' }}</td><td style="white-space:normal">{{ $charge->lender ?? '—' }}</td><td>{{ $money($charge->amount) }}</td>
            <td><span class="ci-pill {{ $isClosed ? '' : 'ok' }}">{{ $isClosed ? 'Closed' : 'Active' }}</span></td><td>{{ $charge->created_on?->format('d M Y') ?? '—' }}</td><td>{{ $charge->satisfied_on?->format('d M Y') ?? '—' }}</td></tr>
    @endforeach
</tbody></table></div>
