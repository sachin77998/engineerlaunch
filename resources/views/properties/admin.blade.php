@extends('layouts.site')
@section('title', 'Property listings — admin')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    <h1 style="margin:22px 0 12px">Property listings</h1>
    <p style="display:flex;gap:8px;flex-wrap:wrap"><a class="pr-btn ghost sm" href="{{ route('admin.projects.index') }}">Projects</a><a class="pr-btn ghost sm" href="{{ route('admin.builders.index') }}">Builders</a><a class="pr-btn ghost sm" href="{{ route('admin.property-cities.index') }}">Cities &amp; localities</a><a class="pr-btn ghost sm" href="{{ route('admin.property-options.index') }}">Option lists</a></p>
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
        <input type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="Title, city, locality, owner name or phone" style="max-width:380px" aria-label="Search">
        <select name="status" style="width:auto" aria-label="Status"><option value="">All statuses</option>@foreach(['active' => 'Live', 'draft' => 'Draft', 'closed' => 'Rented / sold', 'inactive' => 'Taken down'] as $k => $label)<option value="{{ $k }}" @selected(($f['status'] ?? '') === $k)>{{ $label }}</option>@endforeach</select>
        <label class="pr-check" style="margin:0"><input type="checkbox" name="reported" value="1" @checked(!empty($f['reported']))> Reported only</label>
        <button class="pr-btn sm">Filter</button>
    </form>
    <div class="pr-card-box" style="padding:0;overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13.5px">
            <thead><tr style="text-align:left;background:#f9fafb"><th style="padding:10px">Listing</th><th style="padding:10px">Owner</th><th style="padding:10px">Price</th><th style="padding:10px">Status</th><th style="padding:10px">Views / enquiries</th><th style="padding:10px"></th></tr></thead>
            <tbody>
            @forelse($items as $p)
                <tr style="border-top:1px solid var(--pr-line)">
                    <td style="padding:10px;min-width:260px">@if($p->status !== 'draft')<a href="{{ route('properties.show', $p) }}">{{ $p->title }}</a>@else{{ $p->kindLabel() }} for {{ $p->listing }} (draft)@endif<br><span class="pr-mute">{{ $p->created_at->format('d M Y') }}</span>
                        @if($p->open_reports)<details style="margin-top:6px"><summary class="pr-tag amber" style="cursor:pointer">⚑ {{ $p->open_reports }} open {{ $p->open_reports === 1 ? 'report' : 'reports' }}</summary>
                            <ul style="margin:6px 0;padding-left:18px">@foreach($p->reports as $rep)<li>{{ config('properties.report_reasons.' . $rep->reason, $rep->reason) }}@if($rep->details) — {{ $rep->details }}@endif <span class="pr-mute">({{ $rep->created_at->diffForHumans() }})</span></li>@endforeach</ul>
                            <form method="post" action="{{ route('admin.properties.reports', $p->id) }}">@csrf<button class="pr-btn ghost sm">Mark resolved</button></form></details>@endif</td>
                    <td style="padding:10px">{{ $p->owner?->name }}<br><span class="pr-mute">{{ $p->owner?->typeLabel() }} · {{ $p->owner?->phone }}</span>@if($p->owner?->owner_type === 'agent')<form method="post" action="{{ route('admin.properties.feature', $p->owner->id) }}" style="margin-top:4px">@csrf<button class="pr-btn ghost sm">{{ $p->owner->is_featured ? 'Remove featured' : 'Make featured dealer' }}</button></form>@endif</td>
                    <td style="padding:10px;white-space:nowrap">{{ $p->headlinePrice() }}</td>
                    <td style="padding:10px">{{ $p->status }}</td>
                    <td style="padding:10px">{{ number_format($p->views) }} / {{ $p->leads_count }}</td>
                    <td style="padding:10px">
                        @if($p->status === 'active')<form method="post" action="{{ route('admin.properties.verify', $p->id) }}" style="margin-bottom:6px">@csrf<button class="pr-btn {{ $p->is_verified ? 'grey' : 'ghost' }} sm">{{ $p->is_verified ? '✓ Verified — undo' : 'Mark verified' }}</button></form>@endif
                        @if($p->status === 'active')<form method="post" action="{{ route('admin.properties.status', $p->id) }}">@csrf<input type="hidden" name="status" value="inactive"><button class="pr-btn danger sm">Take down</button></form>
                        @elseif($p->status === 'inactive')<form method="post" action="{{ route('admin.properties.status', $p->id) }}">@csrf<input type="hidden" name="status" value="active"><button class="pr-btn sm">Restore</button></form>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:16px">No listings.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px">{{ $items->links() }}</div>
</div></div>
@endsection
