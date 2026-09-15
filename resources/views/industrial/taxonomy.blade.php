@include('industrial.learning-examples')
@if($cities->isNotEmpty())<section class="industrial-block"><h2>Explore industrial cities</h2><div class="industrial-cards">@foreach($cities as $city=>$cityAreas)<?php $cityArea=$cityAreas->first(); ?><a class="industrial-card" href="{{ route('industrial.index',['state'=>$cityArea->state_id,'location'=>$city]) }}"><?php $visual=$cityAreas->flatMap(fn($a)=>$a->sectorCatalog)->flatMap(fn($s)=>$s->assets)->firstWhere('asset_type','hero'); ?>@if($visual?->url)<img src="{{ $visual->url }}" alt="" loading="lazy" style="width:100%;height:140px;object-fit:cover;border-radius:7px">@endif<h3>{{ $city }}</h3><p>{{ $cityAreas->flatMap(fn($a)=>$a->sectors??[])->unique()->take(5)->join(' / ') }}</p><small>{{ $cityAreas->sum('companies_count') }} companies &middot; {{ $cityAreas->sum('openings_count') }} openings</small></a>@endforeach</div></section>@endif
<section class="industrial-block industry-explore">

    <style>
        .industry-explore {
            overflow: hidden;
        }

        .industry-section-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 30px;
            margin-bottom: 24px;
        }

        .industry-section-heading>div {
            min-width: 0;
        }

        .industry-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 8px;
            color: #2563eb;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .industry-eyebrow::before {
            content: "";
            width: 24px;
            height: 2px;
            background: #2563eb;
            border-radius: 10px;
        }

        .industry-breadcrumb {
            margin: 0 0 7px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .industry-section-heading h2 {
            margin: 0;
            color: #0f172a;
            font-size: 26px;
            line-height: 1.25;
            font-weight: 850;
            letter-spacing: -.5px;
        }

        .industry-section-description {
            max-width: 360px;
            margin: 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.65;
        }

        /* -------------------------------------------------------
           SECTOR CARDS
        ------------------------------------------------------- */

        .industry-tiles {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .industry-tile {
            position: relative;
            overflow: hidden;
            min-height: 165px;
            padding: 0;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #0f172a;
            cursor: pointer;
            text-align: left;
            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease;
        }

        .industry-tile:hover {
            transform: translateY(-4px);
            border-color: #93c5fd;
            box-shadow: 0 15px 35px rgba(15, 23, 42, .15);
        }

        .industry-tile[aria-pressed="true"] {
            border: 2px solid #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
        }

        .industry-tile img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .35s ease;
        }

        .industry-tile:hover img {
            transform: scale(1.05);
        }

        .industry-tile::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg,
                    rgba(15, 23, 42, .03) 20%,
                    rgba(15, 23, 42, .82) 100%);
            pointer-events: none;
        }

        .industry-tile span {
            position: absolute;
            z-index: 2;
            left: 16px;
            right: 16px;
            bottom: 15px;
            color: #fff;
            font-size: 15px;
            line-height: 1.3;
            font-weight: 800;
        }

        .industry-tile-selected {
            position: absolute;
            z-index: 3;
            top: 12px;
            right: 12px;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #2563eb;
            color: #fff;
            font-size: 12px;
        }

        /* -------------------------------------------------------
           SUB-SECTOR
        ------------------------------------------------------- */

        .industry-subsector {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin: 5px 0 16px;
            padding: 15px 17px;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            background: #f8fafc;
        }

        .industry-subsector-label {
            color: #94a3b8;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .industry-subsector-name {
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        /* -------------------------------------------------------
           PROCESS FLOW
        ------------------------------------------------------- */

        .industry-process-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin: 25px 0 13px;
        }

        .industry-process-heading h3 {
            margin: 0;
            color: #0f172a;
            font-size: 17px;
            font-weight: 800;
        }

        .industry-process-heading span {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
        }

        .industry-processes {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin: 0 0 25px;
            padding: 0;
            list-style: none;
        }

        .industry-processes li {
            margin: 0;
            padding: 0;
        }

        .industry-processes button {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 38px;
            padding: 8px 13px;
            border: 1px solid #dbe3ec;
            border-radius: 10px;
            background: #fff;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all .18s ease;
        }

        .industry-processes button::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #cbd5e1;
            transition: background .18s ease;
        }

        .industry-processes button:hover {
            border-color: #93c5fd;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .industry-processes button:hover::before {
            background: #2563eb;
        }

        .industry-processes button[aria-pressed="true"] {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1d4ed8;
            box-shadow: 0 3px 10px rgba(37, 99, 235, .10);
        }

        .industry-processes button[aria-pressed="true"]::before {
            background: #2563eb;
        }

        /* -------------------------------------------------------
           IMAGE GALLERY
        ------------------------------------------------------- */

        .industry-gallery-wrapper {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
        }

        .industry-gallery-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 14px;
        }

        .industry-gallery-heading h3 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .industry-gallery-heading span {
            color: #94a3b8;
            font-size: 11px;
        }

        .industry-gallery {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .industry-gallery a {
            display: block;
            overflow: hidden;
            height: 125px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .industry-gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .3s ease;
        }

        .industry-gallery a:hover img {
            transform: scale(1.06);
        }

        .industry-gallery-note {
            margin: 9px 0 0;
        }

        /* -------------------------------------------------------
           CAREER DICTIONARY
        ------------------------------------------------------- */

        .industry-dictionary {
            margin-top: 26px;
            border: 1px solid #dbe3ec;
            border-radius: 15px;
            background: #f8fafc;
            overflow: hidden;
        }

        .industry-dictionary summary {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 17px 19px;
            color: #0f172a;
            background: #fff;
            cursor: pointer;
            list-style: none;
            font-size: 14px;
            font-weight: 800;
        }

        .industry-dictionary summary::-webkit-details-marker {
            display: none;
        }

        .industry-dictionary summary::after {
            content: "+";
            width: 26px;
            height: 26px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 18px;
            font-weight: 500;
            flex: 0 0 26px;
        }

        .industry-dictionary[open] summary::after {
            content: "−";
        }

        .industry-dictionary-content {
            padding: 0 19px 19px;
        }

        .industry-dictionary-content>p {
            margin: 0 0 15px;
        }

        .industry-roles {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .industry-roles a {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 0;
            padding: 13px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            background: #fff;
            color: #0f172a;
            text-decoration: none;
            transition: all .18s ease;
        }

        .industry-roles a:hover {
            border-color: #93c5fd;
            box-shadow: 0 5px 15px rgba(15, 23, 42, .06);
            transform: translateY(-1px);
        }

        .industry-roles strong {
            color: #1e293b;
            font-size: 13px;
            line-height: 1.35;
        }

        .industry-roles small {
            color: #64748b;
            font-size: 11px;
            line-height: 1.45;
        }

        .industry-dictionary-empty {
            margin: 0;
            padding: 15px;
            border-radius: 10px;
            background: #fff;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 1100px) {
            .industry-tiles {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .industry-gallery {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .industry-roles {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {
            .industry-section-heading {
                align-items: flex-start;
                flex-direction: column;
                gap: 8px;
            }

            .industry-section-description {
                max-width: none;
            }

            .industry-tiles {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .industry-gallery {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .industry-tiles {
                grid-template-columns: 1fr;
            }

            .industry-tile {
                min-height: 150px;
            }

            .industry-gallery {
                grid-template-columns: 1fr 1fr;
            }

            .industry-roles {
                grid-template-columns: 1fr;
            }

            .industry-subsector {
                align-items: flex-start;
                flex-direction: column;
            }

            .industry-dictionary summary {
                padding: 15px;
            }
        }
    </style>


    {{-- =========================================================
         SECTION HEADER
    ========================================================== --}}

    <div class="industry-section-heading">

        <div>

            <span class="industry-eyebrow">
                EXPLORE MANUFACTURING
            </span>

            @if($taxonomy['sector']?->family)
            <p class="industry-breadcrumb">
                {{ $taxonomy['sector']->family }}
                <span>&rarr;</span>
                {{ $taxonomy['sector']->name }}
            </p>
            @endif

            <h2>
                {{ $taxonomy['sector']->name ?? 'Inside Industrial India' }}
            </h2>

        </div>

        <p class="industry-section-description">
            Choose a sector and explore industrial areas, companies, manufacturing
            processes and career opportunities connected to it.
        </p>

    </div>


    {{-- =========================================================
         SECTORS
    ========================================================== --}}

    @if($taxonomy['sectors']->isNotEmpty())

    <div class="industry-tiles">

        @foreach($browseSectors as $sector)

        <button
            type="button"
            class="industry-tile"
            data-sector="{{ $sector->id }}"
            aria-pressed="{{ ($filters['sector'] ?? null) == $sector->id ? 'true' : 'false' }}">

            <?php $tileAsset=$sector->assets->firstWhere('asset_type','hero') ?? $sector->assets->first(); ?>
            @if($tileAsset?->url)<img src="{{ $tileAsset->url }}" alt="{{ $tileAsset->alt_text ?: $sector->name }}" loading="lazy">@endif

            @if(($filters['sector'] ?? null) == $sector->id)
            <span class="industry-tile-selected">
                <i class="fas fa-check"></i>
            </span>
            @endif

            <span>
                {{ $sector->name }}<small style="display:block;font-size:12px;margin-top:8px">{{ $sector->company_count }} companies &middot; {{ $sector->opening_count }} openings</small>
            </span>

        </button>

        @endforeach

    </div>

    @endif


    {{-- =========================================================
         SUB-SECTOR
    ========================================================== --}}

    @if($taxonomy['sector'] && $taxonomy['subOptions']->isNotEmpty())
    <h3>Explore products and specialisations</h3>
    <div class="industry-tiles">
    @foreach($taxonomy['subOptions'] as $subCard)
    <button type="button" class="industry-tile" data-subsector="{{ $subCard['id'] }}" aria-pressed="{{ ($filters['subsector']??null)==$subCard['id']?'true':'false' }}">
      @if($taxonomy['heroUrl'])<img src="{{ $taxonomy['heroUrl'] }}" alt="" loading="lazy">@endif
      <span>{{ $subCard['name'] }}</span>
    </button>
    @endforeach
    </div>
    @endif

    @if($taxonomy['sub'])

    @php
    $selectedSub = $taxonomy['subOptions']->firstWhere('id', $taxonomy['sub']->id);
    @endphp

    @if($selectedSub)

    <div class="industry-subsector">

        <div>
            <div class="industry-subsector-label">
                Selected Sub-Sector
            </div>

            <div class="industry-subsector-name">
                {{ $selectedSub['name'] ?? $taxonomy['sub']->name }}
            </div>
        </div>

        <span class="industrial-meta">
            Sector &rarr; Sub-sector &rarr; Process
        </span>

    </div>

    @endif

    @endif


    {{-- =========================================================
         MANUFACTURING PROCESS FLOW
    ========================================================== --}}

    @if($taxonomy['steps']->isNotEmpty())

    <div class="industry-process-heading">

        <h3>
            Manufacturing Processes
        </h3>

        <span>
            {{ $taxonomy['steps']->count() }} mapped processes
        </span>

    </div>

    <ol
        class="industry-processes"
        aria-label="Manufacturing process sequence">

        @foreach($taxonomy['steps'] as $step)

        <li>

            <button
                type="button"
                data-process="{{ $step->id }}"
                aria-pressed="{{ ($filters['process'] ?? null) == $step->id ? 'true' : 'false' }}">
                {{ $step->name }}
            </button>

        </li>

        @endforeach

    </ol>

    @endif


    {{-- =========================================================
         INDUSTRIAL IMAGERY
    ========================================================== --}}

    @if($taxonomy['gallery']->isNotEmpty())

    <div class="industry-gallery-wrapper">

        <div class="industry-gallery-heading">

            <h3>
                Inside the Industry
            </h3>

            <span>
                {{ $taxonomy['gallery']->count() }} photographs
            </span>

        </div>

        <div class="industry-gallery">

            @foreach($taxonomy['gallery'] as $photo)

            <a
                href="{{ $photo->url }}"
                target="_blank"
                rel="noopener">

                <img
                    src="{{ $photo->url }}"
                    alt="{{ $photo->alt_text ?: $photo->alt }}"
                    loading="lazy">

            </a>

            @endforeach

        </div>

        <p class="industrial-meta industry-gallery-note">
            {{ $taxonomy['theme']->name }}
            imagery &middot;
            Category imagery, not photographs of listed companies. {{ $taxonomy['gallery']->pluck('source_name')->unique()->join(' / ') }}
        </p>

    </div>

    @endif


    {{-- =========================================================
         INDUSTRIAL CAREER DICTIONARY
    ========================================================== --}}

    <details
        class="industry-dictionary"
        @if($taxonomy['process'] || !empty($filters['search']))
        open
        @endif>

        <summary>
            <span>
                Industrial Career Dictionary
                &middot;
                {{ $taxonomy['dictionary']->count() }} roles

                @if($taxonomy['process'])
                / {{ $taxonomy['process']->name }}
                @endif
            </span>
        </summary>

        <div class="industry-dictionary-content">

            <p class="industrial-meta">
                Explore industrial occupations and the departments they belong to.
                These are career terms and role mappings; live vacancies appear
                separately below.
            </p>

            <div class="industry-roles">@forelse($taxonomy['dictionary'] as $role)@include('industrial.career-card')@empty<p>No mapped career terms for this selection yet.</p>@endforelse</div></details>
@if($taxonomy['sector'] && empty($filters['search']))<section class="industrial-block"><h2>What jobs exist in {{ $taxonomy['sector']->name }}?</h2><p class="industrial-meta">Career pathways; employers set their own entry requirements.</p>@foreach($taxonomy['dictionary']->filter(fn($r)=>$r->profile?->career_level)->groupBy(fn($r)=>$r->profile->career_level) as $level=>$roles)<h3>{{ $level }}</h3><div class="industry-pathway">@foreach($roles as $role)<a href="{{ route('industrial.index',['search'=>$role->name]) }}">{{ $role->name }}</a>@endforeach</div>@endforeach</section>@endif
</section>
