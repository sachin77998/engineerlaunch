@extends('layouts.site')
@section('title', 'Builders — admin')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap">
    @include('properties.admin._tabs')
    <h1 style="font-size:22px">Builders</h1>
    <div style="display:grid;gap:12px">
        @foreach($builders->prepend(new \App\Models\Property\Builder(['is_active' => true])) as $b)
            <details class="pr-card-box" @if(!$b->exists) open @endif>
                <summary style="cursor:pointer;font-weight:700">{{ $b->exists ? $b->name . ' · ' . $b->projects_count . ' projects' . ($b->is_active ? '' : ' (off)') : '+ Add a builder' }}</summary>
                <form method="post" enctype="multipart/form-data" action="{{ $b->exists ? route('admin.builders.update', $b->id) : route('admin.builders.store') }}" style="margin-top:12px">@csrf
                    <div class="pr-grid">
                        <div><label class="f">Name</label><input name="name" value="{{ $b->name }}" required maxlength="160"></div>
                        <div><label class="f">Website</label><input type="url" name="website" value="{{ $b->website }}" maxlength="191"></div>
                        <div><label class="f">Sales phone</label><input name="phone" value="{{ $b->phone }}" maxlength="20"></div>
                        <div><label class="f">Sales email</label><input type="email" name="email" value="{{ $b->email }}" maxlength="191"></div>
                        <div><label class="f">Established (year)</label><input type="number" name="established_year" value="{{ $b->established_year }}" min="1800" max="{{ date('Y') }}"></div>
                        <div><label class="f">Logo</label><input type="file" name="logo" accept="image/jpeg,image/png,image/webp">@if($b->logoUrl())<img src="{{ $b->logoUrl() }}" alt="" style="height:40px;margin-top:6px">@endif</div>
                        <div class="full"><label class="f">About</label><textarea name="description" rows="3" maxlength="5000">{{ $b->description }}</textarea></div>
                    </div>
                    <label class="pr-check"><input type="checkbox" name="is_active" value="1" @checked($b->is_active)> Active</label>
                    <button class="pr-btn sm" style="margin-top:10px">Save</button> @if($b->exists)<a href="{{ route('builders.show', $b) }}" target="_blank">view page ↗</a>@endif
                </form>
            </details>
        @endforeach
    </div>
</div></div>
@endsection
