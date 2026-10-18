<section class="ci-card ci-locked" id="{{ $id ?? 'premium' }}">
    <div>
        <span class="ci-lock-badge">Premium</span>
        <h2>{{ $title ?? 'Financials, ratios and risk analysis' }}</h2>
        <p class="ci-muted">{{ $text ?? 'Revenue, EBITDA, net profit, net worth, debt and assets for every filing year, 15+ ratios, the financial health score, shareholding, charges and peer comparison are part of the premium plan.' }}</p>
        <div class="ci-lock-preview" aria-hidden="true">
            @foreach($labels ?? ['Revenue', 'EBITDA', 'Net profit', 'Net worth', 'Total debt', 'ROCE'] as $label)<div><span>{{ $label }}</span><b>{{ $unit ?? '₹ ••• Cr' }}</b></div>@endforeach
        </div>
        @guest
            <a class="ci-btn gold" href="{{ route('login') }}">Sign in to continue</a>
        @else
            <a class="ci-btn gold" href="{{ route('contact') }}?subject=Premium%20plan">Get premium</a>
        @endguest
    </div>
</section>
