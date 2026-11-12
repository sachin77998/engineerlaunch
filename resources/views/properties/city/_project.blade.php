{{-- A project tile. Expects $pj; optional $offers (show live offers). --}}
<a class="cp-card" href="{{ route('projects.show', $pj) }}">
    <div class="img" @if($pj->coverUrl()) style="background-image:url('{{ $pj->coverUrl() }}')" @endif>@unless($pj->coverUrl())🏢@endunless</div>
    <div class="t"><b>{{ $pj->name }}</b><small>{{ $pj->locality ?: $pj->city }}@if($pj->builder) · {{ $pj->builder->name }}@endif</small>
        @if($pj->configurationLabel())<small>{{ $pj->configurationLabel() }}</small>@endif
        <div style="display:flex;justify-content:space-between;gap:6px;align-items:center;margin-top:4px"><b style="font-size:15px">{{ $pj->priceRange() ?? 'Price on request' }}</b><span class="cp-pill">{{ $pj->possessionLabel() ?? $pj->statusLabel() }}</span></div></div>
    @if(!empty($offers))
        @foreach($pj->liveOffers->take(2) as $offer)<div class="cp-offer">🎁 <span>{{ $offer->title }}@if($offer->valid_until) <small style="display:inline">· till {{ $offer->valid_until->format('d M') }}</small>@endif</span></div>@endforeach
    @endif
</a>
