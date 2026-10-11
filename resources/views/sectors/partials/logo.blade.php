{{-- Company logo with coloured initials underneath; a logo that fails to load is removed and the initials show. --}}
@php
    $initials = collect(preg_split('/\s+/', trim(preg_replace('/\(.*?\)/', '', $name))))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $hue = crc32($name) % 360;
@endphp
<span class="sl-logo {{ $size ?? '' }}" style="--hue:{{ $hue }}" title="{{ $name }}"><b>{{ $initials ?: '•' }}</b>@if($logo)<img src="{{ $logo }}" alt="" loading="lazy" onerror="this.remove()">@endif</span>
