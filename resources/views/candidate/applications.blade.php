@extends('layouts.app')
@section('title', 'My Applications')
@push('styles')<style>
.trk{width:min(1100px,calc(100% - 32px));margin:24px auto 50px;font-family:Inter,system-ui,sans-serif}
.trk h1{margin:0 0 4px;color:#0b2545}.trk-sub{color:#5b6b82;margin:0 0 16px}
.trk-filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px}
.trk-filters a{padding:7px 12px;border-radius:999px;border:1px solid #dfe6f1;background:#fff;color:#0b2545;text-decoration:none;font-weight:600;font-size:13.5px}
.trk-filters a[aria-current=page]{background:#0b2545;color:#fff;border-color:#0b2545}
.trk-card{padding:18px;margin-bottom:14px;border:1px solid #dfe6f1;border-radius:14px;background:#fff;box-shadow:0 4px 14px rgba(11,37,69,.05)}
.trk-top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}.trk-top h2{margin:0;font-size:17px}.trk-top h2 a{color:#0b2545;text-decoration:none}
.trk-top span{color:#5b6b82;font-size:13.5px}
.trk-status{padding:5px 11px;border-radius:999px;font-weight:800;font-size:12.5px;background:#eef4ff;color:#1d4ed8;height:fit-content}
.trk-status.rejected{background:#fff1f2;color:#b42318}.trk-status.joined{background:#ecfdf3;color:#087443}.trk-status.withdrawn{background:#f1f5f9;color:#475569}
.trk-steps{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin:16px 0 10px;padding:0;list-style:none}
.trk-steps li{position:relative;padding-top:18px;font-size:12.5px;color:#94a3b8;text-align:center}
.trk-steps li:before{content:'';position:absolute;top:4px;left:0;right:0;height:5px;border-radius:3px;background:#e2e8f0}
.trk-steps li.done{color:#0b2545;font-weight:700}.trk-steps li.done:before{background:#f4b400}
.trk-pool{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px}.trk-pool span{padding:4px 9px;border-radius:8px;background:#f7f9fc;border:1px solid #e2e8f0;font-size:12.5px;color:#334155}
.trk-pool b{color:#0b2545}
.trk-history{margin-top:10px}.trk-history summary{cursor:pointer;font-weight:600;color:#1d4ed8;font-size:13.5px}
.trk-history ol{margin:8px 0 0;padding-left:18px;color:#334155;font-size:13.5px;line-height:1.7}
@media(max-width:640px){.trk-steps{grid-template-columns:1fr 1fr}}
</style>@endpush
@section('content')
<main class="trk">
    <h1>My Applications</h1>
    <p class="trk-sub">Track every application, see each status change and how many people applied, were rejected or got offers.</p>
    <nav class="trk-filters" aria-label="Filter by status">
        <a href="{{ route('applications.track') }}" @if(!$status) aria-current="page" @endif>All · {{ $counts->sum() }}</a>
        @foreach(['applied' => 'Applied', 'viewed_by_recruiter' => 'Viewed', 'in_progress' => 'In progress', 'rejected' => 'Rejected', 'joined' => 'Offer / Joined', 'withdrawn' => 'Withdrawn'] as $value => $label)
            <a href="{{ route('applications.track', ['status' => $value]) }}" @if($status === $value) aria-current="page" @endif>{{ $label }} · {{ $counts[$value] ?? 0 }}</a>
        @endforeach
    </nav>
    @forelse($applications as $application)
        @php
            $job = $application->job;
            $pool = $pools[$application->job_id] ?? null;
            $reached = array_search($application->status, array_keys($stages), true);
            $events = $history[$application->id] ?? collect();
        @endphp
        <article class="trk-card">
            <div class="trk-top">
                <div>
                    <h2>@if($job?->slug)<a href="{{ route('jobs.show', $job->slug) }}">{{ $job->title }}</a>@else{{ $job?->title ?? 'Job removed' }}@endif</h2>
                    <span>{{ $job?->company?->name }} · {{ $job?->location ?: 'Location not specified' }} · applied {{ $application->applied_at?->format('d M Y') }}</span>
                </div>
                <span class="trk-status {{ $application->status }}">{{ ucwords(str_replace('_', ' ', $application->status)) }}</span>
            </div>
            @if(!in_array($application->status, ['rejected', 'withdrawn'], true))
                <ol class="trk-steps" aria-label="Application progress">
                    @foreach($stages as $key => $label)<li class="{{ $reached !== false && $loop->index <= $reached ? 'done' : '' }}">{{ $label }}</li>@endforeach
                </ol>
            @endif
            @if($pool)
                <div class="trk-pool" aria-label="Applicants for this job">
                    <span><b>{{ $pool->total }}</b> applied</span><span><b>{{ (int) $pool->in_review }}</b> in review</span>
                    <span><b>{{ (int) $pool->rejected }}</b> rejected</span><span><b>{{ (int) $pool->offers }}</b> offers / joined</span><span><b>{{ (int) $pool->withdrawn }}</b> withdrawn</span>
                </div>
            @endif
            @if($events->isNotEmpty())
                <details class="trk-history"><summary>Status history ({{ $events->count() }})</summary><ol>
                    @foreach($events as $event)<li>{{ \Illuminate\Support\Carbon::parse($event->created_at)->format('d M Y, h:i A') }} — {{ ucwords(str_replace('_', ' ', $event->to_status)) }}@if($event->note) · {{ $event->note }}@endif</li>@endforeach
                </ol></details>
            @endif
            @if(in_array($application->status, ['applied', 'viewed_by_recruiter', 'in_progress'], true))
                <form method="post" action="{{ route('applications.withdraw', $application) }}" style="margin-top:10px">@csrf @method('PATCH')<button class="btn btn-outline-danger btn-sm" type="submit">Withdraw</button></form>
            @endif
        </article>
    @empty
        <p>No applications{{ $status ? ' with this status' : ' yet' }}. <a href="{{ route('home') }}#jobs">Find jobs</a> or ask the <a href="{{ route('home') }}#assistant">job assistant</a>.</p>
    @endforelse
    {{ $applications->links() }}
</main>
@include('partials.recommended-jobs', ['heading' => 'Jobs similar to the ones you applied for'])
@endsection
