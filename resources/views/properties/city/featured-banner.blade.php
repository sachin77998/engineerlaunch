<div class="cp-banner" data-banner>
    @foreach($items as $i => $pj)
        @php($b = $pj->builder)
        <div class="cp-slide" style="background-image:linear-gradient(90deg,rgba(15,23,42,.15) 0%,rgba(15,23,42,.82) 58%),url('{{ $pj->coverUrl() }}')" @if($i > 0) hidden @endif>
            <div class="cp-slide-copy">
                <div style="display:flex;gap:12px;align-items:center">
                    @if($b)<span class="cp-logo" style="width:56px;height:56px;border-radius:10px">@if($b->logoUrl())<img src="{{ $b->logoUrl() }}" alt="{{ $b->name }}">@else{{ $b->initials() }}@endif</span>@endif
                    @if($pj->rera_number)<small style="color:#cbd5e1;line-height:1.4">RERA No: {{ $pj->rera_number }}@if($pj->rera_website)<br>{{ parse_url($pj->rera_website, PHP_URL_HOST) }}@endif</small>@endif
                </div>
                <h2>{{ $pj->name }}@if($b) <small>by {{ $b->name }}</small>@endif</h2>
                <p>{{ collect([$pj->configurationLabel(), $pj->locality, $pj->city])->filter()->implode(' · ') }}@if($pj->price_min) | {{ \App\Services\Property\PropertyFormat::short($pj->price_min) }} onwards @endif</p>
                <a class="cp-slide-btn" href="{{ route('projects.show', $pj) }}">View project →</a>
            </div>
        </div>
    @endforeach
    @if($items->count() > 1)
        <button type="button" class="cp-bnav l" data-bstep="-1" aria-label="Previous project">‹</button><button type="button" class="cp-bnav r" data-bstep="1" aria-label="Next project">›</button>
    @endif
</div>
