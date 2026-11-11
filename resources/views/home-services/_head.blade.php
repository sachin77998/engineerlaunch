{{-- Home services top bar. Expects $city (nullable). --}}
@include('properties._styles')
@push('styles')<style>
.hs-top{background:#fff;border-bottom:1px solid var(--pr-line)}.hs-top .pr-wrap{display:flex;align-items:center;gap:14px;min-height:60px;flex-wrap:wrap}
.hs-top .pr-brand i{background:#e11d48}.hs-top nav{margin-left:auto;display:flex;gap:10px;align-items:center}
.hs-top nav a,.hs-top nav button{border:1px solid var(--pr-line);background:#fff;border-radius:8px;padding:8px 12px;color:#111827;text-decoration:none;font:600 14px Inter,system-ui,sans-serif;cursor:pointer}
dialog.hs-dlg{border:0;border-radius:14px;padding:0;width:min(480px,calc(100% - 24px));box-shadow:0 20px 50px rgba(0,0,0,.25)}dialog.hs-dlg::backdrop{background:rgba(15,23,42,.45)}
.hs-dlg header{padding:16px 20px;border-bottom:1px solid var(--pr-line);display:flex;justify-content:space-between;align-items:center}.hs-dlg header b{font-size:17px}.hs-dlg header button{border:0;background:none;font-size:24px;cursor:pointer}
.hs-dlg input{margin:12px 20px 0;width:calc(100% - 40px)!important}
.hs-cities{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;padding:16px 20px 22px;max-height:60vh;overflow:auto}
.hs-cities a{display:flex;flex-direction:column;align-items:center;gap:6px;text-decoration:none;color:#374151;font-size:14px;text-align:center}.hs-cities a[hidden]{display:none}
.hs-cities a i{font-style:normal;width:52px;height:52px;border-radius:50%;display:grid;place-items:center;font-weight:800;font-size:18px;color:#fff}
</style>@endpush
<div class="hs-top"><div class="pr-wrap">
    <a class="pr-brand" href="{{ route('home-services.index') }}"><i>🛠</i>{{ config('properties.brand') }} Home Services</a>
    <nav>
        <button type="button" data-open-city>📍 {{ $city ?? 'Select city' }}</button>
        @auth<a href="{{ route('home-services.bookings') }}">My bookings</a>@endauth
    </nav>
</div></div>
<dialog class="hs-dlg" id="hs-city" @unless($city) data-autopen @endunless>
    <header><b>Select your city</b>@if($city)<button type="button" data-close aria-label="Close">×</button>@endif</header>
    <input type="search" placeholder="Search city" data-city-q aria-label="Search city">
    <div class="hs-cities">
        @php($palette = ['#be123c', '#1d4ed8', '#047857', '#7c3aed', '#b45309', '#0e7490'])
        @foreach($cities as $i => $c)<a href="{{ route('home-services.city', \Illuminate\Support\Str::slug($c)) }}" data-name="{{ strtolower($c) }}"><i style="background:{{ $palette[$i % 6] }}">{{ mb_substr($c, 0, 1) }}</i>{{ $c }}</a>@endforeach
    </div>
</dialog>
@push('scripts')<script>
(function () {
    var d = document.getElementById('hs-city');
    document.querySelectorAll('[data-open-city]').forEach(function (b) { b.addEventListener('click', function () { d.showModal(); }); });
    var x = d.querySelector('[data-close]'); if (x) x.addEventListener('click', function () { d.close(); });
    if (d.hasAttribute('data-autopen') && d.showModal) d.showModal();
    d.querySelector('[data-city-q]').addEventListener('input', function () { var t = this.value.trim().toLowerCase(); d.querySelectorAll('[data-name]').forEach(function (a) { a.hidden = t && a.dataset.name.indexOf(t) < 0; }); });
})();
</script>@endpush
