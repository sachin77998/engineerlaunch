<a class="cp-tile" href="{{ $l['url'] }}">
    <b>{{ $l['name'] }}</b>
    <small>{{ number_format($l['listings']) }} live {{ $l['listings'] === 1 ? 'listing' : 'listings' }}</small>
    @if($l['psf'])<span>₹{{ \App\Services\Property\PropertyFormat::indian($l['psf']) }}/sq ft @if($l['change'] !== null)<span style="color:{{ $l['change'] >= 0 ? '#047857' : '#b91c1c' }};font-weight:700">{{ $l['change'] >= 0 ? '▲' : '▼' }} {{ abs($l['change']) }}%</span>@endif</span>@endif
    <span>@if($l['rating'])★ {{ $l['rating'] }}/5 · {{ $l['reviews'] }} {{ $l['reviews'] === 1 ? 'review' : 'reviews' }}@else<span class="pr-mute">No reviews yet — rate it</span>@endif</span>
</a>
