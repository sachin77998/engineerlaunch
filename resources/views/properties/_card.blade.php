{{-- One listing in results, shortlist and "similar" lists. Expects $p; optional $shortlisted (ids). --}}
@php
    $o = config('properties');
    $photos = $p->media->where('type', 'photo')->values();
    $isShort = in_array($p->id, $shortlisted ?? [], true);
    $amount = $p->headlineAmount();
    $perSqft = $p->listing === 'sale' && $amount && $p->area_sqft ? \App\Services\Property\PropertyFormat::full(round($amount / $p->area_sqft), $p->currency) . ' /sqft' : null;
    $areaLabel = $p->isLand() || in_array($p->building_type, ['independent_house', 'villa', 'farm_house', 'gated_community'], true) ? 'Plot area' : ($p->segment === 'commercial' ? 'Area' : 'Super built-up area');
    $tags = collect([$p->statusTag()])->merge(collect($p->highlights)->map(fn ($h) => $o['highlights'][$h] ?? null))
        ->merge($p->facing ? [($o['facing'][$p->facing] ?? '') . ' facing'] : [])->filter()->values();
    $owner = $p->owner;
@endphp
<article class="pr-item">
    <div class="pr-thumb" data-slider>
        @if($photos->isNotEmpty())
            @foreach($photos->take(12) as $i => $ph)<img src="{{ $ph->url() }}" alt="{{ $i === 0 ? $p->title : '' }}" loading="lazy" @if($i > 0) hidden @endif>@endforeach
            @if($photos->count() > 1)
                <button type="button" class="pr-slide prev" data-step="-1" aria-label="Previous photo">‹</button><button type="button" class="pr-slide next" data-step="1" aria-label="Next photo">›</button>
                <small data-count>1/{{ min(12, $photos->count()) }}</small>
            @endif
        @else<a href="{{ route('properties.show', $p) }}" style="position:absolute;inset:0;display:grid;place-items:center;color:inherit;text-decoration:none" aria-label="{{ $p->title }}">⌂</a>@endif
        @if($p->is_verified)<span class="pr-verified">✓ Verified</span>@endif
        <a class="pr-thumb-link" href="{{ route('properties.show', $p) }}" tabindex="-1" aria-hidden="true"></a>
    </div>
    <div class="pr-body">
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
            <div style="min-width:0"><h3 style="margin:0"><a href="{{ route('properties.show', $p) }}">{{ $p->title }}</a></h3>
                @if($p->society && $p->society !== $p->locality)<div style="font-weight:600;font-size:14px;margin-top:2px">{{ $p->society }}</div>@endif</div>
            @auth<form method="post" action="{{ route('properties.shortlist.toggle', $p) }}">@csrf<button class="pr-heart {{ $isShort ? 'on' : '' }}" title="Shortlist" aria-label="Shortlist">{{ $isShort ? '♥' : '♡' }}</button></form>@endauth
        </div>
        <div class="pr-pricebar">
            <div><div class="pr-price">{{ $p->listing === 'sale' && $amount ? \App\Services\Property\PropertyFormat::short($amount, $p->currency) : $p->headlinePrice() }}</div>@if($perSqft)<small>{{ $perSqft }}</small>@elseif($p->listing === 'rent' && !$p->isPg() && $p->rentTerms)<small>Deposit {{ $p->money($p->rentTerms->deposit) ?? '—' }}</small>@endif</div>
            @if($p->area_sqft)
                <div><div class="pr-area">{{ number_format($p->area_sqft) }} sqft <small>({{ number_format($p->area_sqft / 10.7639) }} sqm)</small></div><small>{{ $areaLabel }}@if($p->area_unit !== 'sqft') · {{ $p->areaLabel() }}@endif</small></div>
            @endif
            @if($p->isPg() && $p->pgTerms)<div><div class="pr-area">{{ $o['pg']['gender'][$p->pgTerms->gender] ?? 'Anyone' }}</div><small>{{ collect($p->pgTerms->rooms)->map(fn ($r) => $o['pg']['rooms'][$r['type']] ?? '')->filter()->implode(', ') }}</small></div>@endif
        </div>
        @if($tags->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;gap:6px">@foreach($tags->take(3) as $i => $t)<span class="pr-tag {{ $i === 0 ? 'blue' : '' }}">{{ $t }}</span>@endforeach @if($tags->count() > 3)<span class="pr-tag" title="{{ $tags->slice(3)->implode(', ') }}">+{{ $tags->count() - 3 }}</span>@endif</div>
        @endif
        @if($p->description)<details class="pr-desc"><summary>{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $p->description), 90) }}</summary><div>{{ $p->description }}</div></details>@endif
        <div class="pr-foot">
            <div><b>{{ $owner?->typeLabel() ?? 'Owner' }}</b><span class="pr-mute">{{ $p->published_at?->diffForHumans(null, true, true) }} ago</span></div>
            <div style="display:flex;gap:8px;margin-left:auto">
                @auth<button type="button" class="pr-btn ghost sm" data-reveal="{{ route('properties.phone', $p) }}">View number</button>
                @else<a class="pr-btn ghost sm" href="{{ route('login', ['redirect' => route('properties.show', $p)]) }}">View number</a>@endauth
                <a class="pr-btn sm" href="{{ route('properties.show', $p) }}#contact">✆ Contact</a>
            </div>
        </div>
    </div>
