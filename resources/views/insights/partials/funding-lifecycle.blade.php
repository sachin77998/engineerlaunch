{{-- Borrowing & funding lifecycle: MCA charges, rated limits and funding events from rating reports, balance-sheet debt, and borrowing headroom. --}}
@php
    $cap = $funding['capacity'];
    $cr = fn ($v) => $v === null ? '—' : '₹' . \App\Services\Insights\CompanyInsights::indian((float) $v, $v >= 100 ? 0 : 2) . ' Cr';
    $verdictClass = str_contains($cap['verdict'], 'likely') ? 'up' : (str_contains($cap['verdict'], 'difficult') ? 'down' : '');
    $icons = ['charge_created' => '🏦', 'charge_modified' => '✏️', 'charge_satisfied' => '✅', 'rating' => '⭐', 'funding_inflow' => '💰', 'funding_repayment' => '↩️', 'funding_borrowing' => '➕',
        'funding_capex' => '🏭', 'funding_utilisation' => '📊', 'funding_liquidity' => '💧', 'funding_obligation' => '📅', 'facility_drop' => '📉', 'facility_headroom' => '🟢', 'debt_up' => '⬆️', 'debt_down' => '⬇️'];
    $palette = ['#2563eb', '#22c55e', '#f97316', '#a855f7', '#06b6d4', '#eab308', '#ef4444', '#64748b'];
    $types = collect($funding['facilities'])->flatMap(fn ($f) => array_keys($f['by_type']))->unique()->values();
    $facChart = count($funding['facilities']) ? ['type' => 'bar', 'data' => ['labels' => array_map(fn ($f) => $f['agency'] . ' ' . \Carbon\Carbon::parse($f['date'])->format('M Y'), $funding['facilities']),
        'datasets' => $types->map(fn ($t, $i) => ['label' => $t, 'data' => array_map(fn ($f) => $f['by_type'][$t] ?? 0, $funding['facilities']), 'backgroundColor' => $palette[$i % 8], 'unit' => ' Cr', 'stack' => 'a'])->all()],
        'options' => ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'title' => ['display' => true, 'text' => '₹ crore']]]]] : null;
    $debt = collect($funding['debt'])->filter(fn ($d) => $d['total'] !== null || $d['long_term'] !== null);
    $debtChart = $debt->count() > 1 ? ['type' => 'bar', 'data' => ['labels' => $debt->pluck('year')->all(), 'datasets' => [
        ['label' => 'Long-term borrowings', 'data' => $debt->pluck('long_term')->all(), 'backgroundColor' => '#2563eb', 'unit' => ' Cr', 'stack' => 'd'],
        ['label' => 'Short-term borrowings', 'data' => $debt->pluck('short_term')->all(), 'backgroundColor' => '#93c5fd', 'unit' => ' Cr', 'stack' => 'd'],
        ['type' => 'line', 'label' => 'EBITDA', 'data' => $debt->pluck('ebitda')->all(), 'borderColor' => '#16a34a', 'backgroundColor' => '#16a34a', 'unit' => ' Cr', 'tension' => .3]]],
        'options' => ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'title' => ['display' => true, 'text' => '₹ crore']]]]] : null;
