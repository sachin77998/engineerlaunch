@php
    $err = fn ($k) => $errors->has($k) ? '<div class="pr-err">' . e($errors->first($k)) . '</div>' : '';
    $cv = fn ($k) => old($k, $c->{$k});
    $sale = $p->listing === 'sale';
@endphp
<div class="pr-grid">
    <div><label class="f" for="c-ot">Office / space type</label><select id="c-ot" name="office_type"><option value="">Select</option>@foreach($o['office_types'] ?? [] as $k => $label)<option value="{{ $k }}" @selected($cv('office_type') === $k)>{{ $label }}</option>@endforeach</select>{!! $err('office_type') !!}</div>
    <div><label class="f" for="c-si">Situated in</label><select id="c-si" name="situated_in"><option value="">Select</option>@foreach($o['situated_in'] ?? [] as $k => $label)<option value="{{ $k }}" @selected($cv('situated_in') === $k)>{{ $label }}</option>@endforeach</select>{!! $err('situated_in') !!}</div>
    @if($sale)<div><label class="f" for="c-tt">Transaction type</label><select id="c-tt" name="transaction_type"><option value="">Select</option>@foreach($o['transaction_types'] ?? [] as $k => $label)<option value="{{ $k }}" @selected($cv('transaction_type') === $k)>{{ $label }}</option>@endforeach</select>{!! $err('transaction_type') !!}</div>@endif
    <div class="pr-grid" style="gap:12px">
        <div><label class="f" for="c-smin">Seats from</label><input id="c-smin" type="number" min="1" name="seats_min" value="{{ $cv('seats_min') }}">{!! $err('seats_min') !!}</div>
        <div><label class="f" for="c-smax">Seats up to</label><input id="c-smax" type="number" min="1" name="seats_max" value="{{ $cv('seats_max') }}">{!! $err('seats_max') !!}</div>
    </div>
    <div class="pr-grid" style="gap:12px">
        <div><label class="f" for="c-cab">Cabins</label><input id="c-cab" type="number" min="0" name="cabins" value="{{ $cv('cabins') }}">{!! $err('cabins') !!}</div>
        <div><label class="f" for="c-mr">Meeting rooms</label><input id="c-mr" type="number" min="0" name="meeting_rooms" value="{{ $cv('meeting_rooms') }}">{!! $err('meeting_rooms') !!}</div>
    </div>
    <div><label class="f" for="c-wr">Washrooms</label><input id="c-wr" type="text" maxlength="120" name="washrooms" value="{{ $cv('washrooms') }}" placeholder="e.g. 2 private, shared on floor">{!! $err('washrooms') !!}</div>
    <div><label class="f" for="c-cp">Car parking</label><input id="c-cp" type="text" maxlength="160" name="car_parking" value="{{ $cv('car_parking') }}" placeholder="e.g. 4 reserved, visitor parking">{!! $err('car_parking') !!}</div>
</div>

<label class="f" style="margin-top:18px">Facilities</label>
<div class="pr-chips" style="margin-bottom:20px">@foreach($o['facilities'] ?? [] as $k => $f)<label class="pr-chip"><input type="checkbox" name="facilities[]" value="{{ $k }}" @checked(in_array($k, old('facilities', $ticked), true))>{{ $f[1] ?? '' }} {{ $f[0] }}</label>@endforeach</div>

@if($sale)
    <label class="pr-check" style="display:flex;gap:8px;align-items:center;font-weight:700"><input type="checkbox" name="is_pre_leased" value="1" @checked(old('is_pre_leased', $c->is_pre_leased)) data-toggle="#c-lease"> Pre-leased / pre-rented (sold with a tenant)</label>
    <div id="c-lease" class="pr-grid" style="margin-top:10px" @unless(old('is_pre_leased', $c->is_pre_leased)) hidden @endunless>
        <div><label class="f" for="c-la">Current rent per month (₹) <em>*</em></label><input id="c-la" type="number" min="1" name="lease_amount" value="{{ $cv('lease_amount') }}">{!! $err('lease_amount') !!}</div>
        <div><label class="f" for="c-lt">Lease tenure left (years)</label><input id="c-lt" type="number" step="0.5" min="0.5" name="lease_tenure_years" value="{{ $cv('lease_tenure_years') }}">{!! $err('lease_tenure_years') !!}</div>
        <div><label class="f" for="c-tn">Tenant (brand / company)</label><input id="c-tn" type="text" maxlength="160" name="tenant_name" value="{{ $cv('tenant_name') }}">{!! $err('tenant_name') !!}</div>
    </div>
@endif

<div class="pr-grid" style="margin-top:18px">
    <div><label class="f" for="c-rs">RERA status</label><select id="c-rs" name="rera_status"><option value="">Select</option>@foreach(['registered' => 'Registered', 'not_available' => 'Not available', 'not_required' => 'Not required'] as $k => $label)<option value="{{ $k }}" @selected($cv('rera_status') === $k)>{{ $label }}</option>@endforeach</select>{!! $err('rera_status') !!}</div>
    <div><label class="f" for="c-rn">RERA registration number</label><input id="c-rn" type="text" maxlength="80" name="rera_number" value="{{ $cv('rera_number') }}">{!! $err('rera_number') !!}</div>
</div>
@push('scripts')<script>
document.querySelectorAll('[data-toggle]').forEach(function (cb) { cb.addEventListener('change', function () { document.querySelector(cb.dataset.toggle).hidden = !cb.checked; }); });
</script>@endpush
