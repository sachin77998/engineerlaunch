@if(isset($contextArea))
@include('industrial.intelligence')
@endif

<style>
    .industrial-results {
        display: flex;
        flex-direction: column;
        gap: 32px;
    }

    .industrial-block {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 20px;
        padding: 26px;
        box-shadow: 0 8px 30px rgba(15, 23, 42, .05);
    }

    .industrial-section-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 22px;
    }

    .industrial-section-title {
        margin: 0;
        color: #0f172a;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -.3px;
    }

    .industrial-section-title small {
        color: #64748b;
        font-size: 14px;
        font-weight: 600;
        margin-left: 6px;
    }

    .industrial-section-description {
        margin: 7px 0 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
    }

    .industrial-section-link {
        color: #2563eb;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
    }

    .industrial-section-link:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }

    .industrial-cards {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    .industrial-card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        min-height: 245px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .industrial-card:hover {
        transform: translateY(-3px);
        border-color: #bfdbfe;
        box-shadow: 0 14px 35px rgba(15, 23, 42, .09);
    }

    .industrial-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 15px;
    }

    .industrial-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eff6ff;
        color: #2563eb;
        font-size: 18px;
        flex: 0 0 42px;
    }

    .industrial-card-icon.company {
        background: #f0fdf4;
        color: #15803d;
    }

    .industrial-card-icon.job {
        background: #fff7ed;
        color: #c2410c;
    }

    .industrial-meta {
        color: #64748b;
        font-size: 12px;
        line-height: 1.55;
    }

    .industrial-location {
        display: flex;
        align-items: flex-start;
        gap: 6px;
        margin-bottom: 10px;
    }

    .industrial-location i {
        color: #2563eb;
        margin-top: 2px;
    }

    .industrial-card h3 {
        margin: 0 0 12px;
        font-size: 18px;
        line-height: 1.35;
        font-weight: 800;
        color: #0f172a;
    }

    .industrial-card h3 a {
        color: inherit;
        text-decoration: none;
    }

    .industrial-card h3 a:hover {
        color: #2563eb;
    }

    .industrial-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
        margin-bottom: 14px;
    }

    .industrial-tag {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        padding: 5px 9px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
    }

    .industrial-tag.verified {
        background: #ecfdf5;
        color: #047857;
        border-color: #a7f3d0;
    }

    .industrial-tag.jobs {
        background: #eff6ff;
        color: #1d4ed8;
        border-color: #bfdbfe;
    }

    .industrial-card-description {
        color: #475569;
        font-size: 13px;
        line-height: 1.65;
        margin: 0 0 16px;
    }

    .industrial-card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: auto;
        padding-top: 15px;
        border-top: 1px solid #e2e8f0;
    }

    .industrial-stat {
        color: #475569;
        font-size: 12px;
        font-weight: 600;
    }

    .industrial-stat strong {
        color: #0f172a;
        font-size: 14px;
    }

    .industrial-arrow {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #2563eb;
        text-decoration: none;
        transition: .2s ease;
    }

    .industrial-arrow:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }

    .industrial-company-name {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    .industrial-company-name i {
        color: #15803d;
        font-size: 13px;
    }

    .industrial-plant {
        color: #475569;
        font-size: 13px;
        margin: -3px 0 12px;
    }

    .industrial-job-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 8px;
    }

    .industrial-job-company {
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .industrial-job-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin: 12px 0 14px;
    }

    .industrial-job-detail {
        padding: 9px 10px;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .industrial-job-detail-label {
        display: block;
        color: #94a3b8;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .industrial-job-detail-value {
        display: block;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
    }

    .industrial-deadline {
        color: #b45309;
        font-size: 11px;
        font-weight: 700;
        margin-top: 8px;
    }

    .industrial-empty {
        grid-column: 1 / -1;
        margin: 0;
        padding: 35px 20px;
        text-align: center;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        background: #f8fafc;
        color: #64748b;
        font-size: 14px;
    }

    .industrial-empty strong {
        display: block;
        color: #334155;
        font-size: 16px;
        margin-bottom: 5px;
    }

    .industrial-count-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 11px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
    }

    .industrial-count-pill i {
        color: #2563eb;
    }

    .industrial-source {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: #2563eb;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
    }

    .industrial-source:hover {
        text-decoration: underline;
    }

    @media (max-width: 1100px) {
        .industrial-cards {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .industrial-block {
            padding: 18px;
            border-radius: 16px;
        }

        .industrial-section-head {
            align-items: flex-start;
            flex-direction: column;
        }

        .industrial-cards {
            grid-template-columns: 1fr;
        }

        .industrial-card {
            min-height: auto;
        }
    }
</style>

<div class="industrial-results">

    {{-- =========================================================
         INDUSTRIAL AREAS
    ========================================================== --}}
    <section class="industrial-block">

        <div class="industrial-section-head">
            <div>
                <h2 class="industrial-section-title">
                    Industrial Areas
                    <small>({{ number_format($areas->total()) }})</small>
                </h2>

                <p class="industrial-section-description">
                    Explore industrial areas, manufacturing clusters and industrial locations across India.
                </p>
            </div>

            <span class="industrial-count-pill">
                <i class="fas fa-industry"></i>
                Industrial Directory
            </span>
        </div>

        <div class="industrial-cards">

            @forelse($areas as $area)

            <article class="industrial-card">

                <div class="industrial-card-top">
                    <div class="industrial-card-icon">
                        <i class="fas fa-industry"></i>
                    </div>

                    @if($area->verification_label)
                    <span class="industrial-tag verified">
                        {{ $area->verification_label }}
                    </span>
                    @endif
                </div>

                <div class="industrial-location industrial-meta">
                    <i class="fas fa-location-dot"></i>

                    <span>
                        {{ $area->state->name }}
                        @if($area->city || $area->district)
                        / {{ $area->city ?: $area->district }}
                        @endif
                    </span>
                </div>

                <h3>
                    <a href="{{ route('industrial.show', [$area->state->slug, $area->slug]) }}">
                        {{ $area->name }}
                    </a>
                </h3>

                <div class="industrial-tags">

                    @if($area->area_type)
                    <span class="industrial-tag">
                        {{ str_replace('_', ' ', $area->area_type) }}
                    </span>
                    @endif

                    @foreach($area->sectors ?? [] as $sector)
                    <span class="industrial-tag">
                        {{ $sector }}
                    </span>
                    @endforeach

                </div>

                @if($area->description)
                <p class="industrial-card-description">
                    {{ \Illuminate\Support\Str::limit($area->description, 180) }}
                </p>
                @endif

                <div class="industrial-card-footer">

                    <div class="industrial-stat">
                        <strong>{{ number_format($area->companies_count) }}</strong>
                        companies / plants
                        <br>

                        <strong>{{ number_format($area->live_jobs_count) }}</strong>
                        live openings
                    </div>

                    <a
                        href="{{ route('industrial.show', [$area->state->slug, $area->slug]) }}"
                        class="industrial-arrow"
                        aria-label="View {{ $area->name }}">
                        <i class="fas fa-arrow-right"></i>
                    </a>

                </div>

                @if($area->source_url && preg_match('~^https?://~i', $area->source_url))
                <div style="margin-top:12px;">
                    <a
                        href="{{ $area->source_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="industrial-source">
                        <i class="fas fa-shield-check"></i>
                        {{ $area->source_name ?: 'Area data source' }}
                        <span>&nearr;</span>
                    </a>
                </div>
                @endif

            </article>

            @empty

            <div class="industrial-empty">
                <strong>No industrial areas found</strong>
                No industrial areas match your current filters.
            </div>

            @endforelse

        </div>

        @include('industrial.pagination', [
        'paginator' => $areas,
        'label' => 'Industrial areas'
        ])

    </section>


    {{-- =========================================================
         COMPANIES / PLANTS
    ========================================================== --}}
    <section class="industrial-block">

        <div class="industrial-section-head">
            <div>
                <h2 class="industrial-section-title">
                    Companies & Plants
                    <small>({{ number_format($companies->total()) }})</small>
                </h2>

                <p class="industrial-section-description">
                    Discover companies and manufacturing facilities operating inside these industrial locations.
                </p>
            </div>

            <span class="industrial-count-pill">
                <i class="fas fa-building"></i>
                Company Directory
            </span>
        </div>

        <div class="industrial-cards">

            @forelse($companies as $company)

            <article class="industrial-card">

                <div class="industrial-card-top">

                    <div class="industrial-card-icon company">
                        <i class="fas fa-building"></i>
                    </div>

                    @if($company->is_verified)
                    <span class="industrial-tag verified">
                        {{ $company->verification_label }}
                    </span>
                    @endif

                </div>

                <div class="industrial-location industrial-meta">

                    <i class="fas fa-location-dot"></i>

                    <span>
                        {{ $company->area->state->name }}
                        /
                        {{ $company->area->city ?: $company->area->district }}
                        /
                        {{ $company->area->name }}
                    </span>

                </div>

                <h3 class="industrial-company-name">
                    <a href="{{ route('industrial.company', [
                            $company->area->state->slug,
                            $company->area->slug,
                            $company->slug
                        ]) }}">
                        {{ $company->name }}
                    </a>
                </h3>

                @if($company->plant_name)
                <p class="industrial-plant">
                    <i class="fas fa-warehouse"></i>
                    {{ $company->plant_name }}
                </p>
                @endif

                <div class="industrial-tags">

                    @if($company->facility_type)
                    <span class="industrial-tag">
                        {{ $company->facility_type }}
                    </span>
                    @endif

                    <span class="industrial-tag">
                        {{ $company->sectors->pluck('name')->join(' / ') ?: $company->industry ?: $company->sector ?: 'Industry not specified' }}
                    </span>

                </div>

                @if($company->description)
                <p class="industrial-card-description">
                    {{ \Illuminate\Support\Str::limit($company->description, 160) }}
                </p>
                @endif

                <div class="industrial-card-footer">

                    <div class="industrial-stat">
                        <strong>{{ number_format($company->live_jobs_count) }}</strong>
                        live openings
                    </div>

                    <a
                        href="{{ route('industrial.company', [
                                $company->area->state->slug,
                                $company->area->slug,
                                $company->slug
                            ]) }}"
                        class="industrial-arrow"
                        aria-label="View {{ $company->name }}">
                        <i class="fas fa-arrow-right"></i>
                    </a>

                </div>

                <details><summary>Sources and verification</summary>@foreach($company->sources as $source)<p><a href="{{ $source->url }}" target="_blank" rel="noopener">{{ $source->title }}</a> {{ $source->source_period }}<br>{{ $source->evidence_note }}</p>@endforeach</details>@if($company->website && preg_match('~^https?://~i', $company->website))
                <div style="margin-top:12px;">
                    <a
                        href="{{ $company->website }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="industrial-source">
                        <i class="fas fa-globe"></i>
                        Company website
                        <span>&nearr;</span>
                    </a>
                </div>
                @endif

            </article>

            @empty

            <div class="industrial-empty">
                <strong>No companies or plants found</strong>
                No companies or manufacturing facilities match this selection.
            </div>

            @endforelse

        </div>

        @include('industrial.pagination', [
        'paginator' => $companies,
        'label' => 'Companies'
        ])

    </section>


    {{-- =========================================================
         LIVE JOB OPENINGS
    ========================================================== --}}
    <section class="industrial-block">

        <div class="industrial-section-head">
            <div>
                <h2 class="industrial-section-title">
                    Live Industrial Jobs
                    <small>({{ number_format($jobs->total()) }})</small>
                </h2>

                <p class="industrial-section-description">
                    Current job openings connected to industrial companies and manufacturing facilities.
                </p>
            </div>

            <span class="industrial-count-pill">
                <i class="fas fa-briefcase"></i>
                Live Opportunities
            </span>
        </div>

        <div class="industrial-cards">

            @forelse($jobs as $job)

            <article class="industrial-card">

                <div class="industrial-card-top">

                    <div class="industrial-card-icon job">
                        <i class="fas fa-briefcase"></i>
                    </div>

                    @if($job->verification_label)
                    <span class="industrial-tag verified">
                        {{ $job->verification_label }}
                    </span>
                    @endif

                </div>

                <div class="industrial-job-company">
                    <i class="fas fa-building"></i>

                    {{ $job->company?->name ?: 'Company not supplied' }}

                    <span style="margin:0 5px;">&middot;</span>

                    {{ $job->area->name }}
                </div>

                <h3>
                    <a href="{{ route('industrial.jobs.show', $job) }}">
                        {{ $job->job_title }}
                    </a>
                </h3>

                <div class="industrial-tags">

                    @if($job->department)
                    <span class="industrial-tag">
                        {{ $job->department->name }}
                    </span>
                    @endif

                    @if($job->role)
                    <span class="industrial-tag">
                        {{ $job->role->name }}
                    </span>
                    @endif

                </div>

                <div class="industrial-job-details">

                    <div class="industrial-job-detail">

                        <span class="industrial-job-detail-label">
                            Location
                        </span>

                        <span class="industrial-job-detail-value">
                            {{ $job->area->city ?: $job->area->district }},
                            {{ $job->area->state->name }}
                        </span>

                    </div>

                    <div class="industrial-job-detail">

                        <span class="industrial-job-detail-label">
                            Employment
                        </span>

                        <span class="industrial-job-detail-value">
                            {{ $job->employment_type ?: 'Not specified' }}
                        </span>

                    </div>

                </div>

                @if($job->application_deadline)

                <div class="industrial-deadline">
                    <i class="far fa-calendar-alt"></i>
                    Apply by {{ $job->application_deadline->format('d M Y') }}
                </div>

                @endif

                <div class="industrial-card-footer">

                    <div class="industrial-stat">
                        <strong>Live</strong>
                        industrial opportunity
                    </div>

                    <a
                        href="{{ route('industrial.jobs.show', $job) }}"
                        class="industrial-arrow"
                        aria-label="View {{ $job->job_title }}">
                        <i class="fas fa-arrow-right"></i>
                    </a>

                </div>

            </article>

            @empty

            <div class="industrial-empty">
                <strong>No live openings match your filters</strong>
                No current vacancies match your filters. Companies and job roles in the directory do not imply current vacancies.
            </div>

            @endforelse

        </div>

        @include('industrial.pagination', [
        'paginator' => $jobs,
        'label' => 'Live openings'
        ])

    </section>

</div>