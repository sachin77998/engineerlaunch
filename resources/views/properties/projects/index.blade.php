@extends('layouts.site')
@section('title', 'Projects' . ($cityName ? ' in ' . $cityName : '') . ' — new launch, under construction, ready to move')
@section('content')
@include('properties._nav')
@include('properties.city._styles')
@php($year = (int) date('Y'))
<div class="pr">
    <section class="cp-hero"><div class="pr-wrap">
        <h1 style="color:#fff;margin:0 0 12px">Projects{{ $cityName ? ' in ' . $cityName : '' }}</h1>
        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap">
            <input type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Project or locality" style="flex:1;min-width:200px;padding:10px 12px;border-radius:10px;border:0">
            <select name="status" style="padding:10px;border-radius:10px;border:0"><option value="">Any status</option>@foreach(config('properties.project_statuses') as $k => $label)<option value="{{ $k }}" @selected(($f['status'] ?? '') === $k)>{{ $label }}</option>@endforeach</select>
            <select name="possession" style="padding:10px;border-radius:10px;border:0"><option value="">Any possession</option><option value="ready" @selected(($f['possession'] ?? '') === 'ready')>Ready to move</option>
                @foreach(range($year, $year + 2) as $y)<option value="{{ $y }}" @selected(($f['possession'] ?? '') === (string) $y)>In {{ $y }}</option>@endforeach<option value="later" @selected(($f['possession'] ?? '') === 'later')>{{ $year + 3 }} or later</option></select>
            @if(!empty($f['builder']))<input type="hidden" name="builder" value="{{ $f['builder'] }}">@endif
            <button class="pr-btn">Search</button>
        </form>
    </div></section>
    <div class="pr-wrap" style="margin-top:22px">
        @if($city)<p><a href="{{ route('real-estate.city', $city) }}">← Real estate in {{ $cityName }}</a></p>@endif
        @if($projects->isEmpty())
            <div class="cp-empty"><h2>No projects match</h2><p class="pr-mute">Try another status or possession date.</p></div>
        @else
            <p class="pr-mute">{{ number_format($projects->total()) }} {{ $projects->total() === 1 ? 'project' : 'projects' }}</p>
            <div class="cp-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
                @foreach($projects as $pj)
                    @include('properties.city._project', ['pj' => $pj, 'offers' => true])
                @endforeach
            </div>
            <div style="margin-top:18px">{{ $projects->links() }}</div>
        @endif
    </div>
</div>
@endsection
