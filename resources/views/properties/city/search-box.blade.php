<div class="cp-search" data-hero-search data-suggest="{{ route('properties.suggest') }}" data-locate="{{ route('real-estate.locate') }}" data-city="{{ $context->slug() }}">
    <div class="cp-stabs" role="tablist">
        @foreach($tabs as $key => [$label, $url, $field, $choices, $fixed])
            <button type="button" role="tab" class="{{ $loop->first ? 'on' : '' }}" data-stab="{{ $key }}" data-url="{{ $url }}" data-field="{{ $field }}" data-fixed='@json($fixed)'
                data-choices='@json(collect($choices)->map(fn ($l, $v) => [$v, $l])->values())'>{{ $label }}@if($key === 'new')<i class="dot" aria-hidden="true"></i>@endif</button>
        @endforeach
        <a href="{{ route('properties.post') }}" class="post">Post property <span>FREE</span></a>
    </div>
    <form class="cp-sbar" data-search-form>
        <select data-stype aria-label="Property type"></select>
        <div class="cp-sinput"><span aria-hidden="true">🔍</span>
            <input type="text" data-sq placeholder='Search locality, project or landmark in {{ $context->place() }}' autocomplete="off" aria-label="Locality, project or landmark">
            <div class="cp-clist" data-sq-list hidden></div></div>
        <button type="button" class="cp-ibtn" data-locate-me title="Use my location" aria-label="Use my location">◎</button>
        <button type="button" class="cp-ibtn" data-voice title="Search by voice" aria-label="Search by voice" hidden>🎤</button>
        <button class="pr-btn">Search</button>
    </form>
    <p class="cp-snote" data-snote hidden></p>
</div>
@if($recent->isNotEmpty())
    <div class="cp-recent"><span>Recent searches:</span>@foreach($recent as $r)<a href="{{ $r->url }}" title="{{ $r->label }}">🕘 {{ $r->label }}</a>@endforeach<a href="{{ route('real-estate.searches') }}" class="all">View all searches</a></div>
@endif
<div class="cp-trail-row">
    <span>Pick up where you left off</span>
    @foreach($trail as $t)<a href="{{ $t['url'] }}">{{ $t['icon'] }} {{ $t['label'] }}</a>@endforeach
    <a href="#" data-chooser-open>📍 Another city</a>
</div>
