@extends('layouts.site')
@section('title', 'Funding rounds — startup and venture deals')
@push('styles')@include('insights.partials.db-styles')@endpush
@section('content')
<div class="db-page">
<div class="db-layout two">
    @include('insights.partials.explore-nav')
    <main style="min-width:0">
        <div class="ex-top">
            <div><small>Funding</small><h1>Funding rounds<span>{{ number_format($rounds->total()) }} rounds</span></h1></div>
            <small>{{ number_format($stats['rounds_30']) }} rounds · ~${{ number_format($stats['usd_30'] / 1e6, 0) }} mn in the last 30 days · {{ number_format($stats['investors_90']) }} active investors (90 days)</small>
        </div>
        <form class="ex-filters" method="get">
            <input type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Search company or investor" aria-label="Search">
            <select name="type" aria-label="Round"><option value="">All rounds</option>@foreach(\App\Models\FundingRound::TYPES as $k => $label)<option value="{{ $k }}" @selected(($f['type'] ?? '') === $k)>{{ $label }}</option>@endforeach</select>
            <select name="period" aria-label="Period">@foreach(\App\Http\Controllers\FundingController::PERIODS as $k => $label)<option value="{{ $k }}" @selected($f['period'] === (string) $k)>{{ $label }}</option>@endforeach</select>
            <button type="submit">Filter</button>
            @if(filled($f['q'] ?? null) || filled($f['type'] ?? null) || $f['period'] !== '90')<a href="{{ route('funding.rounds') }}">Clear</a>@endif
            <a href="{{ route('funding.investors') }}" style="margin-left:auto">Investors →</a>
        </form>
        <div class="ex-table-wrap">
            <table class="ex-table">
                <thead><tr><th>Date</th><th>Company</th><th>Round</th><th>Amount</th><th>Investors</th><th>Source</th></tr></thead>
                <tbody>
                @forelse($rounds as $r)
                    <tr>
                        <td class="num">{{ $r->announced_on->format('d M Y') }}</td>
                        <td>@include('funding._company')</td>
                        <td><span class="ex-stage">{{ $r->label() }}</span></td>
                        <td class="num">{{ $r->amountLabel() ?? 'Undisclosed' }}</td>
                        <td style="min-width:240px">@include('funding._investors')</td>
                        <td>@if($r->source_url)<a href="{{ $r->source_url }}" target="_blank" rel="noopener nofollow" title="{{ $r->headline }}">News</a>@if($r->sources_count > 1) <small>+{{ $r->sources_count - 1 }}</small>@endif @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No rounds match. Rounds are read from funding news every 30 minutes.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="ci-pager" style="margin-top:14px">{{ $rounds->links() }}</div>
    </main>
</div>
</div>
@endsection
