@extends('layouts.site')
@section('title', $query ? "Company insights: {$query}" : 'Insights into Company — CIN, GSTIN and financials')
@push('styles')@include('insights.partials.styles')@include('insights.partials.db-styles')@endpush
@section('content')
@php $isOwner = auth()->check() && (auth()->user()->role_code === 2 || auth()->user()->role === 'admin'); @endphp
<section class="ci-hero" style="{{ $home ? 'padding:22px 0' : '' }}">
    <div class="ci-wrap">
        @unless($home)<h1>Insights into Company</h1>
        <p>Search any Indian company by name, CIN, GSTIN or PAN, or a US company by name or ticker, for its registry details, revenue, profit, debt, assets and ratios.</p>@endunless
        <form class="ci-search" method="get" action="{{ route('insights.index') }}">
            <input type="search" name="q" value="{{ $query }}" placeholder="Search company, CIN (L29299HR…), GSTIN, PAN or US ticker (AAPL)" aria-label="Company name, CIN, GSTIN, PAN or ticker" autofocus>
            <button class="ci-btn gold" type="submit">Search</button>
        </form>
        @unless($home)<div class="ci-hero-stats"><span><b>{{ number_format($stats['companies']) }}</b> companies with registry data</span><span><b>{{ number_format($stats['with_financials']) }}</b> with financial statements</span></div>@endunless
    </div>
</section>

@if($home)
@php
    $short = fn ($n) => $n >= 10000000 ? round($n / 10000000, 1) . ' Cr' : ($n >= 100000 ? round($n / 100000, 1) . ' L' : ($n >= 1000 ? round($n / 1000, 1) . 'K' : number_format($n)));
    $pctCls = fn ($v) => $v >= 0 ? 'db-up' : 'db-down';
    $sign = fn ($v, $d = 2) => ($v >= 0 ? '+' : '') . number_format($v, $d);
    $india = $home['india'];
