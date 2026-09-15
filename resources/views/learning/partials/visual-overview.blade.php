@include('learning.partials.visual-assets')
<?php $visualOverview=app(\App\Services\LearningPresentation::class)->overview($trackSlug ?? $slug ?? ''); ?>
@if($visualOverview)
<section class="vl-panel" aria-label="Visual learning overview">
 <span class="vl-eyebrow">See the big picture &middot; {{ $track['title'] }}</span>
 <h2>{{ $visualOverview['title'] }}</h2>
 <p class="vl-muted">Start with the diagram, compare the concepts, then apply them to the lesson.</p>
 <ol class="vl-flow" aria-label="Concept flow">@foreach($visualOverview['steps'] as [$stepTitle,$stepBody])<li><strong>{{ $stepTitle }}</strong><span>{{ $stepBody }}</span></li>@endforeach</ol>
 <div class="vl-table-wrap" tabindex="0" role="region" aria-label="Concept comparison"><table class="vl-table"><caption>At a glance</caption><thead><tr>@foreach($visualOverview['columns'] as $column)<th scope="col">{{ $column }}</th>@endforeach</tr></thead><tbody>@foreach($visualOverview['rows'] as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table></div>
 <p class="vl-callout"><strong>Watch for this:</strong> {{ $visualOverview['pitfall'] }}</p>
 @isset($visualOverview['source'])<a class="vl-source" href="{{ $visualOverview['source'][1] }}" target="_blank" rel="noopener">Read the {{ $visualOverview['source'][0] }}</a>@endisset
</section>
@endif
