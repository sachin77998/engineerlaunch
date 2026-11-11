@extends('layouts.site')
@section('title', 'People flags — founder and leadership movements')
@push('styles')@include('insights.partials.db-styles')@endpush
@section('content')
@php
    $kinds = \App\Services\Insights\PeopleFlags::KINDS;
    $person = fn ($r) => $r->din ? '<a href="' . e(route('insights.explore', 'people') . '?q=' . $r->din) . '">' . e($r->name) . '</a>' : '<b>' . e($r->name) . '</b>';
    $co = fn ($name, $slug) => $slug ? '<a href="' . e(route('insights.show', $slug)) . '">' . e($name) . '</a>' : e($name);
    $date = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
@endphp
<div class="db-page">
<div class="db-layout two">
    @include('insights.partials.explore-nav')
    <main style="min-width:0">
        <div class="ex-top">
            <div><small>Funding</small><h1>People flags<span>{{ $kinds[$kind] }} · {{ \App\Services\Insights\PeopleFlags::PERIODS[(string) $days] }}</span></h1></div>
        </div>
        <form class="ex-filters" method="get">
            <input type="hidden" name="kind" value="{{ $kind }}">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search person, DIN or company" aria-label="Search">
            <select name="period" aria-label="Period">@foreach(\App\Services\Insights\PeopleFlags::PERIODS as $k => $label)<option value="{{ $k }}" @selected($days === (int) $k)>{{ $label }}</option>@endforeach</select>
            <button type="submit">Filter</button>
            @if($q)<a href="{{ route('funding.people', ['kind' => $kind, 'period' => $days]) }}">Clear</a>@endif
        </form>
        <div class="ex-filters">
            @foreach($kinds as $k => $label)
                <a href="{{ route('funding.people', array_filter(['kind' => $k, 'period' => $days, 'q' => $q])) }}" class="ex-stage {{ $k === $kind ? '' : 'pvt' }}" style="text-decoration:none;padding:6px 12px">{{ $label }}@isset($counts[$k]) · {{ $counts[$k] >= 200 ? '200+' : $counts[$k] }}@endisset</a>
            @endforeach
        </div>
        <div class="ex-table-wrap">
            <table class="ex-table">
                @if($kind === 'ventures')
                    <thead><tr><th>Incorporated</th><th>Person</th><th>New company</th><th>Role</th><th>Also on the board of</th></tr></thead>
                    <tbody>
                    @forelse($rows as $r)
                        <tr><td class="num">{{ $date($r->on) }}</td><td>{!! $person($r) !!}</td><td>{!! $co($r->company, $r->slug) !!}@if($r->industry)<small style="display:block;color:#5b6b82">{{ $r->industry }}</small>@endif</td><td>{{ $r->designation ?? '—' }}</td>
                            <td>@foreach($r->boards as $b){!! $co($b->name, $b->slug) !!}@if($b->ceased_on) <small>(past)</small>@endif @if(!$loop->last) · @endif @endforeach</td></tr>
                    @empty
                        <tr><td colspan="5">No new ventures by known directors in this period.</td></tr>
                    @endforelse
                    </tbody>
                @elseif($kind === 'moves')
                    <thead><tr><th>Joined</th><th>Person</th><th>From</th><th>To</th><th>New role</th></tr></thead>
                    <tbody>
                    @forelse($rows as $r)
                        <tr><td class="num">{{ $date($r->on) }}</td><td>{!! $person($r) !!}</td><td>{!! $co($r->from_company, $r->from_slug) !!}<small style="display:block;color:#5b6b82">{{ $r->from_designation }} · left {{ $date($r->left_on) }}</small></td><td>{!! $co($r->company, $r->slug) !!}</td><td>{{ $r->designation ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5">No moves between companies in this period.</td></tr>
                    @endforelse
                    </tbody>
                @else
                    <thead><tr><th>{{ $kind === 'left' ? 'Left' : 'Appointed' }}</th><th>Person</th><th>Company</th><th>Role</th><th>Other current boards</th></tr></thead>
                    <tbody>
                    @forelse($rows as $r)
                        <tr><td class="num">{{ $date($r->on) }}</td><td>{!! $person($r) !!}</td><td>{!! $co($r->company, $r->slug) !!}</td><td>{{ $r->designation ?? '—' }}</td><td class="num">{{ $r->other_boards ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5">No {{ $kind === 'left' ? 'exits' : 'appointments' }} in this period.</td></tr>
                    @endforelse
                    </tbody>
                @endif
            </table>
        </div>
    </main>
</div>
</div>
@endsection
