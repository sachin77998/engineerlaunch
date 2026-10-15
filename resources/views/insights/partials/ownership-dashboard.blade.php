<section class="ci-card" id="ownership">
<h2>Control &amp; Ownership</h2>
@php($snapshot=$dashboard['latest_ownership'])
<div class="ci-facts">
    <div><span>Latest parsed filing</span><b>{{ $dashboard['latest_filing']?->form ?? 'Unknown' }} {{ $dashboard['latest_filing']?->financial_year }}</b></div>
    <div><span>Total Equity Shares</span><b>{{ $snapshot && $snapshot['shares']!==null ? number_format($snapshot['shares']) : 'Unknown' }}</b></div>
    @foreach(['Promoter Holding','Public Holding','Institutional Holding','FII Holding','Mutual Fund Holding','Body Corporate','KMP / Directors'] as $label)<div><span>{{ $label }}</span><b>{{ ($snapshot['values'][$label]??null)===null?'Unknown':number_format($snapshot['values'][$label],2).'%' }}</b><small>{{ $snapshot['year']??'No filing year' }}</small></div>@endforeach
</div>
<p class="ci-muted">Ownership categories can overlap. FII and mutual funds may be included in institutional holdings; they must not be added together as separate ownership slices. Missing disclosures are not zero.</p>
@if($snapshot && $snapshot['values']['Promoter Holding']!==null && $snapshot['values']['Public Holding']!==null && abs($snapshot['values']['Promoter Holding']+$snapshot['values']['Public Holding']-100)<0.1)
<div class="ci-two"><div><h3 class="ci-h3">Ownership Distribution</h3><div class="ci-donut" style="background:conic-gradient(#527fad 0 {{ $snapshot['values']['Promoter Holding']*3.6 }}deg,#79b38b {{ $snapshot['values']['Promoter Holding']*3.6 }}deg 360deg)" role="img" aria-label="Promoter {{ $snapshot['values']['Promoter Holding'] }} percent, public {{ $snapshot['values']['Public Holding'] }} percent"></div><p class="ci-muted">Blue: promoter; green: public.</p></div>
<div><h3 class="ci-h3">Year-wise Snapshot</h3>@foreach($dashboard['ownership']->reverse() as $year)
<p>FY {{ $year['year'] }}</p><div style="display:flex;height:22px;background:#eef2f6;border-radius:4px;overflow:hidden">
@foreach(['Promoter Holding'=>'#527fad','Public Holding'=>'#79b38b'] as $key=>$color) @if($year['values'][$key]!==null)<span style="width:{{ $year['values'][$key] }}%;background:{{ $color }}" title="{{ $key }} {{ $year['values'][$key] }}%"></span>@endif @endforeach
</div>@endforeach</div></div>
@endif
<div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>FY</th><th>Total equity shares</th><th>Promoter</th><th>Public</th><th>Institutional</th><th>FII</th><th>Mutual funds</th><th>Body corporate</th><th>KMP / Directors</th></tr></thead><tbody>
@forelse($dashboard['ownership'] as $year)<tr><td>{{ $year['year'] }}</td><td>{{ $year['shares']===null?'Unknown':number_format($year['shares']) }}</td>@foreach($year['values'] as $value)<td>{{ $value===null?'Unknown':number_format($value,2).'%' }}</td>@endforeach</tr>@empty<tr><td colspan="9">Upload the MGT-7 or authorized shareholding data to populate ownership.</td></tr>@endforelse
</tbody></table></div>
</section>
