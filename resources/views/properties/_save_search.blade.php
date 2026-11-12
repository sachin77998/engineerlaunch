{{-- "Save search": keep these filters and get an email when new listings match. Expects $mode, $city, $f, $heading. --}}
@php
    $clean = \App\Services\Property\PropertySearch::clean($f);
    $saved = auth()->check() ? \App\Models\Property\SavedSearch::where(['user_id' => auth()->id(), 'fingerprint' => \App\Models\Property\SavedSearch::fingerprint($mode, $city, $clean)])->first() : null;
@endphp
@if($saved)
    <a class="pr-btn ghost sm" href="{{ route('saved-searches.index') }}" title="Alerts: {{ \App\Models\Property\SavedSearch::FREQUENCIES[$saved->frequency] }}">🔔 Search saved</a>
@elseif(auth()->check())
    <details class="ss-save" style="position:relative">
        <summary class="pr-btn ghost sm" style="list-style:none;cursor:pointer">🔔 Save search &amp; get alerts</summary>
        <form method="post" action="{{ route('saved-searches.store') }}" class="pr-card-box" style="position:absolute;right:0;top:110%;z-index:30;width:300px;display:grid;gap:10px;box-shadow:0 14px 30px rgba(0,0,0,.12)">@csrf
            <input type="hidden" name="mode" value="{{ $mode }}"><input type="hidden" name="city" value="{{ $city }}">
            <input type="hidden" name="query" value="{{ http_build_query($clean) }}">
            <div><label class="f" for="ss-name">Name</label><input id="ss-name" name="name" maxlength="160" value="{{ \Illuminate\Support\Str::limit($heading . ($areas->isNotEmpty() ? ' · ' . $areas->implode(', ') : ''), 150, '') }}"></div>
            <div><label class="f" for="ss-freq">Email me</label><select id="ss-freq" name="frequency">@foreach(\App\Models\Property\SavedSearch::FREQUENCIES as $k => $label)<option value="{{ $k }}" @selected($k === 'daily')>{{ $label }}</option>@endforeach</select></div>
            <button class="pr-btn">Save</button>
        </form>
    </details>
@else
    <a class="pr-btn ghost sm" href="{{ route('login') }}">🔔 Log in to save this search</a>
@endif
