@extends('layouts.app')
@section('title',$module['title'].' — '.config('platform.name'))
@push('styles')<style>.topic-sidebar{grid-column:1}.questions-main{grid-column:2}.course-sidebar{grid-column:3}@media(max-width:1100px){.course-sidebar{grid-column:1/-1}.questions-main{grid-column:2}}@media(max-width:760px){.topic-sidebar,.questions-main,.course-sidebar{grid-column:1}.topic-sidebar{order:1}.questions-main{order:2}.course-sidebar{order:3}}</style>@endpush
@push('styles')@include('learning.partials.three-column-styles')<style>.question-card{margin:0 0 16px;padding:24px;border:1px solid #e0e7f1;border-radius:15px;background:#fff}.question-card:target{border-color:#2563eb;box-shadow:0 8px 24px #2563eb14}.question-answer{margin-top:14px;padding:17px;border-left:4px solid {{$track['color']}};border-radius:0 10px 10px 0;background:#f8fafc;color:#334155;line-height:1.7}.question-answer p{margin:6px 0 0}</style>@endpush
@section('content')
<section class="hero-band"><div class="container"><a href="{{ route('learning.track',$trackSlug) }}" style="color:#bfdbfe">&larr; {{ $track['title'] }}</a><h1>{{ $module['title'] }}</h1><p>Understand the idea. Compare it. Explain it. Test yourself.</p></div></section>
<div class="learning-shell">@include('learning.partials.module-sidebars')<main class="questions-main">
@include('learning.partials.visual-overview')
<label class="vl-mode"><input type="checkbox" data-vl-recall-mode> Recall mode: hide answers until I reveal them</label>
@foreach($questions as $question)
<?php $questionNumber=$questions->firstItem()+$loop->index; $questionText=is_array($question)?($question['question']??$question['title']??''):$question; ?>
<article class="question-card" id="question-{{ $questionNumber }}"><small>Question {{ $questionNumber }} of {{ $questions->total() }}</small><h2>{{ $questionText }}</h2>
@if(is_array($question)&&filled($question['answer']??null))
<button type="button" class="primary" data-vl-reveal="answer-{{ $questionNumber }}" hidden>Reveal explanation</button>
<div class="vl-answer" id="answer-{{ $questionNumber }}"><h3>Solution and explanation</h3>@include('learning.partials.answer-blocks',['answerText'=>$question['answer']])</div>
@else
<span class="vl-practice-label">Practice question &middot; write your own answer</span><ul class="vl-points"><li>Define the concept in one or two sentences.</li><li>Give a small example and trace what happens.</li><li>Explain one limitation, trade-off, or common mistake.</li></ul>
@endif
<details class="vl-recall"><summary>Practice explaining this</summary><label class="vl-notes">Your answer<textarea data-vl-notes="question-{{ $questionNumber }}" placeholder="Definition, example, and one important limitation..."></textarea><span class="vl-note-status">Private practice notes on this browser.</span></label></details>
</article>
@endforeach
@include('learning.partials.question-pagination')
</main></div>
@endsection
