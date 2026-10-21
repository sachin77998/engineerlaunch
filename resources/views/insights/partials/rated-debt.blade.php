{{-- Borrowings & rated debt from rating rationales: limits over time, facility mix, debt instruments and maturity ladder. --}}
@php
    $d = $credit['debt'];
    $crore = fn ($v) => $v === null ? '—' : $money($v * 1e7);
    $palette = ['#1d4ed8', '#0f766e', '#f59e0b', '#7c3aed', '#dc2626', '#0891b2', '#65a30d', '#64748b'];
    $care = $credit['care'];
    $trendChart = ['type' => 'bar', 'data' => ['labels' => $d['trend']->map(fn ($t) => \Carbon\Carbon::parse($t['date'])->format('M Y') . ' · ' . $t['agency'])->all(),
        'datasets' => [['label' => 'Rated limits', 'unit' => ' Cr', 'backgroundColor' => '#1d4ed8', 'borderRadius' => 4, 'data' => $d['trend']->pluck('total')->all()]]], 'options' => ['plugins' => ['legend' => ['display' => false]]]];
    $mixChart = ['type' => 'doughnut', 'data' => ['labels' => $d['mix']->keys()->all(), 'datasets' => [['label' => '₹ crore', 'unit' => ' Cr', 'backgroundColor' => array_slice($palette, 0, $d['mix']->count()), 'data' => $d['mix']->values()->all()]]],
        'options' => ['interaction' => ['mode' => 'nearest'], 'plugins' => ['legend' => ['position' => 'right']]]];
    $ladderChart = ['type' => 'bar', 'data' => ['labels' => $d['ladder']->keys()->map(fn ($y) => (string) $y)->all(), 'datasets' => [['label' => 'Maturing', 'unit' => ' Cr', 'backgroundColor' => '#f59e0b', 'borderRadius' => 4, 'data' => $d['ladder']->values()->all()]]],
        'options' => ['plugins' => ['legend' => ['display' => false]]]];
