@extends('layouts.employer')
@section('title','Interviews')
@include('employer.hiring._style')
@section('content')
<div class="hs-head"><div><h1>Interviews</h1><div class="muted">{{ $interviews->total() }} {{ str_replace('_', ' ', $when) }}</div></div></div>
<div class="chips">
    @foreach(['upcoming' => 'Upcoming', 'today' => 'Today', 'pending_feedback' => 'Awaiting feedback', 'past' => 'Past'] as $k => $v)
        <a href="{{ route('employer.hiring.interviews', ['when' => $k]) }}" class="{{ $when === $k ? 'on' : '' }}">{{ $v }}</a>
    @endforeach
</div>
<section class="hs-card hs-scroll" style="padding:6px">
    <table class="hs-table">
        <thead><tr><th>When</th><th>Candidate</th><th>Job</th><th>Round</th><th>Mode</th><th>Interviewer</th><th>Status</th><th>Feedback</th></tr></thead>
        <tbody>
        @forelse($interviews as $i)
            <tr>
                <td style="white-space:nowrap"><strong>{{ $i->scheduled_at->format('D, d M') }}</strong><div class="muted">{{ $i->scheduled_at->format('h:i A') }} · {{ $i->duration_minutes }}m</div></td>
                <td><a href="{{ route('employer.hiring.candidate', $i->application) }}"><strong>{{ $i->application?->candidate?->name }}</strong></a></td>
                <td>{{ $i->application?->job?->title }}</td>
                <td>{{ $i->round }}</td>
                <td>{{ \App\Models\HiringInterview::MODES[$i->mode] ?? $i->mode }}@if($i->location)<div class="muted" style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis">@if(str_starts_with($i->location, 'http'))<a href="{{ $i->location }}" target="_blank" rel="noopener">Join link</a>@else{{ $i->location }}@endif</div>@endif</td>
                <td>{{ $i->interviewer_name ?: '—' }}</td>
                <td><span class="pill {{ $i->status === 'completed' ? 'ok' : ($i->status === 'scheduled' ? 'wait' : 'bad') }}">{{ ucwords(str_replace('_', ' ', $i->status)) }}</span></td>
                <td>{{ $i->rating ? str_repeat('★', $i->rating) : '' }} {{ \App\Models\HiringInterview::RECOMMENDATIONS[$i->recommendation] ?? '' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center;padding:40px" class="muted">No interviews here.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
{{ $interviews->links() }}
@endsection
