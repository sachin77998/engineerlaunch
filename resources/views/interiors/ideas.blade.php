@extends('layouts.site')
@section('title', 'Interior design ideas — ' . config('properties.brand'))
@section('content')
@include('interiors._head')
@php($c = config('interiors'))
<div class="pr" style="padding-bottom:60px"><div class="pr-wrap">
    <h1 style="margin:24px 0 12px">Interior design ideas</h1>
    <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px">
        <select name="room" onchange="this.form.submit()" style="width:auto"><option value="">All rooms</option>@foreach($c['rooms'] as $k => [$l])<option value="{{ $k }}" @selected(($f['room'] ?? '') === $k)>{{ $l }}</option>@endforeach</select>
        <select name="style" onchange="this.form.submit()" style="width:auto"><option value="">All styles</option>@foreach($c['styles'] as $k => [$l])<option value="{{ $k }}" @selected(($f['style'] ?? '') === $k)>{{ $l }}</option>@endforeach</select>
        <select name="category" onchange="this.form.submit()" style="width:auto"><option value="">All categories</option>@foreach($c['categories'] as $k => $l)<option value="{{ $k }}" @selected(($f['category'] ?? '') === $k)>{{ $l }}</option>@endforeach</select>
        @if(array_filter($f))<a href="{{ route('interiors.ideas') }}" style="align-self:center">Clear</a>@endif
    </form>
    @if($items->isEmpty())
        <div class="pr-card-box" style="text-align:center;padding:30px"><b>No designs here yet.</b><p class="pr-mute">Tell us what you like and our designer will share ideas for your home.</p><a class="in-red" href="{{ route('interiors.quote') }}">Get a free quote</a></div>
    @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
            @foreach($items as $i)<a class="in-tile" href="{{ route('interiors.idea', $i) }}" style="background-image:url('{{ $i->imageUrl() }}');min-height:260px"><div style="display:block">
                <b>{{ $i->title }}</b><small>{{ collect([$i->roomLabel(), $i->styleLabel()])->filter()->implode(' · ') }}</small></div></a>@endforeach
        </div>
        <div style="margin-top:18px">{{ $items->links() }}</div>
    @endif
</div></div>
@endsection
