@extends('layouts.site')
@section('title', $cat[0] . ' questions — property forum — ' . config('properties.brand'))
@section('content')
@include('forum._head', ['category' => $category, 'cta' => $cat[2]])
@push('styles')<style>
.fo-authors{background:#fff;border-radius:8px;box-shadow:0 2px 10px rgba(17,24,39,.08);padding:20px;margin-top:30px}.fo-authors h2{font-size:20px;margin:0 0 14px}
.fo-arow{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(260px,300px);gap:14px;overflow-x:auto;padding-bottom:6px}
.fo-auth{border:1px solid var(--pr-line);border-radius:6px;padding:12px}.fo-auth header{display:flex;gap:10px;align-items:center}.fo-auth header i{font-style:normal;width:44px;height:44px;border-radius:50%;background:#e5e7eb;display:grid;place-items:center;font-weight:800}
.fo-auth .st{display:grid;grid-template-columns:repeat(3,1fr);text-align:center;font-size:13px;margin:12px 0;border:1px solid var(--pr-line);border-radius:4px;padding:6px}
.fo-auth ul{list-style:none;margin:6px 0 0;padding:0;max-height:150px;overflow:auto;font-size:13.5px}.fo-auth li{margin-bottom:8px}.fo-auth li a{color:#1f2937;text-decoration:none}
.fo-unans{list-style:none;padding:0;margin:0}.fo-unans li{border-bottom:1px solid var(--pr-line);padding:12px 0;display:flex;justify-content:space-between;gap:10px}.fo-unans a{color:#111827;font-weight:600;text-decoration:none}
</style>@endpush
<div class="pr" style="background:#fff;padding-bottom:90px"><div class="fo-wrap">
    <div class="fo-crumb"><a href="{{ route('forum.index') }}">Forum</a> / {{ $cat[0] }}</div>
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    <section class="fo-hero">
        <h1>{{ $cat[0] }}</h1>
        <form method="get" class="fo-search" role="search"><input type="search" name="q" value="{{ $q }}" placeholder="Search question" aria-label="Search question"><button aria-label="Search">🔍</button></form>
        <p style="margin:14px 0 10px">{{ $cat[1] }} —</p>
        @auth<button type="button" class="fo-green" data-ask>Ask your question</button>@else<a class="fo-green" href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">Sign in to ask</a>@endauth
    </section>

    <h2 class="fo-h2">{{ $q !== '' ? 'Answered questions matching “' . $q . '”' : 'Recently answered questions' }}</h2>
    @forelse($answered as $item)@include('forum._card', ['q' => $item])@empty<p class="pr-mute" style="text-align:center">No answered questions here yet.</p>@endforelse
    {{ $answered->links() }}

    @if($authors->isNotEmpty())
        <section class="fo-authors"><h2>Top authors of the month</h2><div class="fo-arow">
            @foreach($authors as $a)
                <div><div class="fo-auth">
                    <header><i>{{ mb_strtoupper(mb_substr($a->user->name, 0, 1)) }}</i><div><b>{{ \App\Support\ForumName::short($a->user->name) }}</b><div class="pr-mute" style="font-size:13px">{{ $a->answers }} total {{ $a->answers === 1 ? 'answer' : 'answers' }}</div></div></header>
                    <div class="st"><span>🏅<br>{{ $a->best }}</span><span>👍<br>{{ $a->upvotes }}</span><a href="{{ route('forum.author', $a->user) }}">👤<br>profile</a></div>
                    <b style="font-size:14px">Recently answered</b>
                    <ul>@foreach($a->questions as $aq)<li><a href="{{ $aq->url() }}">{{ \Illuminate\Support\Str::limit($aq->title, 70) }}</a></li>@endforeach</ul>
                </div></div>
            @endforeach
        </div></section>
    @endif

    @if($unanswered->isNotEmpty())
        <h2 class="fo-h2">Most recent unanswered questions</h2>
        <ul class="fo-unans">@foreach($unanswered as $u)<li><a href="{{ $u->url() }}">{{ $u->title }}</a><a class="fo-more" style="align-self:center" href="{{ $u->url() }}#answer">Answer</a></li>@endforeach</ul>
    @endif
</div></div>
@endsection
