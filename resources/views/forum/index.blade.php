@extends('layouts.site')
@section('title', 'Property forum — ask questions on renting, buying, legal, loans & home services — ' . config('properties.brand'))
@section('content')
@include('forum._head')
<div class="pr" style="background:#fff;padding-bottom:80px"><div class="fo-wrap">
    <div class="fo-crumb"><a href="{{ route('home') }}">Home</a> / Forum</div>
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    <section class="fo-hero">
        <h1>Property forum</h1>
        <form method="get" action="{{ route('forum.index') }}" class="fo-search" role="search"><input type="search" name="q" value="{{ $q }}" placeholder="Search question" aria-label="Search question"><button aria-label="Search">🔍</button></form>
        <p style="margin:14px 0 10px">Discuss Indian real estate with owners, tenants and experts —</p>
        @auth<button type="button" class="fo-green" data-ask>Ask your question</button>@else<a class="fo-green" href="{{ route('login', ['redirect' => route('forum.index')]) }}">Sign in to ask</a>@endauth
    </section>

    @if($results)
        <h2 class="fo-h2">{{ $results->total() }} {{ $results->total() === 1 ? 'result' : 'results' }} for “{{ $q }}”</h2>
        @forelse($results as $q)@include('forum._card')@empty<p class="pr-mute" style="text-align:center">Nothing found. Ask it — someone may know.</p>@endforelse
        {{ $results->links() }}
    @else
        <h2 class="fo-h2">Topics</h2>
        <div class="fo-cats">@foreach(config('forum.categories') as $k => [$label, $desc])<a href="{{ route('forum.category', $k) }}"><b>{{ $label }}</b><small>{{ $desc }}</small><small style="margin-top:6px">{{ number_format($counts[$k] ?? 0) }} {{ ($counts[$k] ?? 0) === 1 ? 'question' : 'questions' }}</small></a>@endforeach</div>
        <h2 class="fo-h2">Recently answered questions</h2>
        @forelse($answered as $q)@include('forum._card')@empty<p class="pr-mute" style="text-align:center">No answered questions yet — be the first to ask or answer.</p>@endforelse
        @if($unanswered->isNotEmpty())
            <h2 class="fo-h2">Most recent unanswered questions</h2>
            @foreach($unanswered as $q)@include('forum._card')@endforeach
        @endif
    @endif
</div></div>
@endsection
