{{-- Fund credit quality (CQR) from the AMC's monthly portfolio, per the Fund Credit Quality methodology. --}}
@php
    $cq = $creditQuality;
    $dist = json_decode((string) $cq->distribution, true) ?: [];
    $conc = json_decode((string) $cq->concentration, true) ?: [];
    $flags = json_decode((string) $cq->flags, true) ?: [];
    $colors = ['Sovereign & cash' => '#0f766e', 'AAA / A1+' => '#16a34a', 'AA' => '#65a30d', 'A' => '#eab308', 'BBB & below' => '#dc2626', 'Unrated' => '#9333ea', 'Other' => '#94a3b8'];
    $file = \Illuminate\Support\Facades\DB::table('mf_portfolio_files')->where('id', $cq->file_id)->first();
    $icra = \App\Services\Funds\IcraFundQuality::forScheme((string) $cq->scheme_name);
    $anchor = $icra ? \App\Services\Funds\IcraFundQuality::lineAnchor($icra->amc) : null;
    $icraStatus = $icra ? match ($icra->status) {
        'provisional' => 'Provisional until three month-ends support it', 'curing' => 'Outside the band this month (' . $icra->implied . '); one curing month',
        'downgraded' => 'Revised this month', 'upgraded' => 'Upgraded after three supporting months', 'upgrade_pending' => 'Better this month (' . $icra->implied . '); upgrade needs three months', default => null,
    } : null;
    $top = \Illuminate\Support\Facades\DB::table('mf_portfolio_holdings')->where('scheme_key', $cq->scheme_key)->where('as_of', $cq->as_of)->whereNotIn('asset_type', ['cash'])->orderByDesc('pct_nav')->limit(10)->get();
@endphp
<section class="dt-card" id="credit-quality">
    <h2>Credit quality</h2>
    <div class="dt-kv" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr))">
        <div><span class="dt-muted">Portfolio credit quality</span><b style="display:block;font-size:22px">{{ $cq->cqr }}mfs{{ $cq->cqr_short ? ' · ' . $cq->cqr_short . 'mfs' : '' }}</b>
            @if($cq->curing)<small style="color:#b45309">This month's score ({{ $cq->implied }}) is outside the band; one month to realign</small>@elseif($cq->implied !== $cq->cqr)<small class="dt-muted">This month alone: {{ $cq->implied }}</small>@endif</div>
        <div><span class="dt-muted">Credit score / benchmark</span><b style="display:block">{{ number_format($cq->credit_score, 2) }} / {{ $cq->benchmark_score !== null ? number_format($cq->benchmark_score, 2) : '—' }} bps</b></div>
        <div><span class="dt-muted">Average maturity</span><b style="display:block">{{ $cq->wam_years < 1 ? round($cq->wam_years * 365) . ' days' : number_format($cq->wam_years, 2) . ' years' }}</b></div>
        @if($icra)
            <div><span class="dt-muted">ICRA method</span><b style="display:block;font-size:22px">{{ $icra->rating }}mfs{{ $icra->rating_short ? ' · ' . $icra->rating_short . 'mfs' : '' }}</b>
                <small class="dt-muted">Score {{ number_format($icra->credit_score, 2) }} vs {{ $icra->benchmark_score !== null ? number_format($icra->benchmark_score, 2) : '—' }} at {{ number_format($icra->maturity_considered, 2) }} yrs{{ $icra->category_maturity && $icra->category_maturity > $icra->wam_years ? ' (category maturity)' : '' }}</small>
                @if($icraStatus)<small style="display:block;color:#b45309">{{ $icraStatus }}</small>@endif</div>
        @endif
        <div><span class="dt-muted">Portfolio as on</span><b style="display:block">{{ \Carbon\Carbon::parse($cq->as_of)->format('d M Y') }}</b>@if($file)<a class="dt-muted" href="{{ $file->file_url }}" rel="nofollow noopener" target="_blank" style="font-size:12.5px">AMC disclosure</a>@endif</div>
    </div>

    <div style="display:flex;height:12px;border-radius:6px;overflow:hidden;margin:16px 0 8px;background:#eef2f7">
        @foreach($dist as $band => $pct)@if($pct > 0)<span title="{{ $band }} {{ $pct }}%" style="width:{{ min(100, $pct) }}%;background:{{ $colors[$band] ?? '#94a3b8' }}"></span>@endif @endforeach
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:6px 16px;font-size:13px">
        @foreach($dist as $band => $pct)<span><i style="display:inline-block;width:10px;height:10px;border-radius:3px;background:{{ $colors[$band] ?? '#94a3b8' }};margin-right:5px"></i>{{ $band }} {{ number_format($pct, 1) }}%</span>@endforeach
    </div>

    @if($anchor)
        <p class="dt-muted" style="margin:10px 0 0;font-size:12.5px">{{ $icra->amc }} bank borrowing and intraday lines anchor on its weakest short-duration debt scheme: {{ $anchor->scheme_name }} ({{ $anchor->rating }}mfs, {{ \Carbon\Carbon::parse($anchor->as_of)->format('M Y') }}).</p>
    @endif
    @if($conc)
        <p style="margin:14px 0 4px;font-weight:700;font-size:14px">Issuers above the single-issuer limit</p>
        <ul style="margin:0;padding-left:18px;font-size:13.5px;line-height:1.7">
            @foreach($conc as $c)<li>{{ $c['issuer'] }} — {{ $c['exposure'] }}% of NAV vs {{ rtrim(rtrim(number_format($c['limit'], 1), '0'), '.') }}% limit for {{ $c['rating'] }} (excess scored one notch lower)</li>@endforeach
        </ul>
    @endif
    @if(!empty($flags['unrated']) || !empty($flags['watch']) || !empty($flags['assumed_maturity']))
        <p class="dt-muted" style="margin:10px 0 0;font-size:12.5px">
            @if(!empty($flags['unrated']))Unrated paper scored at an internal BBB- view: {{ implode(', ', array_slice($flags['unrated'], 0, 3)) }}. @endif
            @if(!empty($flags['watch']))Rating watch / negative outlook notched down: {{ implode(', ', array_slice($flags['watch'], 0, 3)) }}. @endif
            @if(!empty($flags['assumed_maturity'])){{ $flags['assumed_maturity'] }} holding(s) without a published maturity scored at 3 years. @endif
        </p>
    @endif

    @if($top->isNotEmpty())
        <div style="overflow-x:auto;margin-top:14px"><table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead><tr style="text-align:left;color:var(--dt-mute);font-size:12px"><th style="padding:6px 8px">Top holdings</th><th style="padding:6px 8px">Rating</th><th style="padding:6px 8px;text-align:right">% of NAV</th><th style="padding:6px 8px;text-align:right">Maturity</th></tr></thead>
            <tbody>@foreach($top as $h)<tr style="border-top:1px solid var(--dt-line)">
                <td style="padding:6px 8px;min-width:180px">{{ $h->instrument }}</td>
                <td style="padding:6px 8px;white-space:nowrap">{{ $h->asset_type === 'sovereign' ? 'Sovereign' : ($h->rating_raw ?: ($h->asset_type === 'unrated' ? 'Unrated' : '—')) }}</td>
                <td style="padding:6px 8px;text-align:right">{{ number_format($h->pct_nav, 2) }}%</td>
                <td style="padding:6px 8px;text-align:right;white-space:nowrap">{{ $h->maturity_on ? \Carbon\Carbon::parse($h->maturity_on)->format('M Y') : '—' }}</td></tr>@endforeach</tbody>
        </table></div>
    @endif
</section>
