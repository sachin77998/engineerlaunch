@if($competitors->isNotEmpty())
@php $byRegion = $competitors->countBy('region')->sortDesc(); @endphp
<section class="ci-card" id="competitors" data-nav="Competitors">
    <h2>Competitors of {{ $company->name }}</h2>
    <div class="pf-metrics" style="grid-template-columns:repeat(auto-fit,minmax(170px,220px))">
        <div><span>Competitors</span><b>{{ $competitors->count() }}</b><small>identified</small></div>
        <div><span>{{ $usOnly ? 'Countries' : 'Locations' }}</span><b>{{ $byRegion->count() }}</b><small>most in {{ $byRegion->keys()->first() }}</small></div>
    </div>
    <h3 class="ci-h3" style="margin-top:14px">Top competitors by location</h3>
    <div class="ci-chart" style="height:{{ 60 + 34 * min(8, $byRegion->count()) }}px"><canvas aria-label="Competitors by location" data-chart="{{ json_encode([
        'type' => 'bar',
        'data' => ['labels' => $byRegion->keys()->take(8)->values(), 'datasets' => [['label' => 'Competitors', 'data' => $byRegion->values()->take(8), 'backgroundColor' => '#4a7cc9', 'borderRadius' => 3]]],
        'options' => ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['ticks' => ['precision' => 0]]]],
    ]) }}"></canvas></div>
    <div class="ci-table-wrap" style="margin-top:12px"><table class="ci-table">
        <thead><tr><th style="text-align:left">Company</th><th style="text-align:left">Description</th><th>Domain</th><th>Founded</th><th style="text-align:left">Location</th></tr></thead>
        <tbody>
        @foreach($competitors as $c)
            <tr>
                <td style="white-space:normal;text-align:left"><span class="pf-co">@if($c->logo)<img src="{{ $c->logo }}" alt="" loading="lazy">@endif<a href="{{ route('insights.show', $c->slug) }}">{{ $c->name }}</a></span></td>
                <td style="white-space:normal;text-align:left;max-width:360px">{{ \Illuminate\Support\Str::limit($c->description, 110) ?: '—' }}</td>
                <td>@if($c->website)<a href="{{ $c->website }}" target="_blank" rel="noopener nofollow">{{ preg_replace('~^https?://(www\.)?~', '', rtrim($c->website, '/')) }}</a>@else — @endif</td>
                <td>{{ $c->founded ?? '—' }}</td>
                <td style="white-space:normal;text-align:left">{{ $c->location ?: '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endif
