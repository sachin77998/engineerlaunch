@if ($paginator->hasPages())
@php($anchor = $anchor ?? '')
<nav class="pg" role="navigation" aria-label="Pages">
    <span class="pg-info">{{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }} of {{ number_format($paginator->total()) }}</span>
    <div class="pg-links">
        @if ($paginator->onFirstPage())<span class="pg-btn off" aria-disabled="true">‹ Prev</span>
        @else<a class="pg-btn" href="{{ $paginator->previousPageUrl() }}{{ $anchor }}" rel="prev">‹ Prev</a>@endif
        @foreach ($elements as $element)
            @if (is_string($element))<span class="pg-gap">…</span>@endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())<span class="pg-btn on" aria-current="page">{{ number_format($page) }}</span>
                    @else<a class="pg-btn" href="{{ $url }}{{ $anchor }}">{{ number_format($page) }}</a>@endif
                @endforeach
            @endif
        @endforeach
        @if ($paginator->hasMorePages())<a class="pg-btn" href="{{ $paginator->nextPageUrl() }}{{ $anchor }}" rel="next">Next ›</a>
        @else<span class="pg-btn off" aria-disabled="true">Next ›</span>@endif
    </div>
</nav>
@once
<style>
.pg{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:18px 0 6px}
.pg-info{color:#5b6b80;font-size:14px}
.pg-links{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.pg-btn{min-width:38px;height:38px;padding:0 12px;display:inline-grid;place-items:center;border:1px solid #d6e1ee;border-radius:9px;background:#fff;color:#1d3557;font-weight:600;font-size:14px;text-decoration:none}
a.pg-btn:hover{border-color:#1d4ed8;color:#1d4ed8}
.pg-btn.on{background:#1d4ed8;border-color:#1d4ed8;color:#fff}
.pg-btn.off{color:#a3b1c2;background:#f4f7fb}
.pg-gap{padding:0 4px;color:#8a99ab}
</style>
@endonce
@endif
