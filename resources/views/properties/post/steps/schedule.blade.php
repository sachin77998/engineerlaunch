@php($err = fn ($k) => $errors->has($k) ? '<div class="pr-err">' . e($errors->first($k)) . '</div>' : '')
<p class="pr-mute" style="margin-top:-4px">When can people visit? This is shown with your contact details.</p>
<div class="pr-grid">
    <div><label class="f">Availability</label>
        <div class="pr-chips">@foreach($o['availability'] as $k => [$label, $days])<label class="pr-chip" style="flex-direction:column;align-items:flex-start;border-radius:12px;gap:0"><input type="radio" name="availability" value="{{ $k }}" @checked($v('availability', 'everyday') === $k)><b>{{ $label }}</b><small class="pr-mute">{{ $days }}</small></label>@endforeach</div></div>
    <div><label class="f">Time</label>
        <div style="display:flex;gap:8px"><input type="time" name="visit_start" value="{{ $v('visit_start', '10:00') }}" aria-label="Start time"><input type="time" name="visit_end" value="{{ $v('visit_end', '19:00') }}" aria-label="End time"></div>
        <label class="pr-check"><input type="checkbox" name="visit_all_day" value="1" id="s-all" @checked(old('visit_all_day', $p->visit_all_day))> Available all day</label>
        {!! $err('visit_start') !!}{!! $err('visit_end') !!}</div>
</div>
@push('scripts')<script>(function(){var a=document.getElementById('s-all');function t(){document.querySelectorAll('[name=visit_start],[name=visit_end]').forEach(function(i){i.disabled=a.checked});}a.addEventListener('change',t);t();})();</script>@endpush
