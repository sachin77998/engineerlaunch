<a class="cp-card" href="{{ route('news.show', $a->slug) }}">
    <div class="img" @if($a->image_url) style="background-image:url('{{ $a->image_url }}')" @endif>@unless($a->image_url)📰@endunless</div>
    <div class="t"><b style="font-size:15px;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $a->title }}</b><small>{{ $a->published_at?->format('M d, Y') }}</small></div>
</a>
