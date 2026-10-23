@extends('layouts.site')
@section('title', $company->name . ' — company insights, financials and ratios')
@push('styles')@include('insights.partials.styles')@include('insights.partials.profile-styles')@endpush
@push('scripts')@include('insights.partials.chart-boot')@include('insights.partials.profile-script')@endpush
@section('content')
@php
    $money = fn ($v) => \App\Services\Insights\CompanyInsights::money($v);
    $pct = fn ($v) => $v === null ? '—' : '<span class="' . ($v >= 0 ? 'up' : 'down') . '">' . ($v >= 0 ? '' : '−') . number_format(abs($v), 1) . '%</span>';
    $x = fn ($v) => $v === null ? '—' : number_format($v, 2) . '×';
    $detail = $company->registryDetail;
    $latest = $ratios->last();
    $isOwner = auth()->check() && (auth()->user()->role_code === 2 || auth()->user()->role === 'admin');
    $statements = $company->financialStatements->sortBy('financial_year')->groupBy('financial_year');
    $years = $statements->keys();
    $status = $detail?->company_status ?? $company->registry_status;
    // Listed only in the US, with no Indian registration: Indian registry fields and MCA deadlines do not apply.
    $usOnly = $company->usListings->isNotEmpty() && !$cin;
    $listing = $company->usListings->first();
