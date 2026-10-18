<section class="ci-card" id="ownership">
<h2>Control &amp; Ownership</h2>
@php $snapshot = $dashboard['latest_ownership']; @endphp
<div class="ci-facts">
    <div><span>Latest parsed filing</span><b>{{ $dashboard['latest_filing']?->form ?? 'Unknown' }} {{ $dashboard['latest_filing']?->financial_year }}</b></div>
    <div><span>Total Equity Shares</span><b>{{ $snapshot && $snapshot['shares']!==null ? number_format($snapshot['shares']) : 'Unknown' }}</b></div>
    @foreach(['Promoter Holding','Public Holding','Institutional Holding','FII Holding','Mutual Fund Holding','Body Corporate','KMP / Directors'] as $label)<div><span>{{ $label }}</span><b>{{ ($snapshot['values'][$label]??null)===null?'Unknown':number_format($snapshot['values'][$label],2).'%' }}</b><small>{{ $snapshot['year']??'No filing year' }}</small></div>@endforeach
</div>
<p class="ci-muted">Ownership categories can overlap. FII and mutual funds may be included in institutional holdings; they must not be added together as separate ownership slices. Missing disclosures are not zero.</p>
@if($snapshot && $snapshot['values']['Promoter Holding']!==null && $snapshot['values']['Public Holding']!==null && abs($snapshot['values']['Promoter Holding']+$snapshot['values']['Public Holding']-100)<0.1)
@php
    $ownYears = $dashboard['ownership']->reverse()->values();
    $ownLabels = $ownYears->map(fn ($y) => 'FY ' . $y['year']);
@endphp
<div class="ci-two"><div style="min-width:0"><h3 class="ci-h3">Ownership Distribution</h3>
<div class="ci-chart"><canvas aria-label="Promoter and public holding, latest year" data-chart="{{ json_encode([
    'type' => 'doughnut',
    'data' => ['labels' => ['Promoter', 'Public'], 'datasets' => [['label' => 'Holding', 'data' => [$snapshot['values']['Promoter Holding'], $snapshot['values']['Public Holding']], 'backgroundColor' => ['#527fad', '#79b38b'], 'unit' => '%']]],
    'options' => ['cutout' => '60%', 'interaction' => ['mode' => 'nearest']],
]) }}"></canvas></div></div>
<div style="min-width:0"><h3 class="ci-h3">Year-wise Snapshot</h3>
<div class="ci-chart"><canvas aria-label="Promoter and public holding by year" data-chart="{{ json_encode([
    'type' => 'bar',
    'data' => ['labels' => $ownLabels, 'datasets' => [
        ['label' => 'Promoter %', 'data' => $ownYears->map(fn ($y) => $y['values']['Promoter Holding']), 'backgroundColor' => '#527fad', 'stack' => 'o', 'unit' => '%'],
        ['label' => 'Public %', 'data' => $ownYears->map(fn ($y) => $y['values']['Public Holding']), 'backgroundColor' => '#79b38b', 'stack' => 'o', 'unit' => '%'],
    ]],
    'options' => ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'max' => 100]]],
]) }}"></canvas></div></div></div>
@endif
<div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>FY</th><th>Total equity shares</th><th>Promoter</th><th>Public</th><th>Institutional</th><th>FII</th><th>Mutual funds</th><th>Body corporate</th><th>KMP / Directors</th></tr></thead><tbody>
@forelse($dashboard['ownership'] as $year)<tr><td>{{ $year['year'] }}</td><td>{{ $year['shares']===null?'Unknown':number_format($year['shares']) }}</td>@foreach($year['values'] as $value)<td>{{ $value===null?'Unknown':number_format($value,2).'%' }}</td>@endforeach</tr>@empty<tr><td colspan="9">Upload the MGT-7 or authorized shareholding data to populate ownership.</td></tr>@endforelse
</tbody></table></div>
</section>
