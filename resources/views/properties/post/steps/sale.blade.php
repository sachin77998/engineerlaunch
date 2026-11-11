@php
    $err = fn ($k) => $errors->has($k) ? '<div class="pr-err">' . e($errors->first($k)) . '</div>' : '';
    $sym = \App\Services\Property\PropertyFormat::symbol($p->currency);
    $t = $p->saleTerms;
    $tv = fn ($k, $d = null) => old($k, $t?->{$k} ?? $d);
@endphp
<div class="pr-grid">
    <div><label class="f" for="s-price">Expected price <em>*</em></label>
        <div class="pr-input-addon"><span class="l">{{ $sym }}</span><input id="s-price" type="number" min="1" step="1" name="price" value="{{ $tv('price') !== null ? (int) $tv('price') : '' }}" placeholder="Enter amount"></div>
        <small class="pr-mute" id="s-words"></small>
        <label class="pr-check"><input type="checkbox" name="price_negotiable" value="1" @checked($tv('price_negotiable'))> Price negotiable</label>{!! $err('price') !!}</div>
    <div><label class="f" for="s-maint">Maintenance</label>
        <div class="pr-input-addon rr"><span class="l">{{ $sym }}</span><input id="s-maint" type="number" min="0" step="1" name="maintenance" value="{{ $tv('maintenance') !== null ? (int) $tv('maintenance') : '' }}"><span class="r">/ month</span></div>{!! $err('maintenance') !!}</div>
    <div><label class="f" for="s-brok">Brokerage</label><div class="pr-input-addon"><span class="l">{{ $sym }}</span><input id="s-brok" type="number" min="0" step="1" name="brokerage" value="{{ $tv('brokerage') !== null ? (int) $tv('brokerage') : '' }}" placeholder="Leave empty if none"></div>{!! $err('brokerage') !!}</div>
    <div><label class="f" for="s-own">Ownership</label><select id="s-own" name="ownership"><option value="">Select</option>@foreach($o['ownership'] as $k => $label)<option value="{{ $k }}" @selected($tv('ownership') === $k)>{{ $label }}</option>@endforeach</select></div>
    <div><label class="f">Property status <em>*</em></label><div class="pr-chips">@foreach($o['possession'] as $k => $label)<label class="pr-chip"><input type="radio" name="possession" value="{{ $k }}" @checked($tv('possession', 'ready') === $k)>{{ $label }}</label>@endforeach</div>{!! $err('possession') !!}</div>
    <div><label class="f" for="s-by">Possession by</label><input id="s-by" type="date" name="possession_by" value="{{ old('possession_by', $t?->possession_by?->toDateString()) }}">{!! $err('possession_by') !!}</div>
    <div class="full"><label class="pr-check" style="margin:0"><input type="checkbox" name="is_new_project" value="1" @checked($tv('is_new_project'))> This is a new builder project</label></div>
    <div><label class="f" for="s-proj">Project name</label><input id="s-proj" type="text" name="project_name" maxlength="160" value="{{ $tv('project_name') }}"></div>
    <div><label class="f" for="s-bld">Builder / developer</label><input id="s-bld" type="text" name="builder_name" maxlength="160" value="{{ $tv('builder_name') }}"></div>
    @if($p->segment === 'commercial')
        <div class="full"><label class="f">Ideal for</label><div class="pr-chips">@foreach($o['commercial']['ideal_for'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="ideal_for[]" value="{{ $k }}" @checked(in_array($k, old('ideal_for', $t?->ideal_for ?? []), true))>{{ $label }}</label>@endforeach</div></div>
    @endif
</div>
@if($p->currency === 'INR')
@push('scripts')<script>
(function () {
    var i = document.getElementById('s-price'), w = document.getElementById('s-words');
    function words() { var n = parseFloat(i.value); w.textContent = !n ? '' : n >= 1e7 ? '₹' + +(n / 1e7).toFixed(2) + ' crore' : n >= 1e5 ? '₹' + +(n / 1e5).toFixed(2) + ' lakh' : ''; }
    i.addEventListener('input', words); words();
})();
</script>@endpush
@endif
