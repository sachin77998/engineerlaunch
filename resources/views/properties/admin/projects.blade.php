@extends('layouts.site')
@section('title', 'Projects — admin')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap">
    @include('properties.admin._tabs')
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center">
        <h1 style="font-size:22px;margin:0">Projects</h1>
        <form method="get" style="display:flex;gap:8px"><input type="search" name="q" value="{{ $q }}" placeholder="Name, city or locality" aria-label="Search"><button class="pr-btn sm">Search</button></form>
        <a class="pr-btn" href="{{ route('admin.projects.create') }}">+ New project</a>
    </div>
    <div class="pr-card-box" style="padding:0;overflow-x:auto;margin-top:12px">
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead><tr style="text-align:left;background:#f9fafb"><th style="padding:10px">Project</th><th style="padding:10px">City</th><th style="padding:10px">Status</th><th style="padding:10px">Photos</th><th style="padding:10px">Listings</th><th style="padding:10px">Offers</th><th style="padding:10px">Views</th></tr></thead>
            @forelse($projects as $p)
                <tr style="border-top:1px solid var(--pr-line)">
                    <td style="padding:10px"><a href="{{ route('admin.projects.edit', $p->id) }}"><b>{{ $p->name }}</b></a>@if($p->is_featured) <span class="pr-tag">Featured</span>@endif @unless($p->is_active)<span class="pr-tag amber">Off</span>@endunless<br><span class="pr-mute">{{ $p->builder?->name }}</span></td>
                    <td style="padding:10px">{{ $p->locality ? $p->locality . ', ' : '' }}{{ $p->city }}</td>
                    <td style="padding:10px">{{ $p->statusLabel() }}</td>
                    <td style="padding:10px">{{ $p->images_count }}</td><td style="padding:10px">{{ $p->properties_count }}</td><td style="padding:10px">{{ $p->live_offers_count }}</td><td style="padding:10px">{{ number_format($p->views) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="padding:16px" class="pr-mute">No projects yet.</td></tr>
            @endforelse
        </table>
    </div>
    <div style="margin-top:12px">{{ $projects->links() }}</div>
</div></div>
@endsection
