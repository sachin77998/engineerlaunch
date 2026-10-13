@extends('layouts.site')
@section('title', $query ? "Company insights: {$query}" : 'Insights into Company — CIN, GSTIN and financials')
@push('styles')@include('insights.partials.styles')@endpush
@section('content')
@php($isOwner = auth()->check() && (auth()->user()->role_code === 2 || auth()->user()->role === 'admin'))
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
            @if($results->isEmpty() && !$decoded)<p class="ci-muted">No company matches “{{ $query }}”. Try the registered name (e.g. “Happy Forgings”), the 21-character CIN or the 15-character GSTIN.</p>@endif
            <div class="ci-grid">
                @foreach($results as $company)
                    @include('insights.partials.company-card', ['company' => $company])
                @endforeach
            </div>
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
    @elseif($recent->isNotEmpty())
        <section>
            <h2 class="ci-h2">Companies with financial statements</h2>
            <div class="ci-grid">@foreach($recent as $company)@include('insights.partials.company-card', ['company' => $company])@endforeach</div>
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
