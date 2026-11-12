<section class="cp-sec cp-band" style="background:#f5f3ff">
    @include('properties.city._head', ['title' => $options['title']])
    <div class="cp-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr))">
        @foreach($items as $s)<a class="cp-tile" href="{{ $s['url'] }}" style="display:flex;gap:12px;align-items:flex-start"><span class="ic">{{ $s['icon'] }}</span><span style="display:grid;gap:3px"><b>{{ $s['title'] }}</b><small>{{ $s['hint'] }}</small></span></a>@endforeach
    </div>
</section>
