@extends('layouts.app')
@section('title','Learning topics')
@push('styles')
<style>.learn-wrap{width:min(1180px,calc(100% - 36px));margin:28px auto}.learn-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;padding:18px 0 50px}.learn-card{display:flex;min-height:170px;flex-direction:column;padding:24px;border:1px solid #dce5ef;border-radius:16px;background:#fff;color:#10213e;text-decoration:none}.learn-card:hover,.learn-card:focus-visible{border-color:var(--accent);outline:2px solid var(--accent)}.learn-card h2{margin:12px 0}.learn-icon{color:var(--accent);font-weight:800}.learn-open{margin-top:20px;color:var(--accent);font-weight:750}</style>
@endpush
@section('content')
<main class="learn-wrap"><h1>Learning topics</h1>
<div class="learn-grid">
@foreach($tracks as $slug=>$track)
<a class="learn-card" style="--accent:{{$track['color']}}" href="{{route('learning.track',$slug)}}">
<span class="learn-icon">{{$track['icon']}}</span><h2>{{$track['title']}}</h2>
<small>{{count($track['topics'])}} topics</small><span class="learn-open">View topics &rarr;</span></a>
@endforeach
</div></main>
@endsection
