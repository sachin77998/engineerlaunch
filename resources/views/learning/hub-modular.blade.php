@extends('layouts.app')
@section('title','Learning — '.config('platform.name'))
@php
    $count = fn ($topic) => isset($topic['sections']) ? count($topic['sections']) : count($topic['questions'] ?? []);
@endphp
@push('styles')
<style>
.lh-hero{padding:38px 0 30px}.lh-hero .lh-inner{width:min(1180px,calc(100% - 36px));margin:auto}
.lh-hero h1{margin:0 0 6px;font-size:clamp(28px,4vw,40px)}.lh-hero p{margin:0 0 18px;max-width:640px}
.lh-search{display:flex;max-width:520px;background:#fff;border-radius:12px;padding:5px;box-shadow:0 8px 24px rgba(0,0,0,.18)}
.lh-search input{flex:1;border:0;padding:11px 12px;font:inherit;border-radius:9px;outline:0;color:#0f1c2e}
.lh-wrap{width:min(1180px,calc(100% - 36px));margin:26px auto 60px}
.lh-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
.lh-card{display:flex;flex-direction:column;gap:10px;padding:20px;border:1px solid #dde5f0;border-radius:16px;background:#fff;color:#0f1c2e;text-decoration:none;transition:transform .15s,box-shadow .15s,border-color .15s;border-top:4px solid var(--accent)}
.lh-card:hover,.lh-card:focus-visible{transform:translateY(-3px);box-shadow:0 14px 30px rgba(11,37,69,.12);outline:0}
.lh-top{display:flex;align-items:center;gap:12px}
.lh-icon{display:grid;place-items:center;min-width:46px;height:46px;padding:0 8px;border-radius:12px;background:color-mix(in srgb,var(--accent) 14%,#fff);color:var(--accent);font-weight:900;font-size:14px}
.lh-card h2{margin:0;font-size:19px;line-height:1.25}
.lh-card p{margin:0;color:#5b6b82;font-size:14px;line-height:1.55;flex:1}
.lh-stats{display:flex;gap:8px;flex-wrap:wrap}.lh-stats span{padding:4px 9px;border-radius:999px;background:#f1f5f9;color:#334155;font-size:12px;font-weight:600}
.lh-open{font-weight:800;color:var(--accent);font-size:14px}
.lh-topics{display:flex;flex-wrap:wrap;gap:6px}.lh-topics em{font-style:normal;font-size:12px;color:#475569;background:#f8fafc;border:1px solid #eef2f7;border-radius:6px;padding:2px 7px}
.lh-empty{display:none;padding:30px;text-align:center;color:#5b6b82;background:#fff;border:1px dashed #dde5f0;border-radius:14px}
</style>
@endpush
@section('content')
<section class="hero-band lh-hero"><div class="lh-inner">
    <h1>Learning topics</h1>
    <p>Interview questions with worked answers, practical lessons and coding practice.</p>
    <label class="lh-search"><input type="search" id="lh-filter" placeholder="Search a course or topic, e.g. Laravel, SQL, Kafka" aria-label="Search courses and topics"></label>
</div></section>
<main class="lh-wrap">
    <div class="lh-grid" id="lh-grid">
        @foreach($tracks as $slug => $track)
            @php $questions = collect($track['topics'])->sum($count); @endphp
            <a class="lh-card" style="--accent:{{ $track['color'] }}" href="{{ route('learning.track', $slug) }}"
               data-search="{{ strtolower($track['title'].' '.collect($track['topics'])->pluck('title')->implode(' ')) }}">
                <span class="lh-top"><span class="lh-icon">{{ $track['icon'] }}</span><h2>{{ $track['title'] }}</h2></span>
                <p>{{ $track['description'] ?? '' }}</p>
                <span class="lh-topics">@foreach(array_slice($track['topics'], 0, 3) as $topic)<em>{{ \Illuminate\Support\Str::limit($topic['title'], 32) }}</em>@endforeach</span>
                <span class="lh-stats"><span>{{ count($track['topics']) }} topics</span><span>{{ $questions }} questions &amp; lessons</span></span>
                <span class="lh-open">Start learning →</span>
            </a>
        @endforeach
    </div>
    <div class="lh-empty" id="lh-empty">No course matches your search.</div>
</main>
@endsection
@push('scripts')
<script>
(() => {
    const input = document.getElementById('lh-filter'), cards = [...document.querySelectorAll('#lh-grid .lh-card')], empty = document.getElementById('lh-empty');
    input?.addEventListener('input', () => {
        const term = input.value.trim().toLowerCase(); let shown = 0;
        cards.forEach(card => { const hit = !term || card.dataset.search.includes(term); card.hidden = !hit; shown += hit; });
        empty.style.display = shown ? 'none' : 'block';
    });
})();
</script>
@endpush
