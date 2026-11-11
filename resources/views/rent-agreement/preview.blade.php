@php
    $PF = \App\Services\Property\PropertyFormat::class;
    $L = $a->landlord ?? [];
    $T = $a->tenant ?? [];
    $P = $a->property ?? [];
    $commercial = $a->usage === 'commercial';
    $kind = $commercial ? 'LEASE AGREEMENT' : 'RENT AGREEMENT';
    $blank = '____________';
    $fixtures = collect($P['fixtures'] ?? [])->map(fn ($n, $k) => $n . ' × ' . (config('properties.furnishings')[$k] ?? $k));
    $extra = collect(preg_split('/\r?\n/', (string) $a->term('extra')))->map(fn ($l) => trim($l))->filter()->values();
    $ord = fn ($d) => $d . (in_array($d % 100, [11, 12, 13], true) ? 'th' : (['th', 'st', 'nd', 'rd'][$d % 10] ?? 'th'));
@endphp
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $kind }} — {{ $a->reference() }}</title>
<style>
body{margin:0;background:#f3f4f6;font:16px/1.7 Georgia,'Times New Roman',serif;color:#111}
.bar{position:sticky;top:0;background:#1e1b4b;color:#fff;padding:10px 16px;display:flex;gap:10px;justify-content:space-between;align-items:center;font:14px system-ui,sans-serif}
.bar a,.bar button{color:#fff;background:rgba(255,255,255,.15);border:0;border-radius:8px;padding:8px 14px;cursor:pointer;text-decoration:none;font:600 14px system-ui,sans-serif}
.doc{max-width:820px;margin:20px auto;background:#fff;padding:56px 64px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
h1{text-align:center;font-size:22px;letter-spacing:.12em;margin:0 0 6px}.sub{text-align:center;color:#555;margin:0 0 28px;font-size:14px}
ol{padding-left:22px}li{margin-bottom:10px}.party{margin:14px 0}.sign{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:60px}.sign div{border-top:1px solid #111;padding-top:6px}
.note{background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;font:14px system-ui,sans-serif;margin-bottom:20px}
@media print{.bar,.note{display:none}body{background:#fff}.doc{box-shadow:none;margin:0;padding:0}}
@media(max-width:640px){.doc{padding:24px 18px}.sign{grid-template-columns:1fr}}
</style></head>
<body>
<div class="bar"><span>{{ $a->reference() }} · {{ $a->status === 'draft' ? 'Draft preview' : 'Ordered' }}</span><span><button onclick="window.print()">Print / save as PDF</button> <a href="{{ $a->status === 'draft' ? route('rent-agreement.step', [$a, 'review']) : route('legal.bookings') }}">Back</a></span></div>
<div class="doc">
    @if($a->usesDraft())<div class="note">You chose to use your own draft (<a href="{{ route('rent-agreement.draft', $a) }}">{{ $a->draft_name }}</a>). Our team prepares that on stamp paper; the template below is for reference only.</div>@endif
    <div class="note">Preview of the text that goes on {{ $a->type === 'registered' ? 'the registered agreement' : 'e-stamp paper' }}. Blanks are filled in when you complete the steps.</div>
    <h1>{{ $kind }}</h1>
    <p class="sub">{{ $a->type === 'registered' ? 'To be registered' : 'Executed on e-stamp paper' }} · {{ $a->city }}@if($a->state), {{ $a->state }}@endif</p>

    <p>This {{ strtolower($kind) }} is made at {{ $a->city }} on {{ $a->start_date?->format('jS F Y') ?? $blank }} between:</p>
    <p class="party"><b>{{ $L['name'] ?? $blank }}</b>, residing at {{ $L['address'] ?? $blank }}@if(!empty($L['pan'])), PAN {{ $L['pan'] }}@endif, hereinafter called the <b>LANDLORD</b>, which expression includes their heirs, successors and assigns;</p>
    <p style="text-align:center">AND</p>
    <p class="party"><b>{{ $T['name'] ?? $blank }}</b>, residing at {{ $T['address'] ?? $blank }}@if(!empty($T['pan'])), PAN {{ $T['pan'] }}@endif, hereinafter called the <b>TENANT</b>, which expression includes their heirs, successors and permitted assigns.</p>

    <p>The Landlord is the owner of the {{ $commercial ? 'premises' : 'residential premises' }}
        ({{ config('rent_agreement.property_types')[$P['type'] ?? ''] ?? 'property' }}@if(!empty($P['bhk'])), {{ $P['bhk'] }} BHK @endif, {{ strtolower(config('properties.furnishing')[$P['furnishing'] ?? ''] ?? '') }})
        at <b>{{ $P['address'] ?? $blank }}</b>@if(!empty($P['pincode'])) – {{ $P['pincode'] }}@endif (the "Premises"), and has agreed to let it to the Tenant on the terms below.</p>

    <h3>Terms agreed</h3>
    <ol>
        <li><b>Term.</b> The tenancy is for {{ $a->months }} {{ $a->months === 1 ? 'month' : 'months' }} starting {{ $a->start_date?->format('d M Y') ?? $blank }} and ending {{ $a->endDate()?->format('d M Y') ?? $blank }}.</li>
        <li><b>Rent.</b> The Tenant shall pay a monthly rent of {{ $PF::full($a->rent) }} on or before the {{ $ord((int) $a->term('due_day', 5)) }} day of each month, by bank transfer or any mode both parties agree in writing.</li>
        <li><b>Security deposit.</b> The Tenant has paid / shall pay an interest-free refundable deposit of {{ $PF::full($a->deposit) }}. The Landlord shall refund it when the Tenant hands over vacant possession, after deducting only unpaid rent, unpaid bills and the cost of repairing damage beyond normal wear and tear, with a written list of any deductions.</li>
        <li><b>Maintenance and bills.</b> @if($a->term('maintenance') === 'tenant')The Tenant shall pay society / maintenance charges{{ $a->term('maintenance_amount') ? ' of ' . $PF::full($a->term('maintenance_amount')) . ' a month' : '' }}.@else Society / maintenance charges are included in the rent and paid by the Landlord.@endif The Tenant shall pay electricity, water, gas and internet charges for the period of occupation as per actual bills.</li>
        <li><b>Use.</b> The Premises shall be used only for {{ $commercial ? 'lawful business purposes' : 'residential purposes by the Tenant and their family' }}. The Tenant shall not sub-let, assign or part with possession of the Premises without the Landlord's written consent.@unless($commercial) {{ $a->term('pets') ? 'Pets are allowed, provided they do not cause nuisance or damage.' : 'Pets are not allowed without the Landlord\'s written consent.' }}@endunless</li>
        <li><b>Care of the Premises.</b> The Tenant shall keep the Premises clean and in good condition, carry out minor day-to-day repairs, and make no structural changes. Major repairs and structural defects are the Landlord's responsibility.</li>
        <li><b>Inspection.</b> The Landlord may inspect the Premises at a reasonable time after giving the Tenant at least 24 hours' notice.</li>
        <li><b>Termination.</b> Either party may end this agreement by giving {{ $a->term('notice_months', 1) }} {{ (int) $a->term('notice_months', 1) === 1 ? 'month\'s' : 'months\'' }} written notice @if($a->term('lockin_months')), but not before the lock-in period of {{ $a->term('lockin_months') }} months ends; a party leaving during the lock-in shall pay rent for the rest of the lock-in period @endif.</li>
        <li><b>Renewal.</b> The agreement may be renewed by mutual consent on fresh terms; unless agreed otherwise, the rent on renewal shall increase by {{ rtrim(rtrim(number_format((float) $a->term('escalation_pct', 5), 1), '0'), '.') }}%.</li>
        <li><b>Handover.</b> On expiry or termination the Tenant shall hand over vacant and peaceful possession of the Premises with the fixtures listed below, in the condition received except normal wear and tear.</li>
        <li><b>Laws and rules.</b> The Tenant shall follow the rules of the society / building and applicable laws, and shall not store anything illegal or hazardous on the Premises.</li>
        @foreach($extra as $e)<li>{{ $e }}</li>@endforeach
    </ol>

    <h3>Fixtures and fittings handed over</h3>
    <p>{{ $fixtures->isNotEmpty() ? $fixtures->implode(', ') : 'None listed.' }}</p>

    <p>IN WITNESS WHEREOF the parties have signed this agreement on the date first written above.</p>
    <div class="sign"><div>LANDLORD<br>{{ $L['name'] ?? '' }}</div><div>TENANT<br>{{ $T['name'] ?? '' }}</div></div>
    <div class="sign"><div>WITNESS 1<br>Name &amp; address</div><div>WITNESS 2<br>Name &amp; address</div></div>
</div>
</body></html>