</article>
@once
@push('styles')<style>
.pr-thumb [hidden]{display:none}
.pr-thumb-link{position:absolute;inset:0;z-index:1}
.pr-slide{position:absolute;top:50%;transform:translateY(-50%);z-index:2;width:30px;height:30px;border-radius:50%;border:0;background:rgba(255,255,255,.85);font-size:20px;line-height:1;cursor:pointer;color:#111827}
.pr-slide.prev{left:8px}.pr-slide.next{right:8px}
.pr-thumb small{z-index:2}
.pr-verified{position:absolute;left:10px;top:10px;z-index:2;background:#047857;color:#fff;font-size:12px;font-weight:700;padding:3px 8px;border-radius:6px}
.pr-pricebar{display:flex;gap:22px;flex-wrap:wrap;align-items:flex-start}.pr-pricebar>div+div{border-left:1px solid var(--pr-line);padding-left:22px}
.pr-pricebar small{display:block;color:var(--pr-mute);font-size:13px;margin-top:2px}.pr-area{font-weight:800;font-size:16px}.pr-area small{display:inline;font-weight:500}
.pr-desc{font-size:14px;color:#374151}.pr-desc summary{cursor:pointer;list-style:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.pr-desc summary::-webkit-details-marker{display:none}
.pr-desc[open] summary{display:none}.pr-desc div{white-space:pre-line}
.pr-foot{display:flex;align-items:center;gap:12px;flex-wrap:wrap;border-top:1px solid var(--pr-line);padding-top:10px;margin-top:auto}.pr-foot b{display:block;font-size:14px}.pr-foot span{font-size:12.5px}
</style>@endpush
@push('scripts')<script>
(function () {
    document.addEventListener('click', function (e) {
        var step = e.target.closest('.pr-slide');
        if (step) {
            var box = step.closest('[data-slider]'), imgs = box.querySelectorAll('img'), cur = [].findIndex.call(imgs, function (i) { return !i.hidden; });
            var next = (cur + parseInt(step.dataset.step, 10) + imgs.length) % imgs.length;
            imgs[cur].hidden = true; imgs[next].hidden = false; box.querySelector('[data-count]').textContent = (next + 1) + '/' + imgs.length;
            return;
        }
        var btn = e.target.closest('[data-reveal]');
        if (btn) {
            btn.disabled = true;
            fetch(btn.dataset.reveal, {method: 'POST', headers: {'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json'}})
                .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
                .then(function (d) { var a = document.createElement('a'); a.href = 'tel:' + d.phone.replace(/[^+\d]/g, ''); a.className = btn.className; a.textContent = d.phone; btn.replaceWith(a); })
                .catch(function () { btn.disabled = false; btn.textContent = 'Try again'; });
        }
    });
})();
</script>@endpush
@endonce
