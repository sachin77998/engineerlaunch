@extends('layouts.site')
@section('title', $state->name . ' business approvals, licences, NOCs and policies')
@include('approvals._styles')
@php
    $tab = $f['tab'] ?? 'services';
    $labels = ['department' => 'Department', 'stage' => 'Stage', 'size' => 'Unit size', 'location' => 'Business location', 'risk' => 'Risk / pollution category', 'investor' => 'Investor type'];
    $kinds = ['policy' => 'Policies', 'act' => 'Acts', 'rule' => 'Rules', 'notification' => 'Notifications'];
@endphp
@section('content')
<div class="sa-wrap">
    <p class="sa-crumbs"><a href="{{ route('approvals.index') }}">State approvals</a> / {{ $state->name }}</p>
    <h1>{{ $state->name }}</h1>
    <div class="sa-links">
        @if($source?->portal_url)<a href="{{ $source->portal_url }}" target="_blank" rel="noopener nofollow">{{ $source->portal_name ?: 'State single-window portal' }}</a>@endif
        <a href="{{ $national[1] }}" target="_blank" rel="noopener nofollow">{{ $national[0] }}</a>
    </div>

    <nav class="sa-tabs">
        <a href="{{ route('approvals.state', [$state->slug] + array_filter(array_merge($f, ['tab' => null]))) }}" class="{{ $tab === 'services' ? 'on' : '' }}">Approvals ({{ number_format($services->total()) }})</a>
        <a href="{{ route('approvals.state', [$state->slug, 'tab' => 'policies']) }}" class="{{ $tab === 'policies' ? 'on' : '' }}">Policies &amp; acts ({{ $policies->flatten(1)->count() }})</a>
    </nav>

    @if($tab === 'policies')
        @foreach($kinds as $kind => $label)
            @if($policies->has($kind))
                <section class="sa-card"><h2>{{ $label }}</h2>
                    <ul class="sa-list">@foreach($policies[$kind] as $p)
                        <li><div><b>{{ $p->title }}</b>@if($p->department)<div class="sa-sub">{{ $p->department }}</div>@endif</div>
                            <div class="sa-right sa-links">@foreach($p->links as $l)<a href="{{ $l->url }}" target="_blank" rel="noopener nofollow">{{ $l->language ?: 'PDF' }}</a>@endforeach</div></li>
                    @endforeach</ul>
                </section>
            @endif
        @endforeach
        @if($policies->isEmpty())<div class="sa-card">No policies or acts have been read for {{ $state->name }} yet.@if($source?->portal_url) <a href="{{ $source->portal_url }}" target="_blank" rel="noopener nofollow">Open the state portal</a>.@endif</div>@endif
    @else
        <form class="sa-card" method="get">
            <div class="sa-filters">
                <div style="grid-column:span 2"><label for="sa-q">Search approvals</label><input class="field" id="sa-q" name="q" value="{{ $f['q'] ?? '' }}" maxlength="120" placeholder="e.g. factory licence, fire NOC, water connection"></div>
                @foreach($labels as $key => $label)
                    @if(($options[$key] ?? collect())->isNotEmpty())
                        <div><label for="sa-{{ $key }}">{{ $label }}</label><select class="field" id="sa-{{ $key }}" name="{{ $key }}"><option value="">All</option>@foreach($options[$key] as $o)<option value="{{ $o }}" @selected(($f[$key] ?? '') === $o)>{{ $o }}</option>@endforeach</select></div>
                    @endif
                @endforeach
                <div style="display:flex;gap:8px"><button class="primary" type="submit">Apply</button>@if(array_filter($f))<a href="{{ route('approvals.state', $state->slug) }}" style="align-self:center">Clear</a>@endif</div>
            </div>
        </form>

        @if($stageCounts->isNotEmpty())
            <div>@foreach($stageCounts as $stage => $n)<a class="sa-pill {{ ($f['stage'] ?? '') === $stage ? 'on' : '' }}" href="{{ route('approvals.state', [$state->slug] + array_filter(array_merge($f, ['stage' => ($f['stage'] ?? '') === $stage ? null : $stage, 'tab' => null]))) }}">{{ $stage }} · {{ $n }}</a>@endforeach</div>
        @endif

        <section class="sa-card">
            @if($services->isEmpty())
                @if($source?->services_count)
                    No approvals match these filters.
                @else
                    {{ $state->name }}'s approvals are not published here yet.@if($source?->portal_url) Apply and check documents on <a href="{{ $source->portal_url }}" target="_blank" rel="noopener nofollow">{{ $source->portal_name }}</a> or @else Use @endif <a href="{{ $national[1] }}" target="_blank" rel="noopener nofollow">{{ $national[0] }}</a>.
                @endif
            @else
                <ul class="sa-list">
                    @foreach($services as $s)
                        @php($c = $docCounts[$s->id] ?? null)
                        <li><div><a class="sa-name" href="{{ route('approvals.service', [$state->slug, $s->external_id]) }}">{{ $s->name }}</a>
                                <div class="sa-sub">{{ $s->department }}@if($s->service_type) · {{ $s->service_type }}@endif</div>
                                <div>@foreach(json_decode((string) $s->stages, true) ?: [] as $st)<span class="sa-pill grey">{{ $st }}</span>@endforeach</div></div>
                            <div class="sa-right">@if($c && ($c->checklist || $c->portal)){{ max((int) $c->checklist, (int) $c->portal) }} documents @elseif($s->checklist_url)<a href="{{ $s->checklist_url }}" target="_blank" rel="noopener nofollow">Checklist PDF</a>@endif</div></li>
                    @endforeach
                </ul>
                @if($services->hasPages())<div class="sa-pages"><span>@if($services->previousPageUrl())<a href="{{ $services->previousPageUrl() }}">&larr; Previous</a>@endif</span><span>Page {{ $services->currentPage() }} of {{ $services->lastPage() }}</span><span>@if($services->nextPageUrl())<a href="{{ $services->nextPageUrl() }}">Next &rarr;</a>@endif</span></div>@endif
            @endif
        </section>
        @if($source?->last_fetched_at && $source->services_count)<p class="sa-sub">Read from {{ $source->portal_name }} on {{ \Carbon\Carbon::parse($source->last_fetched_at)->format('d M Y') }}.</p>@endif
    @endif
</div>
@endsection
