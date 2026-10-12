{{-- One sub-category (e.g. Gears & Transmissions): opens its companies. --}}
@include('sectors.partials.card-styles')
<a class="cat-card sub" href="{{ route('sectors.subcategory', [$sectorSlug, $child->slug]) }}" style="--accent:{{ $accent ?? '#0b2545' }}">
    <div class="cat-visual" @if($child->image) style="background-image:linear-gradient(180deg,rgba(11,37,69,.05),rgba(11,37,69,.75)),url('{{ $child->image }}')" @endif>
        <span class="cat-icon">{{ $icon ?? '🏭' }}</span>
        <span class="cat-count">{{ number_format($child->companies_count) }} companies</span>
    </div>
    <div class="cat-body">
        <h2>{{ $child->name }}</h2>
        <p class="cat-meta">{{ number_format($child->openings_count ?? 0) }} open jobs</p>
        @if($child->preview_companies)
            <ul class="cat-companies">@foreach($child->preview_companies as $company)<li>@include('sectors.partials.logo', ['name' => $company['name'], 'logo' => $company['logo'], 'size' => 'sm']){{ $company['name'] }}</li>@endforeach</ul>
        @endif
    </div>
</a>
