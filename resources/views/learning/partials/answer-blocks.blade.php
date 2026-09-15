@include('learning.partials.visual-assets')
@foreach(app(\App\Services\LearningPresentation::class)->blocks($answerText) as $answerBlock)
 @if($answerBlock['type']==='code')<pre class="vl-code"><code>{{ $answerBlock['text'] }}</code></pre>
 @else<ul class="vl-points">@foreach($answerBlock['items'] as $point)<li>{{ $point }}</li>@endforeach</ul>@endif
@endforeach
