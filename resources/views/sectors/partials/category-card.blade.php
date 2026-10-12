{{-- One category (e.g. Automobile & Ancillaries): opens its sub-categories. --}}
@include('sectors.partials.card-styles')
<a class="cat-card" href="{{ route('sectors.group', $card->slug) }}" style="--accent:{{ $card->color }}">
    <div class="cat-visual" @if($card->image) style="background-image:linear-gradient(180deg,rgba(11,37,69,.05),rgba(11,37,69,.75)),url('{{ $card->image }}')" @endif>
        <span class="cat-icon">{{ $card->icon ?: ($card->symbol ?: mb_substr($card->name, 0, 2)) }}</span>
        <span class="cat-count">{{ number_format($card->companies_count) }} companies</span>
    </div>
    <div class="cat-body">
        <h2>{{ $card->name }}</h2>
        <p class="cat-meta">{{ $card->subcategories->count() }} categories · {{ number_format($card->openings_count) }} open jobs</p>
        <div class="cat-chips">@foreach($card->subcategories->take(5) as $child)<span>{{ $child->name }}</span>@endforeach @if($card->subcategories->count() > 5)<span>+{{ $card->subcategories->count() - 5 }} more</span>@endif</div>
        @if($card->logos)<div class="cat-logos">@foreach($card->logos as $company)@include('sectors.partials.logo', ['name' => $company['name'], 'logo' => $company['logo'], 'size' => 'sm'])@endforeach</div>@endif
    </div>
</a>
