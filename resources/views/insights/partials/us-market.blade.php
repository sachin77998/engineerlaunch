@php
    $usd = fn ($v) => \App\Services\Insights\UsMarketData::money($v === null ? null : (float) $v);
    $primary = $company->usListings->first();
    $usYears = $primary->financials->sortBy('fiscal_year')->take(-6)->keyBy('fiscal_year');
    $usLatest = $usYears->last();
    $cur = $usLatest?->currency ?? 'USD';
    $fin = fn ($v) => \App\Services\Insights\UsMarketData::money($v === null ? null : (float) $v, $cur);
    // P/E only when results and share price are in the same currency.
    $pe = $usLatest && $cur === 'USD' && $primary->last_price && $usLatest->eps_diluted > 0 ? $primary->last_price / $usLatest->eps_diluted : null;
@endphp
<section class="ci-card" id="us-market">
    <h2>US market &amp; SEC filings</h2>
    <p class="ci-muted" style="margin-top:-4px">
        @foreach($company->usListings as $listing)<b>{{ $listing->ticker }}</b>{{ $listing->exchange ? ' · ' . $listing->exchange : '' }}@if(!$loop->last) &nbsp;|&nbsp; @endif @endforeach
        @if($primary->cik) &nbsp;·&nbsp; SEC CIK <a href="{{ $primary->edgarUrl() }}" target="_blank" rel="noopener">{{ str_pad((string) $primary->cik, 10, '0', STR_PAD_LEFT) }}</a>@endif
    </p>
    <div class="ci-metrics">
        <div class="ci-metric"><span>Last price{{ $primary->price_date ? ' · ' . $primary->price_date->format('d M Y') : '' }}</span>
            <b>{{ $primary->last_price !== null ? '$' . number_format($primary->last_price, 2) : '—' }}</b>
            @if($primary->change_pct !== null)<small class="{{ $primary->change_pct >= 0 ? 'up' : 'down' }}" style="font-weight:700">{{ $primary->change_pct >= 0 ? '+' : '−' }}{{ number_format(abs($primary->change_pct), 2) }}%</small>@endif</div>
        <div class="ci-metric"><span>Market cap</span><b>{{ $usd($primary->market_cap) }}</b></div>
        <div class="ci-metric"><span>P/E</span><b>{{ $pe !== null ? number_format($pe, 1) : '—' }}</b><small>{{ $usLatest ? 'On FY ' . $usLatest->fiscal_year . ' diluted EPS' : '' }}</small></div>
        <div class="ci-metric"><span>Volume</span><b>{{ $primary->volume !== null ? number_format($primary->volume) : '—' }}</b></div>
        <div class="ci-metric"><span>Sector</span><b style="font-size:14px">{{ $primary->sector ?: '—' }}</b><small>{{ $primary->industry }}</small></div>
        <div class="ci-metric"><span>Country · IPO</span><b style="font-size:14px">{{ $primary->country ?: '—' }}</b><small>{{ $primary->ipo_year ? 'Listed ' . $primary->ipo_year : '' }}</small></div>
    </div>
    @include('insights.partials.price-chart', ['pcId' => 'us-price', 'pcTitle' => $primary->ticker . ' share price', 'pcUrl' => route('markets.us.history', $primary->ticker), 'pcCur' => '$', 'pcZone' => 'New York time'])
</section>

@if($usYears->isEmpty())
    <section class="ci-card" id="us-financials"><h2>Annual results (SEC EDGAR)</h2><p class="ci-muted">No annual results in SEC XBRL data for this company yet.</p></section>
@elseif($premium)
    <section class="ci-card" id="us-financials">
        <h2>Annual results (SEC EDGAR, {{ $cur }})</h2>
        <div class="ci-table-wrap">
            <table class="ci-table">
                <thead><tr><th>{{ $cur }} · year</th>@foreach($usYears as $year => $row)<th>{{ $year }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach(['Income statement' => null, 'revenue' => 'Revenue', 'operating_income' => 'Operating income', 'net_income' => 'Net income', 'net_margin' => 'Net margin',
                          'eps_diluted' => 'Diluted EPS', 'operating_cash_flow' => 'Operating cash flow',
                          'Balance sheet' => null, 'total_assets' => 'Total assets', 'total_liabilities' => 'Total liabilities', 'equity' => "Shareholders' equity",
                          'cash' => 'Cash and equivalents', 'long_term_debt' => 'Long-term debt', 'roe' => 'Return on equity'] as $field => $label)
                    @if($label === null)
                        <tr class="ci-sub"><td>{{ $field }}</td>@foreach($usYears as $row)<td></td>@endforeach</tr>
                    @else
                        <tr><td>{{ $label }}</td>
                            @foreach($usYears as $row)
                                <td>
                                    @if($field === 'net_margin'){!! $pct($row->revenue > 0 && $row->net_income !== null ? $row->net_income / $row->revenue * 100 : null) !!}
                                    @elseif($field === 'roe'){!! $pct($row->equity > 0 && $row->net_income !== null ? $row->net_income / $row->equity * 100 : null) !!}
                                    @elseif($field === 'eps_diluted'){{ $row->eps_diluted !== null ? ($cur === 'EUR' ? '€' : '$') . number_format($row->eps_diluted, 2) : '—' }}
                                    @else<span class="{{ $row->{$field} !== null && $row->{$field} < 0 ? 'down' : '' }}">{{ $fin($row->{$field}) }}</span>@endif
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
        <p class="ci-muted" style="font-size:12.5px">Source: SEC EDGAR XBRL filings (10-K / 20-F), by calendar year of the fiscal year end{{ $usLatest?->period_end ? '; latest year ended ' . $usLatest->period_end->format('d M Y') : '' }}. Balance sheet as at the latest year-end reported. T = trillion, B = billion, M = million.</p>
    </section>
@else
    @include('insights.partials.locked', ['id' => 'us-financials', 'title' => 'Annual results (SEC EDGAR)',
        'text' => 'Revenue, operating income, net income, EPS, cash flow, assets, liabilities, equity and debt for every year filed with the SEC are part of the premium plan.',
        'labels' => ['Revenue', 'Operating income', 'Net income', 'Diluted EPS', 'Total assets', 'Equity'], 'unit' => '$ •••B'])
@endif
