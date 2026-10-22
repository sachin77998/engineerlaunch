<!doctype html>
<html><head><meta charset="utf-8"><style>
@page{margin:36px 50px}body{font-family:DejaVu Sans,sans-serif;font-size:11.5px;color:#1d2939;line-height:1.42}
.head{border-bottom:2px solid #1e40af;padding-bottom:10px;margin-bottom:14px}.head h1{margin:0;font-size:20px;color:#1e40af}.head div{color:#667085;font-size:10.5px}
h2{font-size:14.5px;margin:12px 0 6px}table{width:100%;border-collapse:collapse;margin:6px 0 10px}td{padding:5px 9px;border:1px solid #d0d5dd}td.k{width:38%;background:#f5f8ff;font-weight:bold}
.sign{margin-top:20px;page-break-inside:avoid}.small{font-size:10px;color:#667085}.accept{margin-top:20px;page-break-inside:avoid;border-top:1px dashed #98a2b3;padding-top:14px}
</style></head><body>
@php($co = $a->job?->company)
@php($p = $a->candidate?->candidateProfile)
<div class="head">
    <h1>{{ $co?->name ?? 'Company' }}</h1>
    <div>{{ collect([$co?->headquarters, $co?->country])->filter()->implode(', ') }}{{ $co?->website ? ' · '.$co->website : '' }}{{ $co?->company_email ? ' · '.$co->company_email : '' }}{{ $co?->registry_cin ? ' · CIN '.$co->registry_cin : '' }}</div>
</div>

<p>Date: {{ ($o->sent_at ?? now())->format('d F Y') }}<br>Ref: OFFER/{{ now()->format('Y') }}/{{ str_pad($o->id, 5, '0', STR_PAD_LEFT) }}</p>
<p><strong>{{ $a->candidate?->name }}</strong><br>{{ $p?->address ?: collect([$p?->city, $p?->state])->filter()->implode(', ') }}<br>{{ $a->candidate?->email }}</p>

<h2>Offer of Employment — {{ $o->designation }}</h2>
<p>Dear {{ strtok((string) $a->candidate?->name, ' ') }},</p>
<p>With reference to your application and the subsequent interviews, we are pleased to offer you the position of <strong>{{ $o->designation }}</strong> with {{ $co?->name ?? 'the company' }} on the following terms:</p>

<table>
    <tr><td class="k">Designation</td><td>{{ $o->designation }}</td></tr>
    <tr><td class="k">Annual Cost to Company (CTC)</td><td>{{ $money($o->annual_ctc) }}<br><span class="small">({{ $words }}{{ $o->currency === 'INR' ? ' Rupees' : '' }} only)</span></td></tr>
    @if($o->fixed_pay)<tr><td class="k">Fixed pay (annual)</td><td>{{ $money($o->fixed_pay) }}</td></tr>@endif
    @if($o->variable_pay)<tr><td class="k">Variable / performance pay (annual)</td><td>{{ $money($o->variable_pay) }}</td></tr>@endif
    @if($o->joining_bonus)<tr><td class="k">One-time joining bonus</td><td>{{ $money($o->joining_bonus) }}</td></tr>@endif
    <tr><td class="k">Date of joining</td><td>{{ $o->joining_date->format('d F Y') }}</td></tr>
    @if($o->work_location)<tr><td class="k">Work location</td><td>{{ $o->work_location }}</td></tr>@endif
    @if($o->probation_months)<tr><td class="k">Probation</td><td>{{ $o->probation_months }} months</td></tr>@endif
    <tr><td class="k">Offer valid until</td><td>{{ $o->expires_on->format('d F Y') }}</td></tr>
</table>

@if($o->terms)<h2>Additional terms</h2><div>{!! nl2br(e($o->terms)) !!}</div>@endif

<p>This offer is subject to satisfactory verification of your identity, education and employment records, and to your submitting the documents requested by HR on or before the date of joining. Your detailed terms of employment will be shared in the appointment letter on joining.</p>
<p>Please confirm your acceptance by {{ $o->expires_on->format('d F Y') }}, after which this offer lapses.</p>
<p>We look forward to welcoming you to the team.</p>

<div class="sign">For <strong>{{ $co?->name ?? 'the company' }}</strong><br><br><strong>{{ $o->signatory_name }}</strong>{{ $o->signatory_title ? ', '.$o->signatory_title : '' }}</div>

<div class="accept"><strong>Acceptance</strong><br>I accept the offer on the terms above.<br><br>Name: ____________________ &nbsp; Signature: ____________________ &nbsp; Date: ____________</div>
</body></html>
