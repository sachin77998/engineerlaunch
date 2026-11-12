<a class="cp-type" href="{{ $t['url'] }}" style="background:{{ $tint }}">
    <span class="tx"><b>{{ $t['label'] }}</b><small>{{ number_format($t['count']) }} {{ $t['count'] === 1 ? 'property' : 'properties' }}</small></span>
    <span class="ph" @if($t['photo']) style="background-image:url('{{ $t['photo'] }}')" @endif>@unless($t['photo'])🏘️@endunless</span>
</a>