@endphp
@include('insights.partials.profile-header')
<main class="ci-wrap ci-main ci-layout">
    @if(session('success'))<p class="ci-flash ok">{{ session('success') }}</p>@endif
    @if(session('warning'))<p class="ci-flash warn">{{ session('warning') }}</p>@endif
    @if($errors->any())<p class="ci-flash warn">{{ $errors->first() }}</p>@endif

    <nav class="ci-tabs pf-nav" id="pf-nav" aria-label="Profile"><b class="pf-nav-title">Profile</b>
        <a class="pf-jobs" href="{{ route('companies.show', $company) }}">Jobs at {{ Str::limit($company->name, 24) }}</a>
    </nav>

    @php
        $prev = $ratios->count() > 1 ? $ratios->slice(-2, 1)->first() : null;
        // Year-on-year change: % for amounts, percentage points for ratios.
        $yoy = function ($key, $points = false) use ($latest, $prev) {
            if (!$prev || ($latest[$key] ?? null) === null || ($prev[$key] ?? null) === null) return '';
            $change = $points ? $latest[$key] - $prev[$key] : ($prev[$key] != 0 ? ($latest[$key] - $prev[$key]) / abs($prev[$key]) * 100 : null);
            if ($change === null) return '';
            return '<small class="' . ($change >= 0 ? 'up' : 'down') . '">' . ($change >= 0 ? '▲ ' : '▼ ') . number_format(abs($change), 1) . ($points ? ' pts' : '%') . ' YoY</small>';
        };
        $threeBack = $ratios->count() >= 4 ? $ratios->slice(-4, 1)->first() : null;
        $cagr = $threeBack && ($threeBack['revenue'] ?? 0) > 0 && ($latest['revenue'] ?? 0) > 0 ? (pow($latest['revenue'] / $threeBack['revenue'], 1 / 3) - 1) * 100 : null;
        $nic = $detail?->nic_code ?? ($cin ? substr($cin, 1, 5) : null);
        $incorporated = $detail?->incorporation_date ?? ($register?->registered_on ? \Carbon\Carbon::parse($register->registered_on) : null);
        $nextDue = collect($compliance)->first(fn ($i) => !$i['done'] && $i['days'] >= 0);
        $dash = fn ($v) => filled($v) ? $v : '—';
    @endphp
    @include('insights.partials.profile-sections')

    <section class="ci-card" id="summary">
        <h2>Company summary</h2>
        @if($usOnly)
            <div class="ci-facts">
                @foreach(['Legal name' => $listing->name, 'Ticker' => $company->usListings->pluck('ticker')->implode(', '), 'Exchange' => $listing->exchange,
                          'Country' => $listing->country ?: $company->country, 'SEC CIK' => $listing->cik ? str_pad((string) $listing->cik, 10, '0', STR_PAD_LEFT) : null,
                          'Listed since' => $listing->ipo_year, 'Industry' => $listing->industry,
                          'Sector' => collect([$company->industry, $company->sector])->filter()->unique()->implode(' · '), 'Website' => $company->website] as $label => $value)
                    <div><span>{{ $label }}</span><b>{{ $dash($value) }}</b></div>
                @endforeach
            </div>
        @else
        <h3 class="ci-h3">Entity details</h3>
        <div class="ci-facts">
            @foreach([
                'CIN' => $cin, 'LEI code' => $detail?->lei, 'State code' => $cin ? substr($cin, 6, 2) : null, 'PIN code' => $detail?->pin_code ?? \App\Services\Insights\CompanyInsights::pinFromAddress($register?->address),
                'Current legal name' => $register?->name ?? mb_strtoupper($company->name),
                'Classification' => collect([$detail?->company_class ?? $register?->company_class, $detail?->company_category ?? $register?->category, $detail?->company_subcategory ?? $register?->sub_category])->filter()->implode(' · '),
                'Listing status' => $detail?->listing_status ?? ($cin ? ($cin[0] === 'L' ? 'Listed' : 'Unlisted') : null), 'Registration no.' => $decoded['Registration number'] ?? null,
                'Company age' => $incorporated ? $incorporated->diff(now())->format('%y years, %m months') : null, 'PAN' => $detail?->pan,
                'Country' => $company->country ?: 'India', 'City' => $detail?->city, 'State' => $register?->state ?? $company->registered_state ?? ($decoded['State of registration'] ?? null),
                'Current status' => $status, 'Date of incorporation' => $incorporated?->format('d M Y'), 'Registrar of companies' => $detail?->roc ?? $register?->roc,
                'Authorised capital' => ($detail?->authorised_capital ?? $register?->authorised_capital) !== null ? $money($detail?->authorised_capital ?? $register?->authorised_capital) : null,
                'Paid-up capital' => ($detail?->paid_up_capital ?? $register?->paid_up_capital) !== null ? $money($detail?->paid_up_capital ?? $register?->paid_up_capital) : null,
                'Employee count' => $detail?->employees ? number_format($detail->employees) : null,
            ] as $label => $value)
                <div><span>{{ $label }}</span><b>{{ $dash($value) }}</b></div>
            @endforeach
        </div>
        <div class="ci-two" style="margin-top:6px">
            <div>
                <h3 class="ci-h3">Compliance</h3>
                <div class="ci-facts">
                    <div><span>Last AGM date</span><b>{{ $dash($detail?->last_agm_date?->format('d M Y')) }}</b></div>
                    <div><span>Last balance sheet</span><b>{{ $dash($detail?->last_balance_sheet_date?->format('d M Y')) }}</b></div>
                    <div><span>Next filing due</span><b>{{ $nextDue ? $nextDue['label'] . ' · ' . $nextDue['due']->format('d M Y') : '—' }}</b></div>
                </div>
            </div>
            <div>
                <h3 class="ci-h3">Industry classification</h3>
                <div class="ci-facts">
                    <div><span>NIC code</span><b>{{ $dash($nic) }}</b></div>
                    <div><span>Description of main activity</span><b>{{ $dash($detail?->principal_activity ?? $register?->activity ?? \App\Services\Insights\CompanyInsights::nicDivision($nic)) }}</b></div>
                    <div><span>Description of business activity</span><b>{{ $dash(\App\Services\Insights\CompanyInsights::nicDivision($nic)) }}</b></div>
                    @if($company->sector || $company->industry)<div><span>Sector</span><b>{{ collect([$company->industry, $company->sector])->filter()->unique()->implode(' · ') }}</b></div>@endif
                </div>
            </div>
        </div>
        @endif
    </section>
    @if($providerStatus && $company->financialStatements->isEmpty())
        <p class="ci-flash warn">Owner: financial statements, charges and AGM dates for private companies come from their MCA filings through the data providers.
            @foreach($providerStatus as $provider => $s) {{ ucfirst($provider) }}: {{ $s ? ($s['ok'] ? 'last call worked (' . $s['at'] . ')' : $s['message'] . ' (' . $s['at'] . ')') : 'not called yet' }}.@endforeach
            When a provider has API calls available, every company page fetches its figures automatically.</p>
    @endif
    @if($company->usListings->isNotEmpty())
        @include('insights.partials.us-market')
    @endif
    {{-- A US-listed company with no Indian (MCA) filings: the MCA overview would only say figures are being fetched. --}}
    @if($company->usListings->isNotEmpty() && $company->financialStatements->isEmpty())
    @elseif($premium)
    <section class="ci-card" id="overview">
        <h2>Key metrics{{ $latest ? ' · FY ' . $latest['year'] : '' }}</h2>
        @if($latest)
            <div class="ci-metrics">
                <div class="ci-metric"><span>Revenue</span><b>{{ $money($latest['revenue']) }}</b>{!! $yoy('revenue') !!}</div>
                <div class="ci-metric"><span>EBITDA</span><b>{{ $money($latest['ebitda']) }}</b>{!! $yoy('ebitda') !!}</div>
                <div class="ci-metric"><span>Net profit</span><b class="{{ ($latest['pat'] ?? 0) < 0 ? 'down' : '' }}">{{ $money($latest['pat']) }}</b>{!! $yoy('pat') !!}</div>
                <div class="ci-metric"><span>Net worth</span><b>{{ $money($latest['net_worth']) }}</b>{!! $yoy('net_worth') !!}</div>
                <div class="ci-metric"><span>Total assets</span><b>{{ $money($latest['total_assets']) }}</b>{!! $yoy('total_assets') !!}</div>
                <div class="ci-metric"><span>Total debt</span><b>{{ $money($latest['total_debt']) }}</b>{!! $yoy('total_debt') !!}</div>
                <div class="ci-metric"><span>Authorised capital</span><b>{{ $money($detail?->authorised_capital) }}</b></div>
                <div class="ci-metric"><span>Paid-up capital</span><b>{{ $money($detail?->paid_up_capital) }}</b></div>
                <div class="ci-metric"><span>Revenue CAGR (3Y)</span><b>{!! $pct($cagr) !!}</b></div>
                <div class="ci-metric"><span>Net profit margin</span><b>{!! $pct($latest['net_margin']) !!}</b>{!! $yoy('net_margin', true) !!}</div>
                <div class="ci-metric"><span>ROCE</span><b>{!! $pct($latest['roce']) !!}</b>{!! $yoy('roce', true) !!}</div>
                <div class="ci-metric"><span>ROE</span><b>{!! $pct($latest['roe']) !!}</b>{!! $yoy('roe', true) !!}</div>
                <div class="ci-metric"><span>Debt / equity</span><b>{{ $x($latest['debt_to_equity']) }}</b></div>
                <div class="ci-metric"><span>EPS · P/E</span><b>{{ $latest['eps'] !== null ? '₹' . number_format($latest['eps'], 2) : '—' }} · {{ $latest['pe'] !== null ? number_format($latest['pe'], 1) : '—' }}</b><small>{{ $latest['pe'] === null ? 'P/E needs a share price (listed only)' : 'At the recorded price' }}</small></div>
            </div>
            @include('insights.partials.financial-charts')
        @else
            <div class="ci-metrics">
                @foreach(['Revenue', 'EBITDA', 'Net profit', 'Net worth', 'Total assets', 'Total debt'] as $label)<div class="ci-metric"><span>{{ $label }}</span><b>—</b></div>@endforeach
                <div class="ci-metric"><span>Authorised capital</span><b>{{ $money($detail?->authorised_capital ?? $register?->authorised_capital) }}</b></div>
                <div class="ci-metric"><span>Paid-up capital</span><b>{{ $money($detail?->paid_up_capital ?? $register?->paid_up_capital) }}</b></div>
                @foreach(['Revenue CAGR (3Y)', 'Net profit margin', 'ROCE', 'ROE', 'Debt / equity'] as $label)<div class="ci-metric"><span>{{ $label }}</span><b>—</b></div>@endforeach
            </div>
            <p class="ci-muted">Financial statements for this company are being fetched from its MCA filings (AOC-4). They appear here as soon as they are available.</p>
        @endif
    </section>

    @else
        @include('insights.partials.locked', ['id' => 'overview'])
    @endif

    @if($compliance && !$usOnly)
        <section class="ci-card" id="compliance">
            <h2>Filing compliance</h2>
            <div class="ci-facts">
                @if($detail?->last_agm_date)<div><span>Last AGM</span><b>{{ $detail->last_agm_date->format('d M Y') }}</b></div>@endif
                @if($detail?->last_balance_sheet_date)<div><span>Last balance sheet</span><b>{{ $detail->last_balance_sheet_date->format('d M Y') }}</b></div>@endif
                @foreach($compliance as $item)
                    <div><span>{{ $item['label'] }}</span><b>{{ $item['due']->format('d M Y') }}</b>
                        <small class="{{ $item['done'] ? 'up' : ($item['overdue'] ? 'down' : '') }}" style="display:block;font-weight:700">{{ $item['done'] === null ? ($item['days'] >= 0 ? 'Statutory due date · ' . $item['days'] . ' days left' : 'Statutory due date passed') : ($item['done'] ? 'Held' : ($item['overdue'] ? 'Overdue by ' . abs($item['days']) . ' days' : $item['days'] . ' days left')) }}</small></div>
                @endforeach
            </div>
            <p class="ci-muted" style="font-size:12.5px">Due dates follow the Companies Act: AGM within six months of the year end, AOC-4 within 30 days and MGT-7 within 60 days of the AGM. A filing made but not yet uploaded here shows as pending.</p>
        </section>
    @endif

    @if($premium || $isOwner)
        @includeSafe('insights.partials.vendor-assessment')
    @else
        <section class="ci-card" id="vendor-checks"><h2>Vendor checks &amp; payment capacity</h2><p>Sign in with access to company financials to review vendor evidence.</p></section>
    @endif

    @if($credit)
        @includeSafe('insights.partials.credit-ratings')
        @includeSafe('insights.partials.rated-debt')
    @endif
    @if(!empty($funding))
        @includeSafe('insights.partials.funding-lifecycle')
    @endif

    @if($health && $premium)
        <section class="ci-card" id="health">
            <h2>Financial health score</h2>
            <div class="ci-health">
                <div class="ci-health-score"><span>FY {{ $health['year'] }}</span><b>{{ $health['score'] }}<small>/100</small></b><em>{{ $health['label'] }}</em>
                    <div class="ci-gauge"><i style="width:{{ $health['score'] }}%"></i></div></div>
                <div class="ci-health-parts">
                    @foreach($health['breakdown'] as $name => $part)
                        <div><strong>{{ $name }} <span>{{ $part['value'] }}%</span></strong><small>{{ $part['hint'] }}</small><div class="ci-gauge"><i style="width:{{ $part['value'] }}%"></i></div></div>
                    @endforeach
                </div>
            </div>
            <div class="ci-two" style="margin-top:14px">
                <div><h3 style="font-size:15px;color:#0b2545;margin:0 0 8px">Risk factors ({{ count($health['risks']) }})</h3>
                    @forelse($health['risks'] as [$level, $text])<p class="ci-risk"><span class="lvl {{ strtolower($level) }}">{{ $level }}</span>{{ $text }}</p>@empty<p class="ci-muted">No risk factors from the available figures.</p>@endforelse</div>
                <div><h3 style="font-size:15px;color:#0b2545;margin:0 0 8px">Strengths ({{ count($health['strengths']) }})</h3>
                    @forelse($health['strengths'] as $text)<p class="ci-risk good"><span class="lvl ok">✓</span>{{ $text }}</p>@empty<p class="ci-muted">—</p>@endforelse</div>
            </div>
            <p class="ci-muted" style="font-size:12px">Scored from the filed figures: profitability (net margin, ROE, ROCE), growth (revenue growth, profitable years), solvency (debt/equity, interest cover, current ratio) and efficiency (asset turnover, EBITDA margin). It is an indicator, not a credit rating.</p>
        </section>
    @endif

    @if($years->isNotEmpty() && $premium)
        <section class="ci-card" id="financials">
            <h2>Financial statements (standalone{{ $consolidatedRatios->isNotEmpty() ? '; consolidated figures in the second table of ratios' : '' }})</h2>
            <div class="ci-table-wrap">
                <table class="ci-table">
                    <thead><tr><th>₹ · FY</th>@foreach($years as $year)<th>{{ $year }}</th>@endforeach</tr></thead>
                    <tbody>
                    @foreach([
                        'Profit and loss' => null, 'revenue' => 'Revenue from operations', 'other_income' => 'Other income', 'total_expenses' => 'Total expenses', 'employee_cost' => 'Employee cost',
                        'finance_cost' => 'Finance cost', 'depreciation' => 'Depreciation', 'profit_before_tax' => 'Profit before tax', 'tax_expense' => 'Tax', 'profit_after_tax' => 'Profit after tax',
                        'Balance sheet' => null, 'share_capital' => 'Share capital', 'reserves' => 'Reserves / other equity', 'net_worth' => 'Net worth', 'long_term_borrowings' => 'Long-term borrowings',
                        'short_term_borrowings' => 'Short-term borrowings', 'total_debt' => 'Total debt', 'trade_payables' => 'Trade payables', 'current_liabilities' => 'Current liabilities',
                        'fixed_assets' => 'Fixed assets', 'inventories' => 'Inventories', 'trade_receivables' => 'Trade receivables', 'cash_and_bank' => 'Cash and bank',
                        'current_assets' => 'Current assets', 'total_assets' => 'Total assets',
                    ] as $field => $label)
                        @if($label === null)
                            <tr class="ci-sub"><td colspan="{{ $years->count() + 1 }}">{{ $field }}</td></tr>
                        @elseif($statements->contains(fn ($rows) => $rows->firstWhere('is_consolidated', false)?->{$field} !== null || $rows->first()?->{$field} !== null))
                            <tr><td>{{ $label }}</td>@foreach($years as $year)@php $s = $statements[$year]->firstWhere('is_consolidated', false) ?? $statements[$year]->first(); @endphp<td>{{ $money($s->{$field}) }}</td>@endforeach</tr>
                        @endif
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p class="ci-muted" style="font-size:12.5px">Source: {{ $company->financialStatements->pluck('source')->filter()->unique()->implode(', ') }} filings. Cr = crore, L = lakh.</p>
        </section>

        @include('insights.partials.analytics-trends')

        <section class="ci-card" id="ratios">
            <h2>Ratios</h2>
            @foreach(['Standalone' => $ratios, 'Consolidated' => $consolidatedRatios] as $title => $set)
                @continue($set->isEmpty())
                @if($consolidatedRatios->isNotEmpty())<h3 style="font-size:15px;color:#0b2545">{{ $title }}</h3>@endif
                <div class="ci-table-wrap" style="margin-bottom:12px">
                    <table class="ci-table">
                        <thead><tr><th>Ratio · FY</th>@foreach($set as $r)<th>{{ $r['year'] }}</th>@endforeach</tr></thead>
                        <tbody>
                            @foreach(['revenue_growth' => ['Revenue growth', 'pct'], 'profit_growth' => ['Profit growth', 'pct'], 'ebitda_margin' => ['EBITDA margin', 'pct'], 'net_margin' => ['Net profit margin', 'pct'],
                                      'roe' => ['Return on equity (ROE)', 'pct'], 'roce' => ['Return on capital employed (ROCE)', 'pct'], 'roa' => ['Return on assets', 'pct'],
                                      'debt_to_equity' => ['Debt / equity', 'x'], 'debt_to_ebitda' => ['Debt / EBITDA', 'x'], 'interest_cover' => ['Interest coverage', 'x'],
                                      'current_ratio' => ['Current ratio', 'x'], 'asset_turnover' => ['Asset turnover', 'x'], 'equity_multiplier' => ['Equity multiplier', 'x'], 'sales_to_fixed_assets' => ['Sales to fixed assets', 'x'], 'eps' => ['EPS (₹)', 'num'], 'pe' => ['P/E', 'num']] as $key => [$label, $kind])
                                <tr><td>{{ $label }}</td>@foreach($set as $r)<td>{!! $kind === 'pct' ? $pct($r[$key]) : ($kind === 'x' ? $x($r[$key]) : ($r[$key] === null ? '—' : number_format($r[$key], 2))) !!}</td>@endforeach</tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
            <p class="ci-muted" style="font-size:12.5px">ROE = PAT ÷ net worth · ROCE = (PBT + finance cost) ÷ (net worth + debt) · Debt includes long- and short-term borrowings · EBITDA = PBT + finance cost + depreciation when not stated.</p>
        </section>
    @endif

    @if($premium && $peers->count() > 1)
        <section class="ci-card" id="peers">
            <h2>Peer comparison · {{ $company->sector }}</h2>
            <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Company</th><th>FY</th><th>Revenue</th><th>Net profit</th><th>Growth</th><th>Net margin</th><th>ROE</th><th>ROCE</th><th>Debt / equity</th></tr></thead><tbody>
                @foreach($peers as $peer)
                    <tr style="{{ $peer->company->id === $company->id ? 'font-weight:700;background:#fff6dc' : '' }}"><td style="white-space:normal">@if($peer->company->id === $company->id){{ $peer->company->name }}@else<a href="{{ route('insights.show', $peer->company) }}">{{ $peer->company->name }}</a>@endif</td>
                        <td>{{ $peer->year }}</td><td>{{ $money($peer->revenue) }}</td><td>{{ $money($peer->pat) }}</td><td>{!! $pct($peer->revenue_growth) !!}</td><td>{!! $pct($peer->net_margin) !!}</td><td>{!! $pct($peer->roe) !!}</td><td>{!! $pct($peer->roce) !!}</td><td>{{ $x($peer->debt_to_equity) }}</td></tr>
                @endforeach
            </tbody></table></div>
        </section>
    @endif

    <div class="ci-two">
        <section class="ci-card" id="registry">
            <h2>Registered office and contact</h2>
            <div class="ci-facts">
                @foreach([
                    'Registered office' => $detail?->registered_address ?? $register?->address ?? $company->headquarters,
                    'GSTIN' => $detail?->gstins ? implode(', ', $detail->gstins) : null, 'Email' => $detail?->email, 'Website' => $company->website,
                    'Members / shareholders' => $detail?->members_count ? number_format($detail->members_count) : null,
                ] as $label => $value)
                    <div><span>{{ $label }}</span><b>{{ $dash($value) }}</b></div>
                @endforeach
            </div>
        </section>

        <section class="ci-card" id="people" style="grid-column:1/-1;min-width:0">
            <h2>Directors and key people</h2>
            @php
                $past = $dashboard['past']; $current = $dashboard['current'];
                $tenure = fn ($o) => $o->appointed_on ? $o->appointed_on->diff($o->ceased_on ?? now())->format('%yy %mm') : '—';
            @endphp
            @if($company->officers->isNotEmpty())
                <div class="ci-facts" style="margin-bottom:10px"><div><span>Current Directors</span><b>{{ $current->count() }}</b></div><div><span>Past Directors</span><b>{{ $past->count() }}</b></div><div><span>Average current director tenure</span><b>{{ $dashboard['average_tenure'] === null ? 'Unknown' : $dashboard['average_tenure'].' years' }}</b><small>{{ $dashboard['tenure_count'] }} known appointment dates</small></div></div>
                @if($analytics['board']['rows']->isNotEmpty())
                    <h3 class="ci-h3">Board tenure timeline</h3>
                    <div class="ci-chart" style="height:{{ max(200, min(640, 30 * $analytics['board']['rows']->count() + 80)) }}px"><canvas aria-label="Director tenures from appointment to cessation" data-chart="{{ json_encode([
                        'type' => 'bar', 'yearAxis' => 'x',
                        'data' => ['labels' => $analytics['board']['rows']->map(fn ($r) => $r['name']), 'datasets' => [[
                            'label' => 'Tenure', 'data' => $analytics['board']['rows']->map(fn ($r) => [$r['from'], $r['to']]),
                            'backgroundColor' => $analytics['board']['rows']->map(fn ($r) => $r['active'] ? (preg_match('/cfo|secretary/i', (string) $r['designation']) ? '#0b2545' : '#527fad') : '#cbd5e1'),
                            'borderRadius' => 4, 'borderSkipped' => false]]],
                        'options' => ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['min' => floor($analytics['board']['rows']->min('from')), 'max' => now()->year + 1, 'ticks' => ['precision' => 0]]]],
                    ]) }}"></canvas></div>
                @endif
                <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Name</th><th>Designation</th><th>DIN / PAN</th><th>Shares held</th><th>Appointed</th><th>Tenure</th></tr></thead><tbody>
                    @foreach($current->sortByDesc('shares_held') as $officer)
                        <tr><td>{{ $officer->name }}</td><td>{{ $officer->designation }}</td><td>{{ $officer->din ?? $officer->pan ?? '—' }}</td><td>{{ $officer->shares_held !== null ? number_format($officer->shares_held) : '—' }}</td><td>{{ $officer->appointed_on?->format('d M Y') ?? '—' }}</td><td>{{ $tenure($officer) }}</td></tr>
                    @endforeach
                </tbody></table></div>
                @if($past->isNotEmpty())
                    <details class="ci-more" style="margin-top:10px"><summary>Past directors ({{ $past->count() }})</summary>
                        <div class="ci-table-wrap" style="margin-top:8px"><table class="ci-table"><thead><tr><th>Name</th><th>Designation</th><th>DIN</th><th>Ceased</th></tr></thead><tbody>
                            @foreach($past->sortByDesc('ceased_on') as $officer)<tr><td>{{ $officer->name }}</td><td>{{ $officer->designation }}</td><td>{{ $officer->din ?? '—' }}</td><td>{{ $officer->ceased_on->format('d M Y') }}</td></tr>@endforeach
                        </tbody></table></div></details>
                @endif
            @else
                <p class="ci-muted">Directors are read from the MGT-7 annual return and AOC-4 signatories.</p>
            @endif
        </section>
    </div>

    @include('insights.partials.profile-competitors')
    @if($similar->isNotEmpty())
        @includeSafe('insights.partials.similar-companies', ['items' => $similar])
    @endif

    @if($premium)
        @include('insights.partials.ownership-dashboard')
    @endif

    <div class="ci-two">
        <section class="ci-card" id="charges" style="grid-column:1/-1;min-width:0">
            <h2>Charges (secured borrowings)</h2>
            @if(!$premium)
                <p class="ci-muted">{{ $company->charges->count() }} registered {{ Str::plural('charge', $company->charges->count()) }}. Lenders, amounts and dates are part of the premium plan.</p>
            @else
            @if($company->charges->isNotEmpty())
                @include('insights.partials.charges-analytics')
            @else
                <p class="ci-muted">Registered charges come from the MCA index of charges (InstaFinancials import).</p>
            @endif
            @endif
        </section>
        <section class="ci-card" id="auditors">
            <h2>Statutory auditors</h2>
            @forelse($company->auditors as $auditor)
                @if($loop->first)<div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Audit firm</th><th>FY</th><th>Firm reg. no.</th><th>City</th></tr></thead><tbody>@endif
                <tr><td style="white-space:normal">{{ $auditor->name }}</td><td>{{ $auditor->financial_year ?? '—' }}</td><td>{{ $auditor->firm_registration_number ?? '—' }}</td><td>{{ $auditor->city ?? '—' }}</td></tr>
                @if($loop->last)</tbody></table></div>@endif
            @empty
                <p class="ci-muted">Auditors are read from the AOC-4 auditor's report section.</p>
            @endforelse
        </section>
    </div>

    @if($subsidiaries->isNotEmpty() || $parentName)
        <section class="ci-card" id="group">
            <h2>Group companies</h2>
            @if($parentName)<p class="ci-muted" style="margin-top:0">Part of @if($parent)<a href="{{ route('insights.show', $parent) }}">{{ $parentName }}</a>@else{{ $parentName }}@endif</p>@endif
            @if($subsidiaries->isNotEmpty())
                <div class="ci-grid">@foreach($subsidiaries as $sub)<a class="ci-company" href="{{ route('insights.show', $sub) }}"><span class="ci-logo">@if($sub->logo_url)<img src="{{ $sub->logo_url }}" alt="" loading="lazy">@else{{ mb_strtoupper(mb_substr($sub->name, 0, 1)) }}@endif</span><span class="ci-company-body"><strong>{{ $sub->name }}</strong><small>{{ $sub->industry }}</small></span></a>@endforeach</div>
            @endif
        </section>
    @endif

    <section class="ci-card" id="business">
        <h2>Business activities and related parties</h2>
        @if($company->businessProducts->isNotEmpty())
            <div class="ci-table-wrap" style="margin-bottom:12px"><table class="ci-table"><thead><tr><th>Product / service</th><th>Code</th><th>FY</th><th>% of turnover</th></tr></thead><tbody>
                @foreach($company->businessProducts->sortByDesc('turnover_percentage') as $product)<tr><td style="white-space:normal">{{ $product->description }}</td><td>{{ $product->product_code }}</td><td>{{ $product->financial_year }}</td><td>{{ $product->turnover_percentage !== null ? $product->turnover_percentage . '%' : '—' }}</td></tr>@endforeach
            </tbody></table></div>
        @endif
        @if($company->relatedParties->isNotEmpty() && $premium)
            <div class="ci-table-wrap"><table class="ci-table"><thead><tr><th>Related party</th><th>Relationship</th><th>Transaction</th><th>Amount</th></tr></thead><tbody>
                @foreach($company->relatedParties as $party)<tr><td style="white-space:normal">{{ $party->party_name }}</td><td style="white-space:normal">{{ $party->relationship }}</td><td style="white-space:normal">{{ $party->transaction_description }}</td><td>{{ $money($party->amount) }}</td></tr>@endforeach
            </tbody></table></div>
        @endif
        @if($company->businessProducts->isEmpty() && $company->relatedParties->isEmpty())
            <p class="ci-muted">{{ $company->products ?: 'Principal business activities and related-party transactions are read from MGT-7 and AOC-4 filings.' }}</p>
        @endif
    </section>

    <section class="ci-card" id="filings">
        <h2>Filings</h2>
        @forelse($company->filings as $filing)
            <details class="ci-more" style="margin-bottom:8px">
                <summary>{{ $filing->form }} {{ $filing->financial_year }} · {{ $filing->created_at->format('d M Y') }} · {{ ucfirst($filing->status) }}</summary>
                @if($filing->message)<p class="ci-flash warn">{{ $filing->message }}</p>@endif
                @if($fieldsRead = $filing->parsed_fields['all_fields'] ?? [])
                    <p class="ci-muted" style="margin:8px 0 4px">All fields read from the filing ({{ count($fieldsRead) }})</p>
                    <div class="ci-table-wrap" style="max-height:340px;overflow:auto"><table class="ci-table"><tbody>
                        @foreach($fieldsRead as $key => $value)<tr><td style="white-space:normal">{{ ucfirst(str_replace('_', ' ', $key)) }}</td><td style="white-space:normal;text-align:left">{{ $value }}</td></tr>@endforeach
                    </tbody></table></div>
                @endif
            </details>
        @empty
            <p class="ci-muted">No filings on record yet.</p>
        @endforelse

        @if($isOwner)
            @if($cin && app(\App\Services\Insights\InstaFinancials::class)->configured())
                <form method="post" action="{{ route('insights.insta') }}" style="margin-top:12px">@csrf<input type="hidden" name="cin" value="{{ $cin }}"><button class="ci-btn light" type="submit">Refresh master data and directors from InstaFinancials (paid call)</button></form>
            @endif
            <details class="ci-more" style="margin-top:16px">
            <summary>Owner tools: add a filing or figures manually</summary>
            <h3 style="font-size:15px;color:#0b2545;margin-top:12px">Upload an MCA filing</h3>
            <p class="ci-muted">AOC-4 / AOC-4 XBRL (financial statements) or MGT-7 (annual return) as PDF or text. The PDF is read in your browser; image-only scans cannot be read.</p>
            <form method="post" action="{{ route('insights.upload', $company) }}" enctype="multipart/form-data" class="ci-inline" id="ci-upload">@csrf
                <select name="form" aria-label="Form type">@foreach($forms as $form)<option>{{ $form }}</option>@endforeach</select>
                <input type="file" name="file" accept=".pdf,.txt" required>
                <input type="hidden" name="text">
                <button class="ci-btn" type="submit">Upload and read</button>
                <small class="ci-muted" id="ci-upload-state"></small>
            </form>

            <details class="ci-more" style="margin-top:16px">
                <summary>Enter or correct a year's figures by hand</summary>
                <form method="post" action="{{ route('insights.statements', $company) }}" style="margin-top:10px">@csrf
                    <div class="ci-form-grid">
                        <label>Financial year<input type="text" name="financial_year" placeholder="2024-25" pattern="\d{4}-\d{2}" required></label>
                        <label>Amounts in<select name="unit"><option value="1">Rupees</option><option value="100000">Lakhs</option><option value="10000000" selected>Crores</option></select></label>
                        <label>Statement<select name="is_consolidated"><option value="0">Standalone</option><option value="1">Consolidated</option></select></label>
                        @foreach($fields as $field => $label)<label>{{ $label }}<input type="number" step="any" name="{{ $field }}"></label>@endforeach
                    </div>
                    <button class="ci-btn" type="submit" style="margin-top:10px">Save figures</button>
                </form>
            </details>
            </details>
        @endif
    </section>
