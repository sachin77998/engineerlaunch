@extends('layouts.site')
@section('title', \Illuminate\Support\Str::limit($item->title, 70) . ' — ' . $cat[0] . ' forum')
@section('content')
@include('forum._head', ['category' => $item->category, 'cta' => $cat[2]])
@php($asker = auth()->id() === $item->user_id)
@push('styles')<style>
.fo-ques{background:#f9fafb;border-radius:8px;box-shadow:0 2px 8px rgba(17,24,39,.08);padding:22px}.fo-ques h1{font-size:24px;margin:0 0 8px;line-height:1.35}
.fo-ans{border-bottom:1px solid var(--pr-line);padding:18px 0}.fo-ans.best{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:18px}
.fo-ans .body{white-space:pre-line;line-height:1.65;color:#1f2937}.fo-ans footer{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:10px;font-size:13.5px;color:#6b7280}
.fo-ans footer button{border:1px solid var(--pr-line);background:#fff;border-radius:999px;padding:4px 12px;cursor:pointer;font-size:13px}.fo-ans footer button.on{background:#0f766e;border-color:#0f766e;color:#fff}
.fo-rep{position:relative}.fo-rep summary{list-style:none;cursor:pointer;color:#9ca3af}.fo-rep form{position:absolute;right:0;z-index:5;background:#fff;border:1px solid var(--pr-line);border-radius:8px;padding:10px;display:grid;gap:6px;width:220px}
</style>@endpush
<div class="pr" style="background:#fff;padding-bottom:90px"><div class="fo-wrap">
    <div class="fo-crumb"><a href="{{ route('forum.index') }}">Forum</a> / <a href="{{ route('forum.category', $item->category) }}">{{ $cat[0] }}</a></div>
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if($errors->has('body') || $errors->has('vote') || $errors->has('reason'))<div class="pr-flash err">{{ $errors->first('body') ?: ($errors->first('vote') ?: $errors->first('reason')) }}</div>@endif
    @if($item->is_hidden)<div class="pr-flash warn">Hidden from the public (moderation).</div>@endif
    <article class="fo-ques">
        <h1>Q. {{ $item->title }}</h1>
        <div class="pr-mute" style="font-size:13px">Asked by {{ \App\Support\ForumName::short($item->user?->name) }} · {{ $item->created_at->format('F j, Y') }}@if($item->city) · {{ $item->city }}@endif · {{ number_format($item->views) }} views</div>
        @if($item->body)<p style="white-space:pre-line;line-height:1.6;margin:12px 0 0">{{ $item->body }}</p>@endif
        @auth<details class="fo-rep" style="text-align:right;margin-top:6px"><summary>Report</summary>
            <form method="post" action="{{ route('forum.report', ['question', $item->id]) }}">@csrf<select name="reason" required>@foreach(config('forum.report_reasons') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select><button class="pr-btn sm">Report question</button></form></details>@endauth
    </article>

    <h2 id="answers" style="margin:26px 0 6px">{{ $answers->count() }} {{ $answers->count() === 1 ? 'answer' : 'answers' }}</h2>
    @foreach($answers as $a)
        <div class="fo-ans {{ $a->is_best ? 'best' : '' }}" id="a{{ $a->id }}">
            @if($a->is_best)<div style="color:#047857;font-weight:800;margin-bottom:6px">✓ Best answer</div>@endif
            @if($a->is_hidden)<div class="pr-tag">hidden</div>@endif
            <div class="body">{{ $a->body }}</div>
            <footer>
                <a href="{{ route('forum.author', $a->user_id) }}" style="color:#374151;font-weight:600">{{ \App\Support\ForumName::short($a->user?->name) }}</a> · {{ $a->created_at->format('F j, Y') }}
                @auth
                    @if(auth()->id() !== $a->user_id)<form method="post" action="{{ route('forum.vote', $a) }}">@csrf<button class="{{ in_array($a->id, $voted, true) ? 'on' : '' }}">👍 Helpful · {{ $a->upvotes }}</button></form>@else<span>👍 {{ $a->upvotes }}</span>@endif
                    @if($asker)<form method="post" action="{{ route('forum.best', $a) }}">@csrf<button>{{ $a->is_best ? 'Unmark best' : 'Mark as best' }}</button></form>@endif
                    @if(auth()->id() === $a->user_id)<form method="post" action="{{ route('forum.answer.destroy', $a) }}" onsubmit="return confirm('Delete your answer?')">@csrf<button>Delete</button></form>
                    @else<details class="fo-rep"><summary>Report</summary><form method="post" action="{{ route('forum.report', ['answer', $a->id]) }}">@csrf<select name="reason" required>@foreach(config('forum.report_reasons') as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select><button class="pr-btn sm">Report answer</button></form></details>@endif
                @else<span>👍 {{ $a->upvotes }}</span>@endauth
            </footer>
        </div>
    @endforeach

    <section id="answer" style="margin-top:22px">
        @auth
            <form method="post" action="{{ route('forum.answer', $item) }}" style="display:grid;gap:10px">@csrf
                <label class="f" for="fo-body">Your answer</label>
                <textarea id="fo-body" name="body" rows="5" required minlength="{{ config('forum.answer_min') }}" maxlength="{{ config('forum.answer_max') }}" placeholder="Share what you know. Please don't post phone numbers or addresses.">{{ old('body') }}</textarea>
                <button class="fo-green" style="justify-self:start">Post answer</button>
            </form>
        @else<a class="fo-green" href="{{ route('login', ['redirect' => request()->getRequestUri() . '#answer']) }}">Sign in to answer</a>@endauth
    </section>

    @if($related->isNotEmpty())
        <h2 style="margin:30px 0 10px;font-size:20px">Related questions</h2>
        <ul style="padding-left:18px;line-height:1.9">@foreach($related as $r)<li><a href="{{ $r->url() }}">{{ $r->title }}</a> <span class="pr-mute">· {{ $r->answers_count }} {{ $r->answers_count === 1 ? 'answer' : 'answers' }}</span></li>@endforeach</ul>
    @endif
</div></div>
@endsection
