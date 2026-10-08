<a class="sd-card" href="{{ route('companies.show', $company->slug) }}">
    <div class="sd-card-top">
        @if($company->logo_url)<img src="{{ $company->logo_url }}" alt="" loading="lazy">@endif
        <div><strong>{{ $company->name }}</strong><div class="sd-meta">{{ collect([$company->headquarters, $company->country])->filter()->unique()->implode(' · ') }}</div></div>
    </div>
    @if($company->brands)<div class="sd-tags">@foreach(array_slice($company->brands, 0, 6) as $brand)<span class="sd-tag">{{ $brand }}</span>@endforeach @if(count($company->brands) > 6)<span class="sd-tag">+{{ count($company->brands) - 6 }}</span>@endif</div>@endif
    @if($company->products)<div class="sd-meta">{{ \Illuminate\Support\Str::limit($company->products, 90) }}</div>@endif
    @if($company->relationLoaded('facilities') && $company->facilities->isNotEmpty())<div class="sd-plant">Plants: {{ $company->facilities->map(fn ($f) => $f->city ?: $f->industrial_area)->filter()->unique()->take(4)->implode(', ') }}@if($company->facilities->count() > 4) +{{ $company->facilities->count() - 4 }}@endif</div>@endif
    <div class="sd-jobs {{ $company->active_jobs_count ? '' : 'none' }}">{{ $company->active_jobs_count ? $company->active_jobs_count.' open '.\Illuminate\Support\Str::plural('job', $company->active_jobs_count) : 'Openings refresh daily' }}</div>
</a>
