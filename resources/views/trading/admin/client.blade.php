@extends('layouts.site')
@section('title', $client->client_code . ' — Back office')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => $client->client_code . ' · ' . $client->full_name, 'subtitle' => ucfirst($client->status) . ' · ' . $client->user?->email])
@php $checks = $client->verifications->groupBy('check')->map->first(); @endphp
<main class="dt-wrap dt-main">
    <div class="dt-two">
        <div style="display:grid;gap:16px">
            <section class="dt-card">
                <h2>Details</h2>
                <div class="dt-stats">
                    @foreach(['Date of birth' => $client->dob?->format('d M Y') . ($client->is_minor ? ' (minor)' : ''), 'Gender' => $client->gender, "Father/spouse" => $client->father_or_spouse_name,
                              'Residential status' => $client->residential_status, 'Occupation' => $client->occupation, 'Income' => $client->annual_income, 'PEP' => $client->is_pep ? 'Yes' : 'No',
                              'Foreign tax residency' => $client->tax_resident_elsewhere ? 'Yes' : 'No', 'PAN' => $client->maskedPan() . ' · ' . $client->pan_name, 'Aadhaar' => $client->aadhaar_last4 ? 'XXXX XXXX ' . $client->aadhaar_last4 : null,
                              'CKYC' => $client->ckyc_number, 'Email' => $client->email . ($client->email_verified_at ? ' ✓' : ''), 'Mobile' => $client->mobile . ($client->mobile_verified_at ? ' ✓' : ''),
                              'Address' => trim($client->address_line1 . ', ' . $client->address_line2 . ', ' . $client->city . ', ' . $client->state . ' ' . $client->pincode, ' ,'),
                              'Bank' => $client->bank_name . ' · ' . $client->bank_branch . ' · XXXX' . $client->bank_account_last4 . ' · ' . $client->ifsc . ' · ' . $client->bank_holder_name,
                              'Holding' => str_replace('_', ' ', $client->holding_mode), 'Guardian' => $client->guardian_name ? $client->guardian_name . ' (' . $client->guardian_relation . ')' : null,
                              'Risk profile' => $client->risk_profile ? ucfirst($client->risk_profile) . ' · ' . $client->risk_score . '/20' : null, 'Consent' => $client->consent_at?->timezone('Asia/Kolkata')->format('d M Y H:i') . ' · ' . $client->consent_ip] as $l => $val)
                        <div class="dt-stat"><span>{{ $l }}</span><b style="font-size:13.5px">{{ filled(trim((string) $val, ' ·,')) ? $val : '—' }}</b></div>
                    @endforeach
                </div>
                @if($client->jointHolders->isNotEmpty())<h3>Joint holders</h3><ul>@foreach($client->jointHolders as $j)<li>{{ $j->sequence }}. {{ $j->name }} · {{ $j->relation_with_primary }} · {{ $j->dob?->format('d M Y') }}</li>@endforeach</ul>@endif
                <h3>Nominees</h3>
                @if($client->nomination_opted === false)<p class="dt-muted">Opted out on {{ $client->nomination_opt_out_at?->format('d M Y') }}.</p>
                @else<ul>@foreach($client->nominees as $n)<li>{{ $n->name }} · {{ $n->relation }} · {{ $n->share_percent }}% · {{ $n->id_type }}@if($n->is_minor) · minor, guardian {{ $n->guardian_name }}@endif</li>@endforeach</ul>@endif
            </section>
            <section class="dt-card">
                <h2>Documents</h2>
                <div class="dt-table-wrap"><table class="dt-table"><thead><tr><th>Type</th><th>File</th><th>Status</th><th>Review</th></tr></thead><tbody>
                    @forelse($client->documents as $d)
                        <tr><td>{{ $documents[$d->type] ?? $d->type }}</td><td><a href="{{ route('trading.onboarding.document', $d) }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($d->original_name, 28) }}</a><br><small class="dt-muted">{{ number_format($d->size / 1024) }} KB</small></td>
                            <td><span class="dt-pill {{ $d->status }}">{{ $d->status }}</span>@if($d->remarks)<br><small>{{ $d->remarks }}</small>@endif</td>
                            <td><form method="post" action="{{ route('trading.admin.document', $d) }}" class="dt-filters">@csrf<input name="remarks" placeholder="Remarks" style="width:120px"><button class="dt-btn sm" name="status" value="verified">Verify</button><button class="dt-btn red sm" name="status" value="rejected">Reject</button></form></td></tr>
                    @empty
                        <tr><td colspan="4">No documents uploaded.</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
            <section class="dt-card">
                <h2>Verification history</h2>
                <div class="dt-table-wrap"><table class="dt-table"><thead><tr><th>Check</th><th>Provider</th><th>Result</th><th>Message</th><th>When</th></tr></thead><tbody>
                    @forelse($client->verifications as $v)<tr><td>{{ $v->check }}</td><td>{{ $v->provider }}</td><td><span class="dt-pill {{ $v->status }}">{{ $v->status }}</span></td><td>{{ $v->message }}</td><td>{{ $v->created_at->timezone('Asia/Kolkata')->format('d M H:i') }}</td></tr>
                    @empty<tr><td colspan="5">No checks yet.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        </div>
        <aside style="display:grid;gap:16px">
            <section class="dt-card">
                <h2>KYC checks</h2>
                @foreach(['pan' => ['PAN', $client->pan_verified_at], 'aadhaar' => ['Aadhaar', $client->aadhaar_verified_at], 'bank' => ['Bank account', $client->bank_verified_at], 'address' => ['Address', $client->address_verified_at], 'mobile' => ['Mobile', $client->mobile_verified_at]] as $check => [$label, $at])
                    <form method="post" action="{{ route('trading.admin.client.check', $client) }}" style="display:flex;gap:6px;align-items:center;justify-content:space-between;border-bottom:1px solid #edf2f7;padding:7px 0">@csrf
                        <input type="hidden" name="check" value="{{ $check }}">
                        <span><b>{{ $label }}</b><br><small class="{{ $at ? 'up' : 'dt-muted' }}">{{ $at ? 'Verified ' . $at->format('d M') : ($checks[$check]->message ?? 'Not verified') }}</small></span>
                        <span style="display:flex;gap:4px"><button class="dt-btn sm" name="result" value="passed">Pass</button><button class="dt-btn red sm" name="result" value="failed">Fail</button></span>
                    </form>
                @endforeach
            </section>
            <section class="dt-card">
                <h2>Decision</h2>
                <form method="post" action="{{ route('trading.admin.client.decide', $client) }}" style="display:grid;gap:8px">@csrf
                    <input name="reason" placeholder="Reason (required to reject or suspend)" style="border:1px solid #cfd9e6;border-radius:9px;padding:9px">
                    <div class="dt-filters"><button class="dt-btn gold" name="decision" value="verified">Approve</button><button class="dt-btn red" name="decision" value="rejected">Reject</button><button class="dt-btn light" name="decision" value="suspended">Suspend</button></div>
                </form>
            </section>
            @if($minorMajor->isNotEmpty())
                <section class="dt-card">
                    <h2>Minor to major</h2>
                    @foreach($minorMajor as $m)
                        <form method="post" action="{{ route('trading.admin.minor-major', $m) }}" style="display:grid;gap:6px;margin-bottom:10px">@csrf
                            <span>Major on {{ $m->major_on->format('d M Y') }} · <span class="dt-pill {{ $m->status }}">{{ str_replace('_', ' ', $m->status) }}</span></span>
                            <input name="remarks" placeholder="Remarks" style="border:1px solid #cfd9e6;border-radius:9px;padding:8px">
                            <div class="dt-filters"><button class="dt-btn sm" name="status" value="verified">Documents verified</button><button class="dt-btn gold sm" name="status" value="completed">Complete</button><button class="dt-btn red sm" name="status" value="rejected">Reject</button></div>
                        </form>
                    @endforeach
                </section>
            @endif
            <section class="dt-card">
                <h2>Holdings</h2>
                @forelse($client->holdings as $h)<p style="margin:4px 0">{{ $h->scheme->name ?? $h->scheme_code }}<br><small class="dt-muted">{{ number_format($h->units, 4) }} units · ₹{{ number_format($h->invested, 2) }}</small></p>@empty<p class="dt-muted">None.</p>@endforelse
                <a class="dt-btn light sm" href="{{ route('trading.admin.orders', ['q' => $client->client_code]) }}">Orders</a>
                <a class="dt-btn light sm" href="{{ route('trading.admin.audit', ['client' => $client->id]) }}">Audit log</a>
            </section>
        </aside>
    </div>
</main>
@endsection
