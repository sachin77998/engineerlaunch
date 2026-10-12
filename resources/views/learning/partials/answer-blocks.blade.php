@include('learning.partials.visual-assets')
@once
<style>
.vl-deep-h{margin:22px 0 8px;font-size:17px;color:#0b2545;font-weight:800}.vl-deep-h:first-child{margin-top:0}
.vl-deep-p{margin:8px 0;line-height:1.75;color:#1e2f4d}
.vl-deep-note{margin:12px 0;padding:12px 15px;border-left:4px solid #f4b400;border-radius:0 9px 9px 0;background:#fffbeb;line-height:1.7;color:#1e2f4d}
.vl-points.ordered{list-style:decimal}
</style>
@endonce
@foreach(app(\App\Services\LearningPresentation::class)->blocks($answerText) as $answerBlock)
 @if($answerBlock['type']==='code')<pre class="vl-code"><code>{{ $answerBlock['text'] }}</code></pre>
 @elseif($answerBlock['type']==='heading')<h4 class="vl-deep-h">{{ $answerBlock['text'] }}</h4>
 @elseif($answerBlock['type']==='paragraph')<p class="vl-deep-p">{{ $answerBlock['text'] }}</p>
 @elseif($answerBlock['type']==='note')<p class="vl-deep-note">{{ $answerBlock['text'] }}</p>
 @elseif(!empty($answerBlock['ordered']))<ol class="vl-points ordered">@foreach($answerBlock['items'] as $point)<li>{{ $point }}</li>@endforeach</ol>
 @else<ul class="vl-points">@foreach($answerBlock['items'] as $point)<li>{{ $point }}</li>@endforeach</ul>@endif
@endforeach
