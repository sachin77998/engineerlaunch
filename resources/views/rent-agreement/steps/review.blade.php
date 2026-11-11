@php
    $PF = \App\Services\Property\PropertyFormat::class;
    $must = \App\Services\Legal\RentAgreementCost::registrationRequired($a->state, $a->months);
    $ordered = $a->status !== 'draft';
@endphp
<div class="ra-person">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap"><b>{{ $a->landlord['name'] ?? '—' }} → {{ $a->tenant['name'] ?? '—' }}</b><a href="{{ route('rent-agreement.preview', $a) }}" target="_blank">Preview full agreement ↗</a></div>
    <div class="pr-mute" style="font-size:14px">
        @if($a->usesDraft())Your uploaded draft: <a href="{{ route('rent-agreement.draft', $a) }}">{{ $a->draft_name }}</a> ·@endif
        {{ $PF::full($a->rent) }}/month · deposit {{ $PF::full($a->deposit) }} · {{ $a->months }} months @if($a->start_date)from {{ $a->start_date->format('d M Y') }}@endif · {{ $a->city }}@if($a->state), {{ $a->state }}@endif
    </div>
</div>
@if($ordered)<div class="pr-flash ok">Ordered — see <a href="{{ route('legal.bookings') }}">My bookings</a>.</div>@endif
<form method="post" action="{{ route('rent-agreement.options', $a) }}" style="display:grid;gap:14px" data-ra-opts>
    @csrf
    <div><label class="f">Agreement type</label>
        <div class="hl-pills">@foreach($o['types'] as $k => [$label, $hint])<label title="{{ $hint }}"><input type="radio" name="type" value="{{ $k }}" @checked($a->type === $k) @disabled($ordered || ($must && $k !== 'registered')) onchange="this.form.submit()"><span>{{ $label }}</span></label>@endforeach</div>
        @if($must)<small class="pr-mute">Registration is required for {{ $a->months > 11 ? 'terms over 11 months' : 'agreements in ' . $a->state }}.</small>@endif</div>
    @if($addonServices->isNotEmpty())
        <div><label class="f">Add-ons</label>
            <div style="display:grid;gap:8px">@foreach($addonServices as $s)<label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="addons[]" value="{{ $s->slug }}" @checked(in_array($s->slug, $a->addons ?? [], true)) @disabled($ordered) onchange="this.form.submit()"> {{ $s->name }} <span class="pr-mute">({{ $s->priceLabel() }})</span></label>@endforeach</div></div>
    @endif
    @if(in_array('doorstep-delivery', $a->addons ?? [], true))
        <div><label class="f" for="ra-del">Delivery address</label><div style="display:flex;gap:8px"><input id="ra-del" type="text" name="delivery_address" maxlength="255" value="{{ $a->delivery_address }}" @disabled($ordered)>@unless($ordered)<button class="pr-btn sm">Save</button>@endunless</div></div>
    @else<input type="hidden" name="delivery_address" value="{{ $a->delivery_address }}">@endif
</form>
<div class="ra-person" style="margin-top:16px">
    <h3>Cost</h3>
    @foreach($cost['services'] as $s)<div style="display:flex;justify-content:space-between"><span>{{ $s->name }}</span><b>{{ $s->price ? $PF::full($s->price) : 'On call' }}</b></div>@endforeach
    <div style="display:flex;justify-content:space-between"><span>Stamp duty (estimate)</span><b>{{ $cost['stamp'] !== null ? $PF::full($cost['stamp']) : 'Confirmed on call' }}</b></div>
    @if($a->type === 'registered')<div style="display:flex;justify-content:space-between"><span>Registration fee (estimate)</span><b>{{ $cost['registration'] !== null ? $PF::full($cost['registration']) : 'Confirmed on call' }}</b></div>@endif
    <div style="display:flex;justify-content:space-between;border-top:1px solid var(--pr-line);padding-top:8px;font-size:18px"><span>Total (estimate)</span><b>{{ $PF::full($cost['total']) }}@if($cost['on_call'] || $cost['stamp'] === null) + on call @endif</b></div>
    <small class="pr-mute">Nothing is charged now. Our team confirms stamp duty and fees on a call before stamping. Government charges are paid at actuals.</small>
</div>
@unless($ordered)
    <form method="post" action="{{ route('rent-agreement.save', [$a, 'review']) }}">
        @csrf
        <div class="hl-bar">
            <a class="pr-btn ghost" style="min-width:0;background:#fff;color:#0f766e" href="{{ route('rent-agreement.step', [$a, $a->usesDraft() ? 'parties' : 'terms']) }}">Back</a>
            <button class="pr-btn">Place order</button>
        </div>
    </form>
    <form method="post" action="{{ route('rent-agreement.destroy', $a) }}" onsubmit="return confirm('Delete this draft?')" style="margin-top:10px">@csrf<button class="pr-btn grey sm">Delete draft</button></form>
@endunless
