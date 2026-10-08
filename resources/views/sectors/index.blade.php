@extends('layouts.site')
@section('title', ($sector ? $sector->name.' Companies' : 'Companies by Sector').' — Ascendia')
@push('styles')<style>
.sd{display:grid;grid-template-columns:260px minmax(0,1fr) 280px;gap:22px;padding:26px 0 56px;width:min(1360px,calc(100% - 32px));margin:auto}
.sd>*{min-width:0}
.sd-side{position:sticky;top:16px;align-self:start;max-height:calc(100vh - 32px);overflow:auto}
.sd-box{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px;margin-bottom:14px}
.sd-box h3{margin:0 0 10px;font-size:13px;text-transform:uppercase;letter-spacing:.04em;color:var(--muted)}
.sd-input{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:9px;font:inherit;margin-bottom:8px;background:#fff}
.sd-btn{width:100%;padding:9px;border:0;border-radius:9px;background:var(--blue);color:#fff;font-weight:700;cursor:pointer}
.sd-clear{display:block;text-align:center;margin-top:8px;font-size:13px;color:var(--muted)}
.sd-nav a{display:flex;align-items:center;gap:9px;padding:8px 9px;border-radius:9px;color:var(--ink);text-decoration:none;font-size:14px}
.sd-nav a:hover,.sd-nav a.on{background:var(--blue-soft);color:var(--blue)}
.sd-sym{display:inline-grid;place-items:center;min-width:30px;height:26px;border-radius:7px;background:var(--blue-soft);color:var(--blue);font-size:11px;font-weight:800}
.sd-count{margin-left:auto;font-size:12px;color:var(--muted)}
.sd-head{display:flex;align-items:center;gap:12px;margin-bottom:14px}.sd-head h1{margin:0;font-size:26px}.sd-head .sd-sym{min-width:42px;height:36px;font-size:14px}
.sd-pills{display:flex;gap:8px;overflow-x:auto;padding-bottom:6px;margin-bottom:12px;position:sticky;top:0;background:var(--bg);z-index:2}
.sd-pills a{white-space:nowrap;padding:6px 12px;border:1px solid var(--line);border-radius:999px;background:#fff;font-size:13px;color:var(--ink);text-decoration:none}
.sd-pills a:hover{border-color:var(--blue);color:var(--blue)}
.sd-group{margin-bottom:26px;scroll-margin-top:52px}.sd-group h2{font-size:17px;margin:0 0 10px;display:flex;gap:8px;align-items:baseline}.sd-group h2 small{color:var(--muted);font-weight:500;font-size:13px}
.sd-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px}
.sd-card{display:flex;flex-direction:column;gap:7px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:13px;color:var(--ink);text-decoration:none;transition:border-color .15s,box-shadow .15s}
.sd-card:hover{border-color:var(--blue);box-shadow:0 8px 22px #2563eb14}
.sd-card-top{display:flex;gap:10px;align-items:center}.sd-card img{width:34px;height:34px;border-radius:8px;object-fit:contain;background:#f8fafc;flex:none}
.sd-card strong{font-size:14px;line-height:1.3}.sd-meta{font-size:12px;color:var(--muted)}
.sd-tags{display:flex;flex-wrap:wrap;gap:5px}.sd-tag{font-size:11px;padding:2px 7px;border-radius:6px;background:#f1f5f9;color:#334155}
.sd-plant{font-size:12px;color:#475569}.sd-jobs{margin-top:auto;font-size:12px;font-weight:700;color:var(--blue)}.sd-jobs.none{color:var(--muted);font-weight:500}
.sd-tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
.sd-tile{background:#fff;border:1px solid var(--line);border-radius:14px;padding:16px}
.sd-tile-head{display:flex;gap:10px;align-items:center;text-decoration:none;color:var(--ink);margin-bottom:10px}.sd-tile-head strong{font-size:16px}
.sd-sub{padding:8px 0;border-top:1px solid #eef2f7}.sd-sub a.sd-subname{font-size:13px;font-weight:700;color:var(--ink);text-decoration:none}.sd-sub a.sd-subname:hover{color:var(--blue)}
.sd-sub p{margin:4px 0 0;font-size:12px;color:var(--muted);line-height:1.5}
.sd-roles h4{margin:10px 0 6px;font-size:12px;color:var(--ink)}.sd-roles a{display:inline-block;margin:0 5px 6px 0;padding:4px 9px;border-radius:999px;background:var(--blue-soft);color:var(--blue);font-size:12px;text-decoration:none}
.sd-roles a.on{background:var(--blue);color:#fff}
.sd-open{display:block;padding:9px 0;border-top:1px solid #eef2f7;text-decoration:none;color:var(--ink)}.sd-open:first-of-type{border-top:0}.sd-open strong{display:block;font-size:13px}.sd-open span{font-size:12px;color:var(--muted)}
.sd-flow{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px;margin-bottom:18px;counter-reset:stage}
.sd-stage{background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px}
.sd-stage b{display:block;font-size:13px;margin-bottom:3px}.sd-stage b::before{counter-increment:stage;content:counter(stage) '. ';color:var(--blue)}
.sd-stage small{display:block;font-size:11px;color:var(--muted);margin-bottom:7px}
.sd-stage a{display:inline-block;margin:0 4px 5px 0;padding:3px 8px;border-radius:999px;background:var(--blue-soft);color:var(--blue);font-size:11px;text-decoration:none}.sd-stage a.on{background:var(--blue);color:#fff}
.sd-hubs{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px;margin-top:8px}
.sd-hub{display:block;background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px;text-decoration:none;color:var(--ink)}.sd-hub:hover{border-color:var(--blue)}
.sd-hub strong{display:block;font-size:14px}.sd-hub span{font-size:12px;color:var(--muted)}
.sd-h2{font-size:18px;margin:28px 0 4px}
.sd-src{display:inline-block;margin-left:4px;padding:0 5px;border-radius:4px;background:#f1f5f9;font-size:10px;color:#64748b}
.sd-more{display:inline-block;margin-top:10px;font-size:13px;font-weight:700;color:var(--blue);text-decoration:none}
.sd-crumb{font-size:13px;color:var(--muted);margin-bottom:8px}.sd-crumb a{color:var(--blue);text-decoration:none}
.sd-empty{padding:30px;text-align:center;color:var(--muted);background:#fff;border:1px dashed var(--line);border-radius:12px}
@media(max-width:1100px){.sd{grid-template-columns:240px minmax(0,1fr)}.sd-right{grid-column:1/-1;position:static;max-height:none}}
@media(max-width:760px){.sd{grid-template-columns:minmax(0,1fr)}.sd-side{position:static;max-height:none}.sd-nav{display:flex;overflow-x:auto;gap:4px}.sd-nav a{white-space:nowrap}}
</style>@endpush
@php
    $base = $sector ? route('sectors.show', $sector->slug) : route('sectors.index');
@endphp
@section('content')
<div class="sd">
    {{-- Left: search, location filters, sector navigation --}}
    <aside class="sd-side">
        <form class="sd-box" method="get" action="{{ $base }}">
            <h3>Search</h3>
            <input class="sd-input" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Company, brand or product">
            <input class="sd-input" name="state" list="sd-states" value="{{ $filters['state'] ?? '' }}" placeholder="State">
            <input class="sd-input" name="city" list="sd-cities" value="{{ $filters['city'] ?? '' }}" placeholder="City">
            <input class="sd-input" name="industrial_area" list="sd-areas" value="{{ $filters['industrial_area'] ?? '' }}" placeholder="Industrial area">
            <input class="sd-input" name="role" value="{{ $filters['role'] ?? '' }}" placeholder="Role, e.g. VMC Operator">
            <button class="sd-btn">Apply</button>
            @if($filters)<a class="sd-clear" href="{{ $base }}">Clear filters</a>@endif
            <datalist id="sd-states">@foreach($locations['states'] as $v)<option value="{{ $v }}">@endforeach</datalist>
            <datalist id="sd-cities">@foreach($locations['cities'] as $v)<option value="{{ $v }}">@endforeach</datalist>
            <datalist id="sd-areas">@foreach($locations['areas'] as $v)<option value="{{ $v }}">@endforeach</datalist>
        </form>
        <nav class="sd-box sd-nav">
            <h3>Sectors</h3>
            <a href="{{ route('sectors.index', $filters) }}" class="{{ $sector ? '' : 'on' }}"><span class="sd-sym">ALL</span>All sectors</a>
            @foreach($tree as $item)
                <a href="{{ route('sectors.show', [$item->slug] + $filters) }}" class="{{ $sector?->id === $item->id ? 'on' : '' }}"><span class="sd-sym">{{ $item->symbol }}</span>{{ $item->name }}<span class="sd-count">{{ $item->companies_count }}</span></a>
            @endforeach
        </nav>
    </aside>

    {{-- Centre: categorised companies --}}
    <main>
        @if($sector)
            <nav class="sd-crumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a> › <a href="{{ route('sectors.index') }}">Companies by Sector</a> › <span>{{ $sector->name }}</span></nav>
            <div class="sd-head"><span class="sd-sym">{{ $sector->symbol }}</span><h1>{{ $sector->name }}</h1></div>
            @if(!empty($flow))
                <div class="sd-flow">
                    @foreach($flow as $stage)
                        <div class="sd-stage"><b>{{ $stage['name'] }}</b><small>{{ implode(' · ', $stage['departments']) }}</small>
                            @foreach($stage['roles'] as $role)<a href="{{ route('sectors.show', [$sector->slug, 'role' => $role] + \Illuminate\Support\Arr::except($filters, 'role')) }}" class="{{ ($filters['role'] ?? '') === $role ? 'on' : '' }}">{{ $role }}</a>@endforeach
                        </div>
                    @endforeach
                </div>
            @endif
            @if($groups->count() > 1)
                <div class="sd-pills">@foreach($groups as $group)<a href="#{{ $group->category->slug }}">{{ $group->category->name }} · {{ $group->companies->count() }}</a>@endforeach</div>
            @endif
            @forelse($groups as $group)
                <section class="sd-group" id="{{ $group->category->slug }}">
                    <h2>{{ $group->category->name }} <small>{{ $group->companies->count() }} companies</small></h2>
                    @php $expanded = request('all') === $group->category->slug; @endphp
                    <div class="sd-grid">@foreach($expanded ? $group->companies : $group->companies->take(24) as $company)@include('sectors.partials.card', ['company' => $company])@endforeach</div>
                    @if(!$expanded && $group->companies->count() > 24)<a class="sd-more" href="{{ route('sectors.show', [$sector->slug, 'all' => $group->category->slug] + $filters) }}#{{ $group->category->slug }}">Show all {{ $group->companies->count() }} companies</a>@endif
                </section>
            @empty
                <div class="sd-empty">No companies in {{ $sector->name }} match these filters.</div>
            @endforelse
        @elseif($results !== null)
            <div class="sd-head"><h1>{{ $results->count() }} {{ \Illuminate\Support\Str::plural('company', $results->count()) }} found</h1></div>
            @if($results->isEmpty())<div class="sd-empty">No company, brand or plant matches these filters.</div>@endif
            @foreach($results->groupBy(fn ($c) => optional($c->categories->firstWhere('parent_id', '!=', null))->name ?? 'Other') as $label => $companies)
                <section class="sd-group"><h2>{{ $label }} <small>{{ $companies->count() }}</small></h2>
                    <div class="sd-grid">@foreach($companies as $company)@include('sectors.partials.card', ['company' => $company])@endforeach</div>
                </section>
            @endforeach
        @else
            <div class="sd-head"><h1>Companies by Sector</h1></div>
            <div class="sd-tiles">
                @foreach($tree as $item)
                    <div class="sd-tile">
                        <a class="sd-tile-head" href="{{ route('sectors.show', $item->slug) }}"><span class="sd-sym">{{ $item->symbol }}</span><strong>{{ $item->name }}</strong><span class="sd-count">{{ $item->companies_count }}</span></a>
                        @foreach($item->children as $child)
                            <div class="sd-sub">
                                <a class="sd-subname" href="{{ route('sectors.show', $item->slug) }}#{{ $child->slug }}">{{ $child->name }} <span class="sd-count">{{ $child->companies_count }}</span></a>
                                <p>{{ implode(' · ', $child->preview ?? []) }}@if($child->companies_count > count($child->preview ?? [])) · +{{ $child->companies_count - count($child->preview ?? []) }} more @endif</p>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            @if(isset($hubs) && $hubs->isNotEmpty())
                <h2 class="sd-h2" id="hubs">Industrial areas &amp; tech parks</h2>
                <div class="sd-hubs">
                    @foreach($hubs as $hub)
                        <a class="sd-hub" href="{{ route('sectors.index', ['industrial_area' => $hub->industrial_area]) }}"><strong>{{ $hub->industrial_area }}</strong><span>{{ collect([$hub->city, $hub->state])->filter()->unique()->implode(', ') }} · {{ $hub->companies_count }} {{ \Illuminate\Support\Str::plural('company', $hub->companies_count) }}</span></a>
                    @endforeach
                </div>
            @endif
            @if($tree->isEmpty())<div class="sd-empty">The sector catalog has not been imported yet. Run <code>php artisan companies:import-catalog --now</code>.</div>@endif
        @endif
    </main>

    {{-- Right: roles hired in this sector and live openings --}}
    <aside class="sd-side sd-right">
        @if($sector && $sector->roles)
            <div class="sd-box sd-roles"><h3>Roles hired</h3>
                @foreach($sector->roles as $department => $roles)
                    <h4>{{ $department }}</h4>
                    @foreach($roles as $role)<a href="{{ route('sectors.show', [$sector->slug, 'role' => $role] + \Illuminate\Support\Arr::except($filters, 'role')) }}" class="{{ ($filters['role'] ?? '') === $role ? 'on' : '' }}">{{ $role }}</a>@endforeach
                @endforeach
            </div>
        @endif
        <div class="sd-box"><h3>Latest openings</h3>
            @forelse($openings as $job)
                <a class="sd-open" href="{{ $job->slug ? route('jobs.show', $job->slug) : route('opportunities.show', $job->id) }}"><strong>{{ $job->title }}</strong><span>{{ $job->company?->name }}@if($job->location) · {{ $job->location }}@endif @if($job->posted_at) · {{ $job->posted_at->diffForHumans(null, true) }}@endif @if($job->posting_source === 'job_board')<span class="sd-src">via {{ ucfirst($job->source) }}</span>@endif</span></a>
            @empty
                <p class="sd-meta" style="margin:0">{{ $sector || $results !== null ? 'No live openings match yet. The daily batch refreshes company career pages.' : 'Choose a sector or search to see openings.' }}</p>
            @endforelse
        </div>
    </aside>
</div>
@endsection
