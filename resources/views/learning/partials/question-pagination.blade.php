@if($questions->hasPages())
<nav class="question-pagination" aria-label="Question pages">
    @if($questions->onFirstPage())<span class="disabled">← Previous</span>@else<a href="{{$questions->previousPageUrl()}}" rel="prev">← Previous</a>@endif
    @foreach(range(1,$questions->lastPage()) as $page)
        @if($page === $questions->currentPage())<span class="active" aria-current="page">{{$page}}</span>@else<a href="{{$questions->url($page)}}">{{$page}}</a>@endif
    @endforeach
    @if($questions->hasMorePages())<a href="{{$questions->nextPageUrl()}}" rel="next">Next →</a>@else<span class="disabled">Next →</span>@endif
</nav>
<p class="page-summary">Showing questions {{$questions->firstItem()}}–{{$questions->lastItem()}} of {{$questions->total()}}</p>
@endif
