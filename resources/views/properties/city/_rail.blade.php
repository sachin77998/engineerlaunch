{{-- Horizontal scroller with arrows. Expects $slot (rendered HTML). --}}
<div class="cp-rail-wrap" data-rail><button type="button" class="cp-arrow l" data-dir="-1" aria-label="Scroll left">‹</button><div class="cp-rail">{!! $slot !!}</div><button type="button" class="cp-arrow r" data-dir="1" aria-label="Scroll right">›</button></div>
