@extends('layouts.employer')
@section('title','Onboarding · ' . ($o->application?->candidate?->name ?? 'New hire'))
@include('employer.hiring._style')
@section('content')
@php($a = $o->application)
<div class="hs-head">
    <div><h1>{{ $a?->candidate?->name ?? 'New hire' }}</h1>
        <div class="muted">{{ $a?->job?->title }} · {{ \App\Models\HiringOnboarding::STATUSES[$o->status] ?? $o->status }} · {{ $o->progress }}% done @if($o->due_on) · joining {{ $o->due_on->format('d M Y') }} @endif</div></div>
    <div class="hs-row">
        @if($a)<a class="hs-mini" href="{{ route('employer.hiring.candidate', $a) }}">Candidate profile</a>@endif
        @if($canEdit && $o->status === 'in_progress')
            <form method="post" action="{{ route('employer.hiring.onboarding.resend', $o) }}">@csrf<button class="hs-mini">Email a new link</button></form>
            <form method="post" action="{{ route('employer.hiring.onboarding.cancel', $o) }}" onsubmit="return confirm('Cancel this onboarding? The candidate\'s link will stop working.')">@csrf<button class="hs-mini danger">Cancel</button></form>
        @endif
    </div>
</div>
<div style="height:8px;border-radius:8px;background:#e6eefa;overflow:hidden;margin-bottom:16px"><div style="height:100%;width:{{ $o->progress }}%;background:{{ $o->progress === 100 ? '#0b7a43' : '#2563eb' }}"></div></div>
@if($o->status === 'in_progress')<p class="muted" style="margin-top:-6px">The candidate's link is valid until {{ $o->token_expires_at?->format('d M Y') ?? '—' }}. When every required item is approved or done, the candidate moves to <strong>Hired</strong> automatically.</p>@endif

@foreach(['document' => 'Documents from the new hire', 'task' => 'Team tasks'] as $kind => $heading)
<section class="hs-card hs-scroll">
    <h2>{{ $heading }}</h2>
    <table class="hs-table">
        <thead><tr><th>Item</th><th>Status</th>@if($kind === 'document')<th>File</th>@endif<th></th></tr></thead>
        <tbody>
        @foreach($o->items->where('kind', $kind) as $item)
            @php($cls = ['pending' => 'wait', 'submitted' => 'new', 'approved' => 'ok', 'rejected' => 'bad', 'done' => 'ok'][$item->status] ?? '')
            <tr>
                <td><strong>{{ $item->label }}</strong>@unless($item->required) <span class="muted">(optional)</span>@endunless
                    @if($item->review_note)<div class="muted" style="font-size:12px">Note: {{ $item->review_note }}</div>@endif</td>
                <td><span class="pill {{ $cls }}">{{ \App\Models\HiringOnboardingItem::STATUSES[$item->status] ?? $item->status }}</span>
                    @if($item->submitted_at)<div class="muted" style="font-size:12px">uploaded {{ $item->submitted_at->diffForHumans() }}</div>@endif</td>
                @if($kind === 'document')
                    <td>@if($item->file_path)<a href="{{ route('employer.hiring.onboarding.file', $item) }}" target="_blank" rel="noopener">{{ $item->file_name }}</a><div class="muted" style="font-size:12px">{{ number_format(($item->file_size ?? 0) / 1024) }} KB</div>@else<span class="muted">—</span>@endif</td>
                @endif
                <td style="white-space:nowrap">
                    @if($canEdit && $o->status === 'in_progress')
                        @if($kind === 'document' && $item->status === 'submitted')
                            <form method="post" action="{{ route('employer.hiring.onboarding.review', $item) }}" style="display:inline">@csrf @method('PATCH')<input type="hidden" name="decision" value="approved"><button class="hs-mini primary">Approve</button></form>
                            <details style="display:inline-block"><summary class="hs-mini danger" style="display:inline-flex">Ask to re-upload</summary>
                                <form method="post" action="{{ route('employer.hiring.onboarding.review', $item) }}" class="hs-row" style="margin-top:6px">@csrf @method('PATCH')<input type="hidden" name="decision" value="rejected">
                                    <input class="hs-in" name="note" required maxlength="300" placeholder="What should they fix?" style="width:220px"><button class="hs-mini danger">Send</button></form></details>
                        @elseif($kind === 'task' && $item->status !== 'done')
                            <form method="post" action="{{ route('employer.hiring.onboarding.review', $item) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="done"><button class="hs-mini primary">Mark done</button></form>
                        @elseif($item->isDone())
                            <form method="post" action="{{ route('employer.hiring.onboarding.review', $item) }}">@csrf @method('PATCH')<input type="hidden" name="decision" value="pending"><button class="hs-mini">Reopen</button></form>
                        @endif
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</section>
@endforeach
@endsection
