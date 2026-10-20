@php
    $nse = \Illuminate\Support\Facades\DB::table('listed_companies')->where('company_id', $company->id)->first(['symbol', 'last_price']);
    $founded = $detail?->incorporation_date?->format('Y') ?? ($register?->registered_on ? substr($register->registered_on, 0, 4) : null) ?? $listing?->ipo_year;
    // Latest annual figures: Indian filings (₹) or SEC results (US$ / €).
    $usRow = $listing?->financials->sortBy('fiscal_year')->last();
    $cur = $usRow?->currency ?? 'USD';
    $usMoney = fn ($v) => \App\Services\Insights\UsMarketData::money($v === null ? null : (float) $v, $cur);
    $prevRatio = $ratios->count() > 1 ? $ratios->slice(-2, 1)->first() : null;
    $usPrev = $listing?->financials->sortBy('fiscal_year')->slice(-2, 1)->first();
    $growth = fn ($now, $before) => $now !== null && $before ? (($now - $before) / abs($before) * 100) : null;
    $trend = fn ($g) => $g === null ? '' : '<small class="' . ($g >= 0 ? 'up' : 'down') . '">' . ($g >= 0 ? '▲ ' : '▼ ') . number_format(abs($g), 1) . '% YoY</small>';
    $current = $company->officers->filter(fn ($o) => !$o->ceased_on || $o->ceased_on->isFuture());
    $openCharges = $company->charges->filter(fn ($c) => !$c->satisfied_on && !preg_match('/satisf|closed/i', (string) $c->status));
    $metrics = [];
    if ($latest) {
        $metrics[] = ['Revenue', $money($latest['revenue']), $trend($growth($latest['revenue'], $prevRatio['revenue'] ?? null)), 'FY ' . $latest['year']];
        $metrics[] = ['EBITDA', $money($latest['ebitda']), $trend($growth($latest['ebitda'], $prevRatio['ebitda'] ?? null)), null];
        $metrics[] = ['Net profit', $money($latest['pat']), $trend($growth($latest['pat'], $prevRatio['pat'] ?? null)), null];
        $metrics[] = ['Net worth', $money($latest['net_worth']), $trend($growth($latest['net_worth'], $prevRatio['net_worth'] ?? null)), null];
    } elseif ($usRow) {
        $metrics[] = ['Revenue', $usMoney($usRow->revenue), $trend($growth($usRow->revenue, $usPrev?->revenue)), 'FY ' . $usRow->fiscal_year];
        $metrics[] = ['Operating income', $usMoney($usRow->operating_income), $trend($growth($usRow->operating_income, $usPrev?->operating_income)), null];
        $metrics[] = ['Net profit', $usMoney($usRow->net_income), $trend($growth($usRow->net_income, $usPrev?->net_income)), null];
        $metrics[] = ["Shareholders' equity", $usMoney($usRow->equity), '', null];
    }
    if ($listing?->market_cap) $metrics[] = ['Market cap', \App\Services\Insights\UsMarketData::money($listing->market_cap), '', $listing->ticker . ' · $' . number_format($listing->last_price, 2)];
    if ($detail?->paid_up_capital || $register?->paid_up_capital) $metrics[] = ['Paid-up capital', $money($detail?->paid_up_capital ?? $register?->paid_up_capital), '', null];
    if ($detail?->employees) $metrics[] = ['Employees', number_format($detail->employees), '', null];
    if ($company->officers->isNotEmpty()) $metrics[] = ['Directors', $current->count(), '', $company->officers->count() - $current->count() . ' past'];
    if ($company->charges->isNotEmpty()) $metrics[] = ['Borrowings (charges)', $company->charges->count(), '', $openCharges->count() . ' active · ' . $money($openCharges->sum('amount'))];
    if ($competitors->isNotEmpty()) $metrics[] = ['Competitors', $competitors->count(), '', 'identified'];
    $activeJobs = rescue(fn () => $company->activeJobs()->count(), 0, false);
    if ($activeJobs) $metrics[] = ['Open jobs', number_format($activeJobs), '', null];
    $keyPeople = $current->sortBy(fn ($o) => preg_match('/managing|chairman|ceo|whole-time/i', (string) $o->designation) ? 0 : 1)->take(3);
    $sectorTags = collect($areas)->flatMap(fn ($a) => [$a[0], $a[1]])->unique()->values();
    $phone = $company->phone_number ? trim(($company->phone_country_code ? '+' . ltrim($company->phone_country_code, '+') . ' ' : '') . $company->phone_number) : null;
