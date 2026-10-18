@php
    $fin = $analytics['financial'];
    $mat = $analytics['materials'];
    $pay = $analytics['payments'];
    $con = $analytics['concentration'];
    $change = fn ($v) => $v === null ? '<span class="ci-muted">—</span>' : '<span class="' . ($v >= 0 ? 'up' : 'down') . '">' . ($v >= 0 ? '▲ ' : '▼ ') . number_format(abs($v), 1) . '%</span>';
    $fy = fn ($list) => collect($list)->map(fn ($y) => 'FY ' . $y)->values();
@endphp

@if($fin['years']->count() > 1)
<section class="ci-card" id="growth">
    <h2>Growth trends</h2>
    <div class="ci-table-wrap">
        <table class="ci-table">
            <thead><tr><th>₹ · FY</th>@foreach($fin['years'] as $year)<th>{{ $year }}</th>@endforeach<th>CAGR 3Y</th><th>CAGR 5Y</th></tr></thead>
            <tbody>
            @foreach($fin['series'] as $field => $s)
                <tr>
                    <td>{{ $s['label'] }}</td>
                    @foreach($s['values'] as $i => $value)
                        <td>{{ $money($value) }}<small style="display:block">{!! $i ? $change($s['yoy'][$i]) : '' !!}</small></td>
                    @endforeach
                    <td>{!! $change($s['cagr3']) !!}</td><td>{!! $change($s['cagr5']) !!}</td>
                </tr>
            @endforeach
            <tr><td>EBITDA margin</td>@foreach($fin['ebitda_margin'] as $v)<td>{{ $v === null ? '—' : number_format($v, 1) . '%' }}</td>@endforeach<td></td><td></td></tr>
            <tr><td>PAT margin</td>@foreach($fin['pat_margin'] as $v)<td>{{ $v === null ? '—' : number_format($v, 1) . '%' }}</td>@endforeach<td></td><td></td></tr>
            </tbody>
        </table>
    </div>
    <div class="ci-chart" style="margin-top:16px"><canvas aria-label="Year-on-year change in revenue, EBITDA and net profit" data-chart="{{ json_encode([
        'type' => 'bar',
        'data' => ['labels' => $fy($fin['years'])->slice(1)->values(), 'datasets' => [
            ['label' => 'Revenue YoY', 'data' => $fin['series']['revenue']['yoy']->slice(1)->values()->map(fn ($v) => $v === null ? null : round($v, 1)), 'backgroundColor' => '#527fad', 'unit' => '%'],
            ['label' => 'EBITDA YoY', 'data' => $fin['series']['ebitda']['yoy']->slice(1)->values()->map(fn ($v) => $v === null ? null : round($v, 1)), 'backgroundColor' => '#f5b800', 'unit' => '%'],
            ['label' => 'Net profit YoY', 'data' => $fin['series']['profit_after_tax']['yoy']->slice(1)->values()->map(fn ($v) => $v === null ? null : round($v, 1)), 'backgroundColor' => '#43936c', 'unit' => '%'],
        ]],
        'options' => ['scales' => ['y' => ['title' => ['display' => true, 'text' => 'Change vs previous year, %']]]],
    ]) }}"></canvas></div>
</section>
@endif

