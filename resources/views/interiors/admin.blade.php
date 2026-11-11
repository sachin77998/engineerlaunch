@extends('layouts.site')
@section('title', 'Home interiors — admin')
@section('content')
@include('properties._nav')
@php
    $c = config('interiors');
    $PF = \App\Services\Property\PropertyFormat::class;
@endphp
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <h1 style="margin:22px 0 10px">Interiors quote requests</h1>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
        <a class="pr-btn sm {{ $status ? 'ghost' : '' }}" href="{{ route('admin.interiors.index') }}">All ({{ $counts->sum() }})</a>
        @foreach($c['statuses'] as $k => $l)<a class="pr-btn sm {{ $status === $k ? '' : 'ghost' }}" href="{{ route('admin.interiors.index', ['status' => $k]) }}">{{ $l }} ({{ $counts[$k] ?? 0 }})</a>@endforeach
        <a class="pr-btn sm ghost" href="#ideas">Design ideas</a><a class="pr-btn sm ghost" href="#projects">Projects</a>
    </div>
    @forelse($leads as $l)
        <div class="pr-card-box" style="margin-bottom:10px;display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:14px">
            <div><b>{{ $l->name }}</b> <span class="pr-tag">{{ $l->statusLabel() }}</span>
                <div style="font-size:14px"><a href="tel:+91{{ $l->phone }}">+91 {{ $l->phone }}</a>@if($l->email) · <a href="mailto:{{ $l->email }}">{{ $l->email }}</a>@endif · {{ $l->city }} · {{ $l->created_at->format('d M Y H:i') }}</div>
                <div class="pr-mute" style="font-size:13.5px">{{ collect([$l->bhk ? $c['bhk'][$l->bhk] ?? null : null, $l->package ? $c['packages'][$l->package][0] ?? null : null, $l->style ? $c['styles'][$l->style][0] ?? null : null, $l->possession])->filter()->implode(' · ') }}
                    @if($l->estimate_total) · estimate {{ $PF::full($l->estimate['low']) }} – {{ $PF::full($l->estimate['high']) }}@endif @if($l->idea) · liked <a href="{{ route('interiors.idea', $l->idea) }}">{{ $l->idea->title }}</a>@endif</div>
                @if($l->notes)<p style="margin:6px 0 0">{{ $l->notes }}</p>@endif</div>
            <form method="post" action="{{ route('admin.interiors.lead', $l) }}" style="display:grid;gap:6px">@csrf
                <select name="status">@foreach($c['statuses'] as $k => $s)<option value="{{ $k }}" @selected($l->status === $k)>{{ $s }}</option>@endforeach</select>
                <textarea name="admin_note" rows="2" maxlength="2000" placeholder="Note">{{ $l->admin_note }}</textarea><button class="pr-btn sm">Save</button></form>
        </div>
    @empty
        <div class="pr-card-box">No quote requests yet.</div>
    @endforelse
    {{ $leads->links() }}

    <h2 id="ideas" style="margin:30px 0 10px">Design ideas (your photos)</h2>
    <form method="post" action="{{ route('admin.interiors.ideas.store') }}" enctype="multipart/form-data" class="pr-card-box" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;align-items:end">
        @csrf
        <input type="text" name="title" required maxlength="120" placeholder="Title, e.g. Warm wood living room">
        <select name="room"><option value="">Room</option>@foreach($c['rooms'] as $k => [$l])<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        <select name="style"><option value="">Style</option>@foreach($c['styles'] as $k => [$l])<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        <select name="category"><option value="">Category</option>@foreach($c['categories'] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        <input type="text" name="description" maxlength="600" placeholder="Short description" style="grid-column:span 2">
        <label><input type="checkbox" name="is_trending" value="1"> Trending</label>
        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
        <button class="pr-btn sm">Upload</button>
    </form>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px;margin-top:12px">
        @foreach($ideas as $i)
            <form method="post" action="{{ route('admin.interiors.ideas.update', $i->id) }}" class="pr-card-box" style="padding:8px;font-size:13px">@csrf
                <img src="{{ $i->imageUrl() }}" alt="" style="width:100%;height:110px;object-fit:cover;border-radius:8px">
                <b style="display:block;margin:4px 0">{{ \Illuminate\Support\Str::limit($i->title, 30) }}</b>
                <label><input type="checkbox" name="is_trending" value="1" @checked($i->is_trending)> Trending</label> <label><input type="checkbox" name="is_active" value="1" @checked($i->is_active)> Live</label>
                <div style="display:flex;gap:4px;margin-top:4px"><button class="pr-btn sm">Save</button><button class="pr-btn danger sm" name="action" value="delete" onclick="return confirm('Delete this idea?')">Delete</button></div>
            </form>
        @endforeach
    </div>

    <h2 id="projects" style="margin:30px 0 10px">Completed projects</h2>
    @foreach([...$projects, null] as $p)
        <form method="post" action="{{ $p ? route('admin.interiors.projects.update', $p->id) : route('admin.interiors.projects.store') }}" enctype="multipart/form-data" class="pr-card-box" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:8px;margin-bottom:10px;align-items:end">
            @csrf
            @if($p)<img src="{{ $p->coverUrl() }}" alt="" style="width:100%;height:90px;object-fit:cover;border-radius:8px">@endif
            <input type="text" name="title" required maxlength="120" value="{{ $p?->title }}" placeholder="{{ $p ? '' : 'New project, e.g. 3BHK Model Town' }}">
            <input type="text" name="city" required maxlength="80" value="{{ $p?->city }}" placeholder="City">
            <select name="style"><option value="">Style</option>@foreach($c['styles'] as $k => [$l])<option value="{{ $k }}" @selected($p?->style === $k)>{{ $l }}</option>@endforeach</select>
            <input type="number" name="bhk" min="1" max="10" value="{{ $p?->bhk }}" placeholder="BHK">
            <input type="number" name="rating" min="1" max="5" value="{{ $p?->rating }}" placeholder="Client rating 1–5">
            <input type="url" name="video_url" value="{{ $p?->video_url }}" placeholder="YouTube / Vimeo link">
            <input type="text" name="reviewer" maxlength="120" value="{{ $p?->reviewer }}" placeholder="Client name">
            <textarea name="review" rows="2" maxlength="3000" placeholder="Client review" style="grid-column:span 2">{{ $p?->review }}</textarea>
            <label>Cover {{ $p ? '(replace)' : '*' }}<input type="file" name="cover" accept="image/jpeg,image/png,image/webp" @unless($p) required @endunless></label>
            <label>Add photos<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label>
            <label><input type="checkbox" name="is_featured" value="1" @checked($p?->is_featured)> Featured</label>
            <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($p ? $p->is_active : true)> Live</label>
            <button class="pr-btn sm">{{ $p ? 'Save' : 'Add project' }}</button>
        </form>
        @if($p)<form method="post" action="{{ route('admin.interiors.projects.destroy', $p->id) }}" onsubmit="return confirm('Delete this project and its photos?')" style="margin:-4px 0 14px">@csrf<button class="pr-btn danger sm">Delete {{ $p->title }}</button> <a href="{{ route('interiors.project', $p) }}">View</a></form>@endif
    @endforeach
</div></div>
@endsection
