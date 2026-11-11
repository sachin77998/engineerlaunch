{{-- Legal services header bar and styles. Expects $city, $cart (service ids). --}}
@include('properties._styles')
@push('styles')<style>
.lg-top{background:#1e1b4b;color:#fff}.lg-top .pr-wrap{display:flex;align-items:center;gap:14px;flex-wrap:wrap;min-height:64px}
.lg-top .pr-brand{color:#fff}.lg-top .pr-brand i{background:#e11d48}
.lg-top nav{margin-left:auto;display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.lg-top nav a{color:#fff;text-decoration:none;font-weight:700;font-size:14px;padding:8px 12px;border-radius:9px}.lg-top nav a:hover{background:rgba(255,255,255,.1)}
.lg-top nav a b{color:#fb7185;margin-left:4px}
.lg-hero{background:linear-gradient(180deg,#1e1b4b,#312e81);color:#fff;text-align:center;padding:34px 0 0}
.lg-hero h1{color:#fff;font-size:clamp(26px,4vw,48px);font-weight:600;margin:0 0 10px}
.lg-hero form{display:inline-flex;gap:8px;align-items:center;margin-bottom:26px}.lg-hero select{width:auto!important;background:rgba(255,255,255,.1)!important;color:#fff!important;border-color:rgba(255,255,255,.3)!important}.lg-hero select option{color:#111}
.lg-tabs{display:flex;justify-content:center;gap:10px;flex-wrap:wrap}
.lg-tabs button{min-width:150px;padding:16px 24px;border:0;border-radius:18px 18px 0 0;background:#4c4a8a;color:#fff;font:600 17px Inter,system-ui,sans-serif;cursor:pointer}
.lg-tabs button.on{background:#fff;color:#111827}
.lg-panel{background:#fff;border-radius:28px;padding:26px;margin-top:-1px;box-shadow:0 10px 30px rgba(17,24,39,.06)}
.lg-search{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-bottom:26px}
.lg-search label{flex:1;max-width:660px;display:flex;align-items:center;gap:8px;border:1px solid #d1d5db;border-radius:999px;padding:0 18px}.lg-search input{border:0!important;outline:0!important;padding:13px 0!important}
.lg-search button{border:2px solid #0f766e;color:#0f766e;background:#fff;border-radius:999px;padding:11px 24px;font-weight:700;cursor:pointer;font-size:16px}
.lg-body{display:grid;grid-template-columns:330px minmax(0,1fr);gap:30px;align-items:start}
.lg-cats{border:1px solid var(--pr-line);border-radius:20px;padding:10px 0 14px;box-shadow:0 4px 18px rgba(17,24,39,.05);position:sticky;top:12px}
.lg-cats h3{color:#64748b;font-weight:600;font-size:18px;padding:14px 24px;margin:0}
.lg-cats button{display:flex;gap:16px;align-items:center;width:100%;border:0;background:none;padding:15px 24px;font:500 17px Inter,system-ui,sans-serif;color:#111827;cursor:pointer;text-align:left;border-left:3px solid transparent}
.lg-cats button.on{background:#f1f5f9;border-left-color:#0f766e;font-weight:700}.lg-cats button[hidden]{display:none}
.lg-cats button span{font-size:22px;width:28px;text-align:center}
.lg-list h2{color:#64748b;font-weight:600;display:flex;gap:10px;align-items:center}.lg-list h2 a{width:30px;height:30px;border:2px solid #0f766e;border-radius:50%;display:grid;place-items:center;color:#0f766e;text-decoration:none;font-size:16px}
.lg-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:18px}
.lg-card{border:1px solid var(--pr-line);border-radius:20px;padding:22px;display:flex;flex-direction:column;gap:10px;color:inherit;text-decoration:none;background:#fff;transition:box-shadow .15s}
.lg-card:hover{box-shadow:0 8px 24px rgba(17,24,39,.08)}.lg-card[hidden]{display:none}
.lg-card h3{font-size:21px;margin:0}.lg-card p{margin:0;color:#475569;font-size:15.5px;line-height:1.55;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.lg-card footer{display:flex;justify-content:space-between;align-items:center;margin-top:auto;padding-top:8px;gap:8px}
.lg-card footer b{font-size:15px}
.lg-add{border:1px solid #0f766e;color:#0f766e;background:#fff;border-radius:999px;padding:7px 14px;font-weight:700;cursor:pointer;font-size:13px}.lg-add.in{background:#0f766e;color:#fff}
.lg-empty{color:var(--pr-mute);padding:30px;text-align:center;border:1px dashed var(--pr-line);border-radius:16px}
@media(max-width:860px){.lg-body{grid-template-columns:1fr}.lg-cats{position:static;display:flex;overflow-x:auto;padding:6px}.lg-cats h3{display:none}.lg-cats button{white-space:nowrap;padding:10px 14px;border-left:0;border-bottom:3px solid transparent}.lg-cats button.on{border-bottom-color:#0f766e}.lg-tabs button{min-width:0;padding:12px 16px}}
</style>@endpush
<div class="lg-top"><div class="pr-wrap">
    <a class="pr-brand" href="{{ route('legal.index', \Illuminate\Support\Str::slug($city)) }}"><i>⚖</i>{{ config('properties.brand') }} Legal Services</a>
    <nav>
        <a href="{{ route('properties.index') }}">Property</a>
        <a href="{{ route('rent-agreement.index') }}">Rent agreement</a>
        <a href="{{ route('home-loan.index') }}">Home loan</a>
        <a href="{{ route('legal.cart') }}">My cart<b>{{ count($cart) }}</b></a>
        <a href="{{ route('legal.bookings') }}">My bookings</a>
    </nav>
</div></div>
