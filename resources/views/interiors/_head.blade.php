{{-- Interiors header bar and shared styles. --}}
@include('properties._styles')
@push('styles')<style>
.in-top{background:#fff;border-bottom:1px solid var(--pr-line);position:sticky;top:0;z-index:30}.in-top .pr-wrap{display:flex;align-items:center;gap:12px;min-height:64px;flex-wrap:wrap}
.in-top .pr-brand i{background:#e11d48}.in-top nav{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
.in-top nav a{border:1px solid #fda4af;color:#e11d48;border-radius:8px;padding:9px 14px;font-weight:700;text-decoration:none;font-size:14px}.in-top nav a.solid{background:#e11d48;color:#fff;border-color:#e11d48}
.in-sec{margin-top:40px}.in-sec>h2{font-size:30px;margin:0 0 18px;display:flex;justify-content:space-between;align-items:baseline;gap:10px;flex-wrap:wrap}.in-sec>h2 a{font-size:15px;color:#e11d48}
.in-row{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(220px,1fr);gap:14px;overflow-x:auto;padding-bottom:6px;scroll-snap-type:x mandatory}.in-row>*{scroll-snap-align:start}
.in-tile{position:relative;border-radius:16px;overflow:hidden;min-height:300px;display:flex;align-items:flex-end;color:#fff;text-decoration:none;background:#44403c center/cover}
.in-tile::before{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 45%,rgba(0,0,0,.75))}
.in-tile div{position:relative;padding:14px;width:100%;display:flex;justify-content:space-between;align-items:flex-end;gap:8px}.in-tile b{font-size:16px}.in-tile small{display:block;opacity:.85;font-size:12px}
.in-tile span.go{width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,.25);display:grid;place-items:center;flex:none}
.in-tile.empty{background:linear-gradient(135deg,#78716c,#292524)}.in-tile em{position:absolute;top:16px;left:16px;font-style:normal;font-size:40px}
.in-band{background:#f5f0e8;border-radius:28px;padding:18px}
.in-cta{background:#fff;border-radius:22px;padding:24px 28px;display:flex;align-items:center;gap:18px;flex-wrap:wrap;box-shadow:0 8px 24px rgba(17,24,39,.06);border-left:5px solid #e11d48}
.in-cta h3{margin:0;font-size:24px}.in-cta p{margin:4px 0 0;color:var(--pr-mute)}.in-cta .chips{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}.in-cta .chips span{background:#f3f4f6;border-radius:999px;padding:9px 16px;font-size:12px;font-weight:700;letter-spacing:.06em}
.in-red{background:#e11d48!important;color:#fff!important;border-radius:10px;padding:14px 24px;font-weight:800;text-decoration:none;border:0;cursor:pointer;font-size:16px}.in-red:hover{background:#be123c!important}
.in-pill{display:inline-block;background:rgba(0,0,0,.45);border:1px solid rgba(255,255,255,.3);border-radius:6px;padding:3px 9px;font-size:12.5px;margin-right:6px}
</style>@endpush
<div class="in-top"><div class="pr-wrap">
    <a class="pr-brand" href="{{ route('interiors.index') }}"><i>✦</i>{{ config('properties.brand') }} Home Interiors</a>
    <nav><a href="{{ route('interiors.ideas') }}">Design ideas</a><a href="{{ route('interiors.estimator') }}">Price calculator</a><a class="solid" href="{{ route('interiors.quote') }}">Get a free quote</a></nav>
</div></div>