@endphp

@if($metrics)
<section class="ci-card" id="key-metrics" data-nav="Key Metrics">
    <h2>Key Metrics of {{ $company->name }}</h2>
    <div class="pf-metrics">
        @foreach($metrics as [$label, $value, $change, $note])
            <div><span>{{ $label }}</span><b>{{ $value }}</b>{!! $change !!}@if($note)<small>{{ $note }}</small>@endif</div>
        @endforeach
    </div>
</section>
@endif

<section class="ci-card" id="about" data-nav="About">
    <h2>About {{ $company->name }}</h2>
    @php $aboutText = $company->description ?: ($detail?->principal_activity ? $company->name . ' is engaged in ' . strtolower($detail->principal_activity) . '.' : null); @endphp
    @if($aboutText)<p class="pf-text">{{ $aboutText }}</p>@endif
    @if($sectorTags->isNotEmpty())
        <h3 class="ci-h3">Sectors</h3>
        <div class="pf-tags">@foreach($sectorTags as $tag)<span>{{ $tag }}</span>@endforeach</div>
    @endif
    <h3 class="ci-h3">Company details</h3>
    <dl class="pf-details">
        @if($company->website)<dt>Website</dt><dd><a href="{{ $company->website }}" target="_blank" rel="noopener nofollow">{{ preg_replace('~^https?://(www\.)?~', '', rtrim($company->website, '/')) }}</a></dd>@endif
        @if($company->company_email || $detail?->email)<dt>Email ID</dt><dd>{{ strtolower($company->company_email ?: $detail->email) }}</dd>@endif
        @if($phone)<dt>Phone number</dt><dd>{{ $phone }}</dd>@endif
        @if($keyPeople->isNotEmpty())<dt>Key people</dt><dd>{{ $keyPeople->map(fn ($o) => $o->name . ($o->designation ? ' (' . $o->designation . ')' : ''))->implode(' · ') }}@if($current->count() > 3) <a href="#people">+{{ $current->count() - 3 }} more</a>@endif</dd>@endif
        @if(isset($founded) && $founded)<dt>Founded</dt><dd>{{ $founded }}</dd>@endif
        @if($detail?->registered_address ?? $register?->address)<dt>Registered office</dt><dd>{{ ucwords(strtolower($detail?->registered_address ?? $register->address)) }}</dd>@endif
        @if($listing)<dt>Listed</dt><dd>{{ $company->usListings->map(fn ($l) => ($l->exchange ?? 'US') . ': ' . $l->ticker)->implode(' · ') }}@if($listing->ipo_year) (since {{ $listing->ipo_year }})@endif</dd>@endif
        @if($nse ?? null)<dt>Listed</dt><dd>NSE: {{ $nse->symbol }}{{ $nse->last_price ? ' · ₹' . number_format($nse->last_price, 2) : '' }}</dd>@endif
    </dl>
</section>

@if($areas)
<section class="ci-card" id="operational-areas" data-nav="Operational Areas">
    <h2>Operational Areas of {{ $company->name }}</h2>
    @foreach($areas as [$sector, $sub, $url])
        <div class="pf-crumb"><b>{{ $sector }}</b><span>›</span>@if($url)<a href="{{ $url }}">{{ $sub }}</a>@else{{ $sub }}@endif</div>
    @endforeach
</section>
@endif