@endphp
<section class="ci-card" id="rated-debt" style="grid-column:1/-1;min-width:0">
    <h2>Borrowings &amp; rated debt</h2>
    <div class="ci-facts">
        <div><span>Rated borrowing limits (latest)</span><b>{{ $crore($d['outstanding']) }}</b></div>
        @if($d['peak'] && $d['trend']->count() > 1)<div><span>Peak rated limits</span><b>{{ $crore($d['peak']['total']) }}</b><small style="display:block">{{ \Carbon\Carbon::parse($d['peak']['date'])->format('M Y') }} · {{ $d['peak']['agency'] }}</small></div>@endif
        <div><span>Limits raised</span><b>{{ $d['enhancements'] }} {{ \Illuminate\Support\Str::plural('time', $d['enhancements']) }}</b><small style="display:block">{{ $d['new_facilities'] }} new {{ \Illuminate\Support\Str::plural('facility', $d['new_facilities']) }} rated</small></div>
        @if($d['weighted_coupon'] !== null)<div><span>Weighted average coupon</span><b>{{ number_format($d['weighted_coupon'], 2) }}%</b></div>@endif
        @if($d['avg_tenor_years'] !== null)<div><span>Average remaining tenor</span><b>{{ $d['avg_tenor_years'] }} years</b></div>@endif
        @if($d['due_12m'])<div><span>Maturing in the next 12 months</span><b class="down">{{ $crore($d['due_12m']) }}</b></div>@endif
        @if($d['next_maturity'])<div><span>Next maturity</span><b>{{ \Carbon\Carbon::parse($d['next_maturity']['maturity_on'])->format('d M Y') }}</b><small style="display:block">{{ $crore($d['next_maturity']['amount_crore']) }}{{ $d['next_maturity']['isin'] ? ' · ' . $d['next_maturity']['isin'] : '' }}</small></div>@endif
    </div>

    <div class="ci-two" style="margin-top:12px">
        @if($d['trend']->count() >= 2)
            <div><h3>Rated limits over time</h3>
            <div class="ci-chart" style="height:240px"><canvas aria-label="Total rated borrowing limits by rating action" data-chart='@json($trendChart)'></canvas></div></div>
        @endif
        @if($d['mix']->isNotEmpty())
            <div><h3>Facility mix</h3>
            <div class="ci-chart" style="height:240px"><canvas aria-label="Rated limits by facility type" data-chart='@json($mixChart)'></canvas></div></div>
        @endif
        @if($d['ladder']->isNotEmpty())
            <div><h3>Maturity ladder</h3>
            <div class="ci-chart" style="height:240px"><canvas aria-label="Debt maturing by year" data-chart='@json($ladderChart)'></canvas></div></div>
        @endif
    </div>

    @if($d['facilities']->isNotEmpty())
        <h3>Rated facilities</h3>
        <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Facility</th><th>Type</th><th>Previous</th><th>Current</th><th>Change</th><th>Rating</th><th>Action</th><th>Agency</th></tr></thead><tbody>
        @foreach($d['facilities'] as $f)
            @php $change = ($f['previous'] ?? null) && ($f['current'] ?? null) !== null ? ($f['current'] - $f['previous']) / $f['previous'] * 100 : null; @endphp
            <tr><td style="white-space:normal">{{ $f['instrument'] }}</td><td>{{ $f['type'] }}</td><td>{{ $crore($f['previous'] ?? null) }}</td><td>{{ $crore($f['current'] ?? null) }}</td><td>{!! $change === null ? '—' : $pct($change) !!}</td>
                <td>{{ collect([$f['rating'] ?? null, $f['rating_short'] ?? null])->filter()->implode(' / ') ?: '—' }}{{ ($f['outlook'] ?? null) ? ' (' . $f['outlook'] . ')' : '' }}</td><td>{{ ucfirst((string) ($f['action'] ?? '')) }}</td><td>{{ $f['agency'] }}</td></tr>
        @endforeach
        </tbody></table></div>
    @endif

    @if($d['instruments']->isNotEmpty())
        <h3>Debt instruments</h3>
        <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>ISIN</th><th>Instrument</th><th>Type</th><th>Coupon</th><th>Issued</th><th>Maturity</th><th>Amount</th><th>Rating</th><th>Status</th></tr></thead><tbody>
        @foreach($d['instruments']->take(40) as $i)
            @php $matured = ($i['maturity_on'] ?? null) && \Carbon\Carbon::parse($i['maturity_on'])->isPast(); @endphp
            <tr><td>{{ $i['isin'] ?? '—' }}</td><td style="white-space:normal">{{ $i['instrument'] ?: '—' }}</td><td>{{ $i['type'] }}</td><td>{{ ($i['coupon'] ?? null) !== null ? number_format($i['coupon'], 2) . '%' : '—' }}</td>
                <td>{{ ($i['issued_on'] ?? null) ? \Carbon\Carbon::parse($i['issued_on'])->format('d M Y') : '—' }}</td><td>{{ ($i['maturity_on'] ?? null) ? \Carbon\Carbon::parse($i['maturity_on'])->format('d M Y') : '—' }}</td>
                <td>{{ $crore($i['amount_crore'] ?? null) }}</td><td>{{ $i['rating'] ?? '—' }}</td>
                <td class="{{ ($i['withdrawn'] ?? false) || $matured || !($i['maturity_on'] ?? null) ? 'ci-muted' : 'up' }}">{{ ($i['withdrawn'] ?? false) ? 'Withdrawn' : ($matured ? 'Matured' : (($i['maturity_on'] ?? null) ? 'Outstanding' : 'Not stated')) }}</td></tr>
        @endforeach
        </tbody></table></div>
    @endif

    @if($care && ($care->current || $care->lenders))
        <div class="ci-two" style="margin-top:12px">
            @if($care->current)
                <div><h3>CARE ratings on record <small class="ci-muted">(checked {{ $care->checked_at?->format('d M Y') }})</small></h3>
                <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Instrument</th><th>Amount</th><th>Rating</th></tr></thead><tbody>
                @foreach($care->current as $c)<tr><td>{{ $c['instrument'] }}</td><td>{{ $crore($c['amount_crore'] ?: null) }}</td><td>{{ $c['rating'] }}</td></tr>@endforeach
                </tbody></table></div></div>
            @endif
            @if($care->lenders)
                <div><h3>Lenders</h3>
                <div class="ci-table-wrap"><table class="ci-table"><tbody>
                @foreach($care->lenders as $l)<tr>@foreach(array_slice($l, 0, 3) as $v)<td>{{ $v }}</td>@endforeach</tr>@endforeach
                </tbody></table></div></div>
            @endif
        </div>
    @endif
</section>
