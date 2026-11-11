@php($PF = \App\Services\Property\PropertyFormat::class)
<p class="pr-mute" style="margin-top:-4px">{{ $PF::full($app->loan_amount) }} for {{ $app->tenure }} years · income {{ $PF::full($app->monthly_income) }}/month @if($app->current_emis) · existing EMIs {{ $PF::full($app->current_emis) }}@endif · <a href="{{ route('home-loan.step', [$app, 'income']) }}">Change</a></p>
@if($offers->where('eligible', true)->isEmpty())<div class="pr-flash warn">At these figures no lender covers the full {{ $PF::full($app->loan_amount) }}. The most you can get is {{ $PF::full($offers->max('max')) }} — try a longer tenure or a smaller amount.</div>@endif
<form method="post" action="{{ route('home-loan.save', [$app, 'offers']) }}">
    @csrf
    @foreach($offers as $x)
        <label class="hl-offer {{ $x->eligible ? '' : 'dim' }}">
            <input type="radio" name="bank_id" value="{{ $x->bank->id }}" required @checked((int) old('bank_id', $app->bank_id) === $x->bank->id)>
            <div class="hl-bank"><i style="background:{{ $x->bank->color }}">{{ $x->bank->initials() }}</i><div><b>{{ $x->bank->name }}</b>
                <small>{{ rtrim(rtrim(number_format($x->bank->rate_from, 2), '0'), '.') }}% p.a. · {{ $x->years }} yrs · up to {{ $x->bank->max_funding }}% funding @if($x->bank->processing_fee)· {{ $x->bank->processing_fee }}@endif</small>
                <small style="color:{{ $x->eligible ? '#047857' : '#b91c1c' }};font-weight:700">{{ $x->eligible ? 'Eligible' : 'Eligible up to ' . $PF::full($x->max) }}</small></div></div>
            <div class="r"><small>EMI</small><b>{{ $PF::full($x->emi) }}</b><small>per month</small></div>
        </label>
    @endforeach
    <div class="hl-bar"><a class="pr-btn ghost" style="min-width:0;background:#fff;color:#0f766e" href="{{ route('home-loan.step', [$app, 'income']) }}">Back</a><button class="pr-btn">Continue</button></div>
</form>
