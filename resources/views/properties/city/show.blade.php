@extends('layouts.site')
@section('title', 'Real estate in ' . ($zone ?: $city->name) . ' — buy, rent, projects')
@section('meta_description', 'Flats, houses, plots, commercial spaces and new projects in ' . $city->name . ': localities, builders, offers and demand.')
@section('content')
@include('properties._nav')
@include('properties.city._styles')
@php
    $top = collect($blocks['top'] ?? []);
    $banner = $top->first(fn ($b) => $b['view'] === 'properties.city.featured-banner');
@endphp
<div class="pr">
    <section class="cp-hero {{ $banner ? 'has-banner' : '' }}">
        <div class="pr-wrap cp-head">
            <h1>Real estate in</h1>
            <button type="button" class="cp-place" data-chooser-open aria-haspopup="dialog">Buy in {{ $zone ?: $city->name }} <span aria-hidden="true">⌄</span></button>
        </div>
        @if($banner)@include($banner['view'], $banner['data'])@endif
        <div class="pr-wrap cp-hero-search">
            @foreach($top as $b)
                @continue($b === $banner)
                @include($b['view'], $b['data'])
            @endforeach
        </div>
    </section>
    <div class="pr-wrap cp-layout">
        <main class="cp-main">
            @foreach($blocks['main'] ?? [] as $b)
                @include($b['view'], $b['data'])
            @endforeach
            @if(empty($blocks['main']))
                <div class="cp-empty"><h2>No listings in {{ $zone ?: $city->name }} yet</h2><p class="pr-mute">Be the first to list a property here.</p><a class="pr-btn" href="{{ route('properties.post') }}">Post property — free</a></div>
            @endif
        </main>
        <aside class="cp-side">
            @foreach($blocks['side'] ?? [] as $b)
                @include($b['view'], $b['data'])
            @endforeach
        </aside>
    </div>
    <div class="pr-wrap">
        @foreach($blocks['bottom'] ?? [] as $b)
            @include($b['view'], $b['data'])
        @endforeach
    </div>