@endphp
<section class="ci-card" id="funding-lifecycle" style="grid-column:1/-1;min-width:0">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center">
        <h2 style="margin:0">Borrowing &amp; funding lifecycle</h2>
        <span style="display:flex;gap:8px"><a class="ci-btn" href="{{ route('insights.report', [$company, 'pdf']) }}">⬇ PDF report</a><a class="ci-btn" href="{{ route('insights.report', [$company, 'xlsx']) }}">⬇ Excel</a></span>
    </div>

    <div class="ci-facts" style="margin-top:14px">
        <div style="grid-column:span 2"><span>Can it borrow more?</span><b class="{{ $verdictClass }}" style="font-size:18px">{{ $cap['verdict'] }}</b>
            @if($cap['outlook'])<small style="display:block">Next rating action stable or better: <b>{{ round($cap['outlook']['probability'] * 100) }}%</b> ({{ $cap['outlook']['label'] }}){{ $cap['model'] ? ' · ML model on ' . $cap['model']['pairs'] . ' rating histories' . (isset($cap['model']['test_auc']) ? ', AUC ' . $cap['model']['test_auc'] : '') : ' · scorecard until enough rating history is collected' }}</small>@endif</div>
        <div><span>Rated limits</span><b>{{ $cr($cap['rated_total']) }}</b></div>
        <div><span>Not yet allocated</span><b>{{ $cr($cap['unallocated'] ?: null) }}</b></div>
        <div><span>Extra debt within {{ $cap['debt_cap_multiple'] }}× EBITDA</span><b>{{ $cr($cap['room_crore']) }}</b></div>
        <div><span>Interest coverage</span><b>{{ $cap['interest_coverage'] !== null ? number_format($cap['interest_coverage'], 2) . '×' : '—' }}</b></div>
        <div><span>Limit utilisation</span><b>{{ $cap['utilisation'] !== null ? $cap['utilisation'] . '%' : '—' }}</b></div>
        <div><span>Open bank charges</span><b>{{ $funding['open_charges']['count'] }} · {{ $cr($funding['open_charges']['amount'] ?: null) }}</b></div>
    </div>
    @if($cap['why'])<ul style="margin:10px 0 0;padding-left:18px;line-height:1.7">@foreach($cap['why'] as $w)<li>{{ $w }}</li>@endforeach</ul>@endif

    @if($funding['stories'])
        <h3>What happened, step by step</h3>
        @foreach($funding['stories'] as $s)
            <div class="ci-card" style="margin:10px 0;padding:14px;box-shadow:none">
                <b>{{ $s['title'] }}</b>
                <ol style="margin:8px 0 0;padding-left:20px;line-height:1.7">@foreach($s['steps'] as $st)<li><span class="ci-muted">{{ $st['date']->format('M Y') }}</span> — {{ $st['text'] }}</li>@endforeach</ol>
                @if($s['evidence'])<p class="ci-muted" style="font-size:12.5px;margin:8px 0 0">“{{ \Illuminate\Support\Str::limit($s['evidence'], 320) }}”</p>@endif
            </div>
        @endforeach
    @endif

    @if($facChart || $debtChart)
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-top:14px">
            @if($facChart)<div><h3 style="margin-top:0">Rated limits by facility</h3><div class="ci-chart" style="height:260px"><canvas aria-label="Rated limits by facility type over time" data-chart='@json($facChart)'></canvas></div></div>@endif
            @if($debtChart)<div><h3 style="margin-top:0">Borrowings vs EBITDA (audited)</h3><div class="ci-chart" style="height:260px"><canvas aria-label="Borrowings and EBITDA by year" data-chart='@json($debtChart)'></canvas></div></div>@endif
        </div>
    @endif

    @if($funding['lenders']->isNotEmpty())
        <h3>Lenders (MCA charges)</h3>
        <div style="overflow-x:auto"><table class="ci-table"><thead><tr><th>Lender</th><th>Charges</th><th>Open</th><th>Open amount</th><th>First loan</th><th>Last closed</th></tr></thead><tbody>
        @foreach($funding['lenders'] as $l)<tr><td><b>{{ $l['lender'] }}</b></td><td>{{ $l['charges'] }}</td><td>{{ $l['open'] }}</td><td>{{ $cr($l['amount_open'] ?: null) }}</td><td>{{ $l['first'] ? \Carbon\Carbon::parse($l['first'])->format('M Y') : '—' }}</td><td>{{ $l['last_closed'] ? \Carbon\Carbon::parse($l['last_closed'])->format('M Y') : '—' }}</td></tr>@endforeach
        </tbody></table></div>
    @endif

    <h3>Timeline</h3>
    <div style="max-height:520px;overflow-y:auto;border:1px solid var(--line, #e3e9f2);border-radius:12px">
        @foreach($funding['timeline'] as $e)
            <div style="display:grid;grid-template-columns:84px 28px 1fr auto;gap:10px;padding:10px 12px;border-bottom:1px solid #eef2f7;align-items:start">
                <span class="ci-muted" style="font-size:12.5px">{{ $e['date']->format('d M Y') }}</span><span aria-hidden="true">{{ $icons[$e['kind']] ?? '•' }}</span>
                <span><b style="font-weight:600">{{ $e['title'] }}</b>@if(!empty($e['detail']))<small class="ci-muted" style="display:block">{{ $e['detail'] }}</small>@endif
                    @if(!empty($e['evidence']))<details><summary class="ci-muted" style="font-size:12px;cursor:pointer">From the report</summary><p style="font-size:12.5px;margin:4px 0 0">{{ $e['evidence'] }}</p></details>@endif</span>
                <span class="ci-muted" style="font-size:12px;white-space:nowrap">@if(!empty($e['url']))<a href="{{ $e['url'] }}" target="_blank" rel="noopener">{{ $e['source'] }}</a>@else{{ $e['source'] }}@endif</span>
            </div>
        @endforeach
    </div>
    @unless($funding['full'])<p class="ci-muted" style="margin-top:10px">Lender names, bank charges and audited borrowings are part of <a href="{{ route('premium.index') }}">Premium</a>.</p>@endunless
    <p class="ci-muted" style="font-size:12px;margin-top:10px">Built from MCA charges, ICRA/CARE rating reports and audited accounts. The borrowing view is an estimate, not a lender's credit decision.</p>
</section>
