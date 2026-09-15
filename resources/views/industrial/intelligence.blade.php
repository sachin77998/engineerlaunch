<section class="industrial-block industrial-intelligence">

    @php
    $record = $contextCompany ?? $contextArea;
    $isCompanyContext = isset($contextCompany);
    @endphp

    <style>
        .industrial-intelligence {position: relative;overflow: hidden;}
        .industrial-intelligence::before {
            content: "";position: absolute;top: 0;right: 0;width: 260px;height: 260px;
            background: radial-gradient(circle,rgba(37, 99, 235, .08),transparent 70%);
            pointer-events: none;
        }
        .intelligence-header {
            position: relative;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 30px;
            margin-bottom: 24px;
        }

        .intelligence-title-area {
            min-width: 0;
        }

        .intelligence-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 9px;
            color: #2563eb;
            font-size: 10px;
            font-weight: 850;
            letter-spacing: 1.1px;
        }

        .intelligence-eyebrow::before {
            content: "";
            width: 25px;
            height: 2px;
            border-radius: 10px;
            background: #2563eb;
        }

        .intelligence-title {
            margin: 0;
            color: #0f172a;
            font-size: 27px;
            line-height: 1.2;
            font-weight: 850;
            letter-spacing: -.5px;
        }

        .intelligence-location {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 9px;
            color: #64748b;
            font-size: 13px;
        }

        .intelligence-location i {
            color: #2563eb;
        }

        .intelligence-status {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 7px;
        }

        .intelligence-status .industrial-tag {
            margin: 0;
        }

        /* -------------------------------------------------------
           DESCRIPTION
        ------------------------------------------------------- */

        .intelligence-description {
            position: relative;
            max-width: 900px;
            margin: 0 0 22px;
            color: #475569;
            font-size: 14px;
            line-height: 1.75;
        }

        /* -------------------------------------------------------
           SECTORS
        ------------------------------------------------------- */

        .intelligence-sectors {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 24px;
        }

        .intelligence-sector {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 11px;
            border: 1px solid #dbe3ec;
            border-radius: 999px;
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
        }

        .intelligence-sector i {
            color: #2563eb;
            font-size: 9px;
        }

        /* -------------------------------------------------------
           SOURCE
        ------------------------------------------------------- */

        .intelligence-source {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 25px;
            padding: 11px 13px;
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
        }

        .intelligence-source i {
            color: #64748b;
        }

        .intelligence-source a {
            color: #2563eb;
            font-weight: 700;
            text-decoration: none;
        }

        .intelligence-source a:hover {
            text-decoration: underline;
        }

        /* -------------------------------------------------------
           HIRING HERO
        ------------------------------------------------------- */

        .hiring-intelligence {
            position: relative;
            margin: 0 0 25px;
            padding: 22px;
            border-radius: 17px;
            border: 1px solid #bfdbfe;
            background: linear-gradient(135deg,
                    #eff6ff 0%,
                    #f8fafc 55%,
                    #ffffff 100%);
        }

        .hiring-intelligence-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
        }

        .hiring-intelligence h2 {
            margin: 0 0 5px;
            color: #0f172a;
            font-size: 19px;
            font-weight: 850;
        }

        .hiring-intelligence-description {
            margin: 0;
            color: #64748b;
            font-size: 12px;
            line-height: 1.6;
        }

        .hiring-count {
            min-width: 130px;
            padding: 13px 17px;
            border-radius: 13px;
            background: #fff;
            border: 1px solid #dbeafe;
            text-align: center;
        }

        .hiring-count strong {
            display: block;
            color: #1d4ed8;
            font-size: 25px;
            line-height: 1;
            font-weight: 850;
        }

        .hiring-count span {
            display: block;
            margin-top: 5px;
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        /* -------------------------------------------------------
           INTELLIGENCE COLUMNS
        ------------------------------------------------------- */

        .intelligence-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .intelligence-panel {
            min-width: 0;
            padding: 18px;
            border: 1px solid #e2e8f0;
            border-radius: 15px;
            background: #fff;
        }

        .intelligence-panel-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .intelligence-panel-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 14px;
        }

        .intelligence-panel-icon.green {
            background: #ecfdf5;
            color: #047857;
        }

        .intelligence-panel-icon.orange {
            background: #fff7ed;
            color: #c2410c;
        }

        .intelligence-panel-header h3 {
            margin: 0;
            color: #0f172a;
            font-size: 15px;
            font-weight: 800;
        }

        .intelligence-panel-header p {
            margin: 2px 0 0;
            color: #94a3b8;
            font-size: 10px;
        }

        /* -------------------------------------------------------
           DEPARTMENT / ROLE ITEMS
        ------------------------------------------------------- */

        .intelligence-list {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .intelligence-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 11px;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #eef2f7;
        }

        .intelligence-list-item a {
            min-width: 0;
            overflow: hidden;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .intelligence-list-item a:hover {
            color: #2563eb;
        }

        .intelligence-opening-count {
            flex: 0 0 auto;
            padding: 4px 7px;
            border-radius: 7px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: 800;
        }

        /* -------------------------------------------------------
           SKILLS
        ------------------------------------------------------- */

        .intelligence-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .intelligence-skill {
            display: inline-flex;
            align-items: center;
            padding: 7px 9px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
        }

        .intelligence-skill span {
            margin-left: 5px;
            color: #2563eb;
        }

        /* -------------------------------------------------------
           EMPTY
        ------------------------------------------------------- */

        .intelligence-empty {
            margin: 0;
            padding: 12px;
            border-radius: 9px;
            background: #f8fafc;
            color: #94a3b8;
            font-size: 11px;
        }

        /* -------------------------------------------------------
           CAREER LINKS
        ------------------------------------------------------- */

        .intelligence-career-tools {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .intelligence-career-tools a {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 12px;
            border: 1px solid #dbe3ec;
            border-radius: 9px;
            background: #fff;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            transition: all .18s ease;
        }

        .intelligence-career-tools a:hover {
            border-color: #93c5fd;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .intelligence-career-tools i {
            color: #2563eb;
        }

        /* -------------------------------------------------------
           NEARBY AREAS
        ------------------------------------------------------- */

        .nearby-industrial {
            margin-top: 30px;
        }

        .nearby-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 18px;
        }

        .nearby-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 21px;
            font-weight: 850;
        }

        .nearby-header p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 12px;
        }

        .nearby-cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .nearby-card {
            display: block;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 13px;
            background: #fff;
            color: #0f172a;
            text-decoration: none;
            transition: all .18s ease;
        }

        .nearby-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
            transform: translateY(-2px);
        }

        .nearby-card-name {
            display: block;
            margin-bottom: 6px;
            color: #1e293b;
            font-size: 13px;
            font-weight: 800;
            line-height: 1.4;
        }

        .nearby-card-location {
            display: flex;
            align-items: flex-start;
            gap: 5px;
            color: #64748b;
            font-size: 10px;
            line-height: 1.5;
        }

        .nearby-card-location i {
            color: #2563eb;
            margin-top: 2px;
        }

        .nearby-distance {
            display: inline-flex;
            margin-top: 9px;
            padding: 4px 7px;
            border-radius: 7px;
            background: #f1f5f9;
            color: #475569;
            font-size: 10px;
            font-weight: 700;
        }

        @media (max-width: 1000px) {
            .intelligence-grid {
                grid-template-columns: 1fr 1fr;
            }

            .nearby-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .intelligence-header {
                flex-direction: column;
                gap: 12px;
            }

            .intelligence-status {
                justify-content: flex-start;
            }

            .hiring-intelligence-content {
                align-items: flex-start;
                flex-direction: column;
            }

            .hiring-count {
                width: 100%;
            }

            .intelligence-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 500px) {
            .nearby-cards {
                grid-template-columns: 1fr;
            }

            .intelligence-title {
                font-size: 23px;
            }
        }
    </style>


    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="intelligence-header">

        <div class="intelligence-title-area">

            <span class="intelligence-eyebrow">
                INDUSTRIAL INTELLIGENCE
            </span>

            <h1 class="intelligence-title">
                {{ $record->name }}
            </h1>

            <div class="intelligence-location">

                <i class="fas fa-location-dot"></i>

                <span>
                    {{ $contextArea->city ?: $contextArea->district }},
                    {{ $contextArea->state->name }}
                </span>

                @if($record->facility_type)
                <span>&middot;</span>
                <span>{{ $record->facility_type }}</span>
                @endif

                @if($record->last_verified_at)
                <span>&middot;</span>
                <span>
                    Last checked {{ $record->last_verified_at->format('d M Y') }}
                </span>
                @endif

            </div>

        </div>

        <div class="intelligence-status">
            <span class="industrial-tag">{{ $liveCount > 0 ? 'Hiring now' : 'No live openings' }} &middot; {{ $liveCount }} live openings</span>

            @if($record->verification_label)
            <span class="industrial-tag verified">
                {{ $record->verification_label }}
            </span>
            @endif

            @if($isCompanyContext)
            <span class="industrial-tag">
                Company / Plant
            </span>
            @else
            <span class="industrial-tag">
                Industrial Area
            </span>
            @endif

        </div>

    </div>


    {{-- =========================================================
         DESCRIPTION
    ========================================================== --}}

    @if($record->description)

    <p class="intelligence-description">
        {{ $record->description }}
    </p>

    @endif


    {{-- =========================================================
         SECTORS
    ========================================================== --}}

    @if(collect($contextArea->sectors ?? [])->isNotEmpty())

    <div class="intelligence-sectors">

        @foreach($contextArea->sectors as $sector)

        <span class="intelligence-sector">
            <i class="fas fa-circle"></i>
            {{ $sector }}
        </span>

        @endforeach

    </div>

    @endif


    {{-- =========================================================
         SOURCE
    ========================================================== --}}

    @if(
    ($record->source_url && preg_match('~^https?://~i', $record->source_url))
    || $record->source_name
    )

    <div class="intelligence-source">

        <i class="fas fa-circle-check"></i>

        <span>
            Data source:
        </span>

        @if($record->source_url && preg_match('~^https?://~i', $record->source_url))

        <a
            href="{{ $record->source_url }}"
            target="_blank"
            rel="noopener noreferrer">
            {{ $record->source_name ?: 'Verified source' }}
            &nearr;
        </a>

        @else

        <strong>
            {{ $record->source_name }}
        </strong>

        @endif

    </div>

    @endif


    {{-- =========================================================
         HIRING INTELLIGENCE
    ========================================================== --}}

    <div class="hiring-intelligence">

        <div class="hiring-intelligence-content">

            <div>

                <h2>
                    Hiring Intelligence
                </h2>

                <p class="hiring-intelligence-description">
                    Current active and unexpired industrial openings connected
                    to this {{ $isCompanyContext ? 'company' : 'industrial area' }}.
                </p>

            </div>

            <div class="hiring-count">

                <strong>
                    {{ number_format($liveCount) }}
                </strong>

                <span>
                    Live Openings
                </span>

            </div>

        </div>

    </div>


    {{-- =========================================================
         DEPARTMENTS / ROLES / SKILLS
    ========================================================== --}}

    <div class="intelligence-grid">


        {{-- DEPARTMENTS --}}

        <div class="intelligence-panel">

            <div class="intelligence-panel-header">

                <div class="intelligence-panel-icon">
                    <i class="fas fa-sitemap"></i>
                </div>

                <div>
                    <h3>Jobs by department</h3>
                    <p>Where hiring is happening</p>
                </div>

            </div>

            <div class="intelligence-list">

                @forelse($departmentCounts as $count)

                <div class="intelligence-list-item">

                    <a
                        href="{{ route('industrial.index', [
                                'state' => $contextArea->state_id,
                                'area' => $contextArea->id,
                                'company' => $contextCompany?->id,
                                'department' => $count->department_id
                            ]) }}">
                        {{ $count->department->name }}
                    </a>

                    <span class="intelligence-opening-count">
                        {{ number_format($count->openings) }}
                    </span>

                </div>

                @empty

                <p class="intelligence-empty">
                    No departments hiring currently.
                </p>

                @endforelse

            </div>

        </div>


        {{-- JOB ROLES --}}

        <div class="intelligence-panel">

            <div class="intelligence-panel-header">

                <div class="intelligence-panel-icon green">
                    <i class="fas fa-user-gear"></i>
                </div>

                <div>
                    <h3>Job Roles</h3>
                    <p>Roles currently in demand</p>
                </div>

            </div>

            <div class="intelligence-list">

                @forelse($roleCounts as $count)

                <div class="intelligence-list-item">

                    <a
                        href="{{ route('industrial.index', [
                                'state' => $contextArea->state_id,
                                'area' => $contextArea->id,
                                'company' => $contextCompany?->id,
                                'department' => $count->role->department_id,
                                'role' => $count->job_role_id
                            ]) }}">
                        {{ $count->role->name }}
                    </a>

                    <span class="intelligence-opening-count">
                        {{ number_format($count->openings) }}
                    </span>

                </div>

                @empty

                <p class="intelligence-empty">
                    No roles hiring currently.
                </p>

                @endforelse

            </div>

        </div>


        {{-- SKILLS --}}

        <div class="intelligence-panel">

            <div class="intelligence-panel-header">

                <div class="intelligence-panel-icon orange">
                    <i class="fas fa-wrench"></i>
                </div>

                <div>
                    <h3>Popular Skills</h3>
                    <p>Skills appearing in live openings</p>
                </div>

            </div>

            @if(!empty($popularSkills))

            <div class="intelligence-skills">

                @foreach($popularSkills as $skill => $count)

                <span class="intelligence-skill">

                    {{ $skill }}

                    <span>
                        {{ number_format($count) }}
                    </span>

                </span>

                @endforeach

            </div>

            @else

            <p class="intelligence-empty">
                No skills from live openings yet.
            </p>

            @endif

        </div>

    </div>


    {{-- =========================================================
         CAREER TOOLS
    ========================================================== --}}

    <div class="intelligence-career-tools">

        <a href="{{ route('learning.index') }}">
            <i class="fas fa-graduation-cap"></i>
            Learning
        </a>

        <a href="{{ route('practice') }}">
            <i class="fas fa-circle-question"></i>
            Interview Preparation
        </a>

        <a href="{{ route('company.experiences.index') }}">
            <i class="fas fa-comments"></i>
            Company Experiences
        </a>

        <a href="{{ route('resume.builder') }}">
            <i class="fas fa-file-lines"></i>
            Resume Builder
        </a>

    </div>

