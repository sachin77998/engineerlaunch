{{-- Recommended companies from the similarity model (content + financial profile + viewed together). --}}
<section class="ci-card" id="similar" style="grid-column:1/-1;min-width:0">
    <h2>{{ $similarTitle ?? 'Similar companies' }}</h2>
    <div class="ci-grid">
        @foreach($items as $s)
            <a class="ci-company" href="{{ route('insights.show', $s->slug) }}"><span class="ci-logo">@if($s->logo_url)<img src="{{ $s->logo_url }}" alt="" loading="lazy">@else{{ mb_strtoupper(mb_substr($s->name, 0, 1)) }}@endif</span>
                <span class="ci-company-body"><strong>{{ $s->name }}</strong><small>{{ $s->industry }}</small>
                    @if(!empty($s->reasons))<em>{{ implode(' · ', $s->reasons) }}</em>@elseif(isset($s->score))<em>Match {{ min(99, (int) round($s->score * 100)) }}%</em>@endif</span></a>
        @endforeach
    </div>
</section>
