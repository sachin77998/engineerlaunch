{{-- One question card: Q / top answer excerpt / author and date. Expects $q (with topAnswer, user). --}}
@php
    $a = $q->topAnswer;
    $who = $a?->user ?? $q->user;
@endphp
<article class="fo-q">
    <span class="lbl">Q.</span>
    <h3><a href="{{ $q->url() }}">{{ $q->title }}</a></h3>
    <div class="meta">{{ \App\Support\ForumName::short($who?->name) }}<br>{{ ($a?->created_at ?? $q->created_at)->format('F j, Y') }}</div>
    @if($a)
        <span class="lbl">Ans.</span>
        <p>{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $a->body), 220) }}</p>
        <a class="fo-more" href="{{ $q->url() }}">Read more</a>
    @else
        <span></span><p class="pr-mute">No answers yet · {{ $q->categoryLabel() }}</p><a class="fo-more" href="{{ $q->url() }}#answer">Answer</a>
    @endif
</article>
