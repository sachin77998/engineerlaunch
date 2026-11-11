@php
    $err = fn ($k) => $errors->has($k) ? '<div class="pr-err">' . e($errors->first($k)) . '</div>' : '';
    $sym = \App\Services\Property\PropertyFormat::symbol($p->currency);
    $t = $p->pgTerms;
    $rooms = collect($t?->rooms ?? [])->keyBy('type');
@endphp
<div class="pr-grid">
    <div><label class="f">{{ $p->kind === 'pg' ? 'PG is for' : 'Flatmate wanted' }} <em>*</em></label><div class="pr-chips">@foreach($o['pg']['gender'] as $k => $label)<label class="pr-chip"><input type="radio" name="gender" value="{{ $k }}" @checked(old('gender', $t?->gender ?? 'any') === $k)>{{ $label }}</label>@endforeach</div></div>
    <div><label class="f">Suitable for <em>*</em></label><div class="pr-chips">@foreach($o['pg']['tenant_type'] as $k => $label)<label class="pr-chip"><input type="radio" name="tenant_type" value="{{ $k }}" @checked(old('tenant_type', $t?->tenant_type ?? 'any') === $k)>{{ $label }}</label>@endforeach</div></div>
    <div class="full">
        <label class="f">Rent per bed (fill the room types you have) <em>*</em></label>
        <div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead><tr style="text-align:left;color:var(--pr-mute)"><th style="padding:6px">Room</th><th style="padding:6px">Rent / month</th><th style="padding:6px">Deposit</th>@if($p->kind === 'pg')<th style="padding:6px">Beds available</th>@endif</tr></thead>
            <tbody>
            @foreach($o['pg']['rooms'] as $k => $label)
                @continue($p->kind === 'flatmate' && in_array($k, ['four'], true))
                <tr>
                    <td style="padding:6px;font-weight:700;white-space:nowrap">{{ $label }}</td>
                    <td style="padding:6px"><div class="pr-input-addon"><span class="l">{{ $sym }}</span><input type="number" min="1" step="1" name="rooms[{{ $k }}][rent]" value="{{ old("rooms.$k.rent", isset($rooms[$k]) ? (int) $rooms[$k]['rent'] : '') }}" aria-label="{{ $label }} rent"></div></td>
                    <td style="padding:6px"><div class="pr-input-addon"><span class="l">{{ $sym }}</span><input type="number" min="0" step="1" name="rooms[{{ $k }}][deposit]" value="{{ old("rooms.$k.deposit", isset($rooms[$k]['deposit']) ? (int) $rooms[$k]['deposit'] : '') }}" aria-label="{{ $label }} deposit"></div></td>
                    @if($p->kind === 'pg')<td style="padding:6px"><input type="number" min="1" max="500" name="rooms[{{ $k }}][beds]" value="{{ old("rooms.$k.beds", $rooms[$k]['beds'] ?? '') }}" aria-label="{{ $label }} beds"></td>@endif
                </tr>
            @endforeach
            </tbody>
        </table></div>{!! $err('rooms') !!}
    </div>
    <div><label class="f" for="pg-from">Available from <em>*</em></label><input id="pg-from" type="date" name="available_from" value="{{ old('available_from', $t?->available_from?->toDateString()) }}">{!! $err('available_from') !!}</div>
    @if($p->kind === 'pg')<div><label class="f" for="pg-gate">Gate closing time</label><input id="pg-gate" type="time" name="gate_closing" value="{{ old('gate_closing', $t?->gate_closing) }}">{!! $err('gate_closing') !!}</div>@endif
    <div class="full"><label class="f">Meals included</label><div class="pr-chips">@foreach($o['pg']['meals'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="meals[]" value="{{ $k }}" @checked(in_array($k, old('meals', $t?->meals ?? []), true))>{{ $label }}</label>@endforeach</div></div>
    <div class="full"><label class="f">Services</label><div class="pr-chips">@foreach($o['pg']['services'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="services[]" value="{{ $k }}" @checked(in_array($k, old('services', $t?->services ?? []), true))>{{ $label }}</label>@endforeach</div></div>
    <div class="full"><label class="f">House rules</label><div class="pr-chips">@foreach($o['pg']['rules'] as $k => $label)<label class="pr-chip"><input type="checkbox" name="rules[]" value="{{ $k }}" @checked(in_array($k, old('rules', $t?->rules ?? []), true))>{{ $label }}</label>@endforeach</div></div>
</div>
