@extends('layouts.site')
@section('title', $investor->name . ' — portfolio, rounds and co-investors')
@push('styles')@include('insights.partials.db-styles')@endpush
@section('content')
<div class="db-page">
<div class="db-layout two">
    @include('insights.partials.explore-nav')
    <main style="min-width:0">
        <div class="ex-top">
            <div><small><a href="{{ route('funding.investors') }}">Investors</a> · {{ $investor->typeLabel() }}</small><h1>{{ $investor->name }}<span>{{ $rounds->count() }} rounds · {{ $investor->lead_count }} led · {{ $rounds->pluck('company_key')->unique()->count() }} companies</span></h1></div>
            <small>@if($disclosed->isNotEmpty())~${{ number_format($disclosed->sum('amount_usd') / 1e6, 0) }} mn in rounds with a disclosed amount · @endif Last deal {{ $investor->last_invested_on?->format('d M Y') ?? '—' }}@if($investor->company?->slug) · <a href="{{ route('insights.show', $investor->company->slug) }}">Company profile</a>@endif</small>
        </div>
        @if($stages->isNotEmpty())
            <div class="db-card"><h3>Stages</h3>@foreach($stages as $type => $n)<span class="ex-stage" style="margin:0 6px 6px 0">{{ \App\Models\FundingRound::TYPES[$type] ?? $type }} · {{ $n }}</span>@endforeach</div>
        @endif
        <div class="ex-table-wrap" style="margin-bottom:16px">
            <table class="ex-table">
                <thead><tr><th>Date</th><th>Company</th><th>Round</th><th>Amount</th><th>Role</th><th>All investors</th></tr></thead>
                <tbody>
                @forelse($rounds as $r)
                    <tr>
                        <td class="num">{{ $r->announced_on->format('d M Y') }}</td>
                        <td>@include('funding._company')</td>
                        <td><span class="ex-stage">{{ $r->label() }}</span></td>
                        <td class="num">@if($r->source_url)<a href="{{ $r->source_url }}" target="_blank" rel="noopener nofollow" title="{{ $r->headline }}">{{ $r->amountLabel() ?? 'Undisclosed' }}</a>@else{{ $r->amountLabel() ?? 'Undisclosed' }}@endif</td>
                        <td>{{ $r->pivot->is_lead ? 'Lead' : 'Participant' }}</td>
                        <td style="min-width:240px">@include('funding._investors')</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No rounds on record.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($coInvestors->isNotEmpty())
            <div class="db-card"><h3>Invests most often with</h3>
                <div class="ex-table-wrap"><table class="ex-table"><thead><tr><th>Co-investor</th><th>Type</th><th>Shared rounds</th></tr></thead><tbody>
                @foreach($coInvestors as $c)<tr><td><a href="{{ route('funding.investor', $c->slug) }}">{{ $c->name }}</a></td><td>{{ \App\Models\Investor::TYPES[$c->type] ?? 'Investment firm' }}</td><td class="num">{{ $c->shared }}</td></tr>@endforeach
                </tbody></table></div>
            </div>
        @endif
    </main>
</div>
</div>
@endsection
