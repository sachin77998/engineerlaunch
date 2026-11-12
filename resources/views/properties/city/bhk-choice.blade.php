<section class="cp-sec cp-band">
    @include('properties.city._head', ['title' => '🏠 ' . $options['title'], 'sub' => 'Homes for sale in ' . $context->place()])
    <div class="cp-grid">@foreach($tiles as $t)<a class="cp-tile" href="{{ $t['url'] }}"><span class="ic">🛏️</span><b>{{ $t['label'] }}</b><small>{{ number_format($t['count']) }} {{ $t['count'] === 1 ? 'property' : 'properties' }}</small></a>@endforeach</div>
</section>
