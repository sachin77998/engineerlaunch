@php
    $fin = $analytics['financial'];
    $years = $fin['years']->map(fn ($y) => 'FY ' . $y)->values();
@endphp
@if($years->count() > 1)
<div class="ci-two" style="margin-top:20px">
    <section class="ci-card" style="min-width:0">
        <h3 class="ci-h3">Revenue vs Net Profit</h3>
        <div class="ci-chart"><canvas aria-label="Revenue and net profit by financial year, ₹ crore" data-chart="{{ json_encode([
            'type' => 'bar',
            'data' => ['labels' => $years, 'datasets' => [
                ['type' => 'bar', 'label' => 'Revenue', 'data' => $fin['series']['revenue']['crore'], 'backgroundColor' => '#527fad', 'borderRadius' => 4, 'unit' => ' Cr', 'order' => 2],
                ['type' => 'line', 'label' => 'Net profit', 'data' => $fin['series']['profit_after_tax']['crore'], 'borderColor' => '#43936c', 'backgroundColor' => '#43936c', 'tension' => .3, 'unit' => ' Cr', 'order' => 1, 'yAxisID' => 'pat'],
            ]],
            'options' => ['scales' => ['y' => ['title' => ['display' => true, 'text' => 'Revenue, ₹ Cr']], 'pat' => ['position' => 'right', 'grid' => ['drawOnChartArea' => false], 'title' => ['display' => true, 'text' => 'Net profit, ₹ Cr']]]],
        ]) }}"></canvas></div>
    </section>
    <section class="ci-card" style="min-width:0">
        <h3 class="ci-h3">Margin Trends</h3>
        <div class="ci-chart"><canvas aria-label="EBITDA and PAT margin by financial year" data-chart="{{ json_encode([
            'type' => 'line',
            'data' => ['labels' => $years, 'datasets' => [
                ['label' => 'EBITDA margin', 'data' => $fin['ebitda_margin'], 'borderColor' => '#527fad', 'backgroundColor' => 'rgba(82,127,173,.12)', 'fill' => true, 'tension' => .3, 'unit' => '%'],
                ['label' => 'PAT margin', 'data' => $fin['pat_margin'], 'borderColor' => '#43936c', 'backgroundColor' => 'rgba(67,147,108,.12)', 'fill' => true, 'tension' => .3, 'unit' => '%'],
            ]],
            'options' => ['scales' => ['y' => ['title' => ['display' => true, 'text' => '%']]]],
        ]) }}"></canvas></div>
    </section>
</div>
@endif
