@extends('layouts.site')
@section('title','Industries, careers and current openings')
@section('content')
<style>
    .career-layout {
        max-width: 1400px;
        margin: 24px auto;
        padding: 16px;
        display: grid;
        grid-template-columns: 265px minmax(0, 1fr);
        gap: 24px
    }

    .career-filters,
    .career-card {
        background: #fff;
        border: 1px solid #d6e1ee;
        border-radius: 12px;
        padding: 18px
    }

    .career-filters label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        margin: 10px 0 4px
    }

    .career-filters input,
    .career-filters select {
        width: 100%;
        padding: 9px;
        border: 1px solid #a8bdcf;
        border-radius: 5px
    }

    .career-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 12px
    }

    .career-card {
        margin-bottom: 12px
    }

    article.career-card {
        position: relative
    }

    article.career-card h3 a:after {
        content: "";
        position: absolute;
        inset: 0
    }

    article.career-card>a {
        position: relative;
        z-index: 1
    }

    article.career-card:focus-within {
        outline: 2px solid #185aa4
    }

    .career-card h3 {
        font-size: 18px;
        margin: 0 0 8px
    }

    .career-card a {
        color: #1454a0
    }

    .career-tabs {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin: 14px 0 24px
    }

    .career-muted {
        color: #516174;
        font-size: 14px
    }

    .career-role {
        border-top: 1px solid #e3eaf1;
        padding: 12px 0
    }

    .career-layout button {
        background: #185aa4;
        color: white;
        padding: 10px;
        border: 0;
        border-radius: 6px;
        cursor: pointer
    }

    .career-layout table {
        width: 100%;
        border-collapse: collapse
    }

    .career-layout td,
    .career-layout th {
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid #ddd
    }

    @media(max-width:800px) {
        .career-layout {
            grid-template-columns: 1fr
        }

        .career-filters {
            position: static
        }
    }