</main>
@endsection
@if($isOwner)
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(() => {
    const form = document.getElementById('ci-upload');
    if (!form || !window.pdfjsLib) return;
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    const input = form.querySelector('input[type=file]'), state = document.getElementById('ci-upload-state'), button = form.querySelector('button');
    // Text of every page, one line per row of the form (items grouped by their vertical position).
    async function extract(file) {
        const pdf = await pdfjsLib.getDocument({data: await file.arrayBuffer()}).promise;
        const pages = [];
        for (let n = 1; n <= pdf.numPages; n++) {
            state.textContent = `Reading page ${n} of ${pdf.numPages}…`;
            const content = await (await pdf.getPage(n)).getTextContent();
            const rows = new Map();
            content.items.forEach(item => {
                if (!item.str.trim()) return;
                const y = Math.round(item.transform[5] / 3) * 3;
                if (!rows.has(y)) rows.set(y, []);
                rows.get(y).push([item.transform[4], item.str]);
            });
            pages.push([...rows.entries()].sort((a, b) => b[0] - a[0]).map(([, items]) => items.sort((a, b) => a[0] - b[0]).map(i => i[1]).join('  ')).join('\n'));
        }
        return pages.join('\n');
    }
    input.addEventListener('change', async () => {
        form.text.value = '';
        const file = input.files[0];
        if (!file || !/\.pdf$/i.test(file.name)) { state.textContent = ''; return; }
        button.disabled = true;
        try {
            const text = await extract(file);
            form.text.value = text;
            state.textContent = text.trim().length > 200 ? `Text ready (${text.length.toLocaleString()} characters).` : 'Very little text found: this may be a scanned PDF.';
        } catch (e) { state.textContent = 'Could not read this PDF in the browser (' + e.message + '). Upload the text version instead.'; }
        button.disabled = false;
    });
})();
</script>
@endpush
@endif
