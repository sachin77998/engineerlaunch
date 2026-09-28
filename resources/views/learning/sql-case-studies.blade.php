@extends('layouts.app')
@section('title',$module['title'].' - SQL')
@push('styles')
@include('learning.partials.three-column-styles')
<style>
.topic-sidebar{grid-column:1;grid-row:1}.course-sidebar{grid-column:3;grid-row:1}.sql-case-main{grid-column:2;grid-row:1;min-width:0}.sql-case-main h1{font-size:28px;margin:0 0 20px}.sql-case-section{padding:22px;margin-bottom:18px;border:1px solid #dce5ef;border-radius:12px;background:#fff;scroll-margin-top:24px}.sql-case-section h2{font-size:21px}.sql-case-section h3{font-size:17px;margin:20px 0 10px}.sql-case-section pre{white-space:pre;overflow:auto;max-width:100%;padding:18px;background:#10213e;color:#edf5ff;border-radius:8px}.sql-case-section p{white-space:pre-line;line-height:1.7}.sql-case-nav{display:flex;flex-wrap:wrap;gap:12px;margin:16px 0}.sql-case-nav a{overflow-wrap:anywhere}.sql-case-example{font-size:13px;color:#526780}.sql-case-table-wrap{max-width:100%;overflow-x:auto;margin:16px 0;border:1px solid #dce5ef;border-radius:8px}.sql-case-table{width:100%;border-collapse:collapse;font-size:14px}.sql-case-table caption{text-align:left;font-weight:700;padding:12px;background:#f3f7fc}.sql-case-table th,.sql-case-table td{text-align:left;padding:10px 12px;border-bottom:1px solid #e2e8f0;vertical-align:top}.sql-case-table th{background:#edf3f8}.sql-case-table td{min-width:100px;overflow-wrap:anywhere}.sql-case-timeline td:first-child{font-weight:700;color:#0f766e;min-width:40px}.sql-case-flow{list-style:none;padding:0;display:flex;flex-wrap:wrap;gap:22px;margin:18px 0}.sql-case-flow li{position:relative;padding:12px;border:1px solid #c7dde4;border-radius:8px;background:#f0f8fa;flex:1 1 150px;overflow-wrap:anywhere}.sql-case-flow li:not(:last-child):after{content:'\2192';position:absolute;right:-18px;top:12px;color:#0f766e}.sql-case-question{border-top:1px solid #dce5ef;padding:14px 0}.sql-case-question summary{cursor:pointer;font-weight:600}.sql-case-section .sql-token-keyword{color:#93c5fd;font-weight:700}.sql-case-section .sql-token-string{color:#86efac}.sql-case-section .sql-token-number{color:#fcd34d}.sql-case-section .sql-token-comment{color:#b6c4d6}.sql-case-main a:focus-visible,.sql-case-main summary:focus-visible,.sql-case-table-wrap:focus-visible{outline:2px solid #2563eb;outline-offset:3px}
@media(max-width:1100px){.course-sidebar{grid-column:1/-1;grid-row:2}}@media(max-width:760px){.topic-sidebar{grid-column:1;grid-row:1}.sql-case-main{grid-column:1;grid-row:2}.course-sidebar{grid-column:1;grid-row:3}}
</style>
@endpush
@section('content')
<div class="learning-shell">
@include('learning.partials.module-sidebars')
<main class="sql-case-main">
<a href="{{route('learning.track','sql')}}">&larr; SQL topics</a>
<h1>{{$module['title']}}</h1>
<p>{{$module['summary']}}</p><p class="sql-case-example">{{$module['example_label']}}</p>
<nav class="sql-case-nav" aria-label="Lesson sections">@foreach($module['sections'] as $section)<a href="#{{$section['id']}}">{{$loop->iteration}}. {{$section['title']}}</a>@endforeach</nav>
@foreach($module['sections'] as $section)
<section class="sql-case-section" id="{{$section['id']}}" aria-labelledby="{{$section['id']}}-heading">
<h2 id="{{$section['id']}}-heading">{{$loop->iteration}}. {{$section['title']}}</h2>
@foreach($section['blocks'] as $block)@include('learning.partials.sql-case-block',['block'=>$block])@endforeach
</section>
@endforeach
<nav class="sql-case-nav" aria-label="Course navigation">
@php($topicSlugs=array_keys($track['topics']))
@php($topicPosition=array_search($moduleSlug,$topicSlugs,true))
@if($topicPosition>0)<a href="{{route('learning.show',[$trackSlug,$topicSlugs[$topicPosition-1]])}}">&larr; Previous topic</a>@endif
<a href="{{route('learning.track','sql')}}">All SQL topics</a>
@if(isset($topicSlugs[$topicPosition+1]))<a href="{{route('learning.show',[$trackSlug,$topicSlugs[$topicPosition+1]])}}">Next topic &rarr;</a>@endif
</nav>
<p><a href="https://dev.mysql.com/doc/refman/8.0/en/innodb-locking-reads.html" target="_blank" rel="noopener">MySQL locking reads</a> &middot; <a href="https://laravel.com/docs/10.x/queues#jobs-and-database-transactions" target="_blank" rel="noopener">Laravel queues and transactions</a></p>
</main></div>
@endsection
