@php
    $nse = \Illuminate\Support\Facades\DB::table('listed_companies')->where('company_id', $company->id)->first(['symbol', 'last_price']);
    $site = $company->website ?: null;
    $place = $usOnly ? ($listing?->country ?? $company->country) : collect([$detail?->city ? ucwords(strtolower($detail->city)) : null, $company->registered_state ?? ($decoded['State of registration'] ?? null), $company->country ?: 'India'])->filter()->unique()->implode(', ');
    $founded = $detail?->incorporation_date?->format('Y') ?? ($register?->registered_on ? substr($register->registered_on, 0, 4) : null) ?? $listing?->ipo_year;
    $about = $company->description ?: ($detail?->principal_activity ? ucfirst(strtolower($detail->principal_activity)) : ($listing?->industry ? $listing->industry . ' company' : null));
@endphp
<section class="pf-head">
    <div class="ci-wrap pf-card">
        <span class="pf-logo">@if($company->logo_url)<img src="{{ $company->logo_url }}" alt="">@else{{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}@endif</span>
        <div class="pf-main">
            <h1>{{ $company->name }} @if($site)<a href="{{ $site }}" target="_blank" rel="noopener nofollow" title="Website" class="pf-ext">↗</a>@endif</h1>
            @if($about)<p class="pf-about">{{ \Illuminate\Support\Str::limit($about, 160) }}</p>@endif
            <div class="pf-meta">
                @if($founded)<span>{{ $founded }}</span>@endif
                @if($place)<span>📍 {{ $place }}</span>@endif
                @if($status)<span class="pf-badge {{ preg_match('/^active/i', $status) ? 'ok' : '' }}">{{ ucfirst(strtolower($status)) }}</span>@endif
                @if($nse)<span class="pf-badge">NSE {{ $nse->symbol }}</span>@endif
                @foreach($company->usListings as $l)<span class="pf-badge">{{ strtoupper($l->exchange ?? 'US') }} {{ $l->ticker }}</span>@endforeach
                @if($cin)<span class="pf-badge light">CIN {{ $cin }}</span>@endif
                @if($company->industry && $company->industry !== 'Other Industries')<span class="pf-badge light">{{ $company->industry }}</span>@endif
            </div>
        </div>
        <div class="pf-actions">
            <button type="button" class="pf-btn" onclick="window.print()">⤓ Download</button>
            <a class="pf-btn light" href="{{ route('insights.index') }}">Search companies</a>
        </div>
    </div>
</section>
<div class="pf-sticky" id="pf-sticky" aria-hidden="true">
    <div class="ci-wrap"><span class="pf-logo sm">@if($company->logo_url)<img src="{{ $company->logo_url }}" alt="">@else{{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}@endif</span><b>{{ $company->name }}</b>
        <button type="button" class="pf-btn" onclick="window.print()">⤓ Download</button></div>
</div>
