@extends('layouts.app')
@section('title',$module['title'].' - SQL')
@push('styles')
@include('learning.partials.three-column-styles')
<style>.topic-sidebar{grid-column:1;grid-row:1}.course-sidebar{grid-column:3;grid-row:1}.sql-case-main{grid-column:2;grid-row:1;min-width:0}.sql-case-main h1{font-size:28px;margin:0 0 20px}.sql-case-section{padding:22px;margin-bottom:18px;border:1px solid #dce5ef;border-radius:12px;background:#fff}.sql-case-section h2{font-size:21px}.sql-case-section pre{white-space:pre;overflow:auto;max-width:100%;padding:18px;background:#10213e;color:#edf5ff;border-radius:8px}.sql-case-section p{white-space:pre-line;line-height:1.7}.sql-case-nav{display:flex;flex-wrap:wrap;gap:12px;margin:16px 0}@media(max-width:1100px){.course-sidebar{grid-column:1/-1;grid-row:2}}@media(max-width:760px){.topic-sidebar{grid-column:1;grid-row:1}.sql-case-main{grid-column:1;grid-row:2}.course-sidebar{grid-column:1;grid-row:3}}</style>
@endpush
@section('content')
<div class="learning-shell">
@include('learning.partials.module-sidebars')
<main class="sql-case-main">
<a href="{{route('learning.track','sql')}}">&larr; SQL topics</a>
<h1>{{$module['title']}}</h1>
<nav class="sql-case-nav" aria-label="Sections">@foreach($questions as $item)<a href="#case-{{$item['number']}}">{{$item['question']}}</a>@endforeach</nav>
@foreach($questions as $item)
<section class="sql-case-section" id="case-{{$item['number']}}">
<h2>{{$item['question']}}</h2>
@php($parts=preg_split('/\x60{3}(?:sql|php)?\r?\n(.*?)\x60{3}/s',$item['answer'],-1,PREG_SPLIT_DELIM_CAPTURE))
@foreach($parts as $part)
@if($loop->index % 2 === 1)<pre><code>{{trim($part)}}</code></pre>
@else @foreach(preg_split('/\R\s*\R/',trim($part),-1,PREG_SPLIT_NO_EMPTY) as $paragraph)<p>{{$paragraph}}</p>@endforeach
@endif
@endforeach
</section>
@endforeach
<p><a href="https://dev.mysql.com/doc/refman/8.0/en/innodb-locking-reads.html" target="_blank" rel="noopener">MySQL locking reads</a> ? <a href="https://laravel.com/docs/10.x/queues#jobs-and-database-transactions" target="_blank" rel="noopener">Laravel queues and transactions</a></p>
@include('learning.partials.question-pagination')
</main></div>
@endsection