<div class="ci-two">
    <section class="ci-card" id="materials" style="min-width:0">
        <h2>Raw material consumption</h2>
        @if($mat['years']->isNotEmpty())
            <div class="ci-metrics">
                <div class="ci-metric"><span>FY {{ $mat['years']->last() }}</span><b>{{ $money($mat['consumed']->last()) }}</b>{!! $change($mat['latest_yoy']) !!}</div>
                <div class="ci-metric"><span>Forecast FY {{ $mat['forecast_year'] ?? '—' }}</span><b>{{ $money($mat['forecast']) }}</b>{!! $change($mat['forecast_change']) !!}</div>
                <div class="ci-metric"><span>Share of revenue</span><b>{{ $mat['intensity']->last() === null ? '—' : number_format($mat['intensity']->last(), 1) . '%' }}</b></div>
            </div>
            <div class="ci-chart"><canvas aria-label="Materials consumed by year with next-year forecast, ₹ crore" data-chart="{{ json_encode([
                'type' => 'bar',
                'data' => ['labels' => $fy($mat['years'])->push($mat['forecast'] !== null ? 'FY ' . $mat['forecast_year'] . ' (forecast)' : null)->filter()->values(), 'datasets' => [
                    ['label' => 'Raw materials', 'data' => $mat['raw'], 'backgroundColor' => '#527fad', 'stack' => 'm', 'unit' => ' Cr'],
                    ['label' => 'Traded goods', 'data' => $mat['traded'], 'backgroundColor' => '#9cb6d3', 'stack' => 'm', 'unit' => ' Cr'],
                    ['label' => 'Forecast', 'data' => $mat['forecast'] !== null ? $mat['years']->map(fn () => null)->push(round($mat['forecast'] / 10000000, 2))->values() : [], 'backgroundColor' => 'rgba(245,184,0,.55)', 'borderColor' => '#f5b800', 'borderWidth' => 1, 'borderDash' => [4, 3], 'stack' => 'm', 'unit' => ' Cr'],
                    ['type' => 'line', 'label' => 'Share of revenue', 'data' => $mat['intensity'], 'borderColor' => '#b42318', 'backgroundColor' => '#b42318', 'yAxisID' => 'pct', 'tension' => .3, 'unit' => '%'],
                ]],
                'options' => ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'title' => ['display' => true, 'text' => '₹ Cr']],
                    'pct' => ['position' => 'right', 'grid' => ['drawOnChartArea' => false], 'title' => ['display' => true, 'text' => '% of revenue']]]],
            ]) }}"></canvas></div>
        @else
            <p class="ci-muted">No cost of materials reported in the filings on record.</p>
        @endif
    </section>

    <section class="ci-card" id="payments" style="min-width:0">
        <h2>Payment behaviour</h2>
        @if($pay['latest_dpo'] !== null)
            <div class="ci-metrics">
                <div class="ci-metric"><span>Pays suppliers in</span><b>{{ $pay['latest_dpo'] }} days</b>@if($pay['band'])<small class="{{ $pay['band'][1] }}" style="font-weight:700">{{ $pay['band'][0] }}</small>@endif</div>
                <div class="ci-metric"><span>Collects from customers in</span><b>{{ $pay['dso']->filter(fn ($v) => $v !== null)->last() ?? '—' }} days</b></div>
            </div>
            <div class="ci-chart"><canvas aria-label="Days payable and days receivable by year" data-chart="{{ json_encode([
                'type' => 'line',
                'data' => ['labels' => $fy($pay['years']), 'datasets' => [
                    ['label' => 'Days to pay suppliers', 'data' => $pay['dpo'], 'borderColor' => '#b45309', 'backgroundColor' => '#b45309', 'tension' => .3, 'unit' => ' days'],
                    ['label' => 'Days to collect from customers', 'data' => $pay['dso'], 'borderColor' => '#527fad', 'backgroundColor' => '#527fad', 'tension' => .3, 'unit' => ' days'],
                ]],
                'options' => ['scales' => ['y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Days']]]],
            ]) }}"></canvas></div>
        @else
            <p class="ci-muted">Needs trade payables and expenses from the filings.</p>
        @endif
    </section>
</div>

<section class="ci-card" id="concentration">
    <h2>Revenue concentration</h2>
    @if($con['rows']->isNotEmpty())
        <p class="ci-muted">FY {{ $con['year'] }} · sales to related parties: {{ $con['top_share'] !== null ? number_format($con['top_share'], 1) . '% of revenue' : '—' }}</p>
        <div class="ci-two">
            <div class="ci-chart"><canvas aria-label="Share of revenue from each related party" data-chart="{{ json_encode([
                'type' => 'bar',
                'data' => ['labels' => $con['rows']->pluck('name'), 'datasets' => [['label' => 'Share of revenue', 'data' => $con['rows']->pluck('share'), 'backgroundColor' => '#0b2545', 'unit' => '%']]],
                'options' => ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]]],
            ]) }}"></canvas></div>
            <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Party</th><th>₹ Cr</th><th>% of revenue</th></tr></thead><tbody>
                @foreach($con['rows'] as $row)<tr><td style="white-space:normal">{{ $row['name'] }}</td><td>{{ number_format($row['amount'], 2) }}</td><td>{{ $row['share'] === null ? '—' : number_format($row['share'], 1) . '%' }}</td></tr>@endforeach
            </tbody></table></div>
        </div>
    @else
        <p class="ci-muted">No related-party sales disclosed in the filings on record.</p>
    @endif
</section>