</style>
<div class="career-layout">
    <aside>
        <form class="career-filters" method="get" action="{{ route('career-explorer') }}">
            <h2>Filter openings</h2>
            @foreach(['country'=>'Country','state'=>'State','city'=>'City','industrial_area'=>'Industrial area','company'=>'Company / factory','industry'=>'Industry','products'=>'Products manufactured','business_unit'=>'Business unit','department'=>'Department','job_family'=>'Job family','role'=>'Role','skills'=>'Skills','experience'=>'Experience (years)','employee_min'=>'Company employees: minimum','employee_max'=>'Company employees: maximum','salary_min'=>'Advertised salary: minimum / year','source_url'=>'Official source URL'] as $key=>$label)
            <label for="career-{{ $key }}">{{ $label }}</label>
            <input id="career-{{ $key }}" name="{{ $key }}" value="{{ $filters[$key]??'' }}" @if(in_array($key,['experience','employee_min','employee_max','salary_min'])) type="number" min="0" @else type="text" @endif @if($key==='industry' ) list="career-sectors" @endif>
            @endforeach
            <label for="career-level">Career level</label><select id="career-level" name="career_level">
                <option value="">All levels</option>@foreach(['intern','entry','experienced','senior','lead','manager','director','executive'] as $level)<option value="{{ $level }}" @selected(($filters['career_level']??'')===$level)>{{ ucfirst($level) }}</option>@endforeach
            </select>
            <label for="career-currency">Salary currency</label><select id="career-currency" name="salary_currency">@foreach(['INR','USD','EUR','GBP'] as $currency)<option @selected(($filters['salary_currency']??'INR')===$currency)>{{ $currency }}</option>@endforeach</select>
            <datalist id="career-sectors">@foreach(config('career_catalog.sectors',[]) as $sector)<option value="{{ $sector }}">@endforeach</datalist>
            <p><button type="submit">Find openings</button> <a href="{{ route('career-explorer') }}">Clear</a></p>
            <details>
                <summary>About these results</summary>
                <p class="career-muted">Employee counts describe company size, not vacancies. Unknown values stay unknown. Career tracks are possible paths, not guaranteed promotions. Google helps discover sources; only registered official feeds are imported.</p>
            </details>
        </form>
    </aside>
    <main>
        <h1>Industries &amp; careers</h1>
        <nav class="career-tabs"><a href="#openings">Current openings ({{ $jobs->total()+$industrialJobs->total() }})</a><a href="#factories">Companies &amp; factories</a><a href="#paths">Career paths</a><a href="{{ route('companies.cyber-city') }}">DLF Cyber City</a></nav>
        <section id="openings">
            <h2>Current openings</h2>
            <div class="career-grid">
                @forelse($jobs as $job)<article class="career-card">
                    <h3><a href="{{ url('/opportunities/'.$job->id) }}">{{ $job->title }}</a></h3>
                    <p>{{ $job->company?->name }} ? {{ $job->location }}</p>
                    <p class="career-muted">{{ $job->technologies->pluck('name')->join(' ? ') }}</p>
                </article>
                @empty<p>No matching openings in the company feeds.</p>@endforelse
                @foreach($industrialJobs as $job)<article class="career-card">
                    <h3><a href="{{ route('industrial.jobs.show',$job->id) }}">{{ $job->job_title }}</a></h3>
                    <p>{{ $job->company?->name }} ? {{ $job->area?->city }}</p>
                </article>@endforeach
            </div>{{ $jobs->links() }}{{ $industrialJobs->links() }}
            @if($jobs->total()+$industrialJobs->total()===0)<p><a href="{{ $discovery_url }}" target="_blank" rel="noopener noreferrer">Find official career pages for these filters</a></p>@endif
        </section>
        <section id="factories">
            <h2>Companies &amp; factories</h2>
            <div class="career-grid">
                @forelse($facilities as $facility)<article class="career-card">
                    <h3><a href="{{ route('companies.show',$facility->company->slug) }}">{{ $facility->company->name }}</a></h3>
                    <p>{{ $facility->name }} ? {{ $facility->facility_type }}</p>
                    <p>{{ $facility->city }}, {{ $facility->state }}, {{ $facility->country }} @if($facility->industrial_area) ? {{ $facility->industrial_area }} @endif</p>
                    <p>{{ $facility->industry }}</p>
                    <p>{{ $facility->products }}</p>
                    <p class="career-muted">Employees: {{ $facility->employee_min ? number_format($facility->employee_min).($facility->employee_max?'?'.number_format($facility->employee_max):'+') : 'Not published' }} @if($facility->employee_scope)({{ $facility->employee_scope }})@endif</p><a href="{{ $facility->source_url }}" target="_blank" rel="noopener noreferrer">Official source</a>@if($facility->company->careers_url) ? <a href="{{ $facility->company->careers_url }}" target="_blank" rel="noopener noreferrer">Careers</a>@endif
                </article>@empty<p>No verified company profiles match these filters.</p>@endforelse
            </div>{{ $facilities->links() }}
        </section>
        <section id="paths">
            <h2>Career paths by department</h2>
            @foreach($tracks as $track)<details class="career-card">
                <summary><strong>{{ $track->sector }} ? {{ $track->department }} ? {{ $track->name }}</strong></summary>
                @foreach($track->roles as $role)<div class="career-role">
                    <h3><a href="{{ route('career-explorer',array_merge($filters,['role'=>$role->name,'page'=>1])) }}#openings">{{ $role->name }}</a></h3><span class="career-muted">{{ ucfirst($role->career_level) }} ? {{ $role->job_family }}</span>
                    @if($role->skills)<p>Skills: @foreach($role->skills as $skill)<a href="{{ route('career-explorer',['skills'=>$skill]) }}">{{ $skill }}</a>{{ !$loop->last?' ? ':'' }}@endforeach</p>@endif
                    @if($role->next_roles)<p>Possible next roles: {{ implode(' / ',$role->next_roles) }}</p>@endif
                </div>@endforeach
            </details>@endforeach
        </section>
        @if($benchmarks->isNotEmpty())<details class="career-card">
            <summary>Experience &amp; indicative salary references</summary>
            <p class="career-muted">User-supplied estimates, not verified offers or guaranteed salaries. Actual openings show employer details separately.</p>
            <div style="overflow:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Sector / role</th>
                            <th>Experience</th>
                            <th>Indicative annual pay</th>
                        </tr>
                    </thead>
                    <tbody>@foreach($benchmarks as $benchmark)<tr>
                            <td>{{ $benchmark->sector }} ? {{ $benchmark->role_name }}</td>
                            <td>{{ $benchmark->experience_label }}</td>
                            <td>{{ $benchmark->salary_label }}</td>
                        </tr>@endforeach</tbody>
                </table>
            </div>
        </details>@endif
    </main>
</div>
@endsection