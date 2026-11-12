@extends('layouts.site')
@section('title', $p->name . ', ' . ($p->locality ? $p->locality . ', ' : '') . $p->city)
@section('meta_description', \Illuminate\Support\Str::limit(trim(($p->configurationLabel() ? $p->configurationLabel() . ' in ' : '') . $p->name . ', ' . $p->city . '. ' . $p->description), 155))
@section('content')
@include('properties._nav')
@include('properties.city._styles')
@php
    $o = config('properties');
    $PF = \App\Services\Property\PropertyFormat::class;
    $images = $p->images;
    $byKind = $images->groupBy('kind');
    $tiles = collect();
    if ($p->videos->isNotEmpty()) $tiles->push(['label' => 'Videos', 'count' => $p->videos->count(), 'img' => $p->videos->first()->thumbUrl() ?? $images->get(1)?->url(), 'tab' => 'videos', 'play' => true]);
    foreach ($byKind as $kind => $group) $tiles->push(['label' => $o['image_kinds'][$kind] ?? ucfirst($kind), 'count' => $group->count(), 'img' => $group->first()->url(), 'tab' => $kind, 'play' => false]);
    $tiles = $tiles->take(2);
    $groups = $p->units->groupBy('label');
    $faqs = $p->allFaqs();
    $amenities = collect($p->amenities ?? [])->filter(fn ($a) => isset($o['amenities'][$a]))->values();
    $categories = $o['nearby_categories'] ?? [];
    $phone = $p->phone();
    $hl = $p->highlights;
    $nav = array_filter(['floor-plans' => $groups->isNotEmpty() ? 'Floor plans' : null, 'facilities' => $amenities->isNotEmpty() ? 'Facilities' : null,
        'brochure' => $p->brochure_path ? 'Brochure' : null, 'book-visit' => 'Site visit', 'locality' => $locality || $nearby->isNotEmpty() ? 'Explore locality' : null,
        'builder' => $p->builder ? 'Builder' : null, 'faqs' => $faqs ? 'FAQs' : null, 'articles' => $articles->isNotEmpty() ? 'Articles' : null]);
