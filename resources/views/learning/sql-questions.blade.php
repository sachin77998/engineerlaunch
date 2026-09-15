@extends('layouts.app')
@section('title',$module['title'].' — '.config('platform.name'))
@push('styles')
@include('learning.partials.three-column-styles')
<style>.topic-sidebar{grid-column:1}.questions-main{grid-column:2}.course-sidebar{grid-column:3}@media(max-width:1100px){.course-sidebar{grid-column:1/-1}.questions-main{grid-column:2}}@media(max-width:760px){.topic-sidebar,.questions-main,.course-sidebar{grid-column:1}.topic-sidebar{order:1}.questions-main{order:2}.course-sidebar{order:3}}</style>
<style>.sql-card{margin:0 0 16px;padding:25px;border:1px solid #dbe5e4;border-radius:15px;background:#fff}.sql-number{color:#0f766e;font-weight:800}.sql-answer{margin-top:14px;padding:18px;border-left:4px solid #0f766e;border-radius:0 10px 10px 0;background:#f0fdfa;white-space:pre-wrap;color:#25364a;font:15px/1.7 Inter,system-ui}.sql-card:target{border-color:#0f766e;box-shadow:0 8px 24px #0f766e20}</style>
@endpush
@section('content')
<section class="hero-band"><div class="container"><a href="{{route('learning.track',$trackSlug)}}" style="color:#bfdbfe">← SQL learning path</a><h1>{{$module['title']}}</h1><p>Questions and solutions from the supplied SQL interview guide.</p></div></section>
<div class="learning-shell">
@include('learning.partials.module-sidebars')
<main class="questions-main">
@include('learning.partials.visual-overview')
<label class="vl-mode"><input type="checkbox" data-vl-recall-mode> Recall mode: hide answers until I reveal them</label>
@foreach($questions as $item)
<article class="sql-card" id="question-{{$item['number']}}">
 <span class="sql-number">Question {{$item['number']}} of 50</span>
 <h2>{{$item['question']}}</h2>
 <button type="button" class="primary" data-vl-reveal="answer-{{ $item['number'] }}" hidden>Reveal explanation</button>
 <div class="vl-answer" id="answer-{{ $item['number'] }}"><h3>Solution</h3>@include('learning.partials.answer-blocks',['answerText'=>$item['answer']])</div>
 <details class="vl-recall"><summary>Predict the result before running the query</summary><ul class="vl-points"><li>Which rows will match?</li><li>Which columns will appear?</li><li>What changes if a value is NULL or repeated?</li></ul><label class="vl-notes">Your prediction<textarea data-vl-notes="question-{{ $item['number'] }}"></textarea><span class="vl-note-status">Private practice notes on this browser.</span></label></details>
</article>
@endforeach
@include('learning.partials.question-pagination')
</main>
</div>
@endsection
