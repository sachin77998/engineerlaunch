@extends('layouts.employer')
@section('title','Hiring Team')
@include('employer.hiring._style')
@section('content')
@php($roles = \App\Services\Hiring\Team::ROLES)
<div class="hs-head">
    <div><h1>Hiring Team</h1><div class="muted">Owner: {{ $owner->name }} ({{ $owner->email }}) · your role: {{ $myRole === 'owner' ? 'Owner' : $roles[$myRole] }}</div></div>
    @if($myRole !== 'owner')<form method="post" action="{{ route('employer.hiring.team.leave') }}" onsubmit="return confirm('Leave this hiring team? You will lose access to its jobs and candidates.')">@csrf<button class="btn">Leave team</button></form>@endif
</div>

@if($canManage)
<form class="hs-card" method="post" action="{{ route('employer.hiring.team.invite') }}">@csrf
    <h2>Invite a team member</h2>
    <div class="hs-row">
        <input class="hs-in" type="email" name="email" placeholder="colleague@company.com" required style="flex:1;min-width:220px">
        <select class="hs-in" name="role" style="width:auto">@foreach($roles as $k => $v)<option value="{{ $k }}" @selected($k === 'recruiter')>{{ $v }}</option>@endforeach</select>
        <button class="hs-mini primary">Send invite</button>
    </div>
    <div class="muted" style="font-size:12px;margin-top:8px">Admin: everything incl. team &amp; costs · Recruiter: candidates, emails, offers, verification · Interviewer: sees candidates, gives feedback on interviews assigned to their email · Viewer: read-only</div>
</form>
@endif

<section class="hs-card hs-scroll" style="padding:6px">
    <table class="hs-table">
        <thead><tr><th>Member</th><th>Role</th><th>Status</th><th>Jobs posted</th><th>Joined</th>@if($canManage)<th></th>@endif</tr></thead>
        <tbody>
            <tr><td><strong>{{ $owner->name }}</strong><div class="muted">{{ $owner->email }}</div></td><td>Owner</td><td><span class="pill ok">Active</span></td><td>{{ $activity[$owner->id] ?? 0 }}</td><td>—</td>@if($canManage)<td></td>@endif</tr>
            @foreach($members as $m)
                <tr>
                    <td><strong>{{ $m->member?->name ?? 'Invited' }}</strong><div class="muted">{{ $m->email }}</div></td>
                    <td>
                        @if($canManage && $m->member_id !== auth()->id())
                            <form method="post" action="{{ route('employer.hiring.team.update', $m) }}">@csrf @method('PATCH')<select class="hs-in" name="role" onchange="this.form.submit()" style="width:auto">@foreach($roles as $k => $v)<option value="{{ $k }}" @selected($m->role === $k)>{{ $v }}</option>@endforeach</select></form>
                        @else{{ $roles[$m->role] ?? $m->role }}@endif
                    </td>
                    <td>@if($m->status === 'active')<span class="pill ok">Active</span>@elseif($m->invite_expires_at?->isPast())<span class="pill bad">Invite expired</span>@else<span class="pill wait">Invite pending</span>@endif</td>
                    <td>{{ $m->member_id ? ($activity[$m->member_id] ?? 0) : '—' }}</td>
                    <td>{{ $m->accepted_at?->format('d M Y') ?? 'Invited '.$m->created_at->format('d M Y') }}</td>
                    @if($canManage)
                        <td class="hs-row">
                            @if($m->status === 'pending')<form method="post" action="{{ route('employer.hiring.team.resend', $m) }}">@csrf<button class="hs-mini">Resend</button></form>@endif
                            @if($m->member_id !== auth()->id())<form method="post" action="{{ route('employer.hiring.team.remove', $m) }}" onsubmit="return confirm('Remove {{ $m->email }}? Jobs they posted move to the owner.')">@csrf @method('DELETE')<button class="hs-mini danger">{{ $m->status === 'pending' ? 'Cancel' : 'Remove' }}</button></form>@endif
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</section>
@endsection
