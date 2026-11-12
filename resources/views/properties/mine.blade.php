@extends('layouts.site')
@section('title', 'My properties — ' . config('properties.brand'))
@section('content')
@include('properties._nav')
@php($steps = fn ($p) => \App\Http\Controllers\PropertyPostController::steps($p))
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}@if(session('posted')) · <a href="{{ route('properties.show', session('posted')) }}">View your listing →</a>@endif</div>@endif
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin:22px 0 14px;flex-wrap:wrap"><h1 style="margin:0">My properties</h1><a class="pr-btn" href="{{ route('properties.post') }}">Post another property</a></div>
    @forelse($items as $p)
        @php($pending = collect($steps($p))->first(fn ($s) => !in_array($s, $p->steps_done ?? [], true)))
        <div class="pr-card-box" style="margin-bottom:12px">
            <div style="display:grid;grid-template-columns:120px minmax(0,1fr) auto;gap:14px;align-items:center">
                <div class="pr-thumb" style="min-height:84px;border-radius:10px;overflow:hidden">@if($p->coverUrl())<img src="{{ $p->coverUrl() }}" alt="">@else⌂@endif</div>
                <div style="min-width:0">
                    <b>{{ $p->title ?: $p->kindLabel() . ' for ' . $p->listing . ' (draft)' }}</b>
                    <div class="pr-mute" style="font-size:13.5px">{{ $p->headlinePrice() }} · {{ number_format($p->views) }} views · {{ $p->leads_count }} {{ $p->leads_count === 1 ? 'enquiry' : 'enquiries' }}</div>
                    <div style="margin-top:6px">
                        <span class="pr-tag {{ ['active' => 'ok', 'draft' => 'amber', 'closed' => '', 'inactive' => ''][$p->status] }}">{{ ['active' => 'Live', 'draft' => 'Draft', 'closed' => $p->listing === 'rent' ? 'Rented out' : 'Sold', 'inactive' => 'Taken down'][$p->status] }}</span>
                        @if($p->status === 'draft' && $pending)<span class="pr-mute" style="font-size:13px">Next: {{ (\App\Http\Controllers\PropertyPostController::labels()[$pending] ?? $pending) }}</span>@endif
                    </div>
                </div>
                <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end">
                    @if($p->status !== 'draft')<a class="pr-btn ghost sm" href="{{ route('properties.show', $p) }}">View</a>@endif
                    <a class="pr-btn ghost sm" href="{{ route('properties.step', [$p->id, $p->status === 'draft' && $pending ? $pending : 'details']) }}">{{ $p->status === 'draft' ? 'Continue' : 'Edit' }}</a>
                    @if($p->status === 'active')<form method="post" action="{{ route('properties.status', [$p->id, 'close']) }}">@csrf<button class="pr-btn grey sm">Mark {{ $p->listing === 'rent' ? 'rented out' : 'sold' }}</button></form>@endif
                    @if($p->status === 'closed')<form method="post" action="{{ route('properties.status', [$p->id, 'reopen']) }}">@csrf<button class="pr-btn sm">Make live again</button></form>@endif
                    <form method="post" action="{{ route('properties.status', [$p->id, 'delete']) }}" onsubmit="return confirm('Delete this listing?')">@csrf<button class="pr-btn danger sm">Delete</button></form>
                </div>
            </div>
            @if(($leads[$p->id] ?? collect())->isNotEmpty())
                <details style="margin-top:12px"><summary style="cursor:pointer;font-weight:700">Enquiries ({{ $leads[$p->id]->count() }})</summary>
                    <div style="overflow-x:auto;margin-top:8px"><table style="width:100%;border-collapse:collapse;font-size:13.5px">
                        <thead><tr style="text-align:left;color:var(--pr-mute)"><th style="padding:6px">When</th><th style="padding:6px">Type</th><th style="padding:6px">Name</th><th style="padding:6px">Phone / email</th><th style="padding:6px">Message</th></tr></thead>
                        <tbody>@foreach($leads[$p->id] as $l)<tr style="border-top:1px solid var(--pr-line)">
                            <td style="padding:6px;white-space:nowrap">{{ $l->created_at->format('d M, g:i A') }}</td>
                            <td style="padding:6px">{{ ['phone' => 'Viewed number', 'message' => 'Message', 'visit' => 'Visit request'][$l->kind] ?? $l->kind }}@if($l->visit_at)<br><b>{{ $l->visit_at->format('d M, g:i A') }}</b>@endif</td>
                            <td style="padding:6px">{{ $l->name }}</td>
                            <td style="padding:6px">@if($l->phone)<a href="tel:{{ $l->phone }}">{{ $l->phone }}</a><br>@endif{{ $l->email }}</td>
                            <td style="padding:6px;white-space:pre-line">{{ $l->message }}</td></tr>@endforeach</tbody>
                    </table></div>
                </details>
            @endif
        </div>
    @empty
        <div class="pr-card-box" style="text-align:center;padding:36px"><h2>No listings yet</h2><a class="pr-btn" href="{{ route('properties.post') }}">Post free property ad</a></div>
    @endforelse
</div></div>
@endsection
