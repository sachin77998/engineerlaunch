@extends('layouts.site')
@section('title', 'Get a free interiors quote — ' . config('properties.brand'))
@section('content')
@include('interiors._head')
@php
    $c = config('interiors');
    $PF = \App\Services\Property\PropertyFormat::class;
@endphp
<div class="pr" style="padding-bottom:60px"><div class="pr-wrap" style="max-width:760px">
    <h1 style="margin:24px 0 4px">Get a free quote</h1><p class="pr-mute" style="margin-top:0">Our designer calls you to understand your home and book a free consultation.</p>
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    @if($idea)<div class="pr-card-box" style="display:flex;gap:12px;align-items:center;margin-bottom:12px"><img src="{{ $idea->imageUrl() }}" alt="" style="width:90px;height:64px;object-fit:cover;border-radius:8px"><span>Design you liked: <b>{{ $idea->title }}</b></span></div>@endif
    @if($estimate && $estimate['total'])
        <div class="pr-card-box" style="margin-bottom:12px"><b>Your estimate ({{ $c['packages'][$estimate['package']][0] }}): {{ $PF::full($estimate['low']) }} – {{ $PF::full($estimate['high']) }}</b>
            <div class="pr-mute" style="font-size:13.5px">@foreach($estimate['lines'] as $l){{ $l['label'] }}{{ $l['count'] > 1 ? ' × ' . $l['count'] : '' }}@if(!$loop->last), @endif @endforeach</div></div>
    @endif
    <form method="post" action="{{ route('interiors.quote.store') }}" class="pr-card-box" style="display:grid;gap:14px;padding:22px">
        @csrf
        @if($idea)<input type="hidden" name="idea" value="{{ $idea->slug }}">@endif
        @if($estimate)<input type="hidden" name="package" value="{{ $estimate['package'] }}">@foreach($estimate['lines'] as $room => $l)<input type="hidden" name="rooms[{{ $room }}]" value="{{ $l['count'] }}">@endforeach @endif
        <div class="pr-grid">
            <div><label class="f" for="iq-name">Full name <em>*</em></label><input id="iq-name" type="text" name="name" required maxlength="120" value="{{ old('name', auth()->user()?->name) }}"></div>
            <div><label class="f" for="iq-phone">Mobile <em>*</em></label><input id="iq-phone" type="tel" name="phone" required pattern="[6-9][0-9]{9}" maxlength="10" inputmode="numeric" value="{{ old('phone') }}"></div>
            <div><label class="f" for="iq-email">Email</label><input id="iq-email" type="email" name="email" maxlength="191" value="{{ old('email', auth()->user()?->email) }}"></div>
            <div><label class="f" for="iq-city">City <em>*</em></label><select id="iq-city" name="city" required><option value="">Select city</option>@foreach($cities as $ct)<option @selected(old('city') === $ct)>{{ $ct }}</option>@endforeach</select></div>
            <div><label class="f" for="iq-bhk">Home</label><select id="iq-bhk" name="bhk"><option value="">Select</option>@foreach($c['bhk'] as $k => $l)<option value="{{ $k }}" @selected((int) old('bhk', $bhk) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label class="f" for="iq-pos">Possession</label><select id="iq-pos" name="possession"><option value="">Select</option>@foreach(['ready' => 'Ready / moved in', 'within_3' => 'Within 3 months', 'later' => 'After 3 months', 'renovation' => 'Renovating my home'] as $k => $l)<option value="{{ $k }}" @selected(old('possession') === $k)>{{ $l }}</option>@endforeach</select></div>
            @unless($estimate)<div><label class="f" for="iq-pkg">Finish</label><select id="iq-pkg" name="package"><option value="">Not sure</option>@foreach($c['packages'] as $k => [$l])<option value="{{ $k }}" @selected(old('package') === $k)>{{ $l }}</option>@endforeach</select></div>@endunless
            <div><label class="f" for="iq-style">Style</label><select id="iq-style" name="style"><option value="">Not sure</option>@foreach($c['styles'] as $k => [$l])<option value="{{ $k }}" @selected(old('style', $style) === $k)>{{ $l }}</option>@endforeach</select></div>
        </div>
        <div><label class="f" for="iq-notes">Anything we should know?</label><textarea id="iq-notes" name="notes" rows="3" maxlength="1000">{{ old('notes') }}</textarea></div>
        <button class="in-red">Book free consultation</button>
    </form>
</div></div>
@endsection
