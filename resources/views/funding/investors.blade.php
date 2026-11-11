@extends('layouts.site')
@section('title', 'Investors — venture funds, angels and corporates')
@push('styles')@include('insights.partials.db-styles')@endpush
@section('content')
<div class="db-page">
<div class="db-layout two">
    @include('insights.partials.explore-nav')
    <main style="min-width:0">
        <div class="ex-top">
            <div><small>Funding</small><h1>Investors<span>{{ number_format($investors->total()) }} investors</span></h1></div>
            <a href="{{ route('funding.rounds') }}">Funding rounds →</a>
        </div>
        <form class="ex-filters" method="get">
            <input type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Search investor" aria-label="Search">
            <select name="type" aria-label="Type"><option value="">All types</option>@foreach(\App\Models\Investor::TYPES as $k => $label)<option value="{{ $k }}" @selected(($f['type'] ?? '') === $k)>{{ $label }}</option>@endforeach</select>
            <select name="sort" aria-label="Sort">@foreach(['rounds' => 'Most rounds', 'leads' => 'Most rounds led', 'recent' => 'Most recent'] as $k => $label)<option value="{{ $k }}" @selected(($f['sort'] ?? 'rounds') === $k)>{{ $label }}</option>@endforeach</select>
            <button type="submit">Filter</button>
            @if(array_filter($f))<a href="{{ route('funding.investors') }}">Clear</a>@endif
        </form>
        <div class="ex-table-wrap">
            <table class="ex-table">
                <thead><tr><th>#</th><th>Investor</th><th>Type</th><th>Rounds</th><th>Led</th><th>Last deal</th><th>Recent portfolio</th></tr></thead>
                <tbody>
                @forelse($investors as $i => $inv)
                    <tr>
                        <td>{{ $investors->firstItem() + $i }}</td>
                        <td><div class="ex-co"><span class="db-logo txt">{{ mb_strtoupper(mb_substr($inv->name, 0, 1)) }}</span><div><a href="{{ route('funding.investor', $inv->slug) }}">{{ $inv->name }}</a></div></div></td>
                        <td><span class="ex-stage pvt">{{ $inv->typeLabel() }}</span></td>
                        <td class="num">{{ number_format($inv->rounds_count) }}</td>
                        <td class="num">{{ number_format($inv->lead_count) }}</td>
                        <td class="num">{{ $inv->last_invested_on?->format('d M Y') ?? '—' }}</td>
                        <td>{{ ($recent[$inv->id] ?? collect())->implode(' · ') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">No investors match.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="ci-pager" style="margin-top:14px">{{ $investors->links() }}</div>
    </main>
</div>
</div>
@endsection
