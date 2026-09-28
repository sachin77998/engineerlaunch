@switch($block['type'])
@case('text')
@foreach($block['paragraphs'] as $paragraph)<p>{{$paragraph}}</p>@endforeach
@break
@case('schema')
<h3>{{$block['name']}}</h3>
<div class="sql-case-table-wrap" role="region" aria-label="{{$block['name']}} schema" tabindex="0">
<table class="sql-case-table"><caption>{{$block['name']}} — educational schema</caption><thead><tr><th scope="col">Column</th><th scope="col">Type / constraint</th></tr></thead><tbody>
@foreach($block['columns'] as $column)<tr><th scope="row">{{$column[0]}}</th><td>{{$column[1]}}</td></tr>@endforeach
</tbody></table></div><p>{{$block['note']}}</p>
@break
@case('table')
<div class="sql-case-table-wrap" role="region" aria-label="{{$block['caption']}}" tabindex="0">
<table class="sql-case-table {{str_contains($block['caption'],'timeline')?'sql-case-timeline':''}}"><caption>{{$block['caption']}}</caption><thead><tr>@foreach($block['columns'] as $column)<th scope="col">{{$column}}</th>@endforeach</tr></thead><tbody>
@forelse($block['rows'] as $row)<tr>@foreach($row as $cell)<td>{{is_bool($cell)?($cell?'true':'false'):($cell===null?'NULL':$cell)}}</td>@endforeach</tr>
@empty<tr><td colspan="{{count($block['columns'])}}">No rows yet — this is the state before processing.</td></tr>@endforelse
</tbody></table></div>
@break
@case('flow')
<h3>{{$block['caption']}}</h3><ol class="sql-case-flow" aria-label="{{$block['caption']}}">
@foreach($block['steps'] as $step)<li>{{$step}}</li>@endforeach</ol>
@break
@case('code')
<h3>{{$block['caption']}}</h3><pre><code class="language-{{$block['language']}}">{{app(\App\Services\LearningCodeHighlighter::class)->render($block['source'],$block['language'])}}</code></pre>
@break
@case('questions')
@foreach($block['items'] as [$question,$answer])<details class="sql-case-question"><summary>{{$question}}</summary><p>{{$answer}}</p></details>@endforeach
@break
@case('links')
<nav class="sql-case-nav" aria-label="Related SQL topics">@foreach($block['items'] as [$label,$slug])<a href="{{route('learning.show',['sql',$slug])}}">{{$label}}</a>@endforeach</nav>
@break
@case('legacy')
<h3>{{$block['title']}}</h3>
@php($parts=preg_split('/\x60{3}(sql|php)?\r?\n(.*?)\x60{3}/s',$block['answer'],-1,PREG_SPLIT_DELIM_CAPTURE))
@for($i=0;$i<count($parts);$i+=3)
@foreach(preg_split('/\R\s*\R/',trim($parts[$i]),-1,PREG_SPLIT_NO_EMPTY) as $paragraph)<p>{{$paragraph}}</p>@endforeach
@if(isset($parts[$i+2]))<pre><code>{{app(\App\Services\LearningCodeHighlighter::class)->render(trim($parts[$i+2]),$parts[$i+1]?:'sql')}}</code></pre>@endif
@endfor
@break
@endswitch
