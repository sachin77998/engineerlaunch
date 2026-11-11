{{-- FAQ and "Services we offer" link directory. Expects $city (nullable). The current city is listed open; major cities follow collapsed. --}}
@php
    $hs = config('home_services');
    $legalOk = \App\Models\Legal\LegalService::where('is_active', true)->pluck('slug')->flip();
    $url = function ($entry, $cityName) use ($legalOk) {
        $slug = \Illuminate\Support\Str::slug($cityName);
        [$label, $kind, $target] = $entry;
        return match ($kind) {
            'category' => route('home-services.category', array_filter([$slug, $target, 'add' => isset($entry[3]) ? \App\Models\HomeService\HomeService::idFor($entry[3]) : null])),
            'legal' => isset($legalOk[$target]) ? route('legal.show', [$slug, $target]) : null,
            default => in_array($target, ['rent-agreement.index', 'legal.index'], true) ? route($target, $slug) : route($target),
        };
    };
    $cities = collect($city ? [$city] : [])->merge($hs['directory_cities'])->unique()->values();
@endphp
@push('styles')<style>
.hs-faq{max-width:1250px;margin:0 auto;padding:30px 16px}.hs-faq h2{text-align:center;color:#1e1b4b;font-size:30px;margin:0 0 16px}
.hs-faq details{border-bottom:1px solid var(--pr-line);padding:16px 14px}.hs-faq summary{cursor:pointer;display:flex;justify-content:space-between;list-style:none;font-size:15px}.hs-faq summary::after{content:"+";font-size:24px;line-height:1}.hs-faq details[open] summary::after{content:"−"}
.hs-faq p{color:#4b5563;line-height:1.6;margin:10px 0 0}
.hs-dir{background:#f3f4f6;padding:30px 0 36px}.hs-dir h2{font-size:22px;margin:0 0 14px}.hs-dir h3,.hs-dir summary{font-size:16px;font-weight:500;color:#374151;margin:18px 0 10px;cursor:pointer}
.hs-dir .links{display:flex;flex-wrap:wrap;gap:10px}.hs-dir .links a{background:#e5e7eb;color:#374151;text-decoration:none;padding:6px 8px;font-size:14px}.hs-dir .links a:hover{background:#dbeafe;color:#1e3a8a}
</style>@endpush
<section class="hs-faq">
    <h2>Frequently asked questions</h2>
    @foreach($hs['faq'] as [$q, $a])<details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>@endforeach
</section>
<section class="hs-dir" aria-label="Services we offer"><div class="pr-wrap">
    <h2>Services we offer</h2>
    @foreach($cities as $cityName)
        @php($links = collect($hs['directory'])->map(fn ($e) => [$e[0] . ' in ' . $cityName, $url($e, $cityName)])->filter(fn ($l) => $l[1]))
        @if($loop->first && $city)
            <h3>In {{ $cityName }}</h3><div class="links">@foreach($links as [$label, $href])<a href="{{ $href }}">{{ $label }}</a>@endforeach</div>
        @else
            <details><summary>In {{ $cityName }}</summary><div class="links">@foreach($links as [$label, $href])<a href="{{ $href }}">{{ $label }}</a>@endforeach</div></details>
        @endif
    @endforeach
</div></section>
