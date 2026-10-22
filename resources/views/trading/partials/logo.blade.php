@php
    // Real logo when one is stored (fund house by name, stock by its website domain); otherwise the initials badge.
    $logoSrc = $src ?? \App\Services\Branding\Logos::url($domain ?? (!empty($amc) ? \App\Services\Branding\Logos::amcDomain($name) : null));
@endphp
@if($logoSrc)
<span class="dt-logo has-img {{ $size ?? '' }}" aria-hidden="true"><img src="{{ $logoSrc }}" alt="" loading="lazy" onerror="this.parentNode.classList.remove('has-img');this.parentNode.style.background='{{ $color ?? \App\Services\Trading\FundCatalogue::color($name) }}';this.parentNode.textContent='{{ \App\Services\Trading\FundCatalogue::initials($name) }}'"></span>
@else
<span class="dt-logo {{ $size ?? '' }}" style="background:{{ $color ?? \App\Services\Trading\FundCatalogue::color($name) }}" aria-hidden="true">{{ \App\Services\Trading\FundCatalogue::initials($name) }}</span>
@endif
