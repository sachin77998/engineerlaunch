{{-- Credit ratings from ICRA / CARE rationales: current ratings, score history, actions, rating drivers and agency financials. --}}
@php
    $crore = fn ($v) => $v === null ? '—' : $money($v * 1e7);
    $notch = fn ($v) => $v === null ? '—' : ($v === 0 ? 'No change' : '<span class="' . ($v > 0 ? 'up' : 'down') . '">' . ($v > 0 ? '▲ ' : '▼ ') . abs($v) . ' ' . \Illuminate\Support\Str::plural('notch', abs($v)) . '</span>');
    $actionClass = fn ($a) => match ($a) { 'upgraded' => 'up', 'downgraded', 'non-cooperating' => 'down', default => '' };
    $drivers = $credit['history']->first(fn ($r) => !empty($r->strengths) || !empty($r->challenges));
    $sens = $credit['history']->first(fn ($r) => !empty($r->sensitivities));
    $fmtVal = fn ($v, $unit) => $v === null ? '—' : ($unit === '%' ? number_format($v, 1) . '%' : ($unit === 'x' ? number_format($v, 2) . '×' : '₹' . \App\Services\Insights\CompanyInsights::indian($v, $v >= 100 ? 0 : 2) . ' Cr'));
@endphp
<section class="ci-card" id="credit-ratings" style="grid-column:1/-1;min-width:0">
    <h2>Credit ratings</h2>
    <div class="ci-facts">
        @foreach($credit['current'] as $r)
            <div><span>{{ $r->agency }} · {{ $r->published_on->format('d M Y') }}</span>
                <b style="font-size:18px">{{ $r->withdrawn ? 'Withdrawn' : ($r->rating_long ? $r->agency . ' ' . $r->rating_long : ($r->rating_short ? $r->agency . ' ' . $r->rating_short : '—')) }}</b>
                <small style="display:block">{{ collect([$r->outlook ? $r->outlook . ' outlook' : null, $r->rating_long && $r->rating_short ? 'Short term ' . $r->rating_short : null, ucfirst((string) $r->action)])->filter()->implode(' · ') }}</small></div>
        @endforeach
        @if($credit['care'] && $credit['care']->current && !$credit['current']->contains('agency', 'CARE'))
            @php $live = collect($credit['care']->current)->reject(fn ($i) => in_array(strtolower($i['rating']), ['withdrawn', ''], true)); @endphp
            <div><span>CARE · current</span><b>{{ $live->first()['rating'] ?? 'Withdrawn' }}</b><small style="display:block">{{ count($credit['care']->current) }} rated {{ \Illuminate\Support\Str::plural('instrument', count($credit['care']->current)) }}</small></div>
        @endif
        <div><span>Change in last 12 months</span><b>{!! $notch($credit['notches_1y']) !!}</b></div>
        <div><span>Change since first rating on record</span><b>{!! $notch($credit['notches_total']) !!}</b>@if($credit['counts']['since'])<small style="display:block">since {{ $credit['counts']['since']->format('M Y') }}</small>@endif</div>
        <div><span>Rating actions</span><b>{{ $credit['counts']['actions'] }}</b><small style="display:block"><em class="up" style="font-style:normal">{{ $credit['counts']['upgrades'] }} up</em> · <em class="down" style="font-style:normal">{{ $credit['counts']['downgrades'] }} down</em></small></div>
        @if($credit['latest']?->liquidity)<div><span>Liquidity ({{ $credit['latest']->agency }})</span><b class="{{ in_array($credit['latest']->liquidity, ['Superior', 'Strong']) ? 'up' : (in_array($credit['latest']->liquidity, ['Stretched', 'Poor']) ? 'down' : '') }}">{{ $credit['latest']->liquidity }}</b></div>@endif
    </div>
    @foreach($credit['flags'] as [$level, $text])
        <p class="ci-risk" style="margin-top:10px"><span class="lvl {{ $level === 'bad' ? 'high' : ($level === 'good' ? 'ok' : 'moderate') }}">{{ $level === 'bad' ? 'Watch' : ($level === 'good' ? 'Positive' : 'Note') }}</span>{{ $text }}</p>
    @endforeach

    @if($credit['chart'])
        @php
            $dates = collect($credit['chart']['datasets'])->flatMap(fn ($d) => collect($d['data'])->pluck('x'))->unique()->sort()->values();
            $datasets = collect($credit['chart']['datasets'])->map(fn ($d) => ['label' => $d['label'], 'stepped' => true, 'spanGaps' => true, 'borderColor' => $d['borderColor'], 'backgroundColor' => $d['backgroundColor'], 'pointRadius' => 4,
                'data' => $dates->map(fn ($x) => collect($d['data'])->firstWhere('x', $x)['y'] ?? null)->all()])->all();
            $scores = collect($credit['chart']['datasets'])->flatMap(fn ($d) => collect($d['data'])->pluck('y'));
            $ratingChart = ['type' => 'line', 'data' => ['labels' => $dates->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M Y'))->all(), 'datasets' => $datasets],
                'tickLabels' => ['axis' => 'y', 'labels' => $credit['chart']['labels']], 'options' => ['scales' => ['y' => ['min' => max(1, $scores->min() - 2), 'max' => min(20, $scores->max() + 1)]]]];
        @endphp
        <h3>Rating history</h3>
        <div class="ci-chart" style="height:240px"><canvas aria-label="Long-term credit rating over time" data-chart='@json($ratingChart)'></canvas></div>
    @endif

    <h3>Rating actions</h3>
    <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Date</th><th>Agency</th><th>Action</th><th>Long term</th><th>Short term</th><th>Outlook</th><th>Rated amount</th><th>Rationale</th></tr></thead><tbody>
    @foreach($credit['history']->take(25) as $r)
        <tr><td>{{ $r->published_on->format('d M Y') }}</td><td>{{ $r->agency }}</td><td class="{{ $actionClass($r->action) }}">{{ ucfirst((string) $r->action) }}{{ $r->previous_rating_long ? ' (from ' . $r->previous_rating_long . ')' : '' }}</td>
            <td>{{ $r->rating_long ?? '—' }}</td><td>{{ $r->rating_short ?? '—' }}</td><td>{{ $r->outlook ?? '—' }}</td><td>{{ $crore($r->total_rated_crore) }}</td>
            <td>@if($r->source_url)<a href="{{ $r->source_url }}" target="_blank" rel="noopener">View</a>@else—@endif</td></tr>
    @endforeach
    </tbody></table></div>

    @if($drivers)
        <div class="ci-two" style="margin-top:14px">
            <div><h3>Credit strengths <small class="ci-muted">({{ $drivers->agency }}, {{ $drivers->published_on->format('M Y') }})</small></h3>
                @forelse($drivers->strengths ?? [] as $s)<details class="ci-risk" style="display:block"><summary><b class="up">+</b> {{ $s['title'] }}</summary><p class="ci-muted" style="margin:6px 0 0">{{ $s['detail'] }}</p></details>@empty<p class="ci-muted">—</p>@endforelse</div>
            <div><h3>Credit challenges</h3>
                @forelse($drivers->challenges ?? [] as $s)<details class="ci-risk" style="display:block"><summary><b class="down">−</b> {{ $s['title'] }}</summary><p class="ci-muted" style="margin:6px 0 0">{{ $s['detail'] }}</p></details>@empty<p class="ci-muted">—</p>@endforelse</div>
        </div>
    @endif
    @if($sens)
        <div class="ci-two" style="margin-top:6px">
            @if($sens->sensitivities['positive'] ?? null)<div><h3>Could lead to an upgrade</h3><p class="ci-muted">{{ $sens->sensitivities['positive'] }}</p></div>@endif
            @if($sens->sensitivities['negative'] ?? null)<div><h3>Could lead to a downgrade</h3><p class="ci-muted">{{ $sens->sensitivities['negative'] }}</p></div>@endif
        </div>
    @endif

    @if($fin = $credit['financials'])
        <h3>Agency-reported financials <small class="ci-muted">({{ $fin['agency'] }}, {{ $fin['as_of']->format('d M Y') }})</small></h3>
        <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Metric</th>@foreach($fin['periods'] as $p)<th>{{ $p['label'] }}</th>@endforeach</tr></thead><tbody>
        @foreach($fin['rows'] as $row)<tr><td style="white-space:normal">{{ $row['label'] }}</td>@foreach($row['values'] as $v)<td>{{ $fmtVal($v, $row['unit']) }}</td>@endforeach</tr>@endforeach
        @foreach($fin['derived'] as $row)<tr><td style="white-space:normal"><b>{{ $row['label'] }}</b></td>@foreach($row['values'] as $v)<td>{!! $row['unit'] === '%' && $v !== null ? $pct($v) : e($fmtVal($v, $row['unit'])) !!}</td>@endforeach</tr>@endforeach
        </tbody></table></div>
    @endif
    @if($credit['latest']?->about && $company->financialStatements->isEmpty())
        <p class="ci-muted" style="margin-top:10px">{{ $credit['latest']->about }}</p>
    @endif
</section>