@endphp
@push('styles')<style>
.pj-gal{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:150px 150px;gap:8px;border-radius:14px;overflow:hidden;margin-top:18px}
.pj-gal button{border:0;padding:0;cursor:pointer;background:#e0e7ff center/cover no-repeat;position:relative;color:#fff;text-align:left}
.pj-gal button:first-child{grid-row:span 2}.pj-gal button span{position:absolute;left:12px;right:12px;bottom:10px;display:flex;justify-content:space-between;font-weight:700;text-shadow:0 1px 4px rgba(0,0,0,.6)}
.pj-gal button::after{content:'';position:absolute;inset:0;background:linear-gradient(180deg,transparent 55%,rgba(0,0,0,.55))}.pj-gal span{z-index:1}
.pj-gal .play{position:absolute;inset:0;display:grid;place-items:center;font-size:30px;z-index:1}
.pj-crumb{font-size:13px;color:#6b7280;margin:12px 0}.pj-crumb a{color:#6b7280}
.pj-grid{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:24px;align-items:start}
.pj-title{display:flex;gap:14px;align-items:center;flex-wrap:wrap}.pj-title h1{margin:0;font-size:26px}
.pj-logo{width:84px;height:62px;border:1px solid var(--pr-line);border-radius:8px;background:#fff center/contain no-repeat;display:grid;place-items:center;font-weight:800;color:#3730a3}
.pj-badges{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0}.pj-badges span{font-size:12.5px;border-radius:6px;padding:4px 8px;background:#f1f5f9}.pj-badges .g{background:#dcfce7;color:#166534}
.pj-poss{background:#eff6ff;border-radius:12px;padding:14px 18px;margin:14px 0}.pj-poss small{color:#6b7280;font-weight:700;letter-spacing:.04em}.pj-poss b{display:block;font-size:20px;margin:4px 0 2px}
.pj-price{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:18px}.pj-price b{font-size:28px}
.pj-conf{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(250px,1fr);gap:12px;overflow-x:auto;padding-bottom:6px;margin-top:10px}
.pj-conf div{border:1px solid var(--pr-line);border-radius:12px;overflow:hidden}.pj-conf h4{margin:0;padding:10px 14px;background:#f1f5f9;font-weight:600}.pj-conf p{margin:0;padding:10px 14px;font-size:14px}
.pj-sub{position:sticky;top:0;z-index:20;background:#fff;border-bottom:1px solid var(--pr-line);margin:24px 0 0}
.pj-sub .pr-wrap{display:flex;gap:4px;align-items:center;overflow-x:auto}.pj-sub a.t{padding:14px 12px;text-decoration:none;color:#374151;white-space:nowrap;border-bottom:3px solid transparent}.pj-sub a.t.on{border-bottom-color:#2563eb;color:#111827;font-weight:700}
.pj-sub .act{margin-left:auto;display:flex;gap:8px}
.pj-sec{padding:26px 0;border-bottom:1px solid var(--pr-line);scroll-margin-top:60px}.pj-sec h2{margin:0 0 12px}
.pj-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}.pj-tabs button{border:1px solid var(--pr-line);background:#fff;border-radius:999px;padding:8px 16px;cursor:pointer;font-size:15px}.pj-tabs button.on{background:#e0f2fe;border-color:#7dd3fc;font-weight:700}
.pj-plans{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;background:#f8fafc;border-radius:14px;padding:16px}
.pj-plan{background:#fff;border:1px solid var(--pr-line);border-radius:12px;padding:14px;display:grid;gap:8px;align-content:start}
.pj-plan img{width:100%;height:170px;object-fit:contain;cursor:zoom-in}.pj-plan .noimg{height:170px;display:grid;place-items:center;background:#f1f5f9;border-radius:8px;color:#94a3b8;font-size:40px}
.pj-fac{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px}.pj-fac div{border-radius:10px;padding:12px 8px;text-align:center;font-size:13px;color:#374151}.pj-fac i{display:block;font-style:normal;font-size:30px;margin-bottom:6px}
.pj-adv{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}.pj-adv div{border:1px solid var(--pr-line);border-radius:12px;padding:12px;display:flex;gap:10px;align-items:center}.pj-adv i{font-style:normal;font-size:24px}
.pj-side{position:sticky;top:70px;display:grid;gap:16px}
.pj-why ul{margin:0;padding-left:18px;display:grid;gap:10px}
.pj-faq details{border-bottom:1px solid var(--pr-line);padding:12px 0}.pj-faq summary{cursor:pointer;font-weight:700}
.pj-modal{position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:1000;display:flex;align-items:flex-start;justify-content:center;padding:40px 16px;overflow:auto}.pj-modal[hidden]{display:none}
.pj-modal>div{background:#fff;border-radius:16px;width:min(980px,100%);padding:20px;position:relative}
.pj-modal .x{position:absolute;top:10px;right:14px;border:0;background:none;font-size:28px;cursor:pointer}
.pj-mgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}.pj-mgrid img{width:100%;height:160px;object-fit:cover;border-radius:10px}
.pj-vid{aspect-ratio:16/9;width:100%;border:0;border-radius:10px}
@media(max-width:960px){.pj-grid{grid-template-columns:1fr}.pj-side{position:static}.pj-gal{grid-template-columns:1fr 1fr;grid-template-rows:200px 120px}.pj-gal button:first-child{grid-column:span 2;grid-row:auto}}
</style>@endpush
<div class="pr">
<div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok" style="margin-top:14px">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pr-flash err" style="margin-top:14px">{{ $errors->first() }}</div>@endif
    @if($images->isNotEmpty() || $p->videos->isNotEmpty())
        <div class="pj-gal" style="{{ $tiles->isEmpty() ? 'grid-template-columns:1fr' : '' }}">
            <button type="button" data-gallery-open="all" style="background-image:url('{{ $p->coverUrl() ?? $p->videos->first()?->thumbUrl() }}')"><span><span>All photos{{ $p->videos->isNotEmpty() ? ' & videos' : '' }}</span><span>⧉ {{ $images->count() + $p->videos->count() }}</span></span></button>
            @foreach($tiles as $t)
                <button type="button" data-gallery-open="{{ $t['tab'] }}" style="background-image:url('{{ $t['img'] }}')">@if($t['play'])<i class="play">▶</i>@endif<span><span>{{ $t['label'] }}</span><span>⧉ {{ $t['count'] }}</span></span></button>
            @endforeach
        </div>
    @endif
    <div class="pj-crumb"><a href="{{ route('home') }}">Home</a> › <a href="{{ route('projects.index', $p->city_slug) }}">Projects in {{ $p->city }}</a>@if($p->locality) › <a href="{{ route('projects.index', [$p->city_slug, 'q' => $p->locality]) }}">{{ $p->locality }}</a>@endif › {{ $p->name }}</div>

    <div class="pj-grid">
        <main>
            <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;align-items:flex-start">
                <div class="pj-title">
                    @php($logo = $p->logoUrl() ?? $p->builder?->logoUrl())
                    <span class="pj-logo" @if($logo) style="background-image:url('{{ $logo }}')" @endif>@unless($logo){{ $p->builder?->initials() ?? mb_substr($p->name, 0, 2) }}@endunless</span>
                    <div><h1>{{ $p->name }}</h1><span class="pr-mute">{{ collect([$p->locality, $p->city])->filter()->implode(', ') }}</span></div>
                    @auth<form method="post" action="{{ route('projects.shortlist', $p) }}">@csrf<button class="cp-heart {{ $shortlisted ? 'on' : '' }}" style="border:1px solid var(--pr-line)" aria-pressed="{{ $shortlisted ? 'true' : 'false' }}" title="Shortlist">{{ $shortlisted ? '♥' : '♡' }}</button></form>
                    @else<a class="cp-heart" style="border:1px solid var(--pr-line)" href="{{ route('login') }}" title="Log in to shortlist">♡</a>@endauth
                </div>
                @include('properties.projects._number', ['big' => true])
            </div>
            <div class="pj-badges">
                @if($p->rera_number)<span class="g" title="RERA {{ $p->rera_number }}">✓ RERA</span>@endif
                @if($p->no_brokerage)<span class="g">No brokerage</span>@endif
                @if($p->units->whereNotNull('floor_plan_path')->isNotEmpty())<span>▦ Floor plans available</span>@endif
                @if($amenities->count())<span>+{{ $amenities->count() }} facilities</span>@endif
                @if($p->videos->isNotEmpty())<span>▶ {{ $p->videos->count() }} {{ $p->videos->count() === 1 ? 'video' : 'videos' }}</span>@endif
            </div>
            <div class="pj-poss"><small>POSSESSION STATUS</small><b>{{ $p->status === 'ready' ? 'Ready to move' : ($p->possession_on ? 'Possession in ' . $p->possession_on->format('M Y') : $p->statusLabel()) }}</b><span class="pr-mute">{{ $p->statusLabel() }}@if($p->launched_on) · launched {{ $p->launched_on->format('M Y') }}@endif</span></div>

            <div class="pj-price">
                <div><b>{{ $p->priceRange() ?? 'Price on request' }}</b>@if($p->charges->isNotEmpty()) <a href="#charges" style="font-weight:600">+ Charges</a>@endif<br><small class="pr-mute">PRICE RANGE</small></div>
                @if($p->brochure_path)<a class="pr-btn ghost" href="{{ auth()->check() ? route('projects.brochure', $p) : route('login') }}">⬇ Download brochure</a>@endif
            </div>
            @if($groups->isNotEmpty())
                <h3 style="margin:18px 0 0">{{ $p->configurationLabel() ?? $groups->keys()->implode(', ') }}</h3>
                <div class="pj-conf">
                    @foreach($groups as $label => $units)
                        @php($priced = $units->whereNotNull('price'))
                        <div><h4>{{ $label }}</h4>
                            <p><small class="pr-mute">{{ $units->first()->areaTypeLabel() }}</small><br>{{ number_format($units->min('area_sqft')) }}@if($units->max('area_sqft') != $units->min('area_sqft')) - {{ number_format($units->max('area_sqft')) }}@endif sqft
                                <small class="pr-mute">({{ number_format($units->min('area_sqft') / 10.7639, 2) }}@if($units->max('area_sqft') != $units->min('area_sqft')) - {{ number_format($units->max('area_sqft') / 10.7639, 2) }}@endif sqm)</small></p>
                            <p style="border-top:1px solid var(--pr-line)"><b>{{ $priced->isEmpty() ? 'Price on request' : collect([$PF::short($priced->min('price')), $priced->max('price') != $priced->min('price') ? $PF::short($priced->max('price')) : null])->filter()->implode(' - ') }}</b></p></div>
                    @endforeach
                </div>
            @endif
            @if($p->charges->isNotEmpty())
                <details id="charges" style="margin-top:12px"><summary style="cursor:pointer;font-weight:700">Other charges</summary>
                    <div class="cp-box" style="margin-top:8px;display:grid;gap:6px">@foreach($p->charges as $c)<div style="display:flex;justify-content:space-between;gap:10px"><span>{{ $c->name }}@if($c->note) <small class="pr-mute">({{ $c->note }})</small>@endif</span><b>{{ $c->amount !== null ? $PF::full($c->amount) : '—' }}</b></div>@endforeach</div></details>
            @endif
        </main>
        <aside class="pj-side">
            @if($hl->isNotEmpty())
                <div class="cp-box pj-why"><h3 style="margin:0 0 12px">✨ Highlights of {{ $p->name }}</h3>
                    <ul>@foreach($hl as $h)<li @if($loop->index >= 3) hidden data-more-hl @endif>{{ $h->text }}</li>@endforeach</ul>
                    @if($hl->count() > 3)<button type="button" class="pr-btn ghost sm" style="margin-top:10px" onclick="document.querySelectorAll('[data-more-hl]').forEach(function(e){e.hidden=false});this.remove()">View {{ $hl->count() - 3 }} more →</button>@endif
                </div>
            @endif
            @if($p->builder)
                <div class="cp-box" style="display:grid;gap:10px">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center"><div><small class="pr-mute" style="font-weight:700;letter-spacing:.05em">DEVELOPED BY</small><br><b style="font-size:17px">{{ $p->builder->name }}</b></div>
                        <span class="cp-logo" style="border-radius:8px;width:80px">@if($p->builder->logoUrl())<img src="{{ $p->builder->logoUrl() }}" alt="">@else{{ $p->builder->initials() }}@endif</span></div>
                    <button type="button" class="pr-btn ghost" data-enquire="contact">Contact builder</button>
                </div>
            @endif
            @include('properties.visits._book', ['type' => 'project', 'slug' => $p->slug])
            @if($p->liveOffers->isNotEmpty())
                <div class="cp-box"><b>🎁 Offers</b>@foreach($p->liveOffers as $offer)<div style="margin-top:8px;font-size:14px"><b>{{ $offer->title }}</b>@if($offer->details) — {{ $offer->details }}@endif @if($offer->valid_until)<small class="pr-mute">(till {{ $offer->valid_until->format('d M Y') }})</small>@endif</div>@endforeach</div>
            @endif
        </aside>
    </div>
</div>

@if($nav)
    <nav class="pj-sub" aria-label="Sections"><div class="pr-wrap">
        @foreach($nav as $id => $label)<a class="t" href="#{{ $id }}" data-spy="{{ $id }}">{{ $label }}</a>@endforeach
        <span class="act">@if($p->brochure_path)<a class="pr-btn ghost sm" href="{{ auth()->check() ? route('projects.brochure', $p) : route('login') }}">⬇ Brochure</a>@endif @include('properties.projects._number', ['big' => false])</span>
    </div></nav>
@endif

<div class="pr-wrap"><div class="pj-grid"><main>
    @if($groups->isNotEmpty())
        <section class="pj-sec" id="floor-plans"><h2>Floor plans &amp; pricing</h2>
            <div class="pj-tabs" data-tabs>@foreach($groups->keys() as $i => $label)<button type="button" data-tab="u{{ $i }}" class="{{ $i === 0 ? 'on' : '' }}">{{ $label }}</button>@endforeach</div>
            @foreach($groups->values() as $i => $units)
                <div data-pane="u{{ $i }}" @if($i > 0) hidden @endif>
                    <p class="pr-mute" style="margin:0 0 8px">{{ $units->count() }} {{ $units->count() === 1 ? 'floor plan' : 'floor plans' }} available</p>
                    <div class="pj-plans">
                        @foreach($units as $u)
                            <div class="pj-plan">
                                <b style="font-size:17px">{{ number_format($u->area_sqft) }} sqft <span class="pr-mute" style="font-weight:500">({{ number_format($u->area_sqft / 10.7639, 2) }} sqm)</span></b>
                                <small class="pr-mute">{{ $u->areaTypeLabel() }}@if($u->bhk) | {{ $o['bhk'][$u->bhk] ?? $u->bhk }}@endif @if($u->bathrooms) | {{ $u->bathrooms }} bath @endif @if($u->balconies) | {{ $u->balconies }} balcony @endif</small>
                                @if($u->floorPlanUrl())<a href="{{ $u->floorPlanUrl() }}" target="_blank"><img src="{{ $u->floorPlanUrl() }}" alt="Floor plan {{ $u->label }}, {{ number_format($u->area_sqft) }} sqft" loading="lazy"></a>@else<div class="noimg">▦</div>@endif
                                <b style="font-size:19px">{{ $u->priceLabel() }}</b>
                                @php($poss = $u->possession_on ?? $p->possession_on)
                                @if($poss || $p->status === 'ready')<div style="background:#f8fafc;border-radius:8px;padding:8px 10px;font-size:13.5px"><b>Possession: {{ $p->status === 'ready' ? 'Ready' : $poss->format('M Y') }}</b><br><span class="pr-mute">{{ $p->statusLabel() }}</span></div>@endif
                                <button type="button" class="pr-btn ghost sm" data-enquire="callback" data-unit="{{ $u->id }}" data-unit-label="{{ $u->label }}, {{ number_format($u->area_sqft) }} sqft">Request callback</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    @if($amenities->isNotEmpty())
        <section class="pj-sec" id="facilities"><h2>Amenities</h2>
            @if($p->description)<p class="pr-mute" style="margin-top:0">{{ \Illuminate\Support\Str::limit($p->description, 160) }}</p>@endif
            <div class="pj-fac">@foreach($amenities as $i => $a)<div style="background:{{ ['#fff7ed', '#eff6ff'][$i % 2] }}"><i>{{ $o['amenity_icons'][$a] ?? '✓' }}</i>{{ $o['amenities'][$a] }}</div>@endforeach</div>
            @if($byKind->has('amenity'))<button type="button" class="pr-btn ghost sm" style="margin-top:14px" data-gallery-open="amenity">Amenity photos →</button>@endif
        </section>
    @endif

    @if($p->description)<section class="pj-sec"><h2>About {{ $p->name }}</h2><p style="white-space:pre-line;margin:0">{{ $p->description }}</p></section>@endif

    <section class="pj-sec"><h2>Project facts</h2>
        <div class="cp-facts">
            @foreach(array_filter([
                'Status' => $p->statusLabel(), 'Possession' => $p->possessionLabel(), 'Launched' => $p->launched_on?->format('M Y'),
                'Units' => $p->total_units ? number_format($p->total_units) : null, 'Towers' => $p->towers, 'Land' => $p->land_acres ? $p->land_acres . ' acres' : null,
                'RERA number' => $p->rera_number, 'Segment' => ucfirst($p->segment),
            ]) as $label => $value)<div><small>{{ $label }}</small><b>{{ $value }}</b></div>@endforeach
        </div>
        @if($p->rera_number && $p->rera_website)<p class="pr-mute" style="font-size:13px">Verify the registration on <a href="{{ $p->rera_website }}" target="_blank" rel="noopener nofollow">the state RERA website ↗</a>.</p>@endif
    </section>

    @if($p->brochure_path)
        <section class="pj-sec" id="brochure"><h2>Brochure</h2>
            <div class="cp-box" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap"><span>📄 {{ $p->name }} brochure (PDF)</span>
                <a class="pr-btn" href="{{ auth()->check() ? route('projects.brochure', $p) : route('login') }}">{{ auth()->check() ? 'Download' : 'Log in to download' }}</a></div>
        </section>
    @endif

    @if($locality || $nearby->isNotEmpty() || ($p->latitude && $p->longitude))
        <section class="pj-sec" id="locality"><h2>What's nearby</h2>
            @if($nearby->isNotEmpty())
                <div class="pj-adv">@foreach($nearby->take(12) as $pl)<div><i>{{ $categories[$pl->category][1] ?? '📍' }}</i><span><b>{{ $pl->name }}</b><br><small class="pr-mute">{{ $categories[$pl->category][0] ?? $pl->category }} · {{ $pl->distanceLabel() }}</small></span></div>@endforeach</div>
                <small class="pr-mute">Straight-line distances. Map data © OpenStreetMap contributors.</small>
            @endif
            @if($p->latitude && $p->longitude)
                <iframe title="Map" loading="lazy" style="width:100%;height:300px;border:0;border-radius:14px;margin-top:14px" src="https://www.openstreetmap.org/export/embed.html?bbox={{ $p->longitude - 0.01 }},{{ $p->latitude - 0.006 }},{{ $p->longitude + 0.01 }},{{ $p->latitude + 0.006 }}&amp;layer=mapnik&amp;marker={{ $p->latitude }},{{ $p->longitude }}"></iframe>
            @endif
            @if($locality)
                <h3 style="margin:20px 0 10px">Explore {{ $locality['name'] }}</h3>
                <div class="cp-facts">
                    @if($locality['summary'])<div><small>Locality rating</small><b>★ {{ $locality['summary']['average'] ?? $locality['summary']['avg'] ?? '' }}/5</b></div>@endif
                    @if($locality['psf'])<div><small>Asking price</small><b>₹{{ $PF::indian($locality['psf']) }}/sq ft</b></div>@endif
                    <div><small>Live listings</small><b>{{ number_format($locality['listings']) }}</b></div>
                </div>
                <p style="display:flex;gap:10px;flex-wrap:wrap"><a class="pr-btn ghost sm" href="{{ $locality['url'] }}">Locality reviews</a><a class="pr-btn ghost sm" href="{{ $locality['buy'] }}">Buy in {{ $locality['name'] }}</a>@if($locality['rent'])<a class="pr-btn ghost sm" href="{{ $locality['rent_url'] }}">Rent in {{ $locality['name'] }}</a>@endif</p>
            @endif
        </section>
    @endif

    @if($listings->isNotEmpty())
        <section class="pj-sec"><h2>Resale &amp; rental units in {{ $p->name }}</h2>
            @php($slot = '')
            @foreach($listings as $l)
                @php($slot .= view('properties.city._listing', ['p' => $l])->render())
            @endforeach
            @include('properties.city._rail', ['slot' => $slot])
        </section>
    @endif

    @if($p->builder)
        @php($b = $p->builder)
        <section class="pj-sec" id="builder"><h2>About the builder</h2>
            <div style="display:flex;gap:16px;align-items:flex-start">
                <span class="cp-logo" style="width:72px;height:72px">@if($b->logoUrl())<img src="{{ $b->logoUrl() }}" alt="">@else{{ $b->initials() }}@endif</span>
                <div><b style="font-size:18px">{{ $b->name }}</b><br><span class="pr-mute">{{ $b->projects_count }} {{ $b->projects_count === 1 ? 'project' : 'projects' }}@if($b->established_year) · since {{ $b->established_year }}@endif</span>
                    @if($b->shortDescription())<p style="margin:8px 0">{{ $b->shortDescription() }}</p>@endif
                    <a href="{{ route('builders.show', $b) }}">All projects by {{ $b->name }} →</a></div>
            </div>
        </section>
    @endif

    @if($faqs)
        <section class="pj-sec pj-faq" id="faqs"><h2>Frequently asked questions</h2>
            @foreach($faqs as [$q, $a])<details @if($loop->first) open @endif><summary>{{ $q }}</summary><p style="margin:8px 0 0;white-space:pre-line">{{ $a }}</p></details>@endforeach
        </section>
    @endif

    @if($articles->isNotEmpty())
        <section class="pj-sec" id="articles"><h2>Articles</h2>
            @php($slot = '')
            @foreach($articles as $a)
                @php($slot .= view('properties.city._article', ['a' => $a])->render())
            @endforeach
            @include('properties.city._rail', ['slot' => $slot])
        </section>
    @endif

    @if($more->isNotEmpty())
        <section class="pj-sec"><h2>More projects in {{ $p->city }}</h2>
            @php($slot = '')
            @foreach($more as $pj)
                @php($slot .= view('properties.city._project', ['pj' => $pj])->render())
            @endforeach
            @include('properties.city._rail', ['slot' => $slot])
        </section>
    @endif
</main><aside></aside></div></div>
</div>

{{-- Gallery: photos by kind and videos --}}
<div class="pj-modal" id="pjGallery" hidden role="dialog" aria-modal="true" aria-label="Photos and videos"><div>
    <button type="button" class="x" data-close aria-label="Close">×</button>
    <h2 style="margin-top:0">{{ $p->name }} — photos{{ $p->videos->isNotEmpty() ? ' & videos' : '' }}</h2>
    <div class="pj-tabs">
        <button type="button" data-gtab="all" class="on">All ({{ $images->count() + $p->videos->count() }})</button>
        @if($p->videos->isNotEmpty())<button type="button" data-gtab="videos">Videos ({{ $p->videos->count() }})</button>@endif
        @foreach($byKind as $kind => $group)<button type="button" data-gtab="{{ $kind }}">{{ $o['image_kinds'][$kind] ?? ucfirst($kind) }} ({{ $group->count() }})</button>@endforeach
    </div>
    <div class="pj-mgrid">
        @foreach($p->videos as $v)
            @if($v->embedUrl())<div data-gkind="videos" style="grid-column:span 2"><iframe class="pj-vid" data-src="{{ $v->embedUrl() }}" title="{{ $v->title ?: 'Project video' }}" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe>@if($v->title)<small>{{ $v->title }}</small>@endif</div>@endif
        @endforeach
        @foreach($images as $img)<a data-gkind="{{ $img->kind }}" href="{{ $img->url() }}" target="_blank"><img src="{{ $img->url() }}" alt="{{ $img->caption ?: $img->kindLabel() }}" loading="lazy"><small>{{ $img->caption ?: $img->kindLabel() }}</small></a>@endforeach
    </div>
</div></div>

{{-- Callback / contact builder --}}
<div class="pj-modal" id="pjEnquire" hidden role="dialog" aria-modal="true" aria-labelledby="pjEnqTitle"><div style="width:min(460px,100%)">
    <button type="button" class="x" data-close aria-label="Close">×</button>
    <h2 id="pjEnqTitle" style="margin-top:0">Contact builder</h2>
    <p class="pr-mute" data-enq-unit hidden></p>
    <form method="post" action="{{ route('projects.enquire', $p) }}" style="display:grid;gap:10px">@csrf
        <input type="hidden" name="kind" value="contact"><input type="hidden" name="unit_id" value="">
        @guest
            <input name="name" required maxlength="120" placeholder="Your name" aria-label="Your name">
            <input name="phone" required maxlength="20" inputmode="tel" placeholder="Mobile number" aria-label="Mobile number">
            <input name="email" type="email" maxlength="191" placeholder="Email (optional)" aria-label="Email">
        @endguest
        <textarea name="message" rows="3" maxlength="1000" placeholder="I am interested in {{ $p->name }}." aria-label="Message"></textarea>
        <button class="pr-btn">Send</button>
        <small class="pr-mute">Your details go to {{ $p->builder?->name ?? 'the builder' }} for this enquiry only.</small>
    </form>
</div></div>

@push('scripts')<script>
(function () {
    function modal(id) { return document.getElementById(id); }
    document.querySelectorAll('.pj-modal').forEach(function (m) {
        m.addEventListener('click', function (e) { if (e.target === m || e.target.hasAttribute('data-close')) { m.hidden = true; m.querySelectorAll('iframe[src]').forEach(function (f) { f.removeAttribute('src'); }); } });
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') document.querySelectorAll('.pj-modal').forEach(function (m) { m.hidden = true; }); });
    var g = modal('pjGallery');
    function gtab(kind) {
        g.querySelectorAll('[data-gtab]').forEach(function (b) { b.classList.toggle('on', b.dataset.gtab === kind); });
        g.querySelectorAll('[data-gkind]').forEach(function (el) { el.hidden = kind !== 'all' && el.dataset.gkind !== kind; });
        g.querySelectorAll('iframe[data-src]').forEach(function (f) { if (!f.closest('[hidden]') && !f.getAttribute('src')) f.src = f.dataset.src; });
    }
    document.querySelectorAll('[data-gallery-open]').forEach(function (b) { b.addEventListener('click', function () { g.hidden = false; gtab(b.dataset.galleryOpen); }); });
    g.querySelectorAll('[data-gtab]').forEach(function (b) { b.addEventListener('click', function () { gtab(b.dataset.gtab); }); });
    var e = modal('pjEnquire');
    document.querySelectorAll('[data-enquire]').forEach(function (b) { b.addEventListener('click', function () {
        var cb = b.dataset.enquire === 'callback';
        e.querySelector('[name=kind]').value = b.dataset.enquire; e.querySelector('[name=unit_id]').value = b.dataset.unit || '';
        e.querySelector('#pjEnqTitle').textContent = cb ? 'Request a callback' : 'Contact builder';
        var u = e.querySelector('[data-enq-unit]'); u.hidden = !b.dataset.unitLabel; u.textContent = b.dataset.unitLabel ? 'For: ' + b.dataset.unitLabel : '';
        e.hidden = false; var f = e.querySelector('input:not([type=hidden]),textarea'); if (f) f.focus();
    }); });
    document.querySelectorAll('[data-reveal]').forEach(function (b) { b.addEventListener('click', function () {
        b.disabled = true;
        fetch(b.dataset.reveal, {method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', Accept: 'application/json'}}).then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
            .then(function (j) { document.querySelectorAll('[data-reveal]').forEach(function (x) { var a = document.createElement('a'); a.className = x.className; a.href = 'tel:' + j.phone.replace(/[^\d+]/g, ''); a.textContent = '📞 ' + j.phone; x.replaceWith(a); }); })
            .catch(function () { b.disabled = false; b.textContent = 'Try again'; });
    }); });
    document.querySelectorAll('[data-rail]').forEach(function (wrap) {
        var rail = wrap.querySelector('.cp-rail');
        wrap.querySelectorAll('[data-dir]').forEach(function (b) { b.addEventListener('click', function () { rail.scrollBy({left: b.dataset.dir * rail.clientWidth * 0.8, behavior: 'smooth'}); }); });
    });
    document.querySelectorAll('[data-tabs]').forEach(function (box) {
        var panes = box.parentNode.querySelectorAll('[data-pane]');
        box.querySelectorAll('[data-tab]').forEach(function (t) { t.addEventListener('click', function () {
            box.querySelectorAll('[data-tab]').forEach(function (x) { x.classList.toggle('on', x === t); });
            panes.forEach(function (p) { p.hidden = p.dataset.pane !== t.dataset.tab; });
        }); });
    });
    // Highlight the section in view in the sticky sub-navigation.
    var links = document.querySelectorAll('[data-spy]');
    if (links.length && 'IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) { entries.forEach(function (en) { if (en.isIntersecting) links.forEach(function (l) { l.classList.toggle('on', l.dataset.spy === en.target.id); }); }); }, {rootMargin: '-40% 0px -55% 0px'});
        links.forEach(function (l) { var s = document.getElementById(l.dataset.spy); if (s) io.observe(s); });
    }
})();
</script>@endpush
@endsection
