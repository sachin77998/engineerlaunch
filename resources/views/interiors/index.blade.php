@extends('layouts.site')
@section('title', 'Home interiors — designs, cost estimator & free quote — ' . config('properties.brand'))
@section('content')
@include('interiors._head')
@php
    $c = config('interiors');
    $tiles = function ($groups, $labels, $param) {
        return collect($labels)->filter(fn ($l, $k) => isset($groups[$k]))->map(fn ($l, $k) => ['key' => $k, 'label' => is_array($l) ? $l[0] : $l, 'icon' => is_array($l) && isset($l[1]) && is_string($l[1]) && mb_strlen($l[1]) <= 2 ? $l[1] : null,
            'n' => $groups[$k]->n, 'img' => \Illuminate\Support\Facades\Storage::disk('public')->url($groups[$k]->img), 'url' => route('interiors.ideas', [$param => $k])]);
    };
@endphp
@push('styles')<style>
.in-hero{position:relative;min-height:440px;display:grid;place-items:center;text-align:center;color:#fff;background:#292524 center/cover}
.in-hero::before{content:"";position:absolute;inset:0;background:rgba(28,25,23,.55)}.in-hero>div{position:relative;padding:40px 16px}
.in-hero h1{color:#fff;font-size:clamp(32px,5vw,56px);margin:0}.in-hero p{font-size:19px;margin:10px 0 24px}
.in-usp{background:rgba(28,25,23,.85);color:#fff;display:flex;justify-content:center;gap:30px;flex-wrap:wrap;padding:14px 16px;font-size:15px}
.in-quiz{border-radius:16px;overflow:hidden;background:linear-gradient(135deg,#fef3c7,#f5f0e8);min-height:300px;display:flex;flex-direction:column;justify-content:space-between;padding:22px;text-decoration:none;color:#1c1917;grid-column:span 2}
.in-quiz h3{font-family:Georgia,serif;font-size:30px;margin:0}.in-quiz .sw{display:flex;gap:8px;margin:12px 0}.in-quiz .sw i{width:26px;height:26px;border-radius:50%}
.in-feat{display:grid;grid-template-columns:1fr 1fr;background:#1c1917;color:#fff;border-radius:22px;overflow:hidden;margin-top:40px}.in-feat img{width:100%;height:100%;object-fit:cover;min-height:320px}
.in-feat>div{padding:30px;display:flex;flex-direction:column;justify-content:center;gap:12px}.in-feat h2{color:#fff;margin:0;font-size:30px}
@media(max-width:760px){.in-feat{grid-template-columns:1fr}.in-quiz{grid-column:auto}}
</style>@endpush
<div class="pr" style="padding-bottom:60px">
<section class="in-hero" @if($hero) style="background-image:url('{{ $hero->imageUrl() }}')" @endif>
    <div><h1>Interior designers for your home</h1><p>Room-wise designs, transparent pricing, one point of contact</p><a class="in-red" href="{{ route('interiors.quote') }}">Get a free quote</a></div>
</section>
<div class="in-usp"><span>✎ Personalised designs</span><span>₹ Room-wise estimate</span><span>☎ Free consultation</span></div>
<div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok" style="margin-top:20px">{{ session('success') }}</div>@endif

    <section class="in-sec">
        <h2>Trending ideas @if($trending->isNotEmpty())<a href="{{ route('interiors.ideas') }}">See all designs →</a>@endif</h2>
        <div class="in-row" style="grid-auto-columns:minmax(220px,1fr)">
            @foreach($trending as $i)<a class="in-tile" href="{{ route('interiors.idea', $i) }}" style="background-image:url('{{ $i->imageUrl() }}')"><div><b>{{ $i->title }}</b></div></a>@endforeach
            <a class="in-quiz" href="{{ route('interiors.quiz') }}">
                <div><h3>Find your home interior style</h3><div class="sw">@foreach(array_slice($c['styles'], 0, 4) as [$l, $d, $sw])<i style="background:{{ $sw[1] }}"></i>@endforeach</div><span style="color:#57534e">Answer 5 quick questions and see which style suits you.</span></div>
                <b style="display:flex;justify-content:space-between;color:#a16207">Discover my style <span>→</span></b>
            </a>
        </div>
    </section>

    <section class="in-sec">
        <div class="in-cta">
            <div><h3>Wondering about the cost?</h3><p>Get a free room-wise estimate for your home.</p></div>
            <div class="chips"><span>1–4 BHK</span><span>ECONOMY · PREMIUM · LUXURY</span><span>~1 MIN</span></div>
            <a class="in-red" href="{{ route('interiors.estimator') }}">Try the estimator →</a>
        </div>
    </section>

    @if($projects->isNotEmpty())
        <section class="in-sec"><h2>Our recent projects</h2>
            <div class="in-row" style="grid-auto-columns:minmax(280px,1fr)">
                @foreach($projects as $p)<a class="in-tile" href="{{ route('interiors.project', $p) }}" style="background-image:url('{{ $p->coverUrl() }}');min-height:320px"><div style="display:block">
                    <span class="in-pill">{{ $p->city }}</span>@if($p->styleLabel())<span class="in-pill">{{ $p->styleLabel() }}</span>@endif @if($p->rating)<span class="in-pill">★ {{ $p->rating }}/5</span>@endif
                    <b style="display:block;margin-top:8px;text-decoration:underline">{{ $p->title }}</b></div></a>@endforeach
            </div>
        </section>
    @endif

    @foreach([['Discover ideas by room', $tiles($byRoom, $c['rooms'], 'room')], ['Discover ideas by style', $tiles($byStyle, $c['styles'], 'style')], ['Discover ideas by category', $tiles($byCategory, $c['categories'], 'category')]] as [$heading, $list])
        @if($list->isNotEmpty())
            <section class="in-sec"><h2>{{ $heading }}</h2>
                <div class="in-band"><div class="in-row" style="grid-auto-columns:minmax(220px,250px)">
                    @foreach($list as $t)<a class="in-tile" href="{{ $t['url'] }}" style="background-image:url('{{ $t['img'] }}')"><div><span><b>{{ $t['label'] }}</b><small>{{ $t['n'] }} {{ $t['n'] === 1 ? 'idea' : 'ideas' }}</small></span><span class="go">›</span></div></a>@endforeach
                </div></div>
            </section>
        @endif
    @endforeach

    @if($featured)
        <section class="in-feat">
            <img src="{{ $featured->coverUrl() }}" alt="{{ $featured->title }}">
            <div><small style="opacity:.8">Featured project</small><h2>{{ $featured->title }}</h2>
                <span>{{ $featured->city }}@if($featured->bhk) · {{ $featured->bhk }} BHK @endif @if($featured->styleLabel()) · {{ $featured->styleLabel() }}@endif</span>
                @if($featured->review)<p style="opacity:.9;margin:0">“{{ \Illuminate\Support\Str::limit($featured->review, 220) }}”@if($featured->reviewer) — {{ $featured->reviewer }}@endif</p>@endif
                <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap"><a class="pr-btn" style="background:#fff;color:#111" href="{{ route('interiors.project', $featured) }}">{{ $featured->video_url ? '▶ Video tour' : 'View project' }}</a></div>
            </div>
        </section>
    @endif
</div>
</div>
@include('partials.property-services')
@endsection
