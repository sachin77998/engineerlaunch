@extends('layouts.site')
@section('title', 'Forum moderation')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    <h1 style="margin:22px 0 6px">Forum moderation</h1>
    <p class="pr-mute" style="margin:0 0 14px">{{ number_format($stats['questions']) }} questions · {{ number_format($stats['answers']) }} answers · {{ number_format($stats['unanswered']) }} unanswered. Items reported by {{ config('forum.auto_hide_reports') }}+ people are hidden automatically until you review them.</p>
    <h2 style="font-size:18px">Open reports ({{ $items->count() }})</h2>
    @forelse($items as $i)
        @php($q = $i->type === 'question' ? $i->target : $i->target->question)
        <div class="pr-card-box" style="margin-bottom:10px;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px">
            <div>
                <span class="pr-tag">{{ $i->type }}</span> @if($i->target->is_hidden)<span class="pr-tag amber">hidden</span>@endif
                <b><a href="{{ $q->url() }}{{ $i->type === 'answer' ? '#a' . $i->target->id : '' }}" target="_blank">{{ $q->title }}</a></b>
                @if($i->type === 'answer')<p style="margin:6px 0;white-space:pre-line">{{ \Illuminate\Support\Str::limit($i->target->body, 400) }}</p>@elseif($i->target->body)<p style="margin:6px 0">{{ \Illuminate\Support\Str::limit($i->target->body, 300) }}</p>@endif
                <div class="pr-mute" style="font-size:13px">@foreach($i->reports as $r){{ config('forum.report_reasons')[$r->reason] ?? $r->reason }} ({{ $r->user?->name }})@if(!$loop->last), @endif @endforeach</div>
            </div>
            <form method="post" action="{{ route('admin.forum.moderate', [$i->type, $i->target->id]) }}" style="display:flex;gap:6px;align-items:start">@csrf
                <button class="pr-btn danger sm" name="action" value="hide">Hide</button><button class="pr-btn sm" name="action" value="dismiss">Keep</button></form>
        </div>
    @empty<div class="pr-card-box">No open reports.</div>@endforelse

    @if($hidden->isNotEmpty() || $hiddenAnswers->isNotEmpty())
        <h2 style="font-size:18px;margin-top:24px">Hidden</h2>
        @foreach($hidden as $h)<form method="post" action="{{ route('admin.forum.moderate', ['question', $h->id]) }}" class="pr-card-box" style="margin-bottom:6px;display:flex;justify-content:space-between;gap:10px">@csrf<span><span class="pr-tag">question</span> {{ $h->title }}</span><button class="pr-btn sm" name="action" value="show">Show again</button></form>@endforeach
        @foreach($hiddenAnswers as $h)<form method="post" action="{{ route('admin.forum.moderate', ['answer', $h->id]) }}" class="pr-card-box" style="margin-bottom:6px;display:flex;justify-content:space-between;gap:10px">@csrf<span><span class="pr-tag">answer</span> {{ \Illuminate\Support\Str::limit($h->body, 120) }} <span class="pr-mute">on “{{ $h->question?->title }}”</span></span><button class="pr-btn sm" name="action" value="show">Show again</button></form>@endforeach
    @endif
</div></div>
@endsection
