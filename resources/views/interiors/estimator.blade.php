@extends('layouts.site')
@section('title', 'Interior cost estimator — ' . config('properties.brand'))
@section('content')
@include('interiors._head')
@php($c = config('interiors'))
@push('styles')<style>
.es{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:22px;align-items:start;margin-top:22px}
.es-step{background:#fff;border:1px solid var(--pr-line);border-radius:18px;padding:22px;margin-bottom:16px}.es-step h2{margin:0 0 12px;font-size:19px}
.es-opts{display:flex;gap:10px;flex-wrap:wrap}.es-opts label{position:relative}.es-opts input{position:absolute;opacity:0}.es-opts span{display:block;padding:12px 18px;border:1px solid #d1d5db;border-radius:12px;cursor:pointer;font-weight:600}
.es-opts input:checked+span{border-color:#e11d48;background:#fff1f2;color:#be123c}.es-opts input:focus-visible+span{outline:2px solid #fda4af}
.es-opts span small{display:block;font-weight:400;color:var(--pr-mute);font-size:12px;max-width:200px}
.es-room{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-top:1px solid var(--pr-line)}.es-room small{display:block;color:var(--pr-mute);font-size:12.5px}
.es-n{display:flex;align-items:center;gap:8px}.es-n button{width:32px;height:32px;border-radius:50%;border:1px solid #d1d5db;background:#fff;font-size:18px;cursor:pointer}.es-n output{min-width:18px;text-align:center;font-weight:700}
.es-sum{position:sticky;top:80px;background:#1c1917;color:#fff;border-radius:18px;padding:22px}.es-sum h2{color:#fff;margin:0 0 6px;font-size:18px}
.es-sum .big{font-size:30px;font-weight:800;margin:6px 0}.es-sum .ln{display:flex;justify-content:space-between;font-size:14px;padding:6px 0;border-top:1px solid rgba(255,255,255,.12)}
@media(max-width:860px){.es{grid-template-columns:1fr}.es-sum{position:static}}
</style>@endpush
<div class="pr" style="padding-bottom:60px"><div class="pr-wrap">
    <h1 style="margin:24px 0 0">Interior cost estimator</h1><span class="pr-mute">Pick your home, rooms and finish — the estimate updates as you go.</span>
    <form method="get" action="{{ route('interiors.quote') }}" class="es" data-es>
        <div>
            <div class="es-step"><h2>1. Your home</h2><div class="es-opts">@foreach($c['bhk'] as $k => $l)<label><input type="radio" name="bhk" value="{{ $k }}" @checked($k === $bhk)><span>{{ $l }}</span></label>@endforeach</div></div>
            <div class="es-step"><h2>2. Rooms to design</h2>
                @foreach($c['rooms'] as $k => [$label, $icon])
                    <div class="es-room"><div><b>{{ $icon }} {{ $label }}</b><small>{{ $c['room_includes'][$k] ?? '' }}</small></div>
                        <div class="es-n"><button type="button" data-d="-1" aria-label="Fewer {{ $label }}">−</button><output data-out="{{ $k }}">{{ $c['bhk_rooms'][$bhk][$k] ?? 0 }}</output><button type="button" data-d="1" aria-label="More {{ $label }}">+</button>
                            <input type="hidden" name="rooms[{{ $k }}]" value="{{ $c['bhk_rooms'][$bhk][$k] ?? 0 }}" data-room="{{ $k }}"></div></div>
                @endforeach
            </div>
            <div class="es-step"><h2>3. Finish</h2><div class="es-opts">@foreach($c['packages'] as $k => [$l, $m, $d])<label><input type="radio" name="package" value="{{ $k }}" @checked($loop->first)><span>{{ $l }}<small>{{ $d }}</small></span></label>@endforeach</div></div>
        </div>
        <aside class="es-sum">
            <h2>Estimated cost</h2>
            <div class="big" data-range>—</div>
            <small style="opacity:.75">Indicative · final price after a site visit and design</small>
            <div style="margin-top:12px" data-lines></div>
            <button class="in-red" style="width:100%;margin-top:16px">Get exact quote →</button>
        </aside>
    </form>
</div></div>
@push('scripts')<script>
(function () {
    var rates = @json($c['room_rates']), labels = @json(collect($c['rooms'])->map(fn ($r) => $r[0])), presets = @json($c['bhk_rooms']), packs = @json(collect($c['packages'])->map(fn ($p) => $p[1])), pct = {{ $c['range_pct'] }}, max = {{ $c['max_rooms'] }};
    var f = document.querySelector('[data-es]'), inr = function (n) { return '₹' + Math.round(n).toLocaleString('en-IN'); }, r1k = function (n) { return Math.round(n / 1000) * 1000; };
    function set(room, n) { n = Math.max(0, Math.min(max, n)); f.querySelector('[data-room="' + room + '"]').value = n; f.querySelector('[data-out="' + room + '"]').textContent = n; }
    function calc() {
        var mult = packs[f.querySelector('[name=package]:checked').value], total = 0, html = document.createDocumentFragment();
        f.querySelectorAll('[data-room]').forEach(function (i) {
            var n = +i.value, room = i.dataset.room; if (!n) return;
            var amt = r1k(rates[room] * mult * n); total += amt;
            var d = document.createElement('div'), a = document.createElement('span'), b = document.createElement('span');
            d.className = 'ln'; a.textContent = labels[room] + (n > 1 ? ' × ' + n : ''); b.textContent = inr(amt); d.append(a, b); html.appendChild(d);
        });
        var lines = f.querySelector('[data-lines]'); lines.innerHTML = ''; lines.appendChild(html);
        f.querySelector('[data-range]').textContent = total ? inr(r1k(total * (1 - pct / 100))) + ' – ' + inr(r1k(total * (1 + pct / 100))) : '—';
    }
    f.addEventListener('click', function (e) {
        var b = e.target.closest('[data-d]'); if (!b) return;
        var i = b.parentNode.querySelector('[data-room]'); set(i.dataset.room, +i.value + +b.dataset.d); calc();
    });
    f.addEventListener('change', function (e) {
        if (e.target.name === 'bhk') Object.keys(labels).forEach(function (k) { set(k, presets[e.target.value][k] || 0); });
        calc();
    });
    calc();
})();
</script>@endpush
@endsection
