@extends('layouts.site')
@section('title', 'State Approvals & Policies | Licences, NOCs and document checklists by state')
@include('approvals._styles')
@section('content')
<div class="sa-wrap">
    <p class="sa-crumbs"><a href="{{ route('industrial.index') }}">Industrial areas</a> / State approvals</p>
    <h1>State approvals &amp; policies</h1>
    <form class="sa-card sa-filters" method="get" onsubmit="if(this.s.value){location.href=this.s.value;return false}">
        <div><label for="sa-state">State</label><select class="field" id="sa-state" name="s">@foreach($states as $st)<option value="{{ route('approvals.state', $st->slug) }}">{{ $st->name }}</option>@endforeach</select></div>
        <button class="primary" type="submit">Open</button>
    </form>
    <div class="sa-grid">
        @foreach($states->sortByDesc(fn ($s) => (int) $s->services_count) as $st)
            <a class="sa-state" href="{{ route('approvals.state', $st->slug) }}"><b>{{ $st->name }}</b>
                <small>
                    @if($st->services_count)<span class="sa-pill ok">{{ number_format($st->services_count) }} approvals</span>@if($policyCounts[$st->id] ?? 0)<span class="sa-pill">{{ $policyCounts[$st->id] }} policies &amp; acts</span>@endif
                    @elseif($st->portal_url)<span class="sa-pill grey">Portal link</span>
                    @else<span class="sa-pill grey">Via {{ $national[0] }}</span>@endif
                    @if($st->portal_name)<br>{{ $st->portal_name }}@endif
                </small></a>
        @endforeach
    </div>
</div>
@endsection
