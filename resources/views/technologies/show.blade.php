@extends('layouts.site')
@section('title', $technology->name.' Jobs — Ascendia')
@push('styles')<style>
.tjs{width:min(1100px,calc(100% - 32px));margin:24px auto 56px;font-family:Inter,system-ui,sans-serif}
.tjs-crumb{font-size:13px;color:#5b6b82;margin-bottom:8px}.tjs-crumb a{color:#1d4ed8;text-decoration:none}
.tjs-head{display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:flex-end;margin-bottom:14px}
.tjs-head h1{margin:0;color:#0b2545}.tjs-head p{margin:4px 0 0;color:#5b6b82}
.tjs-filter{display:flex;gap:8px}.tjs-filter input{padding:9px 12px;border:1px solid #d7e1ef;border-radius:10px;font:inherit}.tjs-filter button{padding:9px 14px;border:0;border-radius:10px;background:#f4b400;color:#0b2545;font-weight:800;cursor:pointer}
.tjs-related{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 18px}.tjs-related a{padding:5px 11px;border-radius:999px;border:1px solid #dfe6f1;background:#fff;color:#0b2545;text-decoration:none;font-size:13px}
.tjs-list{display:grid;gap:10px}
.tjs-job{display:flex;gap:12px;align-items:center;padding:14px;border:1px solid #e2e8f2;border-radius:12px;background:#fff;color:#0f1c2e;text-decoration:none}
.tjs-job:hover{border-color:#f4b400;box-shadow:0 8px 20px rgba(11,37,69,.08)}
.tjs-job strong{display:block;font-size:15px}.tjs-job span{font-size:13px;color:#5b6b82}
.tjs-empty{padding:24px;border:1px dashed #d7e1ef;border-radius:12px;background:#fff;color:#5b6b82}
.sl-logo{position:relative;display:inline-grid;place-items:center;flex:none;width:40px;height:40px;border-radius:10px;overflow:hidden;background:hsl(var(--hue) 55% 92%);color:hsl(var(--hue) 55% 30%);font-size:13px;font-weight:800;border:1px solid #e2e8f2}
.sl-logo img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain;background:#fff;padding:3px;box-sizing:border-box}
</style>@endpush
@section('content')
<main class="tjs">
    <nav class="tjs-crumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a> › <a href="{{ route('technologies.index') }}">Languages &amp; Technologies</a> › <span>{{ $technology->name }}</span></nav>
    <div class="tjs-head">
        <div><h1>{{ $technology->name }} jobs</h1><p>{{ number_format($jobs->total()) }} live {{ \Illuminate\Support\Str::plural('opening', $jobs->total()) }}@if($technology->category) · {{ $technology->category }}@endif</p></div>
        <form class="tjs-filter" method="get"><input name="location" value="{{ request('location') }}" placeholder="City or country" aria-label="Location"><button>Filter</button></form>
    </div>
    @if($related->isNotEmpty())
        <div class="tjs-related" aria-label="Often asked together">@foreach($related as $other)<a href="{{ route('technologies.show', $other->slug) }}">{{ $other->name }}</a>@endforeach</div>
    @endif
    <div class="tjs-list">
        @forelse($jobs as $job)
            <a class="tjs-job" href="{{ $job->slug ? route('jobs.show', $job->slug) : route('opportunities.show', $job->id) }}">
                @include('sectors.partials.logo', ['name' => $job->company?->name ?? $job->title, 'logo' => $job->company?->logo_url])
                <div><strong>{{ $job->title }}</strong><span>{{ $job->company?->name }}@if($job->location) · {{ $job->location }}@endif @if($job->posted_at) · {{ $job->posted_at->diffForHumans(null, true) }}@endif</span></div>
            </a>
        @empty
            <div class="tjs-empty">No live {{ $technology->name }} openings right now. New jobs are matched every day. <a href="{{ route('home') }}?q={{ rawurlencode($technology->name) }}#jobs">Search all jobs for “{{ $technology->name }}”</a>.</div>
        @endforelse
    </div>
    <div style="margin-top:16px">{{ $jobs->links() }}</div>
</main>
@endsection
