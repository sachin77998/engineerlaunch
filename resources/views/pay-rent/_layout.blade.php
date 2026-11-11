{{-- Pay rent shell: blue header with title/back link and a "My payments" button. Expects $title, optional $back. --}}
@include('properties._styles')
@push('styles')<style>
.pz-top{background:#fff;border-bottom:1px solid var(--pr-line)}.pz-top .pr-wrap{display:flex;justify-content:space-between;align-items:center;min-height:64px;gap:12px}
.pz-top a.mine{border:1px solid #111827;border-radius:8px;padding:9px 16px;color:#111827;text-decoration:none;font-weight:600}
.pz-hero{background:linear-gradient(120deg,#1e3a8a,#1d4ed8);color:#fff;padding:40px 0 110px;border-radius:0 0 60px 60px}
.pz-hero h1{color:#fff;margin:0;font-size:clamp(26px,3.4vw,36px);display:flex;gap:14px;align-items:center}.pz-hero h1 a{color:#fff;text-decoration:none}
.pz-hero p{margin:8px 0 0;color:#93c5fd;font-weight:700}
.pz-body{margin-top:-80px;padding-bottom:60px}
.pz-methods{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px;max-width:880px}
.pz-method{background:#fff;border:6px solid #e0e7ff;border-radius:16px;padding:28px 20px;display:flex;align-items:center;justify-content:center;gap:14px;font-size:20px;color:#111827;text-decoration:none;box-shadow:0 8px 20px rgba(30,58,138,.12)}
.pz-method:hover{border-color:#bfdbfe}.pz-method i{font-style:normal;font-size:30px}
.pz-card{background:#fff;border-radius:22px;padding:30px;box-shadow:0 8px 24px rgba(17,24,39,.06);max-width:880px;margin-top:24px}
.pz-card h2{font-size:24px;margin:0 0 18px}
.pz-purposes{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px}
.pz-purpose{display:flex;align-items:center;gap:12px;border:1px solid var(--pr-line);border-radius:12px;padding:14px;text-decoration:none;color:inherit;box-shadow:0 2px 8px rgba(17,24,39,.04)}
.pz-purpose i{font-style:normal;font-size:26px}.pz-purpose b{display:block}.pz-purpose small{color:var(--pr-mute)}.pz-purpose span.go{margin-left:auto;width:30px;height:30px;border-radius:50%;background:#e5e7eb;display:grid;place-items:center;color:#fff;background:#9ca3af}
.pz-form{display:grid;gap:16px;max-width:560px}.pz-form label.f{font-weight:500;color:#374151}
.pz-note{background:#fffbeb;border-left:3px solid #f59e0b;padding:8px 12px;font-size:13px;color:#92400e;border-radius:4px}
.pz-actions{border-top:1px solid var(--pr-line);margin:24px -30px -30px;padding:14px 30px;display:flex;justify-content:flex-end}
.pz-actions .pr-btn{background:#f43f5e;min-width:300px;padding:14px}.pz-actions .pr-btn:hover{background:#e11d48}
.pz-off{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:12px;padding:12px 16px;margin-top:16px;max-width:880px;box-sizing:border-box}
@media(max-width:700px){.pz-methods{grid-template-columns:1fr}.pz-actions .pr-btn{min-width:0;width:100%}}
</style>@endpush
<div class="pz-top"><div class="pr-wrap">
    <a class="pr-brand" href="{{ route('pay-rent.index') }}"><i>₹</i>{{ config('properties.brand') }} Pay</a>
    @auth<a class="mine" href="{{ route('pay-rent.mine') }}">My payments</a>@endauth
</div></div>
<section class="pz-hero"><div class="pr-wrap">
    <h1>@isset($back)<a href="{{ $back }}" aria-label="Back">‹</a>@endisset {{ $title }}</h1>
    @isset($tagline)<p>{{ $tagline }}</p>@endisset
</div></section>
