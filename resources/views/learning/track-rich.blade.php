@extends('layouts.app')
@section('title',$track['title'].' topics')
@push('styles')
<style>.topic-layout{width:min(1250px,calc(100% - 36px));margin:28px auto;display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:24px}.topic-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.topic-card{display:block;padding:22px;border:1px solid #dce5ef;border-radius:14px;background:#fff;text-decoration:none;color:#10213e}.topic-card:hover,.topic-card:focus-visible{outline:2px solid #2563eb}.topic-card h2{font-size:21px;margin:0 0 12px}.topic-nav{padding:18px;border:1px solid #dce5ef;border-radius:14px;background:#fff;align-self:start}.topic-nav a{display:block;margin:10px 0}.topic-nav .vl-panel{padding:10px;margin:10px 0}.topic-nav .vl-flow{display:block}.topic-nav .vl-flow li{margin:10px 0}.topic-nav .vl-table-wrap{overflow-x:auto}.topic-nav details>summary{cursor:pointer;font-weight:750}.topic-nav h2{font-size:20px}@media(max-width:800px){.topic-layout{grid-template-columns:1fr}.topic-nav{order:2}}@media(max-width:540px){.topic-grid{grid-template-columns:1fr}}</style>
@endpush
@section('content')
<div class="topic-layout"><main>
<a href="{{route('learning.index')}}">&larr; All topics</a><h1>{{$track['title']}}</h1>
<div class="topic-grid">
@foreach($track['topics'] as $topicSlug=>$topic)
<a class="topic-card" href="{{route('learning.show',[$slug,$topicSlug])}}"><h2>{{$topic['title']}}</h2>
<span>{{count($topic['questions'])}} {{($topic['type']??null)==='tutorial'?'lessons':'questions'}} &rarr;</span></a>
@endforeach
</div></main><aside class="topic-nav" aria-label="Topic navigation">
<strong>Topics</strong>
@foreach($track['topics'] as $topicSlug=>$topic)<a href="{{route('learning.show',[$slug,$topicSlug])}}">{{$topic['title']}}</a>@endforeach
<details><summary>Optional study guide</summary>@include('learning.partials.visual-overview')</details>
</aside></div>
@endsection