</section>


{{-- =============================================================
     NEARBY INDUSTRIAL AREAS
============================================================= --}}

@if($nearbyAreas->isNotEmpty())

<section class="industrial-block nearby-industrial">

    <div class="nearby-header">

        <div>

            <h2>
                {{ $nearbyLabel }}
            </h2>

            @if($contextArea->latitude === null || $contextArea->longitude === null)

            <p>
                Distance is unavailable until coordinates are added.
            </p>

            @else

            <p>
                Explore other industrial locations around this area.
            </p>

            @endif

        </div>

    </div>

    <div class="nearby-cards">

        @foreach($nearbyAreas as $nearby)

        <a
            href="{{ route('industrial.show', [
                        $nearby->state->slug,
                        $nearby->slug
                    ]) }}"
            class="nearby-card">

            <span class="nearby-card-name">
                {{ $nearby->name }}
            </span>

            <span class="nearby-card-location">

                <i class="fas fa-location-dot"></i>

                <span>
                    {{ $nearby->city ?: $nearby->district }},
                    {{ $nearby->state->name }}
                </span>

            </span>

            @if(isset($nearby->distance_km))

            <span class="nearby-distance">
                {{ $nearby->distance_km }} km away
            </span>

            @endif

        </a>

        @endforeach

    </div>

</section>

@endif