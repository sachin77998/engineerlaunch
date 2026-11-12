@extends('layouts.site')
@section('title', 'My saved searches')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap" style="max-width:900px;margin-top:22px">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <h1 style="margin:0 0 4px">My saved searches</h1>
    <p class="pr-mute" style="margin-top:0">We email you when new listings match. Save a search from any results page with “Save search &amp; get alerts”.</p>
    @forelse($searches as $s)
        <div class="pr-card-box" style="margin-top:12px;display:grid;gap:10px">
            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center">
                <a href="{{ route('saved-searches.open', $s) }}" style="font-weight:700;font-size:17px;text-decoration:none">{{ $s->name }}</a>
                @if($fresh[$s->id])<a href="{{ route('saved-searches.open', $s) }}" class="pr-tag" style="background:#dcfce7;color:#166534">{{ $fresh[$s->id] }} new since you last looked</a>@else<span class="pr-mute" style="font-size:13px">No new listings since you last looked</span>@endif
            </div>
            <form method="post" action="{{ route('saved-searches.update', $s) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end">@csrf
                <div style="flex:1;min-width:220px"><label class="f" for="n{{ $s->id }}">Name</label><input id="n{{ $s->id }}" name="name" value="{{ $s->name }}" required maxlength="160"></div>
                <div><label class="f" for="f{{ $s->id }}">Email me</label><select id="f{{ $s->id }}" name="frequency">@foreach(\App\Models\Property\SavedSearch::FREQUENCIES as $k => $label)<option value="{{ $k }}" @selected($s->frequency === $k)>{{ $label }}</option>@endforeach</select></div>
                <button class="pr-btn ghost sm">Save</button>
                <a class="pr-btn sm" href="{{ route('saved-searches.open', $s) }}">View results</a>
            </form>
            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center">
                <small class="pr-mute">Saved {{ $s->created_at->format('d M Y') }}@if($s->last_sent_at) · last email {{ $s->last_sent_at->diffForHumans() }}@endif</small>
                <form method="post" action="{{ route('saved-searches.destroy', $s) }}" onsubmit="return confirm('Delete this saved search?')">@csrf<button class="pr-btn grey sm">Delete</button></form>
            </div>
        </div>
    @empty
        <div class="pr-card-box" style="margin-top:14px;text-align:center"><p class="pr-mute">No saved searches yet.</p><a class="pr-btn" href="{{ route('properties.search', 'buy') }}">Search properties</a></div>
    @endforelse
</div></div>
@endsection
