@extends('layouts.site')
@php
    use App\Services\Property\PropertyFormat as PF;
    $o = config('properties');
    $photos = $p->media->where('type', 'photo')->values();
    $videos = $p->media->where('type', 'video')->values();
    $rent = $p->rentTerms; $sale = $p->saleTerms; $pg = $p->pgTerms;
    $d = $p->details ?? [];
    $yes = fn ($v) => $v === 'yes' ? 'Yes' : ($v === 'no' ? 'No' : null);
    $amount = $p->headlineAmount();
    $perSqft = $sale && $p->area_sqft ? PF::full(round($sale->price / $p->area_sqft), $p->currency) : null;
    $emi = $p->emi();
    $where = collect([$p->locality ?: $p->society, $p->locality ? null : $p->city])->filter()->implode(', ');
    $ownerWord = $p->owner->owner_type === 'owner' ? 'Owner' : ($p->owner->owner_type === 'builder' ? 'Builder' : 'Dealer');
    $areaKind = $p->isLand() || in_array($p->building_type, ['independent_house', 'villa', 'farm_house', 'gated_community'], true) ? 'Plot area' : ($p->segment === 'commercial' ? 'Area' : 'Super built-up area');
    $roadFt = isset($d['road_width']) ? ((($d['road_unit'] ?? 'ft') === 'm') ? $d['road_width'] / 0.3048 : (float) $d['road_width']) : null;
    $num = fn ($v, $dp = 1) => rtrim(rtrim(number_format((float) $v, $dp), '0'), '.');
    $plural = fn ($n, $word) => $n . ' ' . $word . ($n == 1 ? '' : 's');
    $extraRooms = collect(['pooja_room' => 'Pooja Room', 'study_room' => 'Study Room', 'servant_room' => 'Servant Room'])->filter(fn ($_, $k) => in_array($k, $p->highlights ?? [], true));

    // Facts panel beside the gallery: [icon, label, value, note].
    $facts = array_values(array_filter([
        $p->area_value ? ['▦', $p->isLand() ? 'Dimensions' : 'Area', $areaKind . ' ' . PF::area($p->area_value, $p->area_unit), ($p->area_sqft ? number_format($p->area_sqft) . ' sq. ft · ' . $num($p->area_sqft / 10.7639, 2) . ' sq. m' : '')
            . (isset($d['plot_length'], $d['plot_breadth']) ? ' · ' . $num($d['plot_length']) . ' × ' . $num($d['plot_breadth']) . ' ft' : '')] : null,
        $p->segment === 'residential' && !$p->isLand() && !$p->isPg() ? ['⌂', 'Configuration', (collect([$p->bedrooms !== null ? $plural($p->bedrooms, 'Bedroom') : null, $p->bathrooms !== null ? $plural($p->bathrooms, 'Bathroom') : null,
            $p->balconies !== null ? ($p->balconies == 1 ? '1 Balcony' : $p->balconies . ' Balconies') : null])->filter()->implode(', ') ?: $p->bhkLabel()) . ($extraRooms->isNotEmpty() ? ' with ' . $extraRooms->implode(', ') : ''), null] : null,
        $sale ? ['₹', 'Price', PF::short($sale->price, $p->currency), PF::full($sale->price, $p->currency) . ($perSqft ? ' · @ ' . $perSqft . ' per sqft' : '') . ($sale->price_negotiable ? ' (Negotiable)' : '')] : null,
        $rent ? ['₹', 'Rent', PF::full($rent->rent, $p->currency) . ' / month', 'Deposit ' . (PF::full($rent->deposit, $p->currency) ?? '—') . ($rent->rent_negotiable ? ' · Negotiable' : '')] : null,
        $pg ? ['₹', 'Rent per bed', 'From ' . PF::full($pg->lowestRent(), $p->currency) . ' / month', ($o['pg']['gender'][$pg->gender] ?? 'Anyone') . ' · ' . ($o['pg']['tenant_type'][$pg->tenant_type] ?? 'Anyone')] : null,
        $sale?->ownership ? ['⚖', 'Property ownership', $o['ownership'][$sale->ownership], null] : null,
        $p->totalFloorsLabel() ? ['☰', $p->floor && !in_array($p->building_type, ['independent_house', 'villa', 'farm_house'], true) ? 'Floor' : 'Total floors', $p->floor && !in_array($p->building_type, ['independent_house', 'villa', 'farm_house'], true) ? $p->floorLabel() : $p->totalFloorsLabel(), null] : null,
        $p->isLand() && $p->facing ? ['⊕', 'Facing', $o['facing'][$p->facing] ?? $p->facing, null] : null,
        $p->isLand() && !empty($d['open_sides']) ? ['▢', 'No. of open sides', $d['open_sides'], null] : null,
        $sale ? ['◷', $sale->possession === 'under_construction' ? 'Possession' : 'Ready since', $sale->possession === 'under_construction' ? ($sale->possession_by ? 'By ' . $sale->possession_by->format('M Y') : 'Under construction') : ($p->isLand() ? 'Ready for possession' : (['lt1' => '0-1 yr', '1_5' => '1-5 yrs', '5_10' => '5-10 yrs', 'gt10' => '10+ yrs'][$p->age] ?? 'Ready to move')), $sale->is_new_project ? 'New launch' : null] : null,
        ($rent || $pg) ? ['◷', 'Available from', ($rent?->available_from ?? $pg?->available_from)?->isFuture() ? ($rent?->available_from ?? $pg?->available_from)->format('d M Y') : 'Immediately', $rent?->preferred_tenants ? 'For ' . collect($rent->preferred_tenants)->map(fn ($t) => $o['preferred_tenants'][$t] ?? $t)->implode(', ') : null] : null,
        $p->furnishing && !$p->isLand() ? ['✦', 'Furnishing', $o['furnishing'][$p->furnishing], null] : null,
    ]));

    // Key highlights: owner's ticks, commercial features, and parking counts.
    $highlights = collect()
        ->merge(($d['covered_parking'] ?? 0) ? [$d['covered_parking'] . ' Covered Parking'] : [])
        ->merge(($d['open_parking'] ?? 0) ? [$d['open_parking'] . ' Open Parking'] : [])
        ->merge(collect($d['overlooking'] ?? [])->map(fn ($k) => isset($o['overlooking'][$k]) ? 'Overlooking ' . $o['overlooking'][$k] : null))
        ->merge($p->facing ? [($o['facing'][$p->facing] ?? '') . ' Facing'] : [])
        ->merge(collect($p->highlights ?? [])->reject(fn ($k) => $extraRooms->has($k))->map(fn ($k) => $o['highlights'][$k] ?? null))
        ->merge(collect($p->features ?? [])->map(fn ($k) => $o['commercial']['other_features'][$k] ?? null))->filter()->unique()->values();

    // Detail strip under the highlights.
    $strip = array_filter([
        'Parking' => isset($d['covered_parking']) || isset($d['open_parking']) ? collect([($d['covered_parking'] ?? 0) ? $d['covered_parking'] . ' Covered' : null, ($d['open_parking'] ?? 0) ? $d['open_parking'] . ' Open' : null])->filter()->implode(', ') : ($o['parking'][$d['parking'] ?? ''] ?? null),
        'Width of facing road' => $roadFt ? $num($roadFt) . ' Feet (' . $num($roadFt * 0.3048, 2) . ' m)' : null,
        'Facing' => $o['facing'][$p->facing] ?? null,
        'Flooring' => $o['flooring'][$d['flooring'] ?? ''] ?? null,
        'Power backup' => $o['power_backup'][$d['power_backup'] ?? ''] ?? null,
        'Open sides' => $d['open_sides'] ?? null,
        'Water supply' => $o['water_supply'][$d['water_supply'] ?? ''] ?? null,
        'Gated security' => $yes($d['gated'] ?? null),
        'Modular kitchen' => $p->segment === 'residential' && !$p->isLand() && (isset(($p->furnishings ?? [])['modular_kitchen']) || in_array('modular_kitchen', $p->highlights ?? [], true)) ? 'Yes' : null,
        'Non-veg allowed' => $yes($d['non_veg'] ?? null),
        'Pets allowed' => $yes($d['pets'] ?? null),
        'Property age' => $o['ages'][$p->age] ?? null,
        'Plot dimensions' => isset($d['plot_length'], $d['plot_breadth']) ? $d['plot_length'] . ' × ' . $d['plot_breadth'] . ' ft' : null,
        'No. of floors permissible' => $d['floors_allowed'] ?? null,
        'Overlooking' => collect($d['overlooking'] ?? [])->map(fn ($k) => $o['overlooking'][$k] ?? null)->filter()->implode(', ') ?: null,
        'Boundary wall available' => $p->isLand() ? (($d['boundary_wall'] ?? false) ? 'Yes' : 'No') : null,
        'Lease duration' => $rent?->lease_years ? ($o['lease_years'][$rent->lease_years] ?? null) : null,
        'Lock-in period' => $rent && $rent->lockin_years !== null && $rent->lockin_years !== '' ? ($o['lockin_years'][$rent->lockin_years] ?? null) : null,
        'Maintenance' => $rent ? ($rent->maintenance ? PF::full($rent->maintenance, $p->currency) . ' / month' : ($rent->maintenance_extra ? 'Extra' : 'Included')) : ($sale?->maintenance ? PF::full($sale->maintenance, $p->currency) . ' / month' : null),
        'Brokerage' => ($rent ?? $sale) ? (($rent ?? $sale)->brokerage ? PF::full(($rent ?? $sale)->brokerage, $p->currency) : 'None') : null,
        'Project' => $sale?->project_name ? $sale->project_name . ($sale->builder_name ? ' by ' . $sale->builder_name : '') : null,
        'Building type' => $p->segment === 'commercial' ? ($o['building_types']['commercial'][$p->building_type] ?? null) : null,
    ], fn ($v) => $v !== null && $v !== '');
    $commercialRows = $p->segment === 'commercial' ? array_filter([
        'Power backup' => $o['commercial']['power_backup'][$p->amenities['power_backup'] ?? ''] ?? null,
        'Lift' => $o['commercial']['lift'][$p->amenities['lift'] ?? ''] ?? null,
        'Parking' => $o['commercial']['parking'][$p->amenities['parking'] ?? ''] ?? null,
        'Washroom' => $o['commercial']['washrooms'][$p->amenities['washrooms'] ?? ''] ?? null,
        'Water storage' => $yes($p->amenities['water_storage'] ?? null),
        'Security' => $yes($p->amenities['security'] ?? null),
        'Current condition' => $o['commercial']['condition'][$p->amenities['condition'] ?? ''] ?? null,
        'Business running now' => $o['commercial']['current_business'][$p->amenities['current_business'] ?? ''] ?? null,
        'Similar units available' => $yes($p->amenities['similar_units'] ?? null),
    ]) : [];
    $idealFor = collect($rent?->ideal_for ?? $sale?->ideal_for ?? [])->map(fn ($k) => $o['commercial']['ideal_for'][$k] ?? null)->filter();
    $furnish = collect($p->furnishings ?? [])->filter();
    $mode = $p->mode();
    $typeKey = collect($o['home_types'])->search(fn ($t) => $p->isLand() ? $t[2] === null : in_array($p->building_type, $t[2] ?? [], true));
    $typePlural = $typeKey ? $o['home_types'][$typeKey][1] : ($p->segment === 'commercial' ? $p->kindLabel() : 'Property');
    $crumbs = array_filter([
        ['Home', route('properties.index')],
        $p->city_slug ? ['Property in ' . $p->city, route('properties.search', [$mode, $p->city_slug])] : null,
        $p->city_slug && $typeKey && $p->segment === 'residential' ? [$typePlural . ' for ' . ($p->listing === 'rent' ? 'rent' : 'sale') . ' in ' . $p->city, route('properties.search', [$mode, $p->city_slug, 'type' => [$typeKey]])] : null,
        $p->city_slug && $p->locality ? [$typePlural . ' for ' . ($p->listing === 'rent' ? 'rent' : 'sale') . ' in ' . $p->locality, route('properties.search', array_filter([$mode, $p->city_slug, 'type' => $typeKey && $p->segment === 'residential' ? [$typeKey] : null, 'loc' => [$p->locality]]))] : null,
    ]);
