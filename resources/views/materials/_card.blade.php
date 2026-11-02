@php
    $store = config('materials.store');
    $freeAbove = collect($store['delivery']['slabs'])->first(fn ($s) => $s[1] == 0)[0] ?? null;
    $bestTier = collect($m->bulk_tiers)->sortBy('price')->first();
    $variants = collect($m->variants ?? []);
    $defaultVariant = max(0, (int) $variants->search(fn ($v) => $v['price'] == $m->price && $v['available']));
    $price = $m->packPrice();
    $mrp = $m->mrp && $m->mrp > $m->price ? $m->packPrice(['price' => $m->mrp]) : null;
    $rating = isset($m->reviews_avg_rating) && $m->reviews_count ? round((float) $m->reviews_avg_rating, 1) : null;
@endphp
<article class="mt-p">
    <a class="mt-img" href="{{ route('materials.show', $m) }}" tabindex="-1" aria-hidden="true">
        @if($m->discountPercent())<span class="mt-badge">{{ $m->discountPercent() }}% OFF</span>@endif
        @unless($m->canBuy())<span class="mt-badge out">Out of stock</span>@endunless
        @if($m->image_url)<img src="{{ $m->image_url }}" alt="" loading="lazy">@else<span class="mt-noimg">🧱</span>@endif
    </a>
    <div class="mt-feats">
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>{{ $store['delivery']['express_label'] }}</span>
        @if($store['cod']['enabled'])<span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M9 8h6M9 11h6M13 11c0 2.5-2 3.5-4 3.5l4 3"/></svg>Pay on Delivery</span>@endif
        @if($rating)<span style="color:#b45309">★ {{ $rating }} <span class="mt-muted">({{ $m->reviews_count }})</span></span>@endif
    </div>
    @if($freeAbove)<div class="mt-ribbon"><b><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg>Free Delivery</b>on orders above ₹{{ number_format($freeAbove) }}</div>@endif
    <h3><a href="{{ route('materials.show', $m) }}">{{ Str::limit($m->title, 80) }}</a></h3>
    <div class="mt-price">@if($mrp)<s>₹{{ number_format($mrp) }}</s>@endif<b>₹{{ number_format($price) }}</b>@if($m->unitPrice())<span class="mt-muted">₹{{ number_format($m->unitPrice(), 1) }}/{{ $m->unit }}</span>@endif</div>
    <div class="mt-strip">
        @if($bestTier)<strong>{{ round(($m->price - $bestTier['price']) / $m->price * 100) }}%</strong><span><b>Bulk price</b><br>{{ $bestTier['qty'] }}+ at ₹{{ number_format($m->packPrice(['price' => $bestTier['price']])) }} each</span>
        @else<strong>{{ $store['offer']['percent'] }}%</strong><span><b>Discount</b><br>on orders above ₹{{ number_format($store['offer']['min_order'] / 1000) }}K</span>@endif
    </div>
    @if($bestTier)<a class="mt-unlock" href="{{ route('materials.show', $m) }}">Unlock bulk prices of ₹{{ number_format($m->packPrice(['price' => $bestTier['price']])) }}</a>@endif
    @if(!$m->canBuy())
        <button class="mt-add" disabled style="margin-top:12px">Out of stock</button>
    @elseif($variants->count() > 1)
        <details class="mt-opts">
            <summary class="mt-add">{{ $variants->count() }} Options <span aria-hidden="true">▾</span></summary>
            <div class="mt-opts-list">
                @foreach($variants as $i => $v)
                    <form method="post" action="{{ route('materials.list.add') }}">@csrf
                        <input type="hidden" name="items[0][handle]" value="{{ $m->handle }}"><input type="hidden" name="items[0][qty]" value="1"><input type="hidden" name="items[0][variant]" value="{{ $i }}">
                        <span>{{ $v['title'] }}<br><b>₹{{ number_format($m->packPrice($v)) }}</b>@if(!empty($v['mrp']) && $v['mrp'] > $v['price']) <s class="mt-muted">₹{{ number_format($m->packPrice(['price' => $v['mrp']])) }}</s>@endif</span>
                        <button class="mt-btn sm green" @disabled(!$v['available'])>{{ $v['available'] ? 'Add' : 'Sold out' }}</button>
                    </form>
                @endforeach
            </div>
        </details>
    @else
        <form method="post" action="{{ route('materials.list.add') }}">@csrf
            <input type="hidden" name="items[0][handle]" value="{{ $m->handle }}"><input type="hidden" name="items[0][qty]" value="1">
            <input type="hidden" name="items[0][variant]" value="{{ $defaultVariant }}">
            <button class="mt-add">Add</button>
        </form>
    @endif
</article>
