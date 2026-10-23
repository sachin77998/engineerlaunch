{{-- Sector credit assessment (CareEdge methodologies): indicative rating, pillars, sector ratios vs bands and peers, policy status. --}}
@php
    $a = $careAssessment;
    $fmt = function ($r) {
        if ($r['value'] === null) return '—';
        $v = $r['value'];
        return str_contains($r['label'], '%') ? number_format($v, 1) . '%' : (str_contains($r['label'], 'days') ? number_format($v) . ' d' : (str_contains($r['label'], '₹') ? \App\Services\Insights\CompanyInsights::indian($v, $v >= 100 ? 0 : 2) : number_format($v, 2) . '×'));
    };
    $bandText = fn ($r) => ($r['dir'] === 'up' ? '≥ ' : '≤ ') . $r['band'][1] . ' good · ' . ($r['dir'] === 'up' ? '< ' : '> ') . $r['band'][3] . ' weak';
    $dot = fn ($s) => $s === null ? '#cbd5e1' : ($s >= 4 ? '#16a34a' : ($s === 3 ? '#eab308' : '#dc2626'));
    $pillarNames = ['business' => 'Business risk', 'financial' => 'Financial risk', 'liquidity' => 'Liquidity', 'management' => 'Management & governance'];
    $p = $a['policy'];
