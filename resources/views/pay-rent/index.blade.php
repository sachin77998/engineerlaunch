@extends('layouts.site')
@section('title', 'Pay rent, tuition & maintenance by credit card — ' . config('properties.brand'))
@section('content')
@include('pay-rent._layout', ['title' => 'Payments via credit card', 'tagline' => 'Rent, tuition fees and society maintenance'])
<div class="pr"><div class="pr-wrap pz-body">
    <div class="pz-methods">@foreach(config('pay_rent.methods') as $k => [$label, $icon])<a class="pz-method" href="{{ route('pay-rent.form', ['rent', $k]) }}"><i>{{ $icon }}</i>{{ $label }}</a>@endforeach</div>
    @unless($live)<div class="pz-off">Card payments to landlords and tutors are not switched on yet. You can still fill in a payment — nothing will be charged.</div>@endunless
    <div class="pz-card">
        <h2>Pay your expenses</h2>
        <div class="pz-purposes">
            @foreach(config('pay_rent.purposes') as $k => [$label, $icon])<a class="pz-purpose" href="{{ route('pay-rent.purpose', $k) }}"><i>{{ $icon }}</i><div><b>{{ $label }}</b><small>Pay by credit card</small></div><span class="go">›</span></a>@endforeach
            <a class="pz-purpose" href="{{ route('bills.index') }}"><i>💡</i><div><b>Utility bills</b><small>Electricity, water, gas…</small></div><span class="go">›</span></a>
        </div>
    </div>
    @if($recent->isNotEmpty())
        <div class="pz-card"><h2>Recent</h2>
            @foreach($recent as $t)<a href="{{ route('pay-rent.show', $t) }}" style="display:flex;justify-content:space-between;gap:10px;padding:10px 0;border-top:1px solid var(--pr-line);color:inherit;text-decoration:none"><span>{{ $t->purposeLabel() }} · {{ $t->payee_name }}</span><span><b>₹{{ number_format($t->amount) }}</b> · <span class="pr-mute">{{ $t->statusLabel() }}</span></span></a>@endforeach
        </div>
    @endif
</div></div>
@include('partials.property-services')
@endsection
