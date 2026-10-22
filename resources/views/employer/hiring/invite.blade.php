@extends('employer.hiring._public')
@section('title','Join hiring team')
@section('content')
<h1>Join {{ $company ?? 'the' }} hiring team</h1>
<p class="muted">You were invited as <strong>{{ \App\Services\Hiring\Team::ROLES[$invite->role] ?? $invite->role }}</strong> using {{ $invite->email }}.</p>
@if($mismatch)
    <div class="err">You are signed in as {{ auth()->user()->email }}, but this invite is for {{ $invite->email }}. Sign out and open the link again.</div>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn">Sign out</button></form>
@else
    <form method="post" action="{{ route('hiring.invite.register', $token) }}">@csrf
        <label>Your name</label><input name="name" value="{{ old('name') }}" required maxlength="120">
        <label>Designation</label><input name="designation" value="{{ old('designation') }}" maxlength="100" placeholder="e.g. Talent Acquisition Specialist">
        <label>Create password</label><input type="password" name="password" required minlength="8" autocomplete="new-password">
        <label>Confirm password</label><input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
        <p class="muted" style="font-size:13px">At least 8 characters with letters and numbers.</p>
        <button class="btn primary" style="width:100%">Create account &amp; join team</button>
    </form>
@endif
@endsection
