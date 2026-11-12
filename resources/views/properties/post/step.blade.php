@extends('layouts.site')
@php
    $o = config('properties');
    $labels = \App\Http\Controllers\PropertyPostController::labels();
    $i = array_search($step, $steps, true);
    $done = $p->steps_done ?? [];
    $pct = (int) round(count(array_intersect($steps, $done)) / count($steps) * 100);
    $last = $i === count($steps) - 1;
    // Value for a field: the submitted value after a failed save, else what is stored.
    $v = fn (string $key, $default = null) => old($key, data_get($p, $key) ?? $default);
    $icons = ['details' => '⌂', 'location' => '⌖', 'rental' => '₹', 'sale' => '₹', 'rooms' => '▦', 'amenities' => '✦', 'gallery' => '▣', 'additional' => '☰', 'schedule' => '◷'];
@endphp
@section('title', $labels[$step] . ' — post your property')
@section('content')
@include('properties._nav')
@push('styles')<style>
.pw{display:grid;grid-template-columns:240px minmax(0,1fr);gap:18px;align-items:start;margin-top:18px}
.pw-steps{background:#fff;border:1px solid var(--pr-line);border-radius:16px;overflow:hidden;position:sticky;top:12px}
.pw-steps a{display:flex;align-items:center;gap:10px;padding:14px 16px;border-left:4px solid transparent;border-bottom:1px solid #f3f4f6;color:#4b5563;text-decoration:none;font-weight:600;font-size:14.5px}
.pw-steps a i{font-style:normal;width:26px;height:26px;border-radius:8px;display:grid;place-items:center;background:#f3f4f6;font-size:14px}
.pw-steps a.on{border-left-color:var(--pr-accent);color:var(--pr-ink);background:var(--pr-soft)}.pw-steps a.done i{background:#d1fae5;color:var(--pr-ok)}
.pw-steps a.locked{pointer-events:none;opacity:.55}
.pw-bar{height:6px;background:#e5e7eb;border-radius:9px;overflow:hidden;flex:1}.pw-bar span{display:block;height:100%;background:var(--pr-accent)}
.pw-foot{position:sticky;bottom:0;background:#fff;border-top:1px solid var(--pr-line);padding:12px 18px;display:flex;justify-content:center;gap:12px;margin:22px -18px -18px;border-radius:0 0 16px 16px}
@media(max-width:860px){.pw{grid-template-columns:1fr}.pw-steps{position:static;display:flex;overflow-x:auto}.pw-steps a{border-left:0;border-bottom:3px solid transparent;white-space:nowrap}.pw-steps a.on{border-bottom-color:var(--pr-accent)}}
</style>@endpush
<div class="pr"><div class="pr-wrap">
    <div style="display:flex;align-items:center;gap:14px;margin-top:18px;flex-wrap:wrap">
        <b>{{ $p->segment === 'commercial' ? 'Commercial' : 'Residential' }} · {{ $p->kindLabel() }} for {{ $p->listing }}</b>
        <div class="pw-bar" style="min-width:160px"><span style="width:{{ $pct }}%"></span></div><span class="pr-mute">{{ $pct }}% done</span>
        @if($p->status === 'active')<a class="pr-btn ghost sm" href="{{ route('properties.show', $p) }}" target="_blank">View listing</a>@endif
    </div>
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if(session('warning'))<div class="pr-flash warn">{{ session('warning') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">Please fix the highlighted fields: {{ $errors->first() }}</div>@endif
    <div class="pw">
        <nav class="pw-steps" aria-label="Steps">
            @foreach($steps as $n => $s)
                <a href="{{ route('properties.step', [$p->id, $s]) }}" class="{{ $s === $step ? 'on' : '' }} {{ in_array($s, $done, true) ? 'done' : '' }} {{ $n > 0 && !in_array($steps[$n - 1], $done, true) && !in_array($s, $done, true) && $s !== $step ? 'locked' : '' }}">
                    <i>{{ in_array($s, $done, true) ? '✓' : ($icons[$s] ?? '•') }}</i>{{ $labels[$s] }}</a>
            @endforeach
        </nav>
        <div class="pr-card-box" style="padding:18px 18px">
            <h2 style="font-size:21px">{{ $labels[$step] }}</h2>
            @if($step === 'gallery')
                @include('properties.post.steps.gallery')
                <form method="post" action="{{ route('properties.save', [$p->id, $step]) }}">@csrf
                    <div class="pw-foot">@if($i > 0)<a class="pr-btn grey" href="{{ route('properties.step', [$p->id, $steps[$i - 1]]) }}">Back</a>@endif<button class="pr-btn" type="submit">{{ $last ? 'Finish posting' : 'Save & continue' }}</button></div>
                </form>
            @else
                <form method="post" action="{{ route('properties.save', [$p->id, $step]) }}" novalidate>@csrf
                    @if($extension)
                        @include($extension->view(), $extensionData)
                    @else
                        @include('properties.post.steps.' . $step)
                    @endif
                    <div class="pw-foot">@if($i > 0)<a class="pr-btn grey" href="{{ route('properties.step', [$p->id, $steps[$i - 1]]) }}">Back</a>@endif
                        <button class="pr-btn" type="submit">{{ $last ? 'Finish posting' : 'Save & continue' }}</button>
                        @if(!$last && $p->status === 'active')<button class="pr-btn ghost" type="submit" name="finish" value="1">Save & publish</button>@endif</div>
                </form>
            @endif
        </div>
    </div>
</div></div>
@endsection
