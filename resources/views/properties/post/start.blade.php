@extends('layouts.site')
@section('title', 'Post a free property ad — ' . config('properties.brand'))
@section('content')
@include('properties._nav')
@php($o = config('properties'))
<div class="pr"><div class="pr-wrap" style="max-width:860px">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <h1 style="margin-top:22px">Post a free property ad</h1>

    @if(!$owner)
        <form class="pr-card-box" method="post" action="{{ route('properties.owner') }}" style="margin-top:14px">
            @csrf
            <h2>Your contact details</h2>
            <p class="pr-mute" style="margin-top:-6px">Shown to signed-in visitors who ask for your number.</p>
            <div class="pr-grid">
                <div><label class="f" for="o-type">You are <em>*</em></label><select id="o-type" name="owner_type" required>@foreach($o['owner_types'] as $k => $label)<option value="{{ $k }}" @selected(old('owner_type') === $k)>{{ $label }}</option>@endforeach</select></div>
                <div><label class="f" for="o-name">Name <em>*</em></label><input id="o-name" type="text" name="name" required maxlength="120" value="{{ old('name', auth()->user()->name) }}"></div>
                <div><label class="f" for="o-phone">Mobile number <em>*</em></label><input id="o-phone" type="tel" name="phone" required maxlength="20" value="{{ old('phone') }}" placeholder="10-digit mobile or +country code">
                    <label class="pr-check"><input type="checkbox" name="whatsapp" value="1" @checked(old('whatsapp', true))> Available on WhatsApp</label></div>
                <div data-firm><label class="f" for="o-co">Firm / company name</label><input id="o-co" type="text" name="company_name" maxlength="160" value="{{ old('company_name') }}"></div>
                <div data-firm><label class="f" for="o-rera">RERA number</label><input id="o-rera" type="text" name="rera_number" maxlength="60" value="{{ old('rera_number') }}"></div>
            </div>
            <button class="pr-btn" type="submit" style="margin-top:18px">Save and continue</button>
        </form>
        @push('scripts')<script>(function(){var s=document.getElementById('o-type');function t(){document.querySelectorAll('[data-firm]').forEach(function(e){e.style.display=s.value==='owner'?'none':''});}s.addEventListener('change',t);t();})();</script>@endpush
    @else
        <div class="pr-card-box" style="margin-top:14px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center">
            <div><b>{{ $owner->company_name ?: $owner->name }}</b> · {{ $owner->typeLabel() }} · {{ $owner->phone }}</div>
            <details><summary class="pr-btn ghost sm" style="list-style:none">Change contact</summary>
                <form method="post" action="{{ route('properties.owner') }}" style="display:grid;gap:8px;margin-top:10px;min-width:280px">@csrf
                    <select name="owner_type">@foreach($o['owner_types'] as $k => $label)<option value="{{ $k }}" @selected($owner->owner_type === $k)>{{ $label }}</option>@endforeach</select>
                    <input type="text" name="name" required value="{{ $owner->name }}" aria-label="Name"><input type="tel" name="phone" required value="{{ $owner->phone }}" aria-label="Mobile">
                    <input type="text" name="company_name" value="{{ $owner->company_name }}" placeholder="Firm / company name" aria-label="Firm"><input type="text" name="rera_number" value="{{ $owner->rera_number }}" placeholder="RERA number" aria-label="RERA">
                    <label class="pr-check"><input type="checkbox" name="whatsapp" value="1" @checked($owner->whatsapp)> Available on WhatsApp</label>
                    <button class="pr-btn sm">Save</button></form></details>
        </div>

        <form class="pr-card-box" method="post" action="{{ route('properties.create') }}" id="prType" style="margin-top:14px">
            @csrf
            <h2>What are you posting?</h2>
            <label class="f">Property</label>
            <div class="pr-chips">@foreach(['residential' => 'Residential', 'commercial' => 'Commercial'] as $k => $label)<label class="pr-chip"><input type="radio" name="segment" value="{{ $k }}" @checked(old('segment', 'residential') === $k)>{{ $label }}</label>@endforeach</div>
            <label class="f" style="margin-top:16px">Ad for</label>
            <div class="pr-chips">@foreach(['rent' => 'Rent', 'sale' => 'Sale'] as $k => $label)<label class="pr-chip"><input type="radio" name="listing" value="{{ $k }}" @checked(old('listing', 'rent') === $k)>{{ $label }}</label>@endforeach</div>
            <label class="f" style="margin-top:16px">Type</label>
            <div class="pr-chips">
                @foreach($o['kinds'] as $segment => $kinds)@foreach($kinds as $k => $label)
                    <label class="pr-chip" data-seg="{{ $segment }}" data-only="{{ $k === 'land' ? 'sale' : (in_array($k, ['pg', 'flatmate'], true) ? 'rent' : '') }}"><input type="radio" name="kind" value="{{ $k }}" @checked(old('kind', 'full_house') === $k)>{{ $label }}</label>
                @endforeach
                @endforeach
            </div>
            <button class="pr-btn" type="submit" style="margin-top:20px">Start posting</button>
        </form>
        @push('scripts')<script>
        (function () {
            var f = document.getElementById('prType');
            function sync() {
                var seg = f.querySelector('[name=segment]:checked').value, listing = f.querySelector('[name=listing]:checked').value, first = null;
                f.querySelectorAll('[data-seg]').forEach(function (c) {
                    var ok = c.dataset.seg === seg && (!c.dataset.only || c.dataset.only === listing), r = c.querySelector('input');
                    c.style.display = ok ? '' : 'none'; r.disabled = !ok; if (!ok && r.checked) r.checked = false; if (ok && !first) first = r;
                });
                if (!f.querySelector('[name=kind]:checked') && first) first.checked = true;
            }
            f.addEventListener('change', sync); sync();
        })();
        </script>@endpush

        @if($drafts->isNotEmpty())
            <div class="pr-card-box" style="margin-top:14px"><h2>Unfinished drafts</h2>
                @foreach($drafts as $d)
                    <div style="display:flex;justify-content:space-between;gap:10px;padding:10px 0;border-top:1px solid var(--pr-line)">
                        <span>{{ $d->kindLabel() }} for {{ $d->listing }}{{ $d->locality ? ' · ' . $d->locality : '' }} <span class="pr-mute">· started {{ $d->created_at->diffForHumans() }}</span></span>
                        <a href="{{ route('properties.step', [$d->id, \App\Http\Controllers\PropertyPostController::steps($d)[min(count($d->steps_done ?? []), count(\App\Http\Controllers\PropertyPostController::steps($d)) - 1)]]) }}">Continue →</a>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div></div>
@endsection
