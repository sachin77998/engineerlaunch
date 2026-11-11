{{-- "Our services" directory shown at the foot of the property, loan, legal, rent and interiors pages. Only lists services that exist on this site. --}}
@php
    $legalCity = \Illuminate\Support\Str::slug(session('legal_city', config('legal.default_city')));
    $groups = [
        'Buy' => [
            ['Home loan', route('home-loan.index')], ['Apply home loan', route('home-loan.index') . '#apply'], ['EMI calculator', route('home-loan.index') . '#calculators'],
            ['Home loan eligibility check', route('home-loan.index') . '#calculators'], ['Compare home loan rates', route('home-loan.index') . '#banks'],
            ['Property legal services', route('legal.index', $legalCity)], ['Sale agreement', route('legal.show', [$legalCity, 'agreement-to-sell'])],
            ['Legal title check', route('legal.show', [$legalCity, 'legal-title-opinion'])], ['New builder projects', route('properties.search', ['buy', 'possession' => ['new_launch']])],
            ['Interiors', route('interiors.index')], ['Interior cost estimator', route('interiors.estimator')],
        ],
        'Rent' => [
            ['Rental agreement', route('rent-agreement.index')], ['Pay rent by credit card', route('pay-rent.index')], ['Tenant verification', route('legal.show', [$legalCity, 'tenant-verification'])],
            ['Homes for rent', route('properties.search', 'rent')], ['PG & flatmates', route('properties.search', ['rent', 'kind' => 'pg'])], ['Commercial for rent', route('properties.search', 'commercial-rent')],
            ['Post free property ad', route('properties.post')], ['Refer and earn', route('referrals.index')], ['Home services', route('home-services.index')], ['Packers and movers', route('home-services.index')], ['Locality reviews', route('properties.index')], ['Property forum', route('forum.index')],
        ],
    ];
@endphp
@push('styles')<style>
.ps-dir{background:#f3f4f6;padding:34px 0 40px;margin-top:40px}.ps-dir h2{font-size:22px;margin:0 0 16px}
.ps-dir .cols{display:grid;grid-template-columns:1fr 1fr;gap:28px}.ps-dir h3{font-size:15px;font-weight:500;color:#4b5563;margin:0 0 10px}
.ps-dir .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.ps-dir .grid a{background:#e5e7eb;border-radius:4px;padding:13px 10px;text-align:center;color:#374151;text-decoration:none;font-size:14.5px}.ps-dir .grid a:hover{background:#dbeafe;color:#1e3a8a}
@media(max-width:760px){.ps-dir .cols{grid-template-columns:1fr}}
</style>@endpush
<section class="ps-dir" aria-label="Our services"><div class="pr-wrap" style="width:min(1100px,calc(100% - 32px));margin:0 auto">
    <h2>{{ config('properties.brand') }} services</h2>
    <div class="cols">@foreach($groups as $title => $links)<div><h3>{{ $title }}</h3><div class="grid">@foreach($links as [$label, $url])<a href="{{ $url }}">{{ $label }}</a>@endforeach</div></div>@endforeach</div>
</div></section>
