@extends('layouts.site')
@section('title', $query ? "Company insights: {$query}" : 'Insights into Company — CIN, GSTIN and financials')
@push('styles')@include('insights.partials.styles')@endpush
@section('content')
@php $isOwner = auth()->check() && (auth()->user()->role_code === 2 || auth()->user()->role === 'admin'); @endphp
<section class="ci-hero">
    <div class="ci-wrap">
        <h1>Insights into Company</h1>
        <p>Search any Indian company by name, CIN, GSTIN or PAN for its registry details, revenue, profit, debt, assets and ratios from MCA filings (AOC-4, MGT-7).</p>
        <form class="ci-search" method="get" action="{{ route('insights.index') }}">
            <input type="search" name="q" value="{{ $query }}" placeholder="Company name, CIN (L29299HR…), GSTIN (06AAACH…) or PAN" aria-label="Company name, CIN, GSTIN or PAN" autofocus>
            <button class="ci-btn gold" type="submit">Search</button>
        </form>
        <div class="ci-hero-stats"><span><b>{{ number_format($stats['companies']) }}</b> companies with registry data</span><span><b>{{ number_format($stats['with_financials']) }}</b> with financial statements</span></div>
    </div>
</section>
<main class="ci-wrap ci-main">
    @if(session('success'))<p class="ci-flash ok">{{ session('success') }}</p>@endif
    @if($errors->any())<p class="ci-flash warn">{{ $errors->first() }}</p>@endif

    @if($decoded)
        <section class="ci-card">
            <h2>{{ strtoupper($identified['type']) }} {{ $identified['value'] }}</h2>
            <div class="ci-facts">@foreach($decoded as $label => $value)<div><span>{{ $label }}</span><b>{{ $value }}</b></div>@endforeach</div>
            @if($results->isEmpty())<p class="ci-muted">This {{ strtoupper($identified['type']) }} is not in the database yet. The details above are decoded from the number itself.@if($isOwner) Import the state's MCA master data below@if($instaReady && $identified['type'] === 'cin'), or fetch it from InstaFinancials@endif.@endif</p>
                @if($isOwner && $instaReady && $identified['type'] === 'cin')
                    <form method="post" action="{{ route('insights.insta') }}">@csrf<input type="hidden" name="cin" value="{{ $identified['value'] }}"><button class="ci-btn" type="submit">Fetch from InstaFinancials (paid call)</button></form>
                @endif
            @endif
        </section>
    @endif

    @if($query !== '')
        <section>
            <h2 class="ci-h2">{{ $results->count() }} {{ Str::plural('company', $results->count()) }} found</h2>
            @if($results->isEmpty() && $register->isEmpty() && !$decoded)<p class="ci-muted">No company matches “{{ $query }}”. Try the registered name (e.g. “Happy Forgings”), the 21-character CIN or the 15-character GSTIN.</p>@endif
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
    @elseif($directory)
        <section>
            <div class="ci-metrics" style="margin-bottom:16px">
                <div class="ci-metric"><span>Registered companies</span><b>{{ number_format($stats['companies']) }}</b><small>with CIN</small></div>
                <div class="ci-metric"><span>With financial statements</span><b>{{ number_format($stats['with_financials']) }}</b></div>
                <div class="ci-metric"><span>Directors & KMP</span><b>{{ number_format($stats['directors']) }}</b></div>
                <div class="ci-metric"><span>Charges</span><b>{{ number_format($stats['charges']) }}</b></div>
            </div>
            <div class="ci-card">
                <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:10px;align-items:center">
                    <h2 style="margin:0">Companies <small class="ci-muted" style="font-size:14px;font-weight:400">{{ number_format($directoryIsRegister ? $registerCount : $directory->total()) }} records</small></h2>
                    <form method="get" class="ci-inline">
                        <select name="status" onchange="this.form.submit()"><option value="">All statuses</option>@foreach($facets['status'] as $v)<option @selected(($filters['status'] ?? '') === $v)>{{ $v }}</option>@endforeach</select>
                        <select name="state" onchange="this.form.submit()"><option value="">All states</option>@foreach($facets['state'] as $v)<option @selected(($filters['state'] ?? '') === $v)>{{ $v }}</option>@endforeach</select>
                        <select name="industry" onchange="this.form.submit()"><option value="">{{ $directoryIsRegister ? 'All classes' : 'All industries' }}</option>@foreach($facets['industry'] as $v)<option @selected(($filters['industry'] ?? '') === $v)>{{ $v }}</option>@endforeach</select>
                        <select name="listing" onchange="this.form.submit()"><option value="">Listed & unlisted</option><option value="listed" @selected(($filters['listing'] ?? '') === 'listed')>Listed</option><option value="unlisted" @selected(($filters['listing'] ?? '') === 'unlisted')>Unlisted</option></select>
                        @if(array_filter($filters))<a class="ci-btn light" href="{{ route('insights.index') }}" style="padding:7px 12px">Clear</a>@endif
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
                <div class="ci-pager" style="margin-top:14px">{{ $directory->links() }}</div>
            </div>
        </section>
    @endif

    @if($isOwner)
        <section class="ci-card ci-admin">
            <h2>Import MCA company data</h2>
            <p class="ci-muted">Upload MCA's Excel downloads (mca.gov.in → Data &amp; Reports → Company/LLP Info → “Incorporated/Closed during the month”) or a Company Master Data CSV. Each company gets its CIN, class, category, date of incorporation, authorised and paid-up capital, ROC, activity and address. Large files import in the background.</p>
            <form method="post" action="{{ route('insights.master-data') }}" enctype="multipart/form-data" class="ci-inline">@csrf
                <input type="file" name="csv" accept=".csv,.xlsx" required>
                <button class="ci-btn" type="submit">Upload CSV</button>
            </form>
        </section>
    @endif
</main>
@endsection
