@extends('layouts.site')
@section('title','Industries, careers and current openings')
@section('meta_description', 'Search current openings, companies and factories by country, state, city, industrial area and industry, with career paths by department.')
@section('content')
@include('partials.page-tabs',['tabs'=>[['Industrial Area Jobs',route('industrial.index'),false],['Industry-wise Jobs & Career Paths',route('career-explorer'),true]]])
@php
    $labels = ['country'=>'Country','state'=>'State','city'=>'City','industrial_area'=>'Industrial area','company'=>'Company / factory','industry'=>'Industry','products'=>'Products manufactured','business_unit'=>'Business unit','department'=>'Department','job_family'=>'Job family','role'=>'Role','skills'=>'Skills','experience'=>'Experience (years)','employee_min'=>'Company employees: minimum','employee_max'=>'Company employees: maximum','salary_min'=>'Advertised salary: minimum / year','source_url'=>'Official source URL','career_level'=>'Career level','salary_currency'=>'Salary currency'];
    $active = collect($filters)->only(array_keys($labels))->filter(fn ($v, $k) => filled($v) && !($k === 'salary_currency' && $v === 'INR'));
    $f = fn ($k) => $filters[$k] ?? '';
    $select = function (string $key, $values, string $all) use ($f) {
        $cur = $f($key); $html = '<option value="">' . e($all) . '</option>';
        $vals = collect($values); if ($cur !== '' && !$vals->contains($cur)) $vals->prepend($cur);
        foreach ($vals as $v) $html .= '<option value="' . e($v) . '"' . ((string) $v === (string) $cur ? ' selected' : '') . '>' . e($v) . '</option>';
        return $html;
    };
    $more = collect(['company','products','business_unit','job_family','role','skills','experience','employee_min','employee_max','salary_min','source_url'])->contains(fn ($k) => filled($f($k)));
    $total = $jobs->total() + $industrialJobs->total();
