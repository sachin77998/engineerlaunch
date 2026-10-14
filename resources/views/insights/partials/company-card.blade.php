@php $detail = $company->registryDetail; @endphp
<a class="ci-company" href="{{ route('insights.show', $company) }}">
    <span class="ci-logo">@if($company->logo_url)<img src="{{ $company->logo_url }}" alt="" loading="lazy">@else{{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}@endif</span>
    <span class="ci-company-body">
        <strong>{{ $company->name }}</strong>
        <small>{{ $detail?->cin ?? $company->registry_cin ?? 'CIN not recorded' }}</small>
        <small>{{ collect([$detail?->company_status ?? $company->registry_status, $company->registered_state ?? null, $company->industry])->filter()->unique()->implode(' · ') }}</small>
        @if(($company->financial_statements_count ?? 0) > 0)<em>{{ $company->financial_statements_count }} {{ Str::plural('year', $company->financial_statements_count) }} of financials</em>@endif
    </span>
</a>
