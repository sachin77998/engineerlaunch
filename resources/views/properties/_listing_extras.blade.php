{{-- Listing page: office facts, pre-lease return, facilities, the project it belongs to and places nearby. Expects $p, $o, $nearby. --}}
@php
    $cd = $p->commercialDetail;
    $PF = \App\Services\Property\PropertyFormat::class;
    $officeRows = $cd ? array_filter([
        'Office type' => $o['office_types'][$cd->office_type] ?? null, 'Situated in' => $o['situated_in'][$cd->situated_in] ?? null,
        'Transaction' => $o['transaction_types'][$cd->transaction_type] ?? null, 'Seats' => $cd->seatsLabel(),
        'Cabins' => $cd->cabins, 'Meeting rooms' => $cd->meeting_rooms, 'Washrooms' => $cd->washrooms, 'Car parking' => $cd->car_parking,
        'RERA' => match ($cd->rera_status) { 'registered' => 'Registered' . ($cd->rera_number ? ' · ' . $cd->rera_number : ''), 'not_available' => 'Not available', 'not_required' => 'Not required', default => null },
    ], fn ($v) => $v !== null && $v !== '') : [];
    $roi = $cd?->roi($p->headlineAmount());
@endphp
@if($cd && $cd->is_pre_leased)
    <section class="pd-sec"><h2>Pre-leased details</h2>
        <div class="pd-rows">
            <div><b>Current rent</b><span>{{ $PF::full($cd->lease_amount, $p->currency) }} / month</span></div>
            @if($cd->lease_tenure_years)<div><b>Lease tenure left</b><span>{{ rtrim(rtrim(number_format($cd->lease_tenure_years, 1), '0'), '.') }} years</span></div>@endif
            @if($cd->tenant_name)<div><b>Tenant</b><span>{{ $cd->tenant_name }}</span></div>@endif
            @if($roi)<div><b>Annual rental yield</b><span>{{ $roi }}% <small class="pr-mute">(12 × monthly rent ÷ asking price)</small></span></div>@endif
        </div>
    </section>
@endif
@if($officeRows)
    <section class="pd-sec"><h2>Office details</h2><div class="pd-rows">@foreach($officeRows as $label => $value)<div><b>{{ $label }}</b><span>{{ $value }}</span></div>@endforeach</div></section>
@endif
@if($p->facilities->isNotEmpty())
    <section class="pd-sec"><h2>Facilities</h2><div class="pd-tiles">@foreach($p->facilities as $f)<div><i>{{ $f->icon() }}</i>{{ $f->label() }}</div>@endforeach</div></section>
@endif
@if($p->project && $p->project->is_active)
    @php($pj = $p->project)
    <section class="pd-sec"><h2>About the project</h2>
        <a href="{{ route('projects.show', $pj) }}" style="display:flex;gap:14px;align-items:center;text-decoration:none;color:inherit;border:1px solid var(--pr-line);border-radius:14px;padding:12px">
            <span style="width:120px;height:84px;border-radius:10px;background:#eef2ff center/cover;flex:none;@if($pj->coverUrl())background-image:url('{{ $pj->coverUrl() }}')@endif"></span>
            <span><b style="font-size:17px">{{ $pj->name }}</b><br><span class="pr-mute">@if($pj->builder)by {{ $pj->builder->name }} · @endif{{ $pj->statusLabel() }}@if($pj->possessionLabel()) · {{ $pj->possessionLabel() }}@endif</span><br><span style="font-weight:700">{{ $pj->images->count() }} {{ $pj->images->count() === 1 ? 'photo' : 'photos' }} · see project →</span></span>
        </a>
    </section>
@endif
@if($nearby->isNotEmpty())
    <section class="pd-sec"><h2>Places nearby</h2>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px">
            @foreach($nearby as $cat => $places)
                <div style="border:1px solid var(--pr-line);border-radius:12px;padding:12px"><b>{{ $o['nearby_categories'][$cat][1] ?? '📍' }} {{ $o['nearby_categories'][$cat][0] ?? $cat }}</b>
                    @foreach($places as $pl)<div style="display:flex;justify-content:space-between;gap:8px;font-size:14px;margin-top:6px"><span>{{ $pl->name }}</span><span class="pr-mute" style="white-space:nowrap">{{ $pl->distanceLabel() }}</span></div>@endforeach</div>
            @endforeach
        </div>
        <small class="pr-mute">Straight-line distances. Map data © OpenStreetMap contributors.</small>
    </section>
@endif