@endphp
<section class="ci-card" id="care-assessment" style="grid-column:1/-1;min-width:0">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:baseline">
        <h2 style="margin:0">Credit assessment · {{ $a['sector'] }}</h2>
        <span class="ci-muted" style="font-size:12.5px">{{ $a['criteria'] }} · {{ ucfirst($a['basis']) }} accounts{{ $a['year'] ? ' · FY ' . $a['year'] : '' }}</span>
    </div>

    <div class="ci-facts" style="margin-top:14px">
        <div><span>Indicative long-term</span><b style="font-size:20px">{{ $a['indicative'] ?? '—' }}</b>@if($a['standalone'] && $a['standalone'] !== $a['indicative'])<small style="display:block">Standalone {{ $a['standalone'] }}</small>@endif</div>
        <div><span>Indicative short-term</span><b style="font-size:20px">{{ $a['short_term'] ?? '—' }}</b></div>
        <div><span>Score</span><b>{{ $a['score'] !== null ? number_format($a['score'], 1) . ' / 100' : '—' }}</b></div>
        <div><span>Liquidity</span><b>{{ $a['liquidity']['level'] ?? 'Not assessable' }}</b>@if($a['liquidity']['agency'])<small style="display:block">Agency: {{ $a['liquidity']['agency'] }}</small>@endif</div>
        <div><span>Direction</span><b>{{ $a['outlook']['watch'] ? 'Rating watch – ' . $a['outlook']['watch']['type'] : $a['outlook']['direction'] }}</b>@if($a['outlook']['agency'])<small style="display:block">Agency outlook: {{ $a['outlook']['agency'] }}</small>@endif</div>
        @if($a['agency'])<div><span>{{ $a['agency']['agency'] }} rating · {{ $a['agency']['date']->format('M Y') }}</span><b>{{ $a['agency']['rating'] ?? '—' }}</b>@if($a['agency']['gap'] !== null)<small style="display:block">{{ $a['agency']['gap'] === 0 ? 'Matches indicative band' : abs($a['agency']['gap']) . ' notch' . (abs($a['agency']['gap']) > 1 ? 'es' : '') . ($a['agency']['gap'] > 0 ? ' below' : ' above') . ' indicative' }}</small>@endif</div>@endif
    </div>

    @if($a['adjustments'])
        <ul style="margin:10px 0 0;padding-left:18px;line-height:1.7">@foreach($a['adjustments'] as $x)<li><b>{{ $x['from'] }} → {{ $x['to'] }}</b>: {{ $x['why'] }}</li>@endforeach</ul>
    @endif

    @if($a['pillars'])
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-top:14px">
            @foreach($a['pillars'] as $key => $pl)
                @continue($pl['weight'] === 0)
                <div style="border:1px solid #e3e9f2;border-radius:12px;padding:10px 12px">
                    <div class="ci-muted" style="font-size:12px">{{ $pillarNames[$key] }} · weight {{ $pl['weight'] }}%</div>
                    <b style="font-size:18px">{{ $pl['score'] !== null ? round($pl['score']) : '—' }}</b>
                    <div style="height:6px;background:#eef2f7;border-radius:4px;margin-top:6px"><div style="height:6px;border-radius:4px;width:{{ (int) ($pl['score'] ?? 0) }}%;background:{{ ($pl['score'] ?? 0) >= 70 ? '#16a34a' : (($pl['score'] ?? 0) >= 50 ? '#eab308' : '#dc2626') }}"></div></div>
                </div>
            @endforeach
        </div>
    @endif

    @if($a['rows'])
        <h3>Ratios ({{ $a['sector'] }} bands)</h3>
        <div style="overflow-x:auto"><table class="ci-table"><thead><tr><th>Ratio</th><th style="text-align:right">Company</th><th style="text-align:right">Sector median</th><th>Band</th><th></th></tr></thead><tbody>
        @foreach($a['rows'] as $r)
            <tr><td>{{ $r['label'] }}</td><td style="text-align:right"><b>{{ $fmt($r) }}</b></td><td style="text-align:right" class="ci-muted">{{ $r['peer'] !== null ? $fmt(['value' => $r['peer']] + $r) : '—' }}</td>
                <td class="ci-muted" style="font-size:12.5px;white-space:nowrap">{{ $bandText($r) }}</td><td><span title="{{ $r['score'] ?? 'n/a' }}/5" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $dot($r['score']) }}"></span></td></tr>
        @endforeach
        </tbody></table></div>
        @if($a['notes'])<p class="ci-muted" style="font-size:12.5px;margin:6px 0 0">{{ implode(' ', $a['notes']) }}</p>@endif
    @elseif(!$a['full'])
        <p class="ci-muted" style="margin-top:12px">Ratios, pillar scores and the indicative rating are part of <a href="{{ route('premium.index') }}">Premium</a>.</p>
    @endif

    @if($a['checks'])
        <h3>Sector checks</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:8px">
            @foreach($a['checks'] as $c)
                <div style="border:1px solid #e3e9f2;border-left:4px solid {{ $c['status'] === 'risk' ? '#dc2626' : ($c['status'] === 'watch' ? '#eab308' : '#16a34a') }};border-radius:10px;padding:8px 10px">
                    <b style="font-size:13.5px">{{ $c['label'] }}</b><div class="ci-muted" style="font-size:12.5px">{{ $c['value'] }}</div></div>
            @endforeach
        </div>
    @endif

    <h3>Rating status</h3>
    <div style="overflow-x:auto"><table class="ci-table"><tbody>
        <tr><td>Default</td><td>{{ $p['default']['in_default'] ? 'In default (CARE D)' : ($p['default']['last_default'] ? 'Last default ' . $p['default']['last_default']->format('d M Y') . ($p['default']['cured_on'] ? ', cured ' . $p['default']['cured_on']->format('d M Y') . ' (' . $p['default']['days_since_cure'] . ' days; investment grade needs 365)' : '') : 'No default on record') }}</td></tr>
        <tr><td>Issuer cooperation</td><td>{{ $p['inc']['active'] ? 'Not cooperating since ' . $p['inc']['since']->format('d M Y') . ' (' . $p['inc']['months'] . ' months)' . ($p['inc']['months'] >= 6 ? ' – investment grade cannot be retained' : '') : 'Cooperating' }}</td></tr>
        <tr><td>Credit enhancement</td><td>{{ $p['credit_enhanced'] ? $p['credit_enhanced']['rating'] . ' · ' . $p['credit_enhanced']['type'] . ' (' . $p['credit_enhanced']['agency'] . ', ' . $p['credit_enhanced']['date']->format('M Y') . ')' : 'None – standalone rating' }}</td></tr>
        @if($p['provisional'])<tr><td>Provisional rating</td><td>{{ $p['provisional']['rating'] }} since {{ $p['provisional']['date']->format('d M Y') }}; validity ends {{ $p['provisional']['expires']->format('d M Y') }} at the latest</td></tr>@endif
        @if($p['withdrawal'])<tr><td>Withdrawal</td><td>{{ $p['withdrawal']['agency'] }} · {{ $p['withdrawal']['date']->format('d M Y') }} · {{ $p['withdrawal']['reason'] }}</td></tr>@endif
        @if($a['outlook']['probability'] !== null)<tr><td>Next action stable or better (model)</td><td>{{ round($a['outlook']['probability'] * 100) }}%</td></tr>@endif
        <tr><td>Information on file</td><td>{{ $a['information']['score'] }}%@if($a['information']['missing']) · missing: {{ implode(', ', $a['information']['missing']) }}@endif</td></tr>
    </tbody></table></div>
    <p class="ci-muted" style="font-size:12px;margin-top:8px">Indicative assessment from public data using the published methodology factors; not a credit rating.</p>
</section>
