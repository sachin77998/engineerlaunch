@extends('layouts.site')
@section('title', ($contextCompany->name ?? $contextArea->name ?? 'Industrial India').' | Companies, facilities and jobs')
@push('styles')
<style>
    .industrial-hero {
        padding: 48px 0 32px;
        background: linear-gradient(120deg, #10213e, #164e63);
        color: white
    }

    .industrial-hero h1 {
        font-size: clamp(32px, 5vw, 52px);
        margin: 12px 0
    }

    .industrial-path {
        font-size: 13px;
        line-height: 1.9;
        opacity: .85
    }

    .industrial-form {
        background: white;
        color: #10213e;
        padding: 22px;
        border-radius: 14px;
        margin-top: 26px
    }

    .industrial-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px
    }

    .industrial-form label {
        font-family: inherit !important;
        display: block;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 6px
    }

    .industrial-search {
        display: flex;
        gap: 12px;
        margin-bottom: 18px
    }

    .industrial-search>div {
        flex: 1
    }

    .industrial-search button {
        align-self: end
    }

    .industrial-cards {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px
    }

    .industrial-card {
        background: #fff;
        border: 1px solid #dbe4f0;
        border-radius: 12px;
        padding: 20px;
        overflow-wrap: anywhere
    }

    .industrial-card h3 {
        margin: 8px 0 12px
    }

    .industrial-card a {
        color: #1d4ed8
    }

    .industrial-meta {
        color: #526176;
        font-size: 14px;
        line-height: 1.7
    }

    .industrial-tag {
        display: inline-block;
        background: #eef6ff;
        color: #1e40af;
        border-radius: 5px;
        padding: 4px 8px;
        font-size: 12px;
        margin: 3px 3px 3px 0
    }

    .industrial-block {
        margin: 32px 0
    }

    .industrial-empty {
        padding: 22px;
        border: 1px dashed #bac9dd;
        border-radius: 12px;
        color: #526176
    }

    .industrial-pagination {
        display: flex;
        justify-content: space-between;
        gap: 18px;
        align-items: center;
        margin: 18px 0
    }

    .industrial-pagination a {
        color: #1d4ed8
    }

    .industrial-hubs {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 20px
    }

    .industrial-hubs a {
        display: block;
        color: #1d4ed8;
        margin: 8px 0
    }

    .industrial-status {
        margin: 14px 0;
        color: #b42318
    }

    .industrial-details {
        white-space: pre-line;
        line-height: 1.8
    }

    .industrial-reset {
        display: inline-block;
        margin-top: 16px;
        color: #1d4ed8
    }

    @media(max-width:800px) {

        .industrial-cards,
        .industrial-grid,
        .industrial-hubs {
            grid-template-columns: repeat(2, minmax(0, 1fr))
        }
    }

    @media(max-width:520px) {

        .industrial-cards,
        .industrial-grid,
        .industrial-hubs {
            grid-template-columns: 1fr
        }

        .industrial-search {
            flex-direction: column
        }

        .industrial-search button {
            align-self: stretch
        }
    }

    .industrial-hero {
        background-image: linear-gradient(90deg, rgba(7, 18, 28, .94), rgba(7, 18, 28, .55)), var(--industry-image);
        background-size: cover;
        background-position: center;
        padding: 64px 0 36px
    }

    .industrial-hero h1 {
        max-width: 780px;
        font-size: clamp(36px, 5.2vw, 70px);
        line-height: 1.08;
        letter-spacing: -2px
    }

    .industry-intro {
        max-width: 650px;
        line-height: 1.7;
        color: #dce6ed
    }

    .industrial-path,
    .industry-eyebrow {
        font-weight: 800;
        letter-spacing: 2px;
        font-size: 12px
    }

    .industrial-form {
        box-shadow: 0 18px 50px #0003;
        border-radius: 8px
    }

    .industry-state-chips {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
        margin: 10px 0 22px
    }

    .industry-state-chips button,
    .industry-processes button {
        border: 1px solid #cbd5e1;
        padding: 8px 12px;
        border-radius: 24px;
        background: #f8fafc;
        color: #17334b;
        cursor: pointer
    }

    .industry-filter-details summary,
    .industry-dictionary summary {
        font-weight: 700;
        cursor: pointer;
        padding: 12px 0
    }

    .industry-filter-details .industrial-grid {
        margin-top: 12px
    }

    .industry-section-heading {
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 20px
    }

    .industry-section-heading h2 {
        font-size: 32px;
        margin: 10px 0 22px
    }

    .industry-section-heading p {
        max-width: 350px;
        color: #526176
    }

    .industry-tiles {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 12px
    }

    .industry-tile {
        position: relative;
        border: 3px solid transparent;
        border-radius: 9px;
        overflow: hidden;
        padding: 0;
        height: 150px;
        cursor: pointer;
        background: #122335;
        color: white;
        text-align: left
    }

    .industry-tile img {
        width: 100%;
        height: 100%;
        object-fit: cover
    }

    .industry-tile span {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: end;
        padding: 14px;
        background: linear-gradient(transparent, #07121ce8);
        font-weight: 700
    }

    .industry-tile[aria-pressed=true] {
        border-color: #f5a623
    }

    .industry-gallery {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-top: 26px
    }

    .industry-gallery a:first-child {
        grid-column: span 2;
        grid-row: span 2
    }

    .industry-gallery img {
        display: block;
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 8px
    }

    .industry-gallery a:first-child img {
        height: 312px
    }

    .industry-processes {
        display: flex;
        gap: 26px;
        flex-wrap: wrap;
        padding-left: 24px;
        margin: 22px 0
    }

    .industry-processes button[aria-pressed=true] {
        background: #17334b;
        color: white
    }

    .industry-dictionary {
        border-block: 1px solid #dbe4f0;
        padding: 12px 0;
        margin-top: 28px
    }

    .industry-roles {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        max-height: 440px;
        overflow: auto
    }

    .industry-roles a {
        padding: 12px;
        background: #f1f5f9;
        border-radius: 6px;
        color: #17334b;
        text-decoration: none
    }

    .industry-roles small {
        display: block;
        color: #526176;
        margin-top: 5px
    }

    .industry-tile:focus-visible {
        outline: 3px solid #e88e12;
        outline-offset: 3px
    }

    @media(max-width:900px) {
        .industry-tiles {
            grid-template-columns: repeat(3, minmax(0, 1fr))
        }

        .industry-roles {
            grid-template-columns: repeat(2, minmax(0, 1fr))
        }
    }

    @media(max-width:520px) {
        .industrial-hero {
            padding-top: 36px
        }

        .industry-section-heading {
            display: block
        }

        .industry-tiles {
            grid-template-columns: repeat(2, minmax(0, 1fr))
        }

        .industry-gallery {
            grid-template-columns: repeat(2, minmax(0, 1fr))
        }

        .industry-roles {
            grid-template-columns: 1fr
        }

        .industrial-hero h1 {
            letter-spacing: -1px
        }
    }
.industry-tile span{flex-direction:column;justify-content:end;align-items:start}.industry-tile small{font-size:11px;margin-top:6px}.industry-career-card{padding:16px;background:#f1f5f9;border-radius:8px;font-size:13px;line-height:1.6}.industry-career-card h3{margin:0}.industry-career-card a{display:inline-block}.industry-pathway{display:flex;gap:10px;flex-wrap:wrap}.industry-pathway a{padding:9px 13px;background:#edf4fc;border-radius:5px}.industry-tiles{grid-template-columns:repeat(4,minmax(0,1fr))}.industry-tile{height:170px}@media(max-width:800px){.industry-tiles{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
@endpush
@section('content')
<section class="industrial-hero" id="industry-hero" @if($heroUrl) style="--industry-image:url('{{ $heroUrl }}')" @endif><div class="container">
        <div class="industrial-path">INDUSTRIAL INDIA &middot; PEOPLE. PROCESS. PROGRESS.</div>
        <p class="industry-intro">Discover the industries, companies, skills and jobs powering each industrial cluster.</p>
        <h1 id="industrial-heading">{{ $heroText }}</h1>
        <p id="industrial-hero-caption">{{ $heroCaption }}</p>
        <ol class="industrial-learning-path" aria-label="Industrial discovery diagram"><li>State &rarr; City &rarr; Cluster</li><li>Sector &rarr; Product &rarr; Process</li><li>Company &rarr; Department</li><li>Role &rarr; Opening</li></ol>
        <form class="industrial-form" id="industrial-filters" action="{{ route('industrial.index') }}" method="get">
            <div class="industrial-search">
                <div><label for="industrial-search">Search company, industrial area, skill or job</label><input class="field" id="industrial-search" name="search" maxlength="120" value="{{ $filters['search'] ?? '' }}" placeholder="e.g. Focal Point, company name, CNC Operator"></div><button class="primary" type="submit">Search</button>
            </div>
            <div class="industry-state-chips"><span>Explore:</span>@foreach(['Punjab','Gujarat','Haryana','Rajasthan','Uttarakhand'] as $quickState)@php($quick = $options['state']->firstWhere('name', $quickState))@if($quick)<button type="button" data-state="{{ $quick->id }}">{{ $quickState }}</button>@endif @endforeach</div>
            <details class="industry-filter-details" @if(array_filter($filters)) open @endif>
                <summary>Refine by location, sector, process and career</summary>
                <div class="industrial-grid">
                    @foreach(['state'=>'State','location'=>'City / District','area'=>'Industrial Area / Cluster','sector'=>'Sector','subsector'=>'Subsector / Product','process'=>'Process','company'=>'Company / Plant','department'=>'Department','role'=>'Job Role'] as $key=>$label)
                    <div><label for="industrial-{{ $key }}">{{ $label }}</label><select class="field" id="industrial-{{ $key }}" name="{{ $key }}">
                            <option value="">All {{ $label }}</option>@foreach($options[$key] as $option)<option value="{{ $option['id'] }}" @selected((string)($filters[$key] ?? '' )===(string)$option['id'])>{{ $option['name'] }}</option>@endforeach
                        </select></div>
                    @endforeach
                    @foreach(['experience'=>['Experience',['fresher'=>'Fresher','0-2'=>'0â€“2 years','2-5'=>'2â€“5 years','5-10'=>'5â€“10 years']], 'qualification'=>['Qualification',['10th'=>'10th','12th'=>'12th','ITI'=>'ITI','Diploma'=>'Diploma','B.Tech'=>'B.Tech','Graduate'=>'Graduate']], 'salary'=>['Monthly salary (INR)',['10000-20000'=>'â‚¹10kâ€“â‚¹20k','20000-30000'=>'â‚¹20kâ€“â‚¹30k','30000-50000'=>'â‚¹30kâ€“â‚¹50k','50000+'=>'â‚¹50k+']]] as $key=>[$label,$choices])
                    <div><label for="industrial-{{ $key }}">{{ $label }}</label><select class="field" id="industrial-{{ $key }}" name="{{ $key }}">
                            <option value="">All {{ $label }}</option>@foreach($choices as $value=>$text)<option value="{{ $value }}" @selected(($filters[$key] ?? '' )===$value)>{{ $text }}</option>@endforeach
                        </select></div>
                    @endforeach
                    <div><label for="industrial-skill">Skill</label><input class="field" id="industrial-skill" name="skill" maxlength="120" value="{{ $filters['skill'] ?? '' }}" placeholder="e.g. CNC"></div>
                </div>
            </details><a class="industrial-reset" href="{{ route('industrial.index') }}">Reset filters</a>
        </form>
    </div>
</section>
@include('partials.visual-explainer')
<div class="container">
    <p id="industrial-status" class="industrial-status" role="status" aria-live="polite"></p>
    <div id="industrial-taxonomy">@include('industrial.taxonomy')</div>
    <div id="industrial-results" aria-live="polite">@include('industrial.results')</div>
    @if($hubs->isNotEmpty())<section class="industrial-block">
        <h2>Popular Industrial Hubs</h2>
        <div class="industrial-hubs">@foreach($hubs as $stateName=>$stateAreas)<div>
                <h3>{{ $stateName }}</h3>@foreach($stateAreas->groupBy(fn($area)=>$area->city ?: $area->district) as $location=>$localAreas)<a href="{{ route('industrial.index', ['state'=>$localAreas->first()->state_id,'location'=>$location]) }}">{{ $location }}</a>@endforeach
            </div>@endforeach</div>
    </section>@endif
</div>
@endsection
@push('scripts')
<script>
    (() => {
        const form = document.getElementById('industrial-filters'),
            results = document.getElementById('industrial-results'),
            status = document.getElementById('industrial-status');
        const names = ['state', 'location', 'area', 'sector', 'subsector', 'process', 'company', 'department', 'role'];
        const labels = {
            sector: 'Sector',
            subsector: 'Subsector / Product',
            process: 'Process',
            state: 'State',
            location: 'City / District',
            area: 'Industrial Area / Cluster',
            company: 'Company / Plant',
            department: 'Department',
            role: 'Job Role'
        };
        const endpoint = @json(route('industrial.ajax')),
            index = @json(route('industrial.index'));
        let controller, sequence = 0;
        const dependents = {
            state: ['location', 'area', 'company', 'department', 'role'],
            location: ['area', 'company', 'department', 'role'],
            area: ['company', 'department', 'role'],
            sector: ['subsector', 'process', 'company', 'department', 'role'],
            subsector: ['process', 'company', 'department', 'role'],
            process: ['company', 'department', 'role'],
            company: ['department', 'role'],
            department: ['role'],
            role: []
        };

        function params() {
            return new URLSearchParams([...new FormData(form)].filter(([, v]) => v !== ''));
        }

        function updateDisabled() {
            form.elements.location.disabled = !form.elements.state.value;
            form.elements.area.disabled = !form.elements.state.value;
            form.elements.company.disabled = !form.elements.area.value;
            form.elements.role.disabled = !form.elements.department.value;
            form.elements.subsector.disabled = !form.elements.sector.value;
            form.elements.process.disabled = !form.elements.sector.value;
        }
        async function load(query, push = true) {
            if (controller) controller.abort();
            controller = new AbortController();
            const current = ++sequence;
            status.textContent = 'Loading industrial directory...';
            results.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(endpoint + '?' + query, {
                    headers: {
                        Accept: 'application/json'
                    },
                    signal: controller.signal
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Unable to load results.');
                if (current !== sequence) return;
                for (const name of names) {
                    const select = form.elements[name],
                        chosen = data.filters[name] || '';
                    select.replaceChildren(new Option('All ' + labels[name], ''));
                    for (const item of data.options[name]) select.add(new Option(item.name, item.id));
                    select.value = chosen;
                }
                for (const name of ['search', 'experience', 'qualification', 'salary', 'skill']) form.elements[name].value = query.get(name) || '';
                updateDisabled();
                results.innerHTML = data.html;
                document.getElementById('industrial-heading').textContent = data.heroText;
                document.getElementById('industrial-hero-caption').textContent = data.heroCaption;
                document.getElementById('industrial-taxonomy').innerHTML = data.taxonomyHtml;
                document.getElementById('industry-hero').style.setProperty('--industry-image', data.hero ? 'url(' + JSON.stringify(data.hero) + ')' : 'none');
                document.title = data.heading + ' | Industrial India';
                if (push) history.pushState(null, '', index + (query.size ? '?' + query : ''));
                status.textContent = '';
            } catch (error) {
                if (error.name !== 'AbortError') status.textContent = 'Results could not be refreshed. ' + error.message + ' Use Search to retry.';
            } finally {
                if (current === sequence) results.removeAttribute('aria-busy');
            }
        }
        form.addEventListener('submit', event => {
            event.preventDefault();
            load(params());
        });
        for (const name of names) form.elements[name].addEventListener('change', () => {
            for (const child of dependents[name]) form.elements[child].value = '';
            updateDisabled();
            load(params());
        });
        results.addEventListener('click', event => {
            const link = event.target.closest('a[data-industrial-link]');
            if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            load(new URL(link.href).searchParams);
        });
        for (const name of ['experience', 'qualification', 'salary']) form.elements[name].addEventListener('change', () => load(params()));
        document.addEventListener('click', event => {
            const button = event.target.closest('button[data-sector],button[data-state],button[data-process],button[data-subsector]');
            if (!button) return;
            const name = button.dataset.sector ? 'sector' : button.dataset.state ? 'state' : button.dataset.subsector ? 'subsector' : 'process';
            form.elements[name].value = button.dataset[name];
            for (const child of dependents[name]) form.elements[child].value = '';
            updateDisabled();
            load(params());
        });
        window.addEventListener('popstate', () => load(new URLSearchParams(location.search), false));
        updateDisabled();
    })();
</script>
@endpush