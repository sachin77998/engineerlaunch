@extends('employer.hiring._public')
@section('title','Your offer')
@section('content')
@php($co = $a->job?->company?->name)
<h1>Offer from {{ $co ?? 'the company' }}</h1>
<p class="muted">For {{ $a->candidate?->name }}</p>
<table>
    <tr><td>Position</td><td><strong>{{ $o->designation }}</strong></td></tr>
    <tr><td>Annual CTC</td><td><strong>{{ $o->currency }} {{ number_format($o->annual_ctc) }}</strong></td></tr>
    @if($o->fixed_pay)<tr><td>Fixed pay</td><td>{{ $o->currency }} {{ number_format($o->fixed_pay) }}</td></tr>@endif
    @if($o->variable_pay)<tr><td>Variable pay</td><td>{{ $o->currency }} {{ number_format($o->variable_pay) }}</td></tr>@endif
    @if($o->joining_bonus)<tr><td>Joining bonus</td><td>{{ $o->currency }} {{ number_format($o->joining_bonus) }}</td></tr>@endif
    <tr><td>Joining date</td><td>{{ $o->joining_date->format('d M Y') }}</td></tr>
    @if($o->work_location)<tr><td>Location</td><td>{{ $o->work_location }}</td></tr>@endif
    <tr><td>Respond by</td><td>{{ $o->expires_on->format('d M Y') }}</td></tr>
</table>
@if($token)
    <p><a class="btn" href="{{ route('hiring.offer.pdf', $token) }}" target="_blank" rel="noopener">View offer letter (PDF)</a></p>
    @if($o->effective_status === 'expired')
        <div class="err">This offer expired on {{ $o->expires_on->format('d M Y') }}. Please contact the recruiter.</div>
    @else
        <form method="post" action="{{ route('hiring.offer.respond', $token) }}">@csrf
            <label>Message to the recruiter (optional)</label><textarea name="note" rows="3" maxlength="500"></textarea>
            <div style="display:flex;gap:10px;margin-top:14px">
                <button class="btn primary" name="decision" value="accepted" style="flex:1">Accept offer</button>
                <button class="btn danger" name="decision" value="declined" style="flex:1" onclick="return confirm('Decline this offer?')">Decline</button>
            </div>
        </form>
    @endif
@else
    <div class="ok">Thank you. You have <strong>{{ $o->status }}</strong> this offer{{ $o->status === 'accepted' ? '. The recruiter will contact you with the next steps.' : '.' }}</div>
@endif
@endsection