</div>
@include('properties.city._chooser')
@push('scripts')<script>
document.querySelectorAll('[data-rail]').forEach(function (wrap) {
    var rail = wrap.querySelector('.cp-rail');
    wrap.querySelectorAll('[data-dir]').forEach(function (b) { b.addEventListener('click', function () { rail.scrollBy({left: b.dataset.dir * rail.clientWidth * 0.8, behavior: 'smooth'}); }); });
});
document.querySelectorAll('[data-tabs]').forEach(function (box) {
    box.querySelectorAll('[data-tab]').forEach(function (t) { t.addEventListener('click', function () {
        box.querySelectorAll('[data-tab]').forEach(function (x) { x.classList.toggle('on', x === t); });
        box.querySelectorAll('[data-pane]').forEach(function (p) { p.hidden = p.dataset.pane !== t.dataset.tab; });
    }); });
});
// Featured projects carousel: arrows plus a slow auto-advance that stops once the visitor uses the arrows.
document.querySelectorAll('[data-banner]').forEach(function (bn) {
    var slides = bn.querySelectorAll('.cp-slide'), i = 0, timer;
    function show(n) { slides[i].hidden = true; i = (n + slides.length) % slides.length; slides[i].hidden = false; }
    bn.querySelectorAll('[data-bstep]').forEach(function (b) { b.addEventListener('click', function () { clearInterval(timer); show(i + +b.dataset.bstep); }); });
    if (slides.length > 1) timer = setInterval(function () { show(i + 1); }, 6000);
});
// Hero search: tabs change the type picker and the target page; suggestions, locate-me and voice fill the box.
document.querySelectorAll('[data-hero-search]').forEach(function (box) {
    var tabs = box.querySelectorAll('[data-stab]'), sel = box.querySelector('[data-stype]'), q = box.querySelector('[data-sq]'), list = box.querySelector('[data-sq-list]'), note = box.querySelector('[data-snote]');
    var current = tabs[0], timer;
    function useTab(t) {
        current = t; tabs.forEach(function (x) { x.classList.toggle('on', x === t); });
        var choices = JSON.parse(t.dataset.choices || '[]');
        sel.innerHTML = ''; choices.forEach(function (c) { var o = document.createElement('option'); o.value = c[0]; o.textContent = c[1]; sel.appendChild(o); });
        sel.hidden = !choices.length;
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { useTab(t); q.focus(); }); });
    useTab(current);
    function say(msg) { note.textContent = msg; note.hidden = !msg; }
    box.querySelector('[data-search-form]').addEventListener('submit', function (ev) {
        ev.preventDefault();
        var url = new URL(current.dataset.url, location.origin), p = url.searchParams, fixed = JSON.parse(current.dataset.fixed || '{}'), text = q.value.trim(), v = sel.value;
        Object.keys(fixed).forEach(function (k) { [].concat(fixed[k]).forEach(function (x) { p.append(Array.isArray(fixed[k]) ? k + '[]' : k, x); }); });
        if (v) { if (v.indexOf('kind:') === 0) p.set('kind', v.slice(5)); else if (current.dataset.field === 'kind') p.set('kind', v); else p.append('type[]', v); }
        if (text) { if (current.dataset.stab === 'projects') p.set('q', text); else { p.delete('loc[]'); p.append('q[]', text); } }
        location.href = url.toString();
    });
    q.addEventListener('input', function () {
        clearTimeout(timer); var term = q.value.trim(); if (term.length < 2) { list.hidden = true; return; }
        timer = setTimeout(function () {
            fetch(box.dataset.suggest + '?city=' + encodeURIComponent(box.dataset.city) + '&q=' + encodeURIComponent(term), {headers: {Accept: 'application/json'}})
                .then(function (r) { return r.ok ? r.json() : []; }).then(function (names) {
                    list.innerHTML = ''; names.forEach(function (n) { var b = document.createElement('button'); b.type = 'button'; b.textContent = n; b.addEventListener('click', function () { q.value = n; list.hidden = true; }); list.appendChild(b); });
                    list.hidden = !names.length;
                }).catch(function () {});
        }, 220);
    });
    document.addEventListener('click', function (ev) { if (!box.querySelector('.cp-sinput').contains(ev.target)) list.hidden = true; });
    box.querySelector('[data-locate-me]').addEventListener('click', function () {
        if (!navigator.geolocation) return say('Your browser cannot share its location.');
        say('Finding where you are…');
        navigator.geolocation.getCurrentPosition(function (pos) {
            fetch(box.dataset.locate + '?lat=' + pos.coords.latitude + '&lng=' + pos.coords.longitude, {headers: {Accept: 'application/json'}}).then(function (r) { return r.json(); }).then(function (j) {
                if (!j.found) return say(j.message || 'We could not match your location.');
                if (j.slug !== box.dataset.city) { say('You are in ' + j.city + ' — opening it…'); location.href = j.url; return; }
                if (j.locality) { q.value = j.locality; say('Nearest locality with listings: ' + j.locality); } else say('You are in ' + j.city + '.');
            }).catch(function () { say('Could not look up your location.'); });
        }, function () { say('Location permission was denied.'); }, {timeout: 10000, maximumAge: 600000});
    });
    var Rec = window.SpeechRecognition || window.webkitSpeechRecognition, mic = box.querySelector('[data-voice]');
    if (Rec) {
        mic.hidden = false;
        mic.addEventListener('click', function () {
            var r = new Rec(); r.lang = 'en-IN'; r.interimResults = false; mic.classList.add('on'); say('Listening…');
            r.onresult = function (e) { q.value = e.results[0][0].transcript.replace(/[.?!]$/, ''); say(''); q.dispatchEvent(new Event('input')); };
            r.onerror = function () { say('Did not catch that — try again.'); };
            r.onend = function () { mic.classList.remove('on'); if (note.textContent === 'Listening…') say(''); };
            r.start();
        });
    }
});
</script>@endpush
@endsection
