@php($explanation = $explanation ?? app(\App\Services\SiteExplanation::class)->forRequest(request()))
@if($explanation)
<section class="explain-panel" data-visual-explainer="{{ $explanation['key'] }}" aria-label="{{ $explanation['title'] }}">
 <header class="explain-heading"><span class="explain-kicker">UNDERSTAND &middot; COMPARE &middot; TAKE THE NEXT STEP</span><h2>{{ $explanation['title'] }}</h2><p>{{ $explanation['intro'] }}</p></header>
 <ol class="explain-flow" aria-label="Step-by-step diagram">@foreach($explanation['steps'] as $step)<li><span class="explain-number">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><strong>{{ $step['title'] }}</strong><span>{{ $step['body'] }}</span></li>@endforeach</ol>
 <div class="explain-columns"><div class="explain-table-wrap" role="region" tabindex="0" aria-label="Explanation comparison table"><table><caption>At a glance</caption><thead><tr>@foreach($explanation['columns'] as $column)<th scope="col">{{ $column }}</th>@endforeach</tr></thead><tbody>@foreach($explanation['rows'] as $row)<tr>@foreach($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>@endforeach</tbody></table></div><aside class="explain-points"><h3>Put it into practice</h3><ul>@foreach($explanation['points'] as $point)<li>{{ $point }}</li>@endforeach</ul></aside></div>
</section>
@endif