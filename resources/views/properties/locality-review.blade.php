@extends('layouts.site')
@section('title', 'Review ' . $name . ', ' . $cityName)
@section('content')
@include('properties._nav')
@php
    $o = config('properties');
    $val = fn ($k, $d = null) => old($k, $review?->{$k} ?? ($k === 'rating' && in_array((int) request('stars'), [1, 2, 3, 4, 5], true) ? (int) request('stars') : $d));
    $err = fn ($k) => $errors->has($k) ? '<div class="pr-err">' . e($errors->first($k)) . '</div>' : '';
@endphp
@push('styles')<style>
.lr-pick{display:inline-flex;flex-direction:row-reverse;gap:2px}
.lr-pick input{position:absolute;opacity:0;pointer-events:none}
.lr-pick label{font-size:30px;color:#d1d5db;cursor:pointer;line-height:1}
.lr-pick label:hover,.lr-pick label:hover~label,.lr-pick input:checked~label{color:#f59e0b}
.lr-pick input:focus-visible+label{outline:2px solid #a5b4fc}
</style>@endpush
<div class="pr"><div class="pr-wrap" style="max-width:820px">
    <h1 style="margin-top:22px">{{ $review ? 'Edit your review of' : 'Review' }} {{ $name }}, {{ $cityName }}</h1>
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <form class="pr-card-box" method="post" action="{{ route('localities.review.store', [$city, $locality]) }}" style="display:grid;gap:18px">
        @csrf
        <div class="pr-grid">
            <div><label class="f" for="lr-role">You are <em>*</em></label><select id="lr-role" name="reviewer_role">@foreach($o['reviewer_roles'] as $k => $label)<option value="{{ $k }}" @selected($val('reviewer_role') === $k)>{{ $label }}</option>@endforeach</select></div>
            <div><label class="f" for="lr-soc">Society / building <span class="pr-mute" style="font-weight:500">(optional)</span></label><input id="lr-soc" type="text" name="society" maxlength="150" value="{{ $val('society') }}"></div>
        </div>
        @foreach(['rating' => 'Overall'] + collect($o['review_features'])->map(fn ($f) => $f[0])->all() as $k => $label)
            <div><label class="f">{{ $label }} <em>*</em></label>
                <span class="lr-pick" role="radiogroup" aria-label="{{ $label }}">@for($s = 5; $s >= 1; $s--)<input type="radio" id="lr-{{ $k }}-{{ $s }}" name="{{ $k }}" value="{{ $s }}" @checked((int) $val($k) === $s)><label for="lr-{{ $k }}-{{ $s }}" title="{{ $s }} out of 5">★</label>@endfor</span>{!! $err($k) !!}</div>
        @endforeach
        <div><label class="f" for="lr-pos">What is good about living here?</label><textarea id="lr-pos" name="positives" rows="4" maxlength="2000" placeholder="Parks, markets, schools, clean streets, neighbours…">{{ $val('positives') }}</textarea>{!! $err('positives') !!}</div>
        <div><label class="f" for="lr-neg">What could be better?</label><textarea id="lr-neg" name="negatives" rows="4" maxlength="2000" placeholder="Transport, water, traffic, safety at night…">{{ $val('negatives') }}</textarea>{!! $err('negatives') !!}</div>
        <div style="display:flex;gap:10px"><button class="pr-btn" type="submit">{{ $review ? 'Update review' : 'Post review' }}</button><a class="pr-btn ghost" href="{{ route('localities.show', [$city, $locality]) }}">Cancel</a></div>
    </form>
</div></div>
@endsection
