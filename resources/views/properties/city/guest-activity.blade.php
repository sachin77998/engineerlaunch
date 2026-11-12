<div class="cp-box" style="display:grid;gap:12px">
    <b>👤 {{ auth()->user()?->name ?? 'Guest user' }}</b>
    <small class="pr-mute" style="font-weight:700">Your recent activity</small>
    <div style="display:grid;grid-template-columns:repeat({{ $shortlisted !== null ? 3 : 2 }},1fr);gap:8px">
        <div style="background:#fff7ed;border-radius:10px;padding:10px"><b style="font-size:24px">{{ $viewed }}</b><br><small>Viewed</small></div>
        <div style="background:#eff6ff;border-radius:10px;padding:10px"><b style="font-size:24px">{{ $searched }}</b><br><small>Searched</small></div>
        @if($shortlisted !== null)<a href="{{ route('properties.shortlist') }}" style="background:#ecfdf5;border-radius:10px;padding:10px;text-decoration:none;color:inherit"><b style="font-size:24px">{{ $shortlisted }}</b><br><small>Shortlisted</small></a>@endif
    </div>
    @guest<a class="pr-btn" href="{{ route('login') }}">Log in to save your activity</a><small class="pr-mute" style="text-align:center">and see it on every device</small>@endguest
</div>
