{{-- Locality reviews block. Expects $summary (or null), $reviews, $city (slug), $locality (key), $name, $cityName; optional $show (cards shown before the "Read all" tile), $voted. --}}
@php
    $show = $show ?? 6;
    $voted = $voted ?? [];
    $stars = fn ($v) => str_repeat('★', (int) round($v)) . str_repeat('☆', 5 - (int) round($v));
    $features = config('properties.review_features');
    $tone = fn ($v) => $v >= 4 ? '#047857' : ($v >= 3 ? '#d97706' : '#b91c1c');
@endphp
@once
@push('styles')<style>
.lr-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
.lr-box{display:grid;grid-template-columns:280px minmax(0,1fr);gap:0;background:#fff;border:1px solid var(--pr-line);border-radius:16px;margin-top:14px}
.lr-avg{padding:22px;border-right:1px solid var(--pr-line);text-align:center}.lr-avg .big{font-size:38px;font-weight:800}.lr-avg .big small{font-size:16px;color:var(--pr-mute);font-weight:500}
.lr-stars{color:#f59e0b;font-size:22px;letter-spacing:2px}
.lr-bar{display:grid;grid-template-columns:1fr 34px;gap:8px;align-items:center;font-size:13px;color:var(--pr-mute);margin-top:8px;text-align:left}
.lr-bar span{height:8px;border-radius:9px;background:#e5e7eb;overflow:hidden;display:block}.lr-bar span i{display:block;height:100%;background:var(--pr-accent)}
.lr-feat{padding:22px}.lr-feat .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin:10px 0 20px}
.lr-feat .grid div{display:flex;gap:10px;align-items:center}.lr-feat .grid i{font-style:normal;width:40px;height:40px;border-radius:50%;border:2px solid var(--pr-accent);display:grid;place-items:center;font-size:18px}
.lr-feat .grid b{display:block}.lr-feat .grid small{color:var(--pr-mute)}
.lr-pos{display:flex;align-items:center;gap:14px}.lr-pos span{flex:1;height:14px;background:#fee2e2;border-radius:4px;overflow:hidden}.lr-pos span i{display:block;height:100%;background:#a7f3d0}
.lr-sorts{display:flex;gap:8px;margin:20px 0 12px;flex-wrap:wrap}
.lr-sorts button,.lr-sorts a{padding:7px 14px;border-radius:999px;border:1px solid var(--pr-line);background:#fff;font-size:13.5px;cursor:pointer;color:#374151;text-decoration:none}
.lr-sorts .on{background:var(--pr-soft);border-color:var(--pr-accent);color:var(--pr-accent);font-weight:700}
.lr-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px}
.lr-card{background:#fff;border:1px solid var(--pr-line);border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:8px}
.lr-card[hidden]{display:none}
.lr-score{display:inline-block;color:#fff;font-weight:800;font-size:13px;padding:2px 8px;border-radius:5px;align-self:flex-start}
.lr-card p.clip{display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden}
.lr-more{border:0;background:none;color:var(--pr-accent);font-weight:700;cursor:pointer;padding:0;align-self:flex-start;font-size:13px}
.lr-all{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;background:var(--pr-soft);border-radius:14px;text-decoration:none;color:var(--pr-ink);min-height:180px}
.lr-all span{width:40px;height:40px;border-radius:50%;background:#fff;display:grid;place-items:center;color:var(--pr-accent);font-size:20px}.lr-all small{color:var(--pr-mute)}
.lr-card h4{margin:6px 0 0;font-size:12px;letter-spacing:.06em;color:var(--pr-mute)}.lr-card p{margin:0;white-space:pre-line;font-size:14.5px;line-height:1.5}
.lr-who{display:flex;gap:10px;align-items:center;margin-top:auto;padding-top:8px}.lr-who i{font-style:normal;width:34px;height:34px;border-radius:50%;background:#fef3c7;display:grid;place-items:center;font-weight:800;font-size:13px}
.lr-who b{display:block;font-size:13.5px}.lr-who small{color:var(--pr-mute);font-size:12px}
.lr-help{display:flex;gap:10px;align-items:center;font-size:13px;color:var(--pr-mute)}.lr-help button{border:0;background:none;color:#374151;cursor:pointer;font-size:13px}.lr-help button.on{color:var(--pr-accent);font-weight:700}
@media(max-width:760px){.lr-box{grid-template-columns:1fr}.lr-avg{border-right:0;border-bottom:1px solid var(--pr-line)}}
</style>@endpush
@endonce
<div class="lr-head">
    <div><h2 style="margin:0">Locality reviews</h2><span class="pr-mute">for {{ $name }}, {{ $cityName }}</span></div>
    <a class="pr-btn" href="{{ route('localities.review', [$city, $locality]) }}">Review your society / locality</a>
</div>
@if(!$summary)
    <div class="pr-card-box" style="margin-top:14px;text-align:center;padding:26px"><b>No reviews of {{ $name }} yet.</b><p class="pr-mute" style="margin:6px 0 0">Living here or lived here before? Your review helps people choose where to live.</p></div>
@else
    <div class="lr-box">
        <div class="lr-avg">
            <div class="big">{{ number_format($summary['average'], 1) }}<small> / 5</small></div>
            <div class="lr-stars" aria-label="{{ $summary['average'] }} out of 5">{{ $stars($summary['average']) }}</div>
            <div style="font-weight:700;margin-top:4px">Average rating</div><div class="pr-mute" style="font-size:13px">({{ $summary['count'] }} total {{ $summary['count'] === 1 ? 'review' : 'reviews' }})</div>
            @foreach($summary['stars'] as $s => $n)<div class="lr-bar"><span><i style="width:{{ $summary['count'] ? round($n / $summary['count'] * 100) : 0 }}%"></i></span>{{ $s }} ★</div>@endforeach
        </div>
        <div class="lr-feat">
            <b>Ratings by features</b>
            <div class="grid">@foreach($features as $k => [$label, $icon])<div><i>{{ $icon }}</i><div><b>{{ $label }}</b><small>{{ number_format($summary['features'][$k], 1) }} out of 5</small></div></div>@endforeach</div>
            <b>Popular mentions</b>
            <div class="lr-pos" style="margin-top:10px"><span><i style="width:{{ $summary['positive'] }}%"></i></span><b style="color:#047857">{{ $summary['positive'] }}% positive</b></div>
        </div>
    </div>
    <h3 style="margin:22px 0 0;font-size:18px">Reviews by residents</h3>
    <div class="lr-sorts" data-lr-sorts>@foreach(\App\Services\Property\LocalityRatings::SORTS as $k => $label)<button type="button" data-sort="{{ $k }}" class="{{ $k === 'recommended' ? 'on' : '' }}">{{ $label }}</button>@endforeach</div>
    <div class="lr-cards" data-lr-cards data-show="{{ $show }}">
        @foreach($reviews as $i => $r)
            <article class="lr-card" data-score="{{ $r->score() }}" data-time="{{ $r->created_at->timestamp }}" data-helpful="{{ $r->helpful_yes - $r->helpful_no }}" data-order="{{ $i }}" @if($i >= $show) hidden @endif>
                <span class="lr-score" style="background:{{ $tone($r->score()) }}">{{ number_format($r->score(), 1) }} ★</span>
                @foreach(['POSITIVES' => $r->positives, 'NEGATIVES' => $r->negatives] as $head => $text)
                    @if($text)<h4>{{ $head }}</h4><p class="{{ mb_strlen($text) > 180 ? 'clip' : '' }}">{{ $text }}</p>@if(mb_strlen($text) > 180)<button type="button" class="lr-more">Show more</button>@endif @endif
                @endforeach
                <div class="lr-who"><i>{{ mb_strtoupper(collect(explode(' ', $r->user?->name ?? 'R'))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('')) }}</i>
                    <div><b>{{ $r->user?->name ? \Illuminate\Support\Str::limit($r->user->name, 24) : 'Resident' }}</b><small>{{ $r->roleLabel() }}@if($r->society) · {{ $r->society }}@endif · {{ $r->created_at->diffForHumans(null, true, true) }} ago</small></div></div>
                <div class="lr-help">Helpful?
                    @auth
                        @foreach([1 => '👍 Yes', 0 => '👎 No'] as $val => $label)
                            <form method="post" action="{{ route('localities.vote', $r) }}" style="display:inline">@csrf<input type="hidden" name="helpful" value="{{ $val }}"><button class="{{ array_key_exists($r->id, $voted) && (int) $voted[$r->id] === $val ? 'on' : '' }}">{{ $label }} ({{ $val ? $r->helpful_yes : $r->helpful_no }})</button></form>
                        @endforeach
                    @else<a href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">Sign in to vote</a>@endauth
                </div>
            </article>
        @endforeach
        @if($reviews->count() > $show)<a class="lr-all" href="{{ route('localities.show', [$city, $locality]) }}"><span>→</span><b>Read all</b><small>{{ $reviews->count() }} reviews</small></a>@endif
    </div>
    <div style="display:flex;gap:10px;justify-content:center;margin-top:16px;flex-wrap:wrap">
        <a class="pr-btn" href="{{ route('localities.review', [$city, $locality]) }}">Review your society / locality</a>
    </div>
@endif
@once
@push('scripts')<script>
(function () {
    document.addEventListener('click', function (e) {
        var more = e.target.closest('.lr-more'); if (!more) return;
        var p = more.previousElementSibling, open = p.classList.toggle('clip');
        more.textContent = open ? 'Show more' : 'Show less';
    });
    document.querySelectorAll('[data-lr-sorts]').forEach(function (bar) {
        var grid = bar.nextElementSibling, show = parseInt(grid.dataset.show, 10);
        bar.addEventListener('click', function (e) {
            var b = e.target.closest('[data-sort]'); if (!b) return;
            bar.querySelectorAll('[data-sort]').forEach(function (x) { x.classList.toggle('on', x === b); });
            var cards = [].slice.call(grid.querySelectorAll('.lr-card')), all = grid.querySelector('.lr-all'), key = b.dataset.sort;
            cards.sort(function (a, c) {
                if (key === 'negative') return parseFloat(a.dataset.score) - parseFloat(c.dataset.score);
                if (key === 'recent') return c.dataset.time - a.dataset.time;
                return a.dataset.order - c.dataset.order;
            });
            cards.forEach(function (c, i) { c.hidden = i >= show; grid.appendChild(c); });
            if (all) grid.appendChild(all);
        });
    });
})();
</script>@endpush
@endonce
