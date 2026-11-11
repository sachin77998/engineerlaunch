{{-- A round's company: linked to its insights page when it is in the company database. --}}
<div class="ex-co">@if($r->company?->logo_url)<img class="db-logo" src="{{ $r->company->logo_url }}" alt="" loading="lazy">@else<span class="db-logo txt">{{ mb_strtoupper(mb_substr($r->company_name, 0, 1)) }}</span>@endif
    <div>@if($r->company?->slug)<a href="{{ route('insights.show', $r->company->slug) }}">{{ $r->company_name }}</a>@else<b>{{ $r->company_name }}</b>@endif<small>{{ $r->company?->industry }}</small></div></div>
