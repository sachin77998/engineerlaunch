@extends('layouts.site')
@section('title', \App\Support\ForumName::short($author->name) . ' — forum answers')
@section('content')
@include('forum._head')
<div class="pr" style="background:#fff;padding-bottom:60px"><div class="fo-wrap">
    <div class="fo-crumb"><a href="{{ route('forum.index') }}">Forum</a> / {{ \App\Support\ForumName::short($author->name) }}</div>
    <section class="fo-hero" style="text-align:left">
        <h1 style="text-align:left">{{ \App\Support\ForumName::short($author->name) }}</h1>
        <div style="display:flex;gap:24px;flex-wrap:wrap"><span><b>{{ $stats['answers'] }}</b> answers</span><span><b>{{ $stats['upvotes'] }}</b> 👍 upvotes</span><span><b>{{ $stats['best'] }}</b> best answers</span><span><b>{{ $stats['questions'] }}</b> questions asked</span></div>
    </section>
    <h2 class="fo-h2" style="text-align:left">Answers</h2>
    @forelse($recent as $a)
        <div class="fo-q" style="grid-template-columns:minmax(0,1fr) auto">
            <h3><a href="{{ $a->question->url() }}#a{{ $a->id }}">{{ $a->question->title }}</a></h3>
            <div class="meta">{{ $a->created_at->format('M j, Y') }}<br>👍 {{ $a->upvotes }}@if($a->is_best) · ✓ best @endif</div>
            <p style="grid-column:1/-1">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $a->body), 200) }}</p>
        </div>
    @empty<p class="pr-mute">No answers yet.</p>@endforelse
    {{ $recent->links() }}
</div></div>
@endsection
