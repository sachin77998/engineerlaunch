@php
    $err = fn ($k) => $errors->has($k) ? '<div class="pr-err">' . e($errors->first($k)) . '</div>' : '';
    $sym = \App\Services\Property\PropertyFormat::symbol($p->currency);
    $t = $p->rentTerms;
    $tv = fn ($k, $d = null) => old($k, $t?->{$k} ?? $d);
@endphp
<div class="pr-grid">
    <div><label class="f" for="r-rent">Expected rent <em>*</em></label>
        <div class="pr-input-addon rr"><span class="l">{{ $sym }}</span><input id="r-rent" type="number" min="1" step="1" name="rent" value="{{ $tv('rent') !== null ? (int) $tv('rent') : '' }}" placeholder="Enter amount"><span class="r">/ month</span></div>
        <label class="pr-check"><input type="checkbox" name="rent_negotiable" value="1" @checked($tv('rent_negotiable'))> Rent negotiable</label>{!! $err('rent') !!}</div>
    <div><label class="f" for="r-maint">Maintenance</label>
        <div class="pr-input-addon rr"><span class="l">{{ $sym }}</span><input id="r-maint" type="number" min="0" step="1" name="maintenance" value="{{ $tv('maintenance') !== null ? (int) $tv('maintenance') : '' }}" placeholder="0 if included"><span class="r">/ month</span></div>
        <label class="pr-check"><input type="checkbox" name="maintenance_extra" value="1" @checked($tv('maintenance_extra'))> Maintenance extra (not in rent)</label>{!! $err('maintenance') !!}</div>
    <div><label class="f" for="r-dep">Security deposit <em>*</em></label>
        <div class="pr-input-addon"><span class="l">{{ $sym }}</span><input id="r-dep" type="number" min="0" step="1" name="deposit" value="{{ $tv('deposit') !== null ? (int) $tv('deposit') : '' }}" placeholder="Enter amount"></div>
        <label class="pr-check"><input type="checkbox" name="deposit_negotiable" value="1" @checked($tv('deposit_negotiable'))> Deposit negotiable</label>{!! $err('deposit') !!}</div>
    <div><label class="f" for="r-brok">Brokerage</label>
        <div class="pr-input-addon"><span class="l">{{ $sym }}</span><input id="r-brok" type="number" min="0" step="1" name="brokerage" value="{{ $tv('brokerage') !== null ? (int) $tv('brokerage') : '' }}" placeholder="Leave empty if none"></div>{!! $err('brokerage') !!}</div>
    <div class="pr-grid" style="gap:12px">
        <div><label class="f" for="r-lease">Lease duration</label><select id="r-lease" name="lease_years"><option value="">Select</option>@foreach($o['lease_years'] as $k => $label)<option value="{{ $k }}" @selected((string) $tv('lease_years') === (string) $k)>{{ $label }}</option>@endforeach</select></div>
        <div><label class="f" for="r-lock">Lock-in period</label><select id="r-lock" name="lockin_years"><option value="">Select</option>@foreach($o['lockin_years'] as $k => $label)<option value="{{ $k }}" @selected((string) $tv('lockin_years') === (string) $k)>{{ $label }}</option>@endforeach</select></div>
    </div>
    <div><label class="f" for="r-from">Available from <em>*</em></label><input id="r-from" type="date" name="available_from" value="{{ old('available_from', $t?->available_from?->toDateString()) }}">{!! $err('available_from') !!}</div>
    @if($p->segment === 'commercial')
        <div class="full"><label class="f">Ideal for</label><div class="pr-chips">@foreach($o['commercial']['ideal_for'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="ideal_for[]" value="{{ $k }}" @checked(in_array($k, old('ideal_for', $t?->ideal_for ?? []), true))>{{ $label }}</label>@endforeach</div></div>
    @else
        <div class="full"><label class="f">Preferred tenants</label><div class="pr-chips">@foreach($o['preferred_tenants'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="preferred_tenants[]" value="{{ $k }}" @checked(in_array($k, old('preferred_tenants', $t?->preferred_tenants ?? []), true))>{{ $label }}</label>@endforeach</div></div>
    @endif
</div>