@endphp
<div class="db-page">
<div class="db-layout">
    @include('insights.partials.explore-nav')
    <main style="min-width:0">
        @if(session('success'))<p class="ci-flash ok">{{ session('success') }}</p>@endif
        @if($errors->any())<p class="ci-flash warn">{{ $errors->first() }}</p>@endif

        <div class="db-head"><h2>Data snapshot</h2><small>Updated daily</small></div>
        <div class="db-snap">
            @foreach($home['snapshot'] as [$label, $count, $sub, $icon, $link])
                <a href="{{ $link }}"><span>{{ $icon }} {{ $label }}</span><b>{{ $short($count) }}</b><small>{{ $sub }}</small></a>
            @endforeach
        </div>

        @if(!empty($india['indices']))
            <div class="db-head"><h2>Indian market today</h2><small>{{ !empty($india['indices'][0]['time']) ? 'Updated ' . \Carbon\Carbon::createFromTimestamp($india['indices'][0]['time'])->timezone('Asia/Kolkata')->format('d M, h:i A') . ' IST' : '' }} · <a href="{{ route('trading.stocks') }}">Market today ›</a></small></div>
            <div class="db-idx">
                @foreach($india['indices'] as $i)<div><span>{{ $i['name'] }}</span><b>{{ number_format($i['price'], 2) }}</b><small class="{{ $pctCls($i['change']) }}">{{ $sign($i['change']) }} ({{ $sign($i['change_pct']) }}%)</small></div>@endforeach
            </div>
        @endif
        @if(isset($india['gainers']))
            <div class="db-movers">
                <div class="db-card"><h3>Market breadth</h3>
                    <b class="db-up">{{ number_format($india['up']) }} up</b> · <b class="db-down">{{ number_format($india['down']) }} down</b>
                    <div class="db-breadth"><span style="width:{{ round($india['up'] / max(1, $india['up'] + $india['down']) * 100) }}%"></span></div>
                    <small class="ci-muted">NSE stocks{{ $india['date'] ? ', ' . \Carbon\Carbon::parse($india['date'])->format('d M Y') : '' }}</small></div>
                @foreach(['gainers' => 'Top gainers', 'losers' => 'Top losers', 'active' => 'Most active (value ₹ Cr)'] as $key => $title)
                    <div class="db-card"><h3>{{ $title }}</h3>
                        @foreach($india[$key] as $s)
                            <div class="db-row"><a href="{{ route('trading.instrument', ['exchange' => 'NSE', 'symbol' => $s->symbol]) }}" style="color:#0b2545;text-decoration:none;font-weight:600">{{ $s->symbol }}</a>
                                <span>@if($key === 'active'){{ number_format($s->turnover_lakhs / 100) }} @endif<b class="{{ $pctCls($s->change_pct) }}">{{ $sign($s->change_pct) }}%</b></span></div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
        @if(!empty($home['us']))
            <div class="db-head"><h2>US market today</h2><small><a href="{{ route('markets.us') }}">All US stocks ›</a></small></div>
            <div class="db-idx">
                @foreach($home['us'] as $i)<div><span>{{ $i['name'] }}</span><b>{{ number_format($i['price'], 2) }}</b><small class="{{ $pctCls($i['change']) }}">{{ $sign($i['change']) }} ({{ $sign($i['change_pct']) }}%)</small></div>@endforeach
            </div>
        @endif

        @if($directory)
            <div class="db-card" id="directory">
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;align-items:center">
                    <h2 style="margin:0">Companies <small class="ci-muted" style="font-size:14px;font-weight:400">{{ number_format($directoryIsRegister ? $registerCount : $directory->total()) }} records</small></h2>
                    <form method="get" class="ci-inline">
                        <select name="status" onchange="this.form.submit()"><option value="">All statuses</option>@foreach($facets['status'] as $v)<option @selected(($filters['status'] ?? '') === $v)>{{ $v }}</option>@endforeach</select>
                        <select name="state" onchange="this.form.submit()"><option value="">All states</option>@foreach($facets['state'] as $v)<option @selected(($filters['state'] ?? '') === $v)>{{ $v }}</option>@endforeach</select>
                        <select name="industry" onchange="this.form.submit()"><option value="">{{ $directoryIsRegister ? 'All classes' : 'All industries' }}</option>@foreach($facets['industry'] as $v)<option @selected(($filters['industry'] ?? '') === $v)>{{ $v }}</option>@endforeach</select>
                        <select name="listing" onchange="this.form.submit()"><option value="">Listed & unlisted</option><option value="listed" @selected(($filters['listing'] ?? '') === 'listed')>Listed</option><option value="unlisted" @selected(($filters['listing'] ?? '') === 'unlisted')>Unlisted</option></select>
                        @if(array_filter($filters))<a class="ci-btn light" href="{{ route('insights.index') }}#directory" style="padding:7px 12px">Clear</a>@endif
                    </form>
                </div>
                @if($directoryIsRegister)
                    <div style="margin-top:12px">@include('insights.partials.register-table', ['rows' => $directory])</div>
                @else
                    <div class="ci-table-wrap" style="margin-top:12px">
                        <table class="ci-table">
                            <thead><tr><th>Company</th><th>Status</th><th>Type</th><th>State</th><th>Industry</th><th>Incorporated</th><th>Paid-up capital</th><th>Financials</th></tr></thead>
                            <tbody>
                            @forelse($directory as $c)
                                @php $cinType = (new \App\Services\Insights\CompanyInsights)->decodeCin($c->registry_cin); $detail = $c->registryDetail; @endphp
                                <tr>
                                    <td style="white-space:normal;min-width:240px"><a href="{{ route('insights.show', $c) }}"><strong>{{ $c->name }}</strong></a><br><small class="ci-muted">{{ $c->registry_cin }}</small></td>
                                    <td>@if($c->registry_status)<span class="{{ preg_match('/^active/i', $c->registry_status) ? 'up' : 'down' }}">{{ ucfirst(strtolower($c->registry_status)) }}</span>@else — @endif</td>
                                    <td style="white-space:normal">{{ $cinType['Listing'] === 'Listed on a stock exchange' ? 'Listed' : 'Unlisted' }} · {{ $cinType['Company type'] }}</td>
                                    <td>{{ $c->registered_state ?? $cinType['State of registration'] }}</td>
                                    <td style="white-space:normal">{{ $c->industry ?? '—' }}</td>
                                    <td>{{ $detail?->incorporation_date?->format('d M Y') ?? $cinType['Year of incorporation'] }}</td>
                                    <td>{{ \App\Services\Insights\CompanyInsights::money($detail?->paid_up_capital) }}</td>
                                    <td>{{ $c->financial_statements_count ? $c->financial_statements_count . ' yrs' : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8">No companies match these filters.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="ci-pager" style="margin-top:14px">{{ $directory->fragment('directory')->links() }}</div>
            </div>
        @endif

        @if($isOwner)
            <section class="db-card">
                <h2>Import MCA company data</h2>
                <p class="ci-muted">Upload MCA's Excel downloads (mca.gov.in → Data &amp; Reports → Company/LLP Info → “Incorporated/Closed during the month”) or a Company Master Data CSV. Large files import in the background.</p>
                <form method="post" action="{{ route('insights.master-data') }}" enctype="multipart/form-data" class="ci-inline">@csrf
                    <input type="file" name="csv" accept=".csv,.xlsx" required>
                    <button class="ci-btn" type="submit">Upload CSV</button>
                </form>
            </section>
        @endif
    </main>

    <aside class="db-right">
        @if(!empty($home['sectors']))
            <div class="db-card"><h2>Trends & Analytics</h2>
                <p class="ci-muted" style="margin:-6px 0 8px">Companies by sector</p>
                <div style="position:relative;height:{{ 40 + 28 * count($home['sectors']) }}px"><canvas id="db-sectors" aria-label="Companies by sector"></canvas></div>
            </div>
        @endif
        @unless(auth()->user()?->hasActivePremium())
            <div class="db-card db-cta"><h2>Get full access</h2>
                <p class="ci-muted" style="margin:0">You are on the free plan, so you see a preview of each company. A plan opens everything:</p>
                <ul><li>Full financials, ratios and charge details</li><li>Directors, shareholding and borrowings</li><li>Growth trends, payment behaviour and peers</li><li>Every row of the explore databases</li></ul>
                <a class="db-btn" href="{{ route('premium.index') }}">See plans</a>
                <a class="db-btn alt" href="{{ route('contact') }}?subject=Premium%20plan">Talk to us</a>
            </div>
        @endunless
        @if($home['recent']->isNotEmpty())
            <div class="db-card db-recent"><h2>Recently viewed</h2>
                @foreach($home['recent'] as $r)
                    <a href="{{ route('insights.show', $r['slug']) }}">@if($r['logo'])<img src="{{ $r['logo'] }}" alt="" loading="lazy">@else<span class="db-logo txt">{{ mb_substr($r['name'], 0, 1) }}</span>@endif
                        <span>{{ $r['name'] }}<small>{{ $r['place'] }}</small></span></a>
                @endforeach
            </div>
        @endif
    </aside>
</div>
</div>
@else
<main class="ci-wrap ci-main">
    @if(session('success'))<p class="ci-flash ok">{{ session('success') }}</p>@endif
    @if($errors->any())<p class="ci-flash warn">{{ $errors->first() }}</p>@endif

    @if($decoded)
        <section class="ci-card">
            <h2>{{ strtoupper($identified['type']) }} {{ $identified['value'] }}</h2>
            <div class="ci-facts">@foreach($decoded as $label => $value)<div><span>{{ $label }}</span><b>{{ $value }}</b></div>@endforeach</div>
            @if($results->isEmpty())<p class="ci-muted">This {{ strtoupper($identified['type']) }} is not in the database yet. The details above are decoded from the number itself.@if($isOwner) Import the state's MCA master data on the dashboard@if($instaReady && $identified['type'] === 'cin'), or fetch it from InstaFinancials@endif.@endif</p>
                @if($isOwner && $instaReady && $identified['type'] === 'cin')
                    <form method="post" action="{{ route('insights.insta') }}">@csrf<input type="hidden" name="cin" value="{{ $identified['value'] }}"><button class="ci-btn" type="submit">Fetch from InstaFinancials (paid call)</button></form>
                @endif
            @endif
        </section>
    @endif

    <section>
        <h2 class="ci-h2">{{ $results->count() }} {{ Str::plural('company', $results->count()) }} found</h2>
        @if($results->isEmpty() && $register->isEmpty() && !$decoded)<p class="ci-muted">No company matches “{{ $query }}”. Try the registered name (e.g. “Happy Forgings”), the 21-character CIN, the 15-character GSTIN or a US ticker in capitals (AAPL).</p>@endif
        <div class="ci-grid">
            @foreach($results as $company)
                @include('insights.partials.company-card', ['company' => $company])
            @endforeach
        </div>
        @if($register->isNotEmpty())
            <h2 class="ci-h2">{{ $register->count() }} in the MCA company register</h2>
            @include('insights.partials.register-table', ['rows' => $register])
        @endif
        @if($external)
            <h2 class="ci-h2">Registered companies found on InstaFinancials</h2>
            <div class="ci-grid">
                @foreach($external as $row)
                    <div class="ci-company"><span class="ci-logo">{{ mb_strtoupper(mb_substr($row['name'], 0, 1)) }}</span><span class="ci-company-body"><strong>{{ $row['name'] }}</strong><small>{{ $row['cin'] }}</small>
                        @if($isOwner)<form method="post" action="{{ route('insights.insta') }}">@csrf<input type="hidden" name="cin" value="{{ $row['cin'] }}"><input type="hidden" name="name" value="{{ $row['name'] }}"><button class="ci-btn light" type="submit" style="padding:5px 10px;font-size:12.5px">Add with master data</button></form>@endif
                    </span></div>
                @endforeach
            </div>
        @elseif($externalError)
            <p class="ci-muted">InstaFinancials search failed: {{ $externalError }}</p>
        @endif
    </section>
</main>
@endif
@endsection

@if($home && !empty($home['sectors']))
@push('scripts')
<script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
(() => {
    if (!window.Chart) return;
    const data = @json($home['sectors']);
    new Chart(document.getElementById('db-sectors'), {type: 'bar', data: {labels: Object.keys(data), datasets: [{label: 'Companies', data: Object.values(data), backgroundColor: '#1d4f86', borderRadius: 3}]},
        options: {indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: {legend: {display: false}}, scales: {x: {display: false}, y: {ticks: {font: {size: 11}}}}}});
})();
</script>
@endpush
@endif
