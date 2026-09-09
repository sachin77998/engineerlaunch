@extends('layouts.app')
@section('title',$module['title'].' — '.config('platform.name'))
@push('styles')
<style>.sql-wrap{width:min(940px,calc(100% - 34px));margin:auto;padding:42px 0}.sql-card{margin:16px 0;padding:25px;border:1px solid #dbe5e4;border-radius:15px;background:#fff}.sql-number{color:#0f766e;font-weight:800}.sql-answer{margin-top:14px;padding:18px;border-left:4px solid #0f766e;border-radius:0 10px 10px 0;background:#f0fdfa;white-space:pre-wrap;color:#25364a;font:15px/1.7 Inter,system-ui}.sql-card:target{border-color:#0f766e;box-shadow:0 8px 24px #0f766e20}</style>
@endpush
@section('content')
<section class="hero-band"><div class="container"><a href="{{route('learning.track',$trackSlug)}}" style="color:#bfdbfe">← SQL learning path</a><h1>{{$module['title']}}</h1><p>Questions and solutions from the supplied SQL interview guide.</p></div></section>
<div class="sql-wrap">
@foreach($module['questions'] as $item)
<article class="sql-card" id="question-{{$item['number']}}">
 <span class="sql-number">Question {{$item['number']}} of 50</span>
 <h2>{{$item['question']}}</h2>
 <div class="sql-answer"><strong>Answer</strong><br>{{$item['answer']}}</div>
</article>
@endforeach
</div>
@endsection