@endphp
@section('title', $p->title . ' — ' . $p->headlinePrice())
@section('meta_description', \Illuminate\Support\Str::limit(trim($p->title . '. ' . $p->headlinePrice() . '. ' . $p->description), 300))
@section('content')
@include('properties._nav')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<style>
.pd-crumb{font-size:12.5px;color:var(--pr-mute);margin:14px 0 4px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}.pd-crumb a{color:var(--pr-mute)}
.pd-head{position:sticky;top:0;z-index:30;background:var(--pr-bg);padding:10px 0 0;border-bottom:1px solid var(--pr-line)}
.pd-headrow{display:flex;align-items:center;gap:22px;flex-wrap:wrap}
.pd-big{font-size:30px;font-weight:800;line-height:1}.pd-big small{display:block;font-size:13px;font-weight:500;color:var(--pr-mute);margin-top:6px}
.pd-title{border-left:1px solid var(--pr-line);padding-left:22px;min-width:0;flex:1}.pd-title h1{font-size:24px;font-weight:600;margin:0}.pd-title div{font-size:14px;color:var(--pr-mute);margin-top:4px}
.pd-acts{display:flex;flex-direction:column;gap:8px;margin-left:auto}
.pd-tabs{display:flex;gap:4px;overflow-x:auto;margin-top:12px}
.pd-tabs a{padding:10px 16px;color:#374151;text-decoration:none;font-weight:600;border-bottom:3px solid transparent;white-space:nowrap}
.pd-tabs a.on,.pd-tabs a:hover{color:var(--pr-accent);border-bottom-color:var(--pr-accent)}
.pd-top{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.05fr);gap:0;margin-top:18px;background:#fff;border:1px solid var(--pr-line);border-radius:18px;overflow:hidden}
.pd-gal{position:relative;background:#e0e7ff;min-height:380px}
.pd-gal img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.pd-gal img[hidden]{display:none}
.pd-gal .lbl{position:absolute;left:14px;top:14px;z-index:2;color:#fff;font-weight:800;text-shadow:0 1px 4px rgba(0,0,0,.6)}
.pd-gal .social{position:absolute;left:14px;bottom:14px;z-index:2;background:rgba(17,24,39,.78);color:#fff;font-weight:700;font-size:14px;padding:7px 12px;border-radius:8px}
.pd-gal .empty{position:absolute;inset:0;display:grid;place-items:center;font-size:70px;color:#818cf8}
.pd-facts{padding:22px 24px;display:flex;flex-direction:column}
.pd-fgrid{display:grid;grid-template-columns:1fr 1fr;gap:0 22px}
.pd-fgrid div{padding:14px 0;border-bottom:1px solid #f1f2f6;display:flex;gap:10px}.pd-fgrid i{font-style:normal;width:28px;height:28px;border-radius:8px;background:var(--pr-soft);color:var(--pr-accent);display:grid;place-items:center;flex:0 0 28px}
.pd-fgrid b{display:block;font-size:12.5px;color:var(--pr-mute);font-weight:600}.pd-fgrid span{display:block;font-weight:700}.pd-fgrid small{display:block;color:var(--pr-mute);font-size:12.5px;margin-top:2px}
.pd-cta{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:auto;padding-top:18px}
.pd-hl{display:grid;grid-template-columns:180px minmax(0,1fr);gap:18px;background:var(--pr-soft);border-radius:16px;padding:18px;margin-top:18px}
.pd-hl ul{list-style:none;margin:0;padding:14px 18px;background:#fff;border-radius:12px;display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px}
.pd-hl li:before{content:'✓ ';color:var(--pr-ok);font-weight:800}
.pd-strip{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px 22px;padding:18px 0;border-bottom:1px solid var(--pr-line);margin-top:18px}
.pd-strip div{font-size:14.5px}.pd-strip b{color:var(--pr-mute);font-weight:500}
.pd-sec{padding:22px 0;border-bottom:1px solid var(--pr-line)}.pd-sec h2{font-size:20px}
.pd-rows{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px 18px}.pd-rows div b{display:block;font-size:12.5px;color:var(--pr-mute);font-weight:600}.pd-rows div span{font-weight:700}
.pd-furn{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}.pd-furn div{display:flex;justify-content:space-between;padding:10px 12px;border:1px solid var(--pr-line);border-radius:10px;font-size:14px;background:#fff}
.pd-furn div.off{color:#9ca3af;text-decoration:line-through}
.pd-owner{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.2fr);gap:20px}
.pd-owner .num{font-size:22px;font-weight:800;letter-spacing:.02em}
#pdMap{height:260px;border-radius:12px;margin-top:10px;z-index:0}
.pd-tiles{display:flex;flex-wrap:wrap;gap:12px}.pd-tiles div{width:120px;padding:12px;border-radius:12px;background:#fff;border:1px solid var(--pr-line);font-size:13px;color:#374151;text-align:center}
.pd-tiles i{display:block;font-style:normal;font-size:30px;margin-bottom:6px}
.pd-avatar{flex:0 0 64px;width:64px;height:64px;border-radius:14px;background:#d1fae5;color:#047857;display:grid;place-items:center;font-size:22px;font-weight:800}
.pd-related>div{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:18px}
.pd-related b{display:block;color:var(--pr-mute);font-size:13.5px;margin-bottom:8px}.pd-related a{display:block;color:#374151;text-decoration:none;font-size:14px;padding:3px 0}.pd-related a:hover{color:var(--pr-accent)}
.pd-sim{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:14px}
.pd-sim article{background:#fff;border:1px solid var(--pr-line);border-radius:12px;overflow:hidden;display:flex;flex-direction:column}
.pd-sim .img{position:relative;display:block;height:130px;background:#e0e7ff;color:#818cf8;text-decoration:none}.pd-sim .img img{width:100%;height:100%;object-fit:cover}.pd-sim .img span{display:grid;place-items:center;height:100%;font-size:40px}
.pd-sim .img small{position:absolute;left:0;right:0;bottom:0;background:rgba(17,24,39,.75);color:#fff;font-size:11.5px;font-weight:700;padding:4px 8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pd-sim .t{padding:10px 12px;display:flex;flex-direction:column;gap:2px;font-size:14px}.pd-sim .t a{color:var(--pr-accent);text-decoration:none}.pd-sim .t small{color:var(--pr-mute);font-size:12.5px}
.pd-sim .enq{border-top:1px solid var(--pr-line);padding:10px 12px;color:var(--pr-ok);font-weight:800;text-decoration:none;margin-top:auto}
.pd-report{display:flex;justify-content:flex-end;align-items:center;gap:12px;padding:18px 0;flex-wrap:wrap}
@media(max-width:900px){.pd-top,.pd-owner,.pd-hl{grid-template-columns:1fr}.pd-gal{min-height:260px}.pd-title{border-left:0;padding-left:0}.pd-acts{flex-direction:row;margin-left:0}.pd-fgrid{grid-template-columns:1fr}}
</style>@endpush
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    @if($p->status !== 'active')<div class="pr-flash warn">This listing is {{ $p->status === 'draft' ? 'a draft and not public yet' : ($p->status === 'closed' ? 'closed (' . ($p->listing === 'rent' ? 'rented out' : 'sold') . ')' : 'hidden') }}. Only you can see it.</div>@endif
    <div class="pd-crumb"><nav aria-label="Breadcrumb">@foreach($crumbs as [$label, $href])<a href="{{ $href }}">{{ $label }}</a> › @endforeach<span>{{ $p->heading() }}{{ $p->locality ? ' in ' . $p->locality : '' }}</span></nav>
        <span>Posted on {{ $p->published_at?->format('M d, Y') }}</span></div>

    <div class="pd-head">
        <div class="pd-headrow">
            <div class="pd-big">{{ $sale ? PF::short($sale->price, $p->currency) : $p->headlinePrice() }}
                <small>@if($perSqft)@ {{ $perSqft }} per sqft @endif @if($emi)· <a href="{{ route('home-loan.index', ['property' => $p->slug]) }}" title="{{ (int) ($o['emi']['loan_share'] * 100) }}% loan at {{ $o['emi']['rate'] }}% for {{ $o['emi']['years'] }} years">EMI ~{{ PF::full($emi, $p->currency) }}</a>@endif
                    @if($rent) Deposit {{ PF::full($rent->deposit, $p->currency) ?? '—' }}@endif</small></div>
            <div class="pd-title"><h1>{{ $p->heading() }}</h1>
                <div>{{ $where ? 'in ' . $where : '' }}@if($p->society && $p->locality) · {{ $p->society }}@endif @if($p->owner->rera_number) · <span class="pr-tag ok" title="RERA {{ $p->owner->rera_number }}">RERA</span>@endif @if($p->is_verified) · <span class="pr-tag ok">✓ Verified</span>@endif</div></div>
            <div class="pd-acts">
                @unless($mine)<a class="pr-btn" href="#owner">Contact {{ $ownerWord }} <small style="font-size:10px;opacity:.85;margin-left:4px">FREE</small></a>@endunless
                @auth<form method="post" action="{{ route('properties.shortlist.toggle', $p) }}">@csrf<button class="pr-btn ghost" style="width:100%">{{ $shortlisted ? '♥ Shortlisted' : '♡ Shortlist' }}</button></form>
                @else<a class="pr-btn ghost" href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">♡ Shortlist</a>@endauth
            </div>
        </div>
        <nav class="pd-tabs" aria-label="Sections">
            <a href="#overview" class="on">Overview</a><a href="#details">Details</a><a href="#owner">{{ $ownerWord }} details</a>@if($localityReviews)<a href="#locality-reviews">Locality reviews</a>@endif @if($similar->isNotEmpty())<a href="#similar">Recommendations</a>@endif
        </nav>
    </div>

    <section id="overview" class="pd-top">
        <div class="pd-gal">
            @forelse($photos as $i => $ph)<img src="{{ $ph->url() }}" alt="{{ $i === 0 ? $p->title : '' }}" @if($i > 0) hidden @endif>@empty<div class="empty">⌂</div>@endforelse
            @if($photos->isNotEmpty())<span class="lbl">Photos ({{ $photos->count() }})@if($videos->isNotEmpty()) · Videos ({{ $videos->count() }})@endif</span>@endif
            @if($photos->count() > 1)<button type="button" class="pr-slide prev" data-step="-1" aria-label="Previous photo">‹</button><button type="button" class="pr-slide next" data-step="1" aria-label="Next photo">›</button><small data-count class="pd-count" style="position:absolute;right:14px;bottom:14px;z-index:2;background:rgba(17,24,39,.75);color:#fff;font-size:12px;padding:3px 8px;border-radius:6px">1/{{ $photos->count() }}</small>@endif
            @if(($saves->recent ?? 0) > 0)<span class="social">♥ {{ $saves->recent }} {{ $saves->recent == 1 ? 'person' : 'people' }} shortlisted this in the last day</span>
            @elseif($viewedYesterday > 1)<span class="social">👁 {{ $viewedYesterday }} people viewed this property yesterday</span>
            @elseif(($saves->total ?? 0) > 1)<span class="social">♥ Shortlisted by {{ $saves->total }} people</span>@endif
        </div>
        <div class="pd-facts">
            <div class="pd-fgrid">
                @foreach($facts as [$icon, $label, $value, $note])
                    <div><i>{{ $icon }}</i><div style="min-width:0"><b>{{ $label }}</b>
                        @if($label === 'Area')<span id="pdArea" data-sqft="{{ $p->area_sqft }}">{{ $value }}</span>
                            <select id="pdUnit" aria-label="Show area in" style="margin-top:4px;padding:3px 6px;font-size:12.5px;width:auto">@foreach($o['area_units'] as $k => [$ul, $sq])<option value="{{ $sq }}" data-label="{{ $ul }}" @selected($k === $p->area_unit)>{{ $ul }}</option>@endforeach</select>
                        @else<span>{{ $value }}</span>@endif
                        @if($note)<small>{{ $note }}</small>@endif</div></div>
                @endforeach
            </div>
            @unless($mine)
                <div class="pd-cta">
                    <a class="pr-btn ghost" href="#owner">✆ Contact {{ $ownerWord }}</a>
                    @auth<button type="button" class="pr-btn" data-reveal-main="{{ route('properties.phone', $p) }}" data-auto="{{ $revealed ? 1 : 0 }}">View number</button>
                    @else<a class="pr-btn" href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">View number</a>@endauth
                </div>
            @else
                <div class="pd-cta"><a class="pr-btn ghost" href="{{ route('properties.step', [$p->id, 'details']) }}">Edit listing</a><a class="pr-btn" href="{{ route('properties.mine') }}">My properties</a></div>
            @endunless
        </div>
    </section>

    @if($highlights->isNotEmpty())
        <section class="pd-hl"><div><div style="font-size:26px">◎</div><h2 style="margin:4px 0 0;font-size:18px">Key highlights</h2><span class="pr-mute" style="font-size:13px">of the property</span></div>
            <ul>@foreach($highlights as $h)<li>{{ $h }}</li>@endforeach</ul></section>
    @endif

    <div id="details">
        @if($strip)<div class="pd-strip">@foreach($strip as $label => $value)<div><b>{{ $label }}:</b> {{ $value }}</div>@endforeach</div>@endif

        @if($pg)
            <section class="pd-sec"><h2>{{ $p->kind === 'pg' ? 'PG / hostel details' : 'Flatmate details' }}</h2>
                <div class="pd-rows" style="margin-bottom:14px">@foreach($pg->rooms as $room)<div><b>{{ $o['pg']['rooms'][$room['type']] ?? $room['type'] }}</b><span>{{ $p->money($room['rent']) }} / month</span>@if(!empty($room['deposit']))<small class="pr-mute">Deposit {{ $p->money($room['deposit']) }}</small>@endif</div>@endforeach</div>
                <div class="pd-rows">
                    <div><b>For</b><span>{{ $o['pg']['gender'][$pg->gender] ?? 'Anyone' }}</span></div>
                    <div><b>Suitable for</b><span>{{ $o['pg']['tenant_type'][$pg->tenant_type] ?? 'Anyone' }}</span></div>
                    <div><b>Meals</b><span>{{ collect($pg->meals)->map(fn ($m) => $o['pg']['meals'][$m] ?? $m)->implode(', ') ?: 'Not included' }}</span></div>
                    @if($pg->gate_closing)<div><b>Gate closes at</b><span>{{ \Carbon\Carbon::createFromFormat('H:i', $pg->gate_closing)->format('g:i A') }}</span></div>@endif
                </div>
                @if($pg->services)<h3 style="margin-top:16px">Services</h3><div class="pr-chips">@foreach($pg->services as $s)<span class="pr-tag">{{ $o['pg']['services'][$s] ?? $s }}</span>@endforeach</div>@endif
                @if($pg->rules)<h3 style="margin-top:16px">House rules</h3><div class="pr-chips">@foreach($pg->rules as $s)<span class="pr-tag amber">{{ $o['pg']['rules'][$s] ?? $s }}</span>@endforeach</div>@endif
            </section>
        @endif

        @if($commercialRows || $idealFor->isNotEmpty())
            <section class="pd-sec"><h2>Building and amenities</h2>
                <div class="pd-rows">@foreach($commercialRows as $label => $value)<div><b>{{ $label }}</b><span>{{ $value }}</span></div>@endforeach</div>
                @if($idealFor->isNotEmpty())<h3 style="margin-top:16px">Ideal for</h3><div class="pr-chips">@foreach($idealFor as $i)<span class="pr-tag blue">{{ $i }}</span>@endforeach</div>@endif
            </section>
        @endif

        @if($p->segment === 'residential' && !$p->isLand() && $p->furnishing !== 'none' && $furnish->isNotEmpty())
            <section class="pd-sec"><h2>Furnishing</h2>
                <div class="pd-furn">@foreach($o['furnishings'] as $k => $label)<div class="{{ $furnish->has($k) ? '' : 'off' }}"><span>{{ $label }}</span>@if($furnish->has($k))<b>{{ $furnish[$k] }}</b>@endif</div>@endforeach</div>
            </section>
        @endif

        @if($p->isLand() && $p->amenities)
            <section class="pd-sec"><h2>Amenities</h2><div class="pd-tiles">@foreach($p->amenities as $a)@if(isset($o['plot_amenities'][$a]))<div><i>{{ $o['plot_amenities'][$a][1] }}</i>{{ $o['plot_amenities'][$a][0] }}</div>@endif @endforeach</div></section>
        @elseif($p->segment === 'residential' && $p->amenities)
            <section class="pd-sec"><h2>Amenities</h2><div class="pr-chips">@foreach($p->amenities as $a)<span class="pr-tag" style="font-size:13.5px;padding:6px 12px">{{ $o['amenities'][$a] ?? $a }}</span>@endforeach</div></section>
        @endif

        <section class="pd-sec"><h2>About property</h2>
            <div><b>Address:</b> {{ $p->address() }}@if($p->landmark) · Near {{ $p->landmark }}@endif</div>
            @if($p->description)<div style="white-space:pre-line;line-height:1.65;margin-top:8px">{{ $p->description }}</div>@endif
            @if($p->directions)<div style="margin-top:8px"><b>Directions:</b> {{ $p->directions }}</div>@endif
            @if($videos->isNotEmpty())<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;margin-top:14px">@foreach($videos as $v)<video src="{{ $v->url() }}" controls preload="metadata" style="width:100%;border-radius:12px;background:#000"></video>@endforeach</div>@endif
            @if($p->latitude && $p->longitude)<div id="pdMap" data-lat="{{ $p->latitude }}" data-lng="{{ $p->longitude }}"></div>
                <a href="https://www.openstreetmap.org/?mlat={{ $p->latitude }}&mlon={{ $p->longitude }}#map=16/{{ $p->latitude }}/{{ $p->longitude }}" target="_blank" rel="noopener" style="display:inline-block;margin-top:8px">Open in map →</a>@endif
        </section>
    </div>

    <section id="owner" class="pd-sec"><h2>{{ $ownerWord }} details</h2>
        <div class="pd-owner">
            <div class="pr-card-box">
                <div style="display:flex;gap:16px;align-items:center">
                    <span class="pd-avatar">{{ mb_strtoupper(collect(explode(' ', $p->owner->company_name ?: $p->owner->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('')) }}</span>
                    <div><div style="font-size:18px;font-weight:800">{{ $p->owner->company_name ?: $p->owner->name }}</div>
                        <div class="pr-mute">{{ $p->owner->typeLabel() }}@if($p->owner->company_name) · {{ $p->owner->name }}@endif @if($p->owner->rera_number)<br>RERA {{ $p->owner->rera_number }}@endif</div></div>
                </div>
                <div style="margin-top:12px;font-size:14px"><span style="color:var(--pr-accent);font-weight:700">Properties listed:</span> {{ $ownerListings->count() }}
                    @if($ownerListings->pluck('locality')->filter()->isNotEmpty())<br><span class="pr-mute">Localities:</span> {{ $ownerListings->pluck('locality')->filter()->unique()->take(6)->implode(', ') }}@endif</div>
                <div style="margin-top:14px">
                    @if($mine)<div class="num">{{ $p->owner->phone }}</div>
                    @elseif(auth()->check())
                        <div id="pdPhone" hidden><div class="num" data-num></div><a data-wa class="pr-btn ghost sm" target="_blank" rel="noopener" hidden style="margin-top:6px">WhatsApp</a></div>
                        <button class="pr-btn" style="width:100%" id="pdReveal" data-url="{{ route('properties.phone', $p) }}">View phone number</button>
                    @else<a class="pr-btn" style="width:100%;box-sizing:border-box" href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">Sign in to view the number</a>@endif
                </div>
                @if($p->availability)<p class="pr-mute" style="font-size:13.5px;margin:14px 0 0">Site visits: {{ $o['availability'][$p->availability][0] ?? '' }} ({{ $o['availability'][$p->availability][1] ?? '' }}), {{ $p->visit_all_day ? 'any time' : \Carbon\Carbon::createFromFormat('H:i', $p->visit_start)->format('g:i A') . ' – ' . \Carbon\Carbon::createFromFormat('H:i', $p->visit_end)->format('g:i A') }}</p>@endif
                <p class="pr-mute" style="font-size:12.5px;margin:8px 0 0">{{ number_format($p->views) }} views</p>
            </div>
            @unless($mine)
                <form id="contact" class="pr-card-box" method="post" action="{{ route('properties.message', $p) }}" style="display:grid;gap:10px">
                    @csrf
                    <h3 style="margin:0">Send enquiry to {{ strtolower($ownerWord) }}</h3>
                    <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;font-size:14px"><b>You are</b>
                        @foreach(['individual' => 'Individual', 'dealer' => 'Dealer'] as $k => $label)<label class="pr-check" style="margin:0"><input type="radio" name="sender_type" value="{{ $k }}" @checked(old('sender_type', 'individual') === $k)> {{ $label }}</label>@endforeach</div>
                    <input type="text" name="name" required maxlength="120" placeholder="Your name" value="{{ old('name', auth()->user()?->name) }}" aria-label="Your name">
                    <div style="display:flex;gap:8px"><select name="dial" aria-label="Country code" style="width:130px">@foreach(collect($o['countries'])->unique('dial') as $country => $c)<option value="{{ $c['dial'] }}" @selected(old('dial', $o['countries'][$p->country]['dial'] ?? '+91') === $c['dial'])>{{ strtoupper(substr($country, 0, 3)) }} ({{ $c['dial'] }})</option>@endforeach</select>
                        <input type="tel" name="phone" required maxlength="20" placeholder="Mobile number" value="{{ old('phone') }}" aria-label="Mobile number" style="flex:1"></div>
                    <input type="email" name="email" maxlength="190" placeholder="Email (optional)" value="{{ old('email', auth()->user()?->email) }}" aria-label="Email">
                    <div><textarea name="message" rows="3" maxlength="400" placeholder="I am interested in this property." aria-label="Message" data-count-to="pdChars">{{ old('message') }}</textarea><small class="pr-mute" id="pdChars" style="float:right">400 chars</small></div>
                    <label class="f" style="margin:0">Request a site visit <span class="pr-mute" style="font-weight:500">(optional)</span><input type="datetime-local" name="visit_at" value="{{ old('visit_at') }}" style="margin-top:6px"></label>
                    <label class="pr-check" style="margin:0"><input type="checkbox" name="agree" value="1" required @checked(old('agree'))> I agree to the <a href="{{ route('privacy') }}" target="_blank">Privacy Policy</a></label>
                    <button class="pr-btn" type="submit">Send enquiry</button>
                </form>
            @endunless
        </div>
    </section>

    @if($localityReviews)
        <section id="locality-reviews" class="pd-sec">
            @include('properties._locality_reviews', ['summary' => $localityReviews['summary'], 'reviews' => $localityReviews['reviews'], 'city' => $p->city_slug, 'locality' => $localityReviews['localityKey'],
                'name' => $p->locality, 'cityName' => $p->city, 'show' => 3, 'voted' => $localityReviews['voted']])
        </section>
    @endif

    @if($similar->isNotEmpty())
        <section id="similar" class="pd-sec"><h2>Similar properties</h2>
            <div class="pd-sim">
                @foreach($similar as $s)
                    @php $cover = $s->coverUrl(); @endphp
                    <article>
                        <a class="img" href="{{ route('properties.show', $s) }}">@if($cover)<img src="{{ $cover }}" alt="" loading="lazy">@else<span>⌂</span>@endif
                            <small>{{ $s->owner?->company_name ?: $s->owner?->name }} ({{ $s->owner?->typeLabel() }}) {{ $s->published_at?->format('jS M, Y') }}</small></a>
                        <div class="t"><a href="{{ route('properties.show', $s) }}"><b>{{ $s->listing === 'sale' && $s->headlineAmount() ? PF::short($s->headlineAmount(), $s->currency) : $s->headlinePrice() }}{{ $s->bhkLabel() ? ', ' . $s->bhkLabel() : '' }}</b></a>
                            @if($s->society)<span>{{ $s->society }}</span>@endif<small>{{ collect([$s->locality, $s->city])->filter()->implode(', ') }}</small></div>
                        <a class="enq" href="{{ route('properties.show', $s) }}#owner">Enquire now</a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @unless($mine)
        <div class="pd-report" id="report">
            <span class="pr-mute">{{ $p->listing === 'rent' ? 'Property rented out' : 'Property sold out' }}? Incorrect data?</span>
            <details><summary class="pr-btn grey sm" style="list-style:none">⚑ Report</summary>
                <form method="post" action="{{ route('properties.report', $p) }}" class="pr-card-box" style="display:grid;gap:8px;margin-top:8px;min-width:280px">@csrf
                    <select name="reason" required aria-label="Reason">@foreach($o['report_reasons'] as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
                    <textarea name="details" rows="2" maxlength="1000" placeholder="Anything we should know (optional)" aria-label="Details"></textarea>
                    <button class="pr-btn sm" type="submit">Send report</button></form></details>
        </div>
    @endunless

    @if($p->locality && $p->city_slug)
        @php
            $verb = $p->listing === 'rent' ? 'for rent' : 'for sale';
            $options = collect($p->segment === 'residential' ? $o['home_types'] : [])->reject(fn ($t, $k) => $p->listing === 'rent' && $k === 'plot')
                ->map(fn ($t, $k) => [$t[1] . ' ' . $verb . ' in ' . $p->locality, route('properties.search', [$mode, $p->city_slug, 'type' => [$k], 'loc' => [$p->locality]])])->values();
        @endphp
        <section class="pd-sec pd-related"><h2>Related to your search</h2>
            <div>
                <div><b>About {{ $p->locality }}</b>
                    <a href="{{ route('localities.show', [$p->city_slug, \App\Models\Property\LocalityReview::key($p->locality)]) }}">{{ $p->locality }}, {{ $p->city }} overview</a>
                    <a href="{{ route('localities.show', [$p->city_slug, \App\Models\Property\LocalityReview::key($p->locality)]) }}">Reviews of {{ $p->locality }}, {{ $p->city }}</a></div>
                <div><b>{{ $p->locality }} &amp; nearby listings</b>
                    <a href="{{ route('properties.show', $p) }}">{{ $p->heading() }} in {{ $p->locality }} for {{ $sale ? PF::short($sale->price, $p->currency) : $p->headlinePrice() }}</a>
                    @foreach($similar->take(3) as $s)<a href="{{ route('properties.show', $s) }}">{{ $s->heading() }} in {{ $s->locality }} for {{ $s->listing === 'sale' && $s->headlineAmount() ? PF::short($s->headlineAmount(), $s->currency) : $s->headlinePrice() }}</a>@endforeach</div>
                <div><b>Property options in {{ $p->locality }}</b>
                    @foreach($options as [$label, $href])<a href="{{ $href }}">{{ $label }}</a>@endforeach
                    <a href="{{ route('properties.search', [$p->listing === 'rent' ? 'rent' : 'buy', $p->city_slug, 'loc' => [$p->locality]]) }}">All property {{ $verb }} in {{ $p->locality }}</a></div>
            </div>
        </section>
    @endif
</div></div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    document.querySelectorAll('[data-count-to]').forEach(function (t) {
        var out = document.getElementById(t.dataset.countTo), max = parseInt(t.getAttribute('maxlength'), 10);
        function left() { out.textContent = (max - t.value.length) + ' chars'; }
        t.addEventListener('input', left); left();
    });
    var unit = document.getElementById('pdUnit'), area = document.getElementById('pdArea');
    if (unit && area) unit.addEventListener('change', function () {
        var v = parseFloat(area.dataset.sqft) / parseFloat(unit.value), o = unit.options[unit.selectedIndex];
        area.textContent = (Math.round(v * 100) / 100).toLocaleString('en-IN') + ' ' + o.dataset.label;
    });
    // Gallery arrows; stopped here so the listing cards' slider script does not also handle them.
    document.querySelectorAll('.pd-gal .pr-slide').forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.stopPropagation();
            var box = b.closest('.pd-gal'), imgs = box.querySelectorAll('img'), cur = [].findIndex.call(imgs, function (i) { return !i.hidden; }), next = (cur + parseInt(b.dataset.step, 10) + imgs.length) % imgs.length;
            imgs[cur].hidden = true; imgs[next].hidden = false; box.querySelector('.pd-count').textContent = (next + 1) + '/' + imgs.length;
        }, true);
    });
    var btn = document.getElementById('pdReveal'), box = document.getElementById('pdPhone'), main = document.querySelector('[data-reveal-main]');
    function reveal() {
        if (!btn) return;
        fetch(btn.dataset.url, {method: 'POST', headers: {'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json'}})
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
            .then(function (d) {
                box.hidden = false; box.querySelector('[data-num]').textContent = d.phone; var wa = box.querySelector('[data-wa]'); if (d.whatsapp) { wa.href = d.whatsapp; wa.hidden = false; } btn.hidden = true;
                if (main) { var a = document.createElement('a'); a.className = main.className; a.href = 'tel:' + d.phone.replace(/[^+\d]/g, ''); a.textContent = d.phone; main.replaceWith(a); main = null; }
            })
            .catch(function () { btn.textContent = 'Could not load the number — try again in a minute'; });
    }
    if (btn) btn.addEventListener('click', reveal);
    if (main) { main.addEventListener('click', reveal); if (main.dataset.auto === '1') reveal(); }
    // Section tabs follow the scroll position.
    var tabs = [].slice.call(document.querySelectorAll('.pd-tabs a'));
    window.addEventListener('scroll', function () {
        var current = tabs[0];
        tabs.forEach(function (t) { var s = document.querySelector(t.getAttribute('href')); if (s && s.getBoundingClientRect().top < 160) current = t; });
        tabs.forEach(function (t) { t.classList.toggle('on', t === current); });
    }, {passive: true});
    var map = document.getElementById('pdMap');
    if (map && window.L) {
        var pos = [parseFloat(map.dataset.lat), parseFloat(map.dataset.lng)], m = L.map(map, {scrollWheelZoom: false}).setView(pos, 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(m);
        L.marker(pos).addTo(m);
    }
})();
</script>
@endpush
@endsection
