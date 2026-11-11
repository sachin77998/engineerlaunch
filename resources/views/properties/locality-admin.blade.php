@extends('layouts.site')
@section('title', 'Locality reviews — admin')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin:22px 0 12px;flex-wrap:wrap"><h1 style="margin:0">Locality reviews</h1><a href="{{ route('admin.properties.index') }}">Property listings →</a></div>
    <form method="get" style="display:flex;gap:8px;margin-bottom:12px"><input type="search" name="q" value="{{ request('q') }}" placeholder="Locality or city" style="max-width:320px" aria-label="Search"><button class="pr-btn sm">Search</button></form>
    <div class="pr-card-box" style="padding:0;overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13.5px">
            <thead><tr style="text-align:left;background:#f9fafb"><th style="padding:10px">Locality</th><th style="padding:10px">Reviewer</th><th style="padding:10px">Rating</th><th style="padding:10px">Review</th><th style="padding:10px">Helpful</th><th style="padding:10px"></th></tr></thead>
            <tbody>
            @forelse($reviews as $r)
                <tr style="border-top:1px solid var(--pr-line);{{ $r->status === 'hidden' ? 'opacity:.55' : '' }}">
                    <td style="padding:10px"><a href="{{ route('localities.show', [$r->city_slug, $r->locality_key]) }}">{{ $r->locality }}</a><br><span class="pr-mute">{{ $r->city }} · {{ $r->created_at->format('d M Y') }}</span></td>
                    <td style="padding:10px">{{ $r->user?->name }}<br><span class="pr-mute">{{ $r->roleLabel() }} · {{ $r->user?->email }}</span></td>
                    <td style="padding:10px;white-space:nowrap">{{ number_format($r->score(), 1) }} ★</td>
                    <td style="padding:10px;max-width:460px">@if($r->positives)<b>+</b> {{ \Illuminate\Support\Str::limit($r->positives, 160) }}<br>@endif @if($r->negatives)<b>−</b> {{ \Illuminate\Support\Str::limit($r->negatives, 160) }}@endif</td>
                    <td style="padding:10px">{{ $r->helpful_yes }} / {{ $r->helpful_no }}</td>
                    <td style="padding:10px"><form method="post" action="{{ route('admin.localities.toggle', $r) }}">@csrf<button class="pr-btn {{ $r->status === 'published' ? 'danger' : '' }} sm">{{ $r->status === 'published' ? 'Hide' : 'Publish' }}</button></form></td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:16px">No reviews yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px">{{ $reviews->links() }}</div>
</div></div>
@endsection
