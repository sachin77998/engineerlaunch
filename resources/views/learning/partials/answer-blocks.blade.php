@include('learning.partials.visual-assets')
@once
<style>
.vl-deep-h{margin:22px 0 8px;font-size:17px;color:#0b2545;font-weight:800}.vl-deep-h:first-child{margin-top:0}
.vl-deep-p{margin:8px 0;line-height:1.75;color:#1e2f4d}
.vl-deep-note{margin:12px 0;padding:12px 15px;border-left:4px solid #f4b400;border-radius:0 9px 9px 0;background:#fffbeb;line-height:1.7;color:#1e2f4d}
.vl-points.ordered{list-style:decimal}
.vl-deep-table-wrap{margin:12px 0;overflow-x:auto;border:1px solid #dfe6f1;border-radius:10px}
.vl-deep-table{width:100%;border-collapse:collapse;font-size:14px}.vl-deep-table th,.vl-deep-table td{padding:9px 12px;border-bottom:1px solid #eef2f7;text-align:left;vertical-align:top;line-height:1.55;color:#1e2f4d}
.vl-deep-table thead th{background:#0b2545;color:#fff;font-weight:700}.vl-deep-table tbody tr:nth-child(even){background:#f7f9fc}
.vl-inline-code{padding:1px 6px;border-radius:5px;background:#eef2f8;color:#0b2545;font-family:ui-monospace,Consolas,monospace;font-size:.92em}
.vl-deep-table thead .vl-inline-code{background:rgba(255,255,255,.16);color:#fff}
</style>
@endonce
@php($inline = fn ($text) => \App\Services\LearningPresentation::inline((string) $text))
@foreach(app(\App\Services\LearningPresentation::class)->blocks($answerText) as $answerBlock)
 @if($answerBlock['type']==='code')<pre class="vl-code"><code>{{ $answerBlock['text'] }}</code></pre>
 @elseif($answerBlock['type']==='heading')<h4 class="vl-deep-h">{!! $inline($answerBlock['text']) !!}</h4>
 @elseif($answerBlock['type']==='paragraph')<p class="vl-deep-p">{!! $inline($answerBlock['text']) !!}</p>
 @elseif($answerBlock['type']==='table')<div class="vl-deep-table-wrap"><table class="vl-deep-table"><thead><tr>@foreach($answerBlock['columns'] as $cell)<th>{!! $inline($cell) !!}</th>@endforeach</tr></thead><tbody>@foreach($answerBlock['rows'] as $row)<tr>@foreach($row as $cell)<td>{!! $inline($cell) !!}</td>@endforeach</tr>@endforeach</tbody></table></div>
 @elseif($answerBlock['type']==='note')<p class="vl-deep-note">{!! $inline($answerBlock['text']) !!}</p>
 @elseif(!empty($answerBlock['ordered']))<ol class="vl-points ordered">@foreach($answerBlock['items'] as $point)<li>{!! $inline($point) !!}</li>@endforeach</ol>
 @else<ul class="vl-points">@foreach($answerBlock['items'] as $point)<li>{!! $inline($point) !!}</li>@endforeach</ul>@endif
@endforeach