@endphp
<style>
.ce{max-width:1400px;margin:20px auto 40px;padding:0 16px;display:grid;grid-template-columns:300px minmax(0,1fr);gap:26px;align-items:start}
.ce-filters{position:sticky;top:96px;max-height:calc(100vh - 110px);overflow:auto;background:#fff;border:1px solid #dbe5f1;border-radius:16px;padding:20px;box-shadow:0 8px 24px rgba(16,33,62,.06)}
.ce-filters h2{margin:0 0 4px;font-size:20px}
.ce-group{border-top:1px solid #edf2f8;padding:12px 0 4px;margin-top:10px}
.ce-group>b{display:block;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#64748b;margin-bottom:4px}
.ce-filters label{display:block;font-size:13px;font-weight:700;margin:10px 0 5px;color:#1e293b}
.ce-filters input,.ce-filters select{width:100%;height:40px;padding:0 11px;border:1px solid #cbd8e8;border-radius:9px;background:#fff;font:inherit;font-size:14px;color:#0f172a}
.ce-filters select{appearance:none;background-image:linear-gradient(45deg,transparent 50%,#64748b 50%),linear-gradient(135deg,#64748b 50%,transparent 50%);background-position:calc(100% - 17px) 17px,calc(100% - 12px) 17px;background-size:5px 5px;background-repeat:no-repeat;padding-right:30px}
.ce-filters select:disabled{background-color:#f4f7fb;color:#94a3b8}
.ce-filters details>summary{cursor:pointer;font-weight:700;color:#1d4ed8;margin-top:12px;list-style:none}
.ce-filters details>summary::before{content:'＋ '}.ce-filters details[open]>summary::before{content:'－ '}
.ce-actions{position:sticky;bottom:-20px;background:#fff;padding:14px 0 2px;margin-top:12px;display:flex;gap:10px;align-items:center}
.ce-btn{flex:1;height:44px;border:0;border-radius:10px;background:#1d4ed8;color:#fff;font-weight:700;font-size:15px;cursor:pointer}
.ce-btn:hover{background:#1e40af}
.ce-clear{color:#475569;font-weight:600;text-decoration:none;padding:0 6px}
.ce-head h1{margin:6px 0 6px;font-size:clamp(26px,3vw,34px)}
.ce-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 16px}
.ce-tabs a{padding:8px 14px;border:1px solid #dbe5f1;border-radius:30px;background:#fff;color:#1e3a8a;font-weight:600;font-size:14px;text-decoration:none}
.ce-tabs a:hover{border-color:#1d4ed8}
.ce-chips{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 16px}
.ce-chip{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:20px;background:#e8f0ff;color:#1e3a8a;font-size:13px;font-weight:600;text-decoration:none}
.ce-chip span{font-size:15px;line-height:1;color:#64748b}
.ce-section{margin-bottom:34px}
.ce-section h2{font-size:22px;margin:0 0 14px;display:flex;align-items:baseline;gap:10px}
.ce-section h2 small{font-size:14px;color:#64748b;font-weight:600}
.ce-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px}
.ce-card{position:relative;display:flex;flex-direction:column;gap:6px;background:#fff;border:1px solid #dbe5f1;border-radius:14px;padding:16px 16px 14px;transition:box-shadow .15s,border-color .15s}
.ce-card:hover{border-color:#93b4f5;box-shadow:0 10px 24px rgba(29,78,216,.08)}
.ce-card:focus-within{outline:2px solid #1d4ed8}
.ce-card h3{font-size:16px;line-height:1.35;margin:0}
.ce-card h3 a{color:#0f2a5c;text-decoration:none}
.ce-card h3 a::after{content:"";position:absolute;inset:0}
.ce-card>a,.ce-card .ce-links a{position:relative;z-index:1}
.ce-meta{color:#334155;font-size:14px;margin:0;display:flex;gap:6px;align-items:flex-start}
.ce-meta i{font-style:normal;color:#64748b;flex:0 0 auto}
.ce-tags{display:flex;gap:6px;flex-wrap:wrap;margin-top:4px}
.ce-tag{padding:3px 8px;border-radius:6px;background:#f1f5f9;color:#475569;font-size:12px;font-weight:600}
.ce-tag.blue{background:#e8f0ff;color:#1e40af}
.ce-muted{color:#64748b;font-size:13px}
.ce-links{display:flex;gap:14px;margin-top:auto;padding-top:6px;font-size:14px}
.ce-links a{color:#1d4ed8;font-weight:600}
.ce-empty{padding:22px;border:1px dashed #cbd8e8;border-radius:12px;color:#475569;background:#fff}
.ce-path{background:#fff;border:1px solid #dbe5f1;border-radius:12px;margin-bottom:10px}
.ce-path>summary{cursor:pointer;padding:14px 16px;font-weight:700;color:#0f2a5c;display:flex;gap:8px;align-items:center;flex-wrap:wrap;list-style:none}
.ce-path>summary::before{content:'›';display:inline-block;transition:transform .15s;color:#64748b;font-size:20px;line-height:1}
.ce-path[open]>summary::before{transform:rotate(90deg)}
.ce-path .crumb{color:#94a3b8;font-weight:400}
.ce-role{border-top:1px solid #edf2f8;padding:12px 16px}
.ce-role h3{font-size:16px;margin:0 0 4px}.ce-role h3 a{color:#1d4ed8}
.ce-role p{margin:6px 0 0;font-size:14px;color:#334155}
.ce-table{width:100%;border-collapse:collapse;font-size:14px}.ce-table th,.ce-table td{text-align:left;padding:10px;border-bottom:1px solid #edf2f8}
@media(max-width:900px){.ce{grid-template-columns:1fr}.ce-filters{position:static;max-height:none}}
</style>
<div class="ce">
    <aside>
        <form class="ce-filters" method="get" action="{{ route('career-explorer') }}" id="ce-form">
            <h2>Filter openings</h2>
            <div class="ce-muted">{{ number_format($total) }} openings match</div>

            <div class="ce-group"><b>Location</b>
                <label for="career-country">Country</label>
                <select id="career-country" name="country">{!! $select('country', $options['countries'], 'All countries') !!}</select>
                <label for="career-state">State / region</label>
                <select id="career-state" name="state" data-value="{{ $f('state') }}"><option value="">All states</option>@if($f('state'))<option selected>{{ $f('state') }}</option>@endif</select>
                <label for="career-city">City</label>
                <select id="career-city" name="city" data-value="{{ $f('city') }}"><option value="">All cities</option>@if($f('city'))<option selected>{{ $f('city') }}</option>@endif</select>
                <label for="career-industrial_area">Industrial area</label>
                <select id="career-industrial_area" name="industrial_area" data-value="{{ $f('industrial_area') }}"><option value="">All industrial areas</option>@if($f('industrial_area'))<option selected>{{ $f('industrial_area') }}</option>@endif</select>
            </div>

            <div class="ce-group"><b>Job</b>
                <label for="career-industry">Industry</label>
                <select id="career-industry" name="industry">{!! $select('industry', $options['industries'], 'All industries') !!}</select>
                <label for="career-department">Department</label>
                <select id="career-department" name="department">{!! $select('department', $options['departments'], 'All departments') !!}</select>
                <label for="career-level">Career level</label>
                <select id="career-level" name="career_level">{!! $select('career_level', ['intern','entry','experienced','senior','lead','manager','director','executive'], 'All levels') !!}</select>
            </div>

            <details @if($more) open @endif>
                <summary>More filters</summary>
                <div class="ce-group" style="border:0;margin:0;padding-top:4px">
                    <label for="career-job_family">Job family</label>
                    <select id="career-job_family" name="job_family">{!! $select('job_family', $options['job_families'], 'All job families') !!}</select>
                    <label for="career-role">Role</label>
                    <input id="career-role" name="role" value="{{ $f('role') }}" list="career-roles" placeholder="e.g. Quality Engineer">
                    <label for="career-skills">Skills</label>
                    <input id="career-skills" name="skills" value="{{ $f('skills') }}" placeholder="e.g. Python, AutoCAD">
                    <label for="career-experience">Experience (years)</label>
                    <input id="career-experience" name="experience" type="number" min="0" value="{{ $f('experience') }}">
                    <label for="career-company">Company / factory</label>
                    <input id="career-company" name="company" value="{{ $f('company') }}" placeholder="Company name">
                    <label for="career-products">Products manufactured</label>
                    <input id="career-products" name="products" value="{{ $f('products') }}" placeholder="e.g. gears, castings">
                    <label for="career-business_unit">Business unit</label>
                    <input id="career-business_unit" name="business_unit" value="{{ $f('business_unit') }}">
                    <label for="career-employee_min">Company employees: minimum</label>
                    <input id="career-employee_min" name="employee_min" type="number" min="0" value="{{ $f('employee_min') }}">
                    <label for="career-employee_max">Company employees: maximum</label>
                    <input id="career-employee_max" name="employee_max" type="number" min="0" value="{{ $f('employee_max') }}">
                    <label for="career-salary_min">Advertised salary: minimum / year</label>
                    <input id="career-salary_min" name="salary_min" type="number" min="0" value="{{ $f('salary_min') }}">
                    <label for="career-currency">Salary currency</label>
                    <select id="career-currency" name="salary_currency">@foreach(['INR','USD','EUR','GBP'] as $currency)<option @selected(($filters['salary_currency']??'INR')===$currency)>{{ $currency }}</option>@endforeach</select>
                    <label for="career-source_url">Official source URL</label>
                    <input id="career-source_url" name="source_url" value="{{ $f('source_url') }}" placeholder="https://">
                </div>
            </details>
            <datalist id="career-roles">@foreach($options['roles'] as $r)<option value="{{ $r }}">@endforeach</datalist>
            <div class="ce-actions"><button class="ce-btn" type="submit">Find openings</button><a class="ce-clear" href="{{ route('career-explorer') }}">Clear</a></div>
            <details style="margin-top:12px"><summary style="color:#475569">About these results</summary>
                <p class="ce-muted">Employee counts describe company size, not vacancies. Unknown values stay unknown. Career tracks are possible paths, not guaranteed promotions. Google helps discover sources; only registered official feeds are imported.</p>
            </details>
        </form>
    </aside>

    <main>
        <div class="ce-head">
            <h1>Industries &amp; careers</h1>
            <nav class="ce-tabs"><a href="#openings">Current openings ({{ number_format($total) }})</a><a href="#factories">Companies &amp; factories</a><a href="#paths">Career paths</a><a href="{{ route('companies.cyber-city') }}">DLF Cyber City</a></nav>
            @if($active->isNotEmpty())
            <div class="ce-chips">
                @foreach($active as $k => $v)<a class="ce-chip" href="{{ route('career-explorer', collect($filters)->except([$k, 'page', 'industrial_page', 'companies_page'])->filter(fn ($x) => filled($x))->all()) }}" title="Remove this filter">{{ $labels[$k] }}: {{ $v }} <span>×</span></a>@endforeach
                <a class="ce-chip" style="background:#fff;border:1px solid #dbe5f1" href="{{ route('career-explorer') }}">Clear all</a>
            </div>
            @endif
        </div>

        <section id="openings" class="ce-section">
            <h2>Current openings <small>{{ number_format($total) }}</small></h2>
            <div class="ce-grid">
                @forelse($jobs as $job)<article class="ce-card">
                    <h3><a href="{{ url('/opportunities/'.$job->id) }}">{{ $job->title }}</a></h3>
                    @if($job->company?->name)<p class="ce-meta"><i>🏢</i>{{ $job->company->name }}</p>@endif
                    @if($job->location)<p class="ce-meta"><i>📍</i>{{ $job->location }}</p>@endif
                    @if($job->technologies->isNotEmpty())<div class="ce-tags">@foreach($job->technologies->take(4) as $t)<span class="ce-tag blue">{{ $t->name }}</span>@endforeach</div>@endif
                </article>
                @empty<div class="ce-empty">No matching openings in the company feeds.</div>@endforelse
                @foreach($industrialJobs as $job)<article class="ce-card">
                    <h3><a href="{{ route('industrial.jobs.show',$job->id) }}">{{ $job->job_title }}</a></h3>
                    @if($job->company?->name)<p class="ce-meta"><i>🏢</i>{{ $job->company->name }}</p>@endif
                    @if($job->area?->city)<p class="ce-meta"><i>📍</i>{{ $job->area->city }}</p>@endif
                    <div class="ce-tags"><span class="ce-tag">Industrial area</span></div>
                </article>@endforeach
            </div>
            {{ $jobs->links('partials.pagination', ['anchor' => '#openings']) }}{{ $industrialJobs->links('partials.pagination', ['anchor' => '#openings']) }}
            @if($total===0)<p><a href="{{ $discovery_url }}" target="_blank" rel="noopener noreferrer">Find official career pages for these filters</a></p>@endif
        </section>

        <section id="factories" class="ce-section">
            <h2>Companies &amp; factories <small>{{ number_format($facilities->total()) }}</small></h2>
            <div class="ce-grid">
                @forelse($facilities as $facility)<article class="ce-card">
                    <h3><a href="{{ route('companies.show',$facility->company->slug) }}">{{ $facility->company->name }}</a></h3>
                    <p class="ce-meta"><i>🏭</i>{{ $facility->name }}@if($facility->facility_type) · {{ $facility->facility_type }}@endif</p>
                    <p class="ce-meta"><i>📍</i>{{ collect([$facility->industrial_area, $facility->city, $facility->state, $facility->country])->filter()->unique()->implode(', ') }}</p>
                    <div class="ce-tags">@if($facility->industry)<span class="ce-tag blue">{{ $facility->industry }}</span>@endif @if($facility->products)<span class="ce-tag">{{ \Illuminate\Support\Str::limit($facility->products, 60) }}</span>@endif</div>
                    <p class="ce-muted" style="margin:4px 0 0">Employees: {{ $facility->employee_min ? number_format($facility->employee_min).($facility->employee_max?'–'.number_format($facility->employee_max):'+') : 'Not published' }} @if($facility->employee_scope)({{ $facility->employee_scope }})@endif</p>
                    <div class="ce-links">@if($facility->source_url)<a href="{{ $facility->source_url }}" target="_blank" rel="noopener noreferrer">Official source ↗</a>@endif @if($facility->company->careers_url)<a href="{{ $facility->company->careers_url }}" target="_blank" rel="noopener noreferrer">Careers ↗</a>@endif</div>
                </article>@empty<div class="ce-empty">No verified company profiles match these filters.</div>@endforelse
            </div>
            {{ $facilities->links('partials.pagination', ['anchor' => '#factories']) }}
        </section>

        <section id="paths" class="ce-section">
            <h2>Career paths by department <small>{{ count($tracks) }}</small></h2>
            @foreach($tracks as $track)<details class="ce-path">
                <summary>{{ $track->sector }} <span class="crumb">›</span> {{ $track->department }} <span class="crumb">›</span> {{ $track->name }}</summary>
                @foreach($track->roles as $role)<div class="ce-role">
                    <h3><a href="{{ route('career-explorer',array_merge($filters,['role'=>$role->name,'page'=>1])) }}#openings">{{ $role->name }}</a></h3>
                    <div class="ce-tags"><span class="ce-tag blue">{{ ucfirst($role->career_level) }}</span>@if($role->job_family)<span class="ce-tag">{{ $role->job_family }}</span>@endif</div>
                    @if($role->skills)<p>Skills: @foreach($role->skills as $skill)<a href="{{ route('career-explorer',['skills'=>$skill]) }}">{{ $skill }}</a>{{ !$loop->last?' · ':'' }}@endforeach</p>@endif
                    @if($role->next_roles)<p>Possible next roles: {{ implode(' → ',$role->next_roles) }}</p>@endif
                </div>@endforeach
            </details>@endforeach
        </section>

        @if($benchmarks->isNotEmpty())<details class="ce-path">
            <summary>Experience &amp; indicative salary references</summary>
            <div style="padding:0 16px 14px"><p class="ce-muted">User-supplied estimates, not verified offers or guaranteed salaries. Actual openings show employer details separately.</p>
            <div style="overflow:auto"><table class="ce-table"><thead><tr><th>Sector / role</th><th>Experience</th><th>Indicative annual pay</th></tr></thead>
                <tbody>@foreach($benchmarks as $benchmark)<tr><td>{{ $benchmark->sector }} · {{ $benchmark->role_name }}</td><td>{{ $benchmark->experience_label }}</td><td>{{ $benchmark->salary_label }}</td></tr>@endforeach</tbody></table></div></div>
        </details>@endif
    </main>
</div>
@push('scripts')<script>
(function () {
    var states = @json($options['states']), placesUrl = @json(route('career-explorer.places'));
    var country = document.getElementById('career-country'), state = document.getElementById('career-state'),
        city = document.getElementById('career-city'), area = document.getElementById('career-industrial_area');
    function fill(sel, values, all, keep) {
        var cur = keep ? sel.dataset.value || sel.value : '';
        sel.innerHTML = '';
        sel.add(new Option(all, ''));
        if (cur && values.indexOf(cur) < 0) values = [cur].concat(values);
        values.forEach(function (v) { var o = new Option(v, v); if (v === cur) o.selected = true; sel.add(o); });
        sel.disabled = values.length === 0;
        sel.dataset.value = '';
    }
    function fillStates(keep) {
        var list = country.value ? (states[country.value] || []) : [];
        fill(state, list.slice(), country.value ? 'All states' : 'Choose a country first', keep);
    }
    function loadPlaces(keep) {
        if (!state.value) { fill(city, [], 'Choose a state first', keep); fill(area, [], 'Choose a state first', keep); return; }
        fetch(placesUrl + '?' + new URLSearchParams({country: country.value, state: state.value}), {headers: {Accept: 'application/json'}})
            .then(function (r) { return r.json(); })
            .then(function (d) { fill(city, d.cities || [], 'All cities', keep); fill(area, d.areas || [], 'All industrial areas', keep); })
            .catch(function () {});
    }
    country.addEventListener('change', function () { fillStates(false); loadPlaces(false); });
    state.addEventListener('change', function () { loadPlaces(false); });
    fillStates(true); loadPlaces(true);
})();
</script>@endpush
@endsection
