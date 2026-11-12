{{-- A listing card for city-page rails. Expects $p; uses $shortlisted (ids) when present. --}}
@php
    $isShort = in_array($p->id, $shortlisted ?? [], true);
    $by = $p->relationLoaded('owner') && $p->owner ? ($p->owner->owner_type === 'agent' ? 'Dealer' : ucfirst($p->owner->owner_type)) : null;
@endphp
<div class="cp-card cp-lcard">
    <a href="{{ route('properties.show', $p) }}" class="img" @if($p->coverUrl()) style="background-image:url('{{ $p->coverUrl() }}')" @endif aria-label="{{ $p->title }}">@unless($p->coverUrl())⌂@endunless</a>
    @auth
        <form method="post" action="{{ route('properties.shortlist.toggle', $p) }}" class="cp-heart-f">@csrf<button class="cp-heart {{ $isShort ? 'on' : '' }}" title="{{ $isShort ? 'Remove from shortlist' : 'Shortlist' }}" aria-label="Shortlist" aria-pressed="{{ $isShort ? 'true' : 'false' }}">{{ $isShort ? '♥' : '♡' }}</button></form>
    @else
        <a href="{{ route('login') }}" class="cp-heart-f"><span class="cp-heart" title="Log in to shortlist">♡</span></a>
    @endauth
    <a href="{{ route('properties.show', $p) }}" class="t">
        <small style="color:#111827;font-weight:600">{{ $p->title }}</small>
        <b>{{ $p->headlinePrice() }}</b>
        <small>In {{ collect([$p->locality, $p->city])->filter()->implode(', ') }}</small>
        <span class="meta">@if($by)<span>Posted by {{ $by }}</span>@endif<span>{{ $p->published_at?->diffForHumans(null, true) }}{{ $p->published_at ? ' ago' : '' }}</span></span>
    </a>
</div>
