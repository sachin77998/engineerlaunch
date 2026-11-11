@extends('layouts.site')
@section('title', 'Find your home interior style — ' . config('properties.brand'))
@section('content')
@include('interiors._head')
@php($c = config('interiors'))
@push('styles')<style>
.qz{max-width:720px;margin:24px auto 0}.qz fieldset{border:0;margin:0 0 18px;padding:20px;background:#fff;border-radius:16px;border:1px solid var(--pr-line)}
.qz legend{font-weight:800;font-size:18px;padding:0;float:left;width:100%;margin-bottom:12px}
.qz .opts{display:grid;grid-template-columns:1fr 1fr;gap:10px;clear:both}.qz label{position:relative}.qz input{position:absolute;opacity:0}
.qz label span{display:block;padding:14px;border:1px solid #d1d5db;border-radius:12px;cursor:pointer}.qz input:checked+span{border-color:#a16207;background:#fef3c7;font-weight:700}.qz input:focus-visible+span{outline:2px solid #fcd34d}
.qz-res{background:linear-gradient(135deg,#fef3c7,#f5f0e8);border-radius:18px;padding:24px;text-align:center}.qz-res h2{font-family:Georgia,serif;font-size:34px;margin:6px 0}.qz-res .sw{display:flex;gap:8px;justify-content:center;margin:12px 0}.qz-res .sw i{width:34px;height:34px;border-radius:50%}
@media(max-width:560px){.qz .opts{grid-template-columns:1fr}}
</style>@endpush
<div class="pr" style="padding-bottom:60px"><div class="pr-wrap qz">
    <h1 style="margin:0 0 4px">Find your home interior style</h1><p class="pr-mute" style="margin-top:0">Pick what feels like you.</p>
    <form data-qz>
        @foreach($c['quiz'] as $qi => [$question, $answers])
            <fieldset><legend>{{ $qi + 1 }}. {{ $question }}</legend><div class="opts">
                @foreach($answers as $ai => [$text, $points])<label><input type="radio" name="q{{ $qi }}" value="{{ $ai }}" required><span>{{ $text }}</span></label>@endforeach
            </div></fieldset>
        @endforeach
        <button class="in-red" style="width:100%">See my style</button>
    </form>
    <div class="qz-res" data-qz-res hidden aria-live="polite">
        <small style="letter-spacing:.08em;color:#a16207;font-weight:800">YOUR STYLE</small>
        <h2 data-name></h2><div class="sw" data-sw></div><p data-desc style="color:#57534e"></p>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:12px"><a class="pr-btn ghost" data-ideas href="#">See designs in this style</a><a class="in-red" data-quote href="#">Get a free quote</a></div>
        <p><button type="button" class="pr-btn ghost sm" data-again>Take it again</button></p>
    </div>
</div></div>
@push('scripts')<script>
(function () {
    var quiz = @json(collect($c['quiz'])->map(fn ($q) => collect($q[1])->map(fn ($a) => $a[1]))), styles = @json($c['styles']);
    var ideas = @json(route('interiors.ideas')), quote = @json(route('interiors.quote'));
    var f = document.querySelector('[data-qz]'), res = document.querySelector('[data-qz-res]');
    f.addEventListener('submit', function (e) {
        e.preventDefault();
        var score = {};
        quiz.forEach(function (answers, qi) { var pick = f.querySelector('[name=q' + qi + ']:checked'); if (!pick) return; var pts = answers[pick.value]; Object.keys(pts).forEach(function (s) { score[s] = (score[s] || 0) + pts[s]; }); });
        var best = Object.keys(score).sort(function (a, b) { return score[b] - score[a]; })[0], s = styles[best];
        res.querySelector('[data-name]').textContent = s[0]; res.querySelector('[data-desc]').textContent = s[1];
        var sw = res.querySelector('[data-sw]'); sw.innerHTML = ''; s[2].forEach(function (c) { var i = document.createElement('i'); i.style.background = c; sw.appendChild(i); });
        res.querySelector('[data-ideas]').href = ideas + '?style=' + best; res.querySelector('[data-quote]').href = quote + '?style=' + best;
        f.hidden = true; res.hidden = false; res.scrollIntoView({ behavior: 'smooth' });
    });
    res.querySelector('[data-again]').addEventListener('click', function () { f.reset(); res.hidden = true; f.hidden = false; });
})();
</script>@endpush
@endsection
