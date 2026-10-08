@extends('layouts.app')
@section('title',$track['title'].' — Learning')
@php
    $count = fn ($topic) => isset($topic['sections']) ? count($topic['sections']) : count($topic['questions'] ?? []);
    $unit = fn ($topic) => isset($topic['sections']) ? 'sections' : ((($topic['type'] ?? null) === 'tutorial') ? 'lessons' : 'questions');
    $total = collect($track['topics'])->sum($count);
@endphp
@push('styles')
<style>
.lt-hero{padding:30px 0 26px}.lt-inner{width:min(1250px,calc(100% - 36px));margin:auto}
.lt-back{color:#dbe5f3!important;text-decoration:none;font-size:14px}.lt-back:hover{color:#f4b400!important}
.lt-head{display:flex;align-items:center;gap:14px;margin:12px 0 8px}
.lt-icon{display:grid;place-items:center;min-width:52px;height:52px;padding:0 8px;border-radius:14px;background:#fff;color:var(--accent);font-weight:900}
.lt-hero h1{margin:0;font-size:clamp(26px,4vw,38px)}.lt-hero p{margin:0;max-width:720px}
.lt-chips{display:flex;gap:8px;margin-top:14px;flex-wrap:wrap}.lt-chips span{padding:5px 11px;border-radius:999px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);color:#fff;font-size:13px;font-weight:600}
.lt-layout{width:min(1250px,calc(100% - 36px));margin:24px auto 60px;display:grid;grid-template-columns:minmax(0,1fr) 290px;gap:24px;align-items:start}
.lt-search{width:100%;padding:11px 13px;border:1px solid #dde5f0;border-radius:11px;font:inherit;margin-bottom:14px;background:#fff}
.lt-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:14px}
.lt-card{display:grid;grid-template-columns:auto 1fr;gap:4px 14px;align-items:start;padding:18px;border:1px solid #dde5f0;border-radius:14px;background:#fff;color:#0f1c2e;text-decoration:none;transition:transform .15s,box-shadow .15s,border-color .15s}
.lt-card:hover,.lt-card:focus-visible{transform:translateY(-2px);border-color:var(--accent);box-shadow:0 12px 26px rgba(11,37,69,.1);outline:0}
.lt-num{grid-row:1/4;display:grid;place-items:center;width:38px;height:38px;border-radius:10px;background:color-mix(in srgb,var(--accent) 14%,#fff);color:var(--accent);font-weight:900;font-size:13px}
.lt-card h2{margin:0;font-size:16.5px;line-height:1.3}.lt-card small{color:#5b6b82;font-size:13px}.lt-card b{color:var(--accent);font-size:13px}
.lt-card.is-new{border-color:var(--accent);background:linear-gradient(180deg,color-mix(in srgb,var(--accent) 6%,#fff),#fff)}
.lt-card .lt-badge{justify-self:start;font-size:10.5px;font-weight:800;letter-spacing:.04em;padding:2px 7px;border-radius:5px;background:#f4b400;color:#0b2545}
.lt-side{position:sticky;top:16px;display:grid;gap:14px}
.lt-box{padding:16px;border:1px solid #dde5f0;border-radius:14px;background:#fff}
.lt-box h3{margin:0 0 10px;font-size:13px;text-transform:uppercase;letter-spacing:.05em;color:#5b6b82}
.lt-links a{display:flex;gap:9px;align-items:center;padding:7px 8px;border-radius:9px;color:#1e2f4d;text-decoration:none;font-size:14px;line-height:1.3}
.lt-links a:hover{background:#f4f6fb;color:var(--accent)}.lt-links span{flex:none;display:grid;place-items:center;width:24px;height:24px;border-radius:7px;background:#f1f5f9;font-size:11px;font-weight:800;color:#475569}
.lt-box details summary{cursor:pointer;font-weight:700;color:#1e2f4d}
.lt-box .vl-panel{padding:10px;margin:10px 0}.lt-box .vl-flow{display:block}.lt-box .vl-flow li{margin:10px 0}.lt-box .vl-table-wrap{overflow-x:auto}
@media(max-width:900px){.lt-layout{grid-template-columns:minmax(0,1fr)}.lt-side{position:static}}
</style>
@endpush
@section('content')
<section class="hero-band lt-hero" style="--accent:{{ $track['color'] }}"><div class="lt-inner">
    <a class="lt-back" href="{{ route('learning.index') }}">← All courses</a>
    <div class="lt-head"><span class="lt-icon">{{ $track['icon'] }}</span><h1>{{ $track['title'] }}</h1></div>
    @if(!empty($track['description']))<p>{{ $track['description'] }}</p>@endif
    <div class="lt-chips"><span>{{ count($track['topics']) }} topics</span><span>{{ $total }} questions &amp; lessons</span></div>
</div></section>
<div class="lt-layout" style="--accent:{{ $track['color'] }}">
    <main>
        <input class="lt-search" type="search" id="lt-filter" placeholder="Filter topics in {{ $track['title'] }}" aria-label="Filter topics">
        <div class="lt-grid" id="lt-grid">
            @foreach($track['topics'] as $topicSlug => $topic)
                <a class="topic-card lt-card {{ str_contains($topicSlug, 'interview') ? 'is-new' : '' }}" href="{{ route('learning.show', [$slug, $topicSlug]) }}" data-search="{{ strtolower($topic['title']) }}">
                    <span class="lt-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    @if(str_contains($topicSlug, 'interview'))<span class="lt-badge">INTERVIEW SET</span>@endif
                    <h2>{{ $topic['title'] }}</h2>
                    <small>{{ $count($topic) }} {{ $unit($topic) }} · <b>Open →</b></small>
                </a>
            @endforeach
        </div>
    </main>
    <aside class="lt-side" aria-label="Topic navigation">
        <div class="lt-box"><h3>Topics</h3>
            <nav class="lt-links">@foreach($track['topics'] as $topicSlug => $topic)<a href="{{ route('learning.show', [$slug, $topicSlug]) }}"><span>{{ $loop->iteration }}</span>{{ $topic['title'] }}</a>@endforeach</nav>
        </div>
        <div class="lt-box"><details><summary>Optional study guide</summary>@include('learning.partials.visual-overview')</details></div>
    </aside>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const input = document.getElementById('lt-filter'), cards = [...document.querySelectorAll('#lt-grid .lt-card')];
    input?.addEventListener('input', () => { const t = input.value.trim().toLowerCase(); cards.forEach(c => c.hidden = !!t && !c.dataset.search.includes(t)); });
})();
</script>
@endpush
