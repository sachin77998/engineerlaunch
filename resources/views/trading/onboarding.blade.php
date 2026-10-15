@extends('layouts.site')
@section('title', 'Account opening — Do Trading')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'Account opening (KYC)', 'subtitle' => 'Client ' . $client->client_code . ' · ' . ucfirst($client->status) . ($client->status === 'rejected' && $client->rejection_reason ? ' — ' . $client->rejection_reason : '')])
@php
    $v = fn ($field, $default = null) => old($field, $client->{$field} ?? $default);
    $locked = in_array($client->status, ['submitted', 'verified'], true);
    $checks = $client->verifications->groupBy('check')->map->first();
@endphp
<main class="dt-wrap dt-main">
    @if($client->status === 'verified')<p class="dt-flash ok">Your account is verified. <a href="{{ route('trading.index') }}">Start investing</a>.</p>
    @elseif($client->status === 'submitted')<p class="dt-flash warn">Submitted on {{ $client->consent_at?->timezone('Asia/Kolkata')->format('d M Y, H:i') }}. We are verifying your documents.</p>@endif
    <section class="dt-card">
        <nav class="dt-steps" aria-label="Steps">
            @foreach($steps as $n => $label)<a href="{{ route('trading.onboarding', ['step' => $n]) }}" class="{{ $n === $step ? 'on' : ($n < $client->step ? 'done' : '') }}">{{ $n }}. {{ $label }}</a>@endforeach
        </nav>
        <h2>{{ $step }}. {{ $steps[$step] }}</h2>
        @if($locked && $step !== 8)<p class="dt-muted">Your details are locked while they are {{ $client->status }}.</p>@endif

        <form method="post" action="{{ route('trading.onboarding.save', $step) }}" enctype="multipart/form-data">
            @csrf
            <fieldset @disabled($locked && $step !== 8) style="border:0;padding:0;margin:0">
            @switch($step)
            @case(1)
                <div class="dt-form">
                    <label>Full name (as on PAN)<input name="full_name" value="{{ $v('full_name') }}" required maxlength="120"></label>
                    <label>Date of birth<input type="date" name="dob" value="{{ old('dob', $client->dob?->toDateString()) }}" required></label>
                    <label>Gender<select name="gender" required>@foreach(['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $k => $l)<option value="{{ $k }}" @selected($v('gender') === $k)>{{ $l }}</option>@endforeach</select></label>
                    <label>Father's / spouse's name<input name="father_or_spouse_name" value="{{ $v('father_or_spouse_name') }}" required></label>
                    <label>Mother's name<input name="mother_name" value="{{ $v('mother_name') }}"></label>
                    <label>Marital status<select name="marital_status"><option value="">—</option>@foreach(['single' => 'Single', 'married' => 'Married', 'other' => 'Other'] as $k => $l)<option value="{{ $k }}" @selected($v('marital_status') === $k)>{{ $l }}</option>@endforeach</select></label>
                    <label>Residential status<select name="residential_status">@foreach(['resident' => 'Resident Indian', 'nri' => 'Non-resident Indian', 'foreign_national' => 'Foreign national'] as $k => $l)<option value="{{ $k }}" @selected($v('residential_status') === $k)>{{ $l }}</option>@endforeach</select></label>
                    <label>Occupation<select name="occupation" required>@foreach(['Salaried (private)', 'Salaried (government)', 'Business', 'Professional', 'Self-employed', 'Student', 'Homemaker', 'Retired', 'Agriculture', 'Other'] as $o)<option @selected($v('occupation') === $o)>{{ $o }}</option>@endforeach</select></label>
                    <label>Annual income<select name="annual_income" required>@foreach(['Below ₹1 lakh', '₹1–5 lakh', '₹5–10 lakh', '₹10–25 lakh', '₹25 lakh–1 crore', 'Above ₹1 crore'] as $o)<option @selected($v('annual_income') === $o)>{{ $o }}</option>@endforeach</select></label>
                    <label>Source of wealth<input name="source_of_wealth" value="{{ $v('source_of_wealth') }}" placeholder="Salary, business income…"></label>
                    <label>Place of birth<input name="birth_place" value="{{ $v('birth_place') }}"></label>
                    <label class="check"><input type="checkbox" name="is_pep" value="1" @checked($v('is_pep'))> I am a politically exposed person (or related to one)</label>
                    <label class="check"><input type="checkbox" name="tax_resident_elsewhere" value="1" @checked($v('tax_resident_elsewhere'))> I am a tax resident of a country other than India (FATCA/CRS)</label>
                </div>
                @break
            @case(2)
                <div class="dt-form">
                    <label>Email<input type="email" name="email" value="{{ $v('email', auth()->user()->email) }}"></label>
                    <div style="display:flex;gap:6px;align-items:end">@if($client->email_verified_at)<span class="dt-pill verified">Email verified</span>@else<button class="dt-btn light sm" name="action" value="send_email">Send email OTP</button>@endif</div>
                    @unless($client->email_verified_at)<label>Email OTP<input name="email_otp" inputmode="numeric" maxlength="6"></label><div style="display:flex;align-items:end"><button class="dt-btn sm" name="action" value="verify_email">Verify email</button></div>@endunless
                    <label>Mobile (10 digits)<input name="mobile" value="{{ $v('mobile') }}" inputmode="numeric" maxlength="10"></label>
                    <div style="display:flex;gap:6px;align-items:end">@if($client->mobile_verified_at)<span class="dt-pill verified">Mobile verified</span>@else<button class="dt-btn light sm" name="action" value="send_mobile">{{ $smsReady ? 'Send SMS OTP' : 'Save mobile' }}</button>@endif</div>
                    @if($smsReady && !$client->mobile_verified_at)<label>SMS OTP<input name="mobile_otp" inputmode="numeric" maxlength="6"></label><div style="display:flex;align-items:end"><button class="dt-btn sm" name="action" value="verify_mobile">Verify mobile</button></div>@endif
                </div>
                @break
            @case(3)
                <div class="dt-form">
                    <label>PAN<input name="pan" value="{{ old('pan') }}" placeholder="{{ $client->maskedPan() ?? 'ABCPE1234F' }}" maxlength="10" style="text-transform:uppercase" required></label>
                    <label>Name exactly as on PAN<input name="pan_name" value="{{ $v('pan_name') }}" required></label>
                    <label>Last 4 digits of Aadhaar<input name="aadhaar_last4" value="{{ $v('aadhaar_last4') }}" inputmode="numeric" maxlength="4" required></label>
                    <label>CKYC number (optional)<input name="ckyc_number" value="{{ $v('ckyc_number') }}" inputmode="numeric" maxlength="14"></label>
                    <label class="check wide"><input type="checkbox" name="pan_aadhaar_linked" value="1" @checked($client->pan_aadhaar_linked)> My PAN is linked with my Aadhaar</label>
                </div>
                <p class="dt-muted">We never store your full Aadhaar number. Upload a masked Aadhaar (first 8 digits hidden) in step 8.</p>
                @if($c = $checks['pan'] ?? null)<p class="dt-muted">PAN check: <span class="dt-pill {{ $c->status }}">{{ $c->status }}</span> {{ $c->message }}</p>@endif
                @break
            @case(4)
                <div class="dt-form">
                    <label class="wide">Address line 1<input name="address_line1" value="{{ $v('address_line1') }}" required maxlength="200"></label>
                    <label class="wide">Address line 2<input name="address_line2" value="{{ $v('address_line2') }}" maxlength="200"></label>
                    <label>PIN code<input name="pincode" id="dt-pin" value="{{ $v('pincode') }}" inputmode="numeric" maxlength="6" required></label>
                    <label>City / district<input name="city" id="dt-city" value="{{ $v('city') }}" required></label>
                    <label>State<input name="state" id="dt-state" value="{{ $v('state') }}" required></label>
                    <label>Address proof<select name="address_proof_type">@foreach(['aadhaar' => 'Aadhaar (masked)', 'passport' => 'Passport', 'voter_id' => 'Voter ID', 'driving_licence' => 'Driving licence', 'utility_bill' => 'Utility bill (≤ 2 months)', 'bank_statement' => 'Bank statement'] as $k => $l)<option value="{{ $k }}" @selected($v('address_proof_type') === $k)>{{ $l }}</option>@endforeach</select></label>
                    <label class="check wide"><input type="checkbox" name="correspondence_same" value="1" @checked($v('correspondence_same', true))> Correspondence address is the same</label>
                    <label>Correspondence address<input name="corr_line1" value="{{ old('corr_line1', $client->correspondence_address['line1'] ?? '') }}"></label>
                    <label>Correspondence PIN<input name="corr_pincode" value="{{ old('corr_pincode', $client->correspondence_address['pincode'] ?? '') }}" maxlength="6"></label>
                    <label>Correspondence city<input name="corr_city" value="{{ old('corr_city', $client->correspondence_address['city'] ?? '') }}"></label>
                    <label>Correspondence state<input name="corr_state" value="{{ old('corr_state', $client->correspondence_address['state'] ?? '') }}"></label>
                </div>
                @break
            @case(5)
                <div class="dt-form">
                    <label>Account number<input name="bank_account" inputmode="numeric" maxlength="18" placeholder="{{ $client->bank_account_last4 ? 'XXXX' . $client->bank_account_last4 : '' }}" required autocomplete="off"></label>
                    <label>Confirm account number<input name="bank_account_confirmation" inputmode="numeric" maxlength="18" required autocomplete="off"></label>
                    <label>IFSC<input name="ifsc" id="dt-ifsc" value="{{ $v('ifsc') }}" maxlength="11" style="text-transform:uppercase" required><small id="dt-ifsc-info" class="dt-muted">{{ $client->bank_name ? $client->bank_name . ', ' . $client->bank_branch : '' }}</small></label>
                    <label>Account type<select name="account_type">@foreach(['savings' => 'Savings', 'current' => 'Current', 'nre' => 'NRE', 'nro' => 'NRO'] as $k => $l)<option value="{{ $k }}" @selected($v('account_type') === $k)>{{ $l }}</option>@endforeach</select></label>
                    <label>Account holder name<input name="bank_holder_name" value="{{ $v('bank_holder_name', $client->full_name) }}" required></label>
                </div>
                @if($c = $checks['bank'] ?? null)<p class="dt-muted">Bank check: <span class="dt-pill {{ $c->status }}">{{ $c->status }}</span> {{ $c->message }}</p>@endif
                @break
            @case(6)
                <div class="dt-form">
                    <label>Mode of holding<select name="holding_mode">@foreach(['single' => 'Single', 'joint' => 'Joint', 'anyone_or_survivor' => 'Anyone or survivor'] as $k => $l)<option value="{{ $k }}" @selected($v('holding_mode') === $k)>{{ $l }}</option>@endforeach</select></label>
                </div>
                @for($i = 0; $i < 2; $i++)
                    @php $j = $client->jointHolders[$i] ?? null; @endphp
                    <h3>{{ $i === 0 ? 'Second' : 'Third' }} holder (optional)</h3>
                    <div class="dt-form">
                        <label>Name<input name="joint[{{ $i }}][name]" value="{{ old("joint.$i.name", $j?->name) }}"></label>
                        <label>PAN<input name="joint[{{ $i }}][pan]" value="{{ old("joint.$i.pan") }}" maxlength="10" style="text-transform:uppercase" placeholder="{{ $j ? 'Saved' : '' }}"></label>
                        <label>Date of birth<input type="date" name="joint[{{ $i }}][dob]" value="{{ old("joint.$i.dob", $j?->dob?->toDateString()) }}"></label>
                        <label>Relation<input name="joint[{{ $i }}][relation_with_primary]" value="{{ old("joint.$i.relation_with_primary", $j?->relation_with_primary) }}"></label>
                        <label>Mobile<input name="joint[{{ $i }}][mobile]" value="{{ old("joint.$i.mobile", $j?->mobile) }}" maxlength="10"></label>
                        <label>Email<input type="email" name="joint[{{ $i }}][email]" value="{{ old("joint.$i.email", $j?->email) }}"></label>
                    </div>
                @endfor
                @if($client->is_minor)
                    <h3>Guardian (the investor is a minor)</h3>
                    <div class="dt-form">
                        <label>Guardian name<input name="guardian_name" value="{{ $v('guardian_name') }}" required></label>
                        <label>Relation<select name="guardian_relation">@foreach(['father' => 'Father', 'mother' => 'Mother', 'court_appointed' => 'Court-appointed'] as $k => $l)<option value="{{ $k }}" @selected($v('guardian_relation') === $k)>{{ $l }}</option>@endforeach</select></label>
                        <label>Guardian PAN<input name="guardian_pan" maxlength="10" style="text-transform:uppercase" required></label>
                        <label>Guardian date of birth<input type="date" name="guardian_dob" value="{{ old('guardian_dob', $client->guardian_dob?->toDateString()) }}" required></label>
                    </div>
                @endif
                @break
            @case(7)
                <p class="dt-muted">Add up to 10 nominees; shares must total 100%. Or opt out with a declaration.</p>
                @php $noms = old('nominees', $client->nominees->map(fn ($n) => $n->only(['name', 'relation', 'share_percent', 'id_type', 'mobile', 'email', 'address', 'guardian_name', 'guardian_relation']) + ['dob' => $n->dob?->toDateString()])->all()) ?: [[]]; @endphp
                <div id="dt-noms">
                @foreach($noms as $i => $n)
                    <div class="dt-form dt-nom" style="border:1px dashed #cfd9e6;border-radius:12px;padding:12px;margin-bottom:10px">
                        <label>Nominee name<input name="nominees[{{ $i }}][name]" value="{{ $n['name'] ?? '' }}"></label>
                        <label>Relation<input name="nominees[{{ $i }}][relation]" value="{{ $n['relation'] ?? '' }}"></label>
                        <label>Date of birth<input type="date" name="nominees[{{ $i }}][dob]" value="{{ $n['dob'] ?? '' }}"></label>
                        <label>Share %<input type="number" step="0.01" name="nominees[{{ $i }}][share_percent]" value="{{ $n['share_percent'] ?? '' }}"></label>
                        <label>ID type<select name="nominees[{{ $i }}][id_type]">@foreach(['pan' => 'PAN', 'aadhaar_last4' => 'Aadhaar (last 4)', 'passport' => 'Passport', 'driving_licence' => 'Driving licence'] as $k => $l)<option value="{{ $k }}" @selected(($n['id_type'] ?? '') === $k)>{{ $l }}</option>@endforeach</select></label>
                        <label>ID number<input name="nominees[{{ $i }}][id_number]" maxlength="20" placeholder="{{ isset($n['name']) ? 'Re-enter to save' : '' }}"></label>
                        <label>Mobile<input name="nominees[{{ $i }}][mobile]" value="{{ $n['mobile'] ?? '' }}" maxlength="10"></label>
                        <label>Email<input type="email" name="nominees[{{ $i }}][email]" value="{{ $n['email'] ?? '' }}"></label>
                        <label>Guardian (if nominee is a minor)<input name="nominees[{{ $i }}][guardian_name]" value="{{ $n['guardian_name'] ?? '' }}"></label>
                        <label>Guardian relation<input name="nominees[{{ $i }}][guardian_relation]" value="{{ $n['guardian_relation'] ?? '' }}"></label>
                        <label class="wide">Address<input name="nominees[{{ $i }}][address]" value="{{ $n['address'] ?? '' }}"></label>
                    </div>
                @endforeach
                </div>
                <button type="button" class="dt-btn light sm" id="dt-add-nom">Add nominee</button>
                <h3>Opt out</h3>
                <label class="check"><input type="checkbox" name="opt_out" value="1" @checked($client->nomination_opted === false)> I do not wish to nominate</label>
                <label class="check"><input type="checkbox" name="opt_out_declaration" value="1"> I understand that without a nominee my legal heirs will need to prove succession to claim the investments.</label>
                @break
            @case(8)
                @php $have = $client->documents->keyBy('type'); @endphp
                <div class="dt-table-wrap"><table class="dt-table"><thead><tr><th>Document</th><th>Status</th><th>File</th></tr></thead><tbody>
                    @foreach($documents as $type => $label)
                        @continue(in_array($type, ['birth_certificate', 'guardian_id'], true) && !$client->is_minor)
                        @continue($type === 'major_pan' && !$minorMajor)
                        @continue($type === 'nominee_id' && !$client->nomination_opted)
                        <tr><td>{{ $label }}</td><td>@if($d = $have[$type] ?? null)<span class="dt-pill {{ $d->status }}">{{ $d->status }}</span>@if($d->remarks)<br><small class="dt-muted">{{ $d->remarks }}</small>@endif @else<span class="dt-muted">Not uploaded</span>@endif</td>
                            <td>@if($d = $have[$type] ?? null)<a href="{{ route('trading.onboarding.document', $d) }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($d->original_name, 30) }}</a>@endif</td></tr>
                    @endforeach
                </tbody></table></div>
                <div class="dt-form" style="margin-top:12px">
                    <label>Document<select name="type">@foreach($documents as $type => $label)<option value="{{ $type }}">{{ $label }}</option>@endforeach</select></label>
                    <label>File (JPG, PNG or PDF, up to 4 MB)<input type="file" name="document" accept=".jpg,.jpeg,.png,.pdf"></label>
                </div>
                <p class="dt-muted">Choose a file and press Save to upload it. Press Save without a file to continue once everything is uploaded.</p>
                @break
            @case(9)
                @foreach($riskQuestions as $key => [$question, $options])
                    <h3>{{ $question }}</h3>
                    <div class="dt-filters">@foreach($options as $label => $score)<label class="check" style="border:1px solid #e3eaf3;border-radius:9px;padding:8px 10px"><input type="radio" name="risk[{{ $key }}]" value="{{ $label }}" @checked(($client->risk_answers[$key] ?? null) === $label) required> {{ $label }}</label>@endforeach</div>
                @endforeach
                @if($client->risk_profile)<p class="dt-muted" style="margin-top:12px">Current profile: <b>{{ ucfirst($client->risk_profile) }}</b> (score {{ $client->risk_score }}/20)</p>@endif
                @break
            @case(10)
                <div class="dt-stats">
                    @foreach(['Name' => $client->full_name, 'Date of birth' => $client->dob?->format('d M Y'), 'PAN' => $client->maskedPan(), 'Aadhaar' => $client->aadhaar_last4 ? 'XXXX XXXX ' . $client->aadhaar_last4 : null,
                              'Email' => $client->email . ($client->email_verified_at ? ' ✓' : ''), 'Mobile' => $client->mobile . ($client->mobile_verified_at ? ' ✓' : ''), 'Address' => trim($client->city . ' ' . $client->pincode),
                              'Bank' => $client->bank_name ? $client->bank_name . ' XXXX' . $client->bank_account_last4 : null, 'Holding' => str_replace('_', ' ', $client->holding_mode),
                              'Nominees' => $client->nomination_opted === false ? 'Opted out' : $client->nominees->count(), 'Documents' => $client->documents->count(), 'Risk profile' => ucfirst((string) $client->risk_profile)] as $label => $value)
                        <div class="dt-stat"><span>{{ $label }}</span><b style="font-size:14px">{{ filled($value) ? $value : '—' }}</b></div>
                    @endforeach
                </div>
                <div style="display:grid;gap:8px;margin-top:14px">
                    <label class="check"><input type="checkbox" name="consent_terms" value="1" required> I have read the scheme information documents and terms, and I invest at my own risk.</label>
                    <label class="check"><input type="checkbox" name="consent_kyc" value="1" required> I consent to my KYC details being verified with KRA/CKYC and shared with fund houses for my investments.</label>
                    <label class="check"><input type="checkbox" name="consent_fatca" value="1" required> My FATCA/CRS declaration above is true and I will report any change within 30 days.</label>
                </div>
                @break
            @endswitch
            </fieldset>
            @if(!$locked || $step === 8)<div style="margin-top:16px;display:flex;gap:8px"><button class="dt-btn gold" type="submit">{{ $step === 10 ? 'Submit for verification' : 'Save and continue' }}</button>@if($step > 1)<a class="dt-btn light" href="{{ route('trading.onboarding', ['step' => $step - 1]) }}">Back</a>@endif</div>@endif
        </form>
    </section>

    @if($client->is_minor && $client->dob && $client->dob->copy()->addYears(18)->isPast())
        <section class="dt-card">
            <h2>Minor to major</h2>
            @if($minorMajor)<p class="dt-muted">Request status: <span class="dt-pill {{ $minorMajor->status }}">{{ str_replace('_', ' ', $minorMajor->status) }}</span> {{ $minorMajor->remarks }}</p>
            @else
                <p class="dt-muted">You turned 18 on {{ $client->dob->copy()->addYears(18)->format('d M Y') }}. Update the account with your own PAN and bank account.</p>
                <form method="post" action="{{ route('trading.onboarding.minor-major') }}" class="dt-form">@csrf
                    <label>Your PAN<input name="new_pan" maxlength="10" style="text-transform:uppercase" required></label>
                    <label>Your bank account<input name="new_bank_account" inputmode="numeric" maxlength="18" required></label>
                    <label>IFSC<input name="new_ifsc" maxlength="11" style="text-transform:uppercase" required></label>
                    <div style="display:flex;align-items:end"><button class="dt-btn" type="submit">Request change</button></div>
                </form>
            @endif
        </section>
    @endif
</main>
@endsection
@push('scripts')
<script>
(() => {
  const pin = document.getElementById('dt-pin');
  if (pin) pin.addEventListener('input', () => {
    if (!/^[1-9]\d{5}$/.test(pin.value)) return;
    fetch('{{ route('trading.onboarding.pincode') }}?pin=' + pin.value, { headers: { Accept: 'application/json' } }).then(r => r.json()).then(j => {
      if (j.data && j.data.state) { document.getElementById('dt-city').value = j.data.district; document.getElementById('dt-state').value = j.data.state; }
    });
  });
  const ifsc = document.getElementById('dt-ifsc');
  if (ifsc) ifsc.addEventListener('input', () => {
    const v = ifsc.value.toUpperCase(); if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(v)) return;
    fetch('{{ route('trading.onboarding.ifsc') }}?ifsc=' + v, { headers: { Accept: 'application/json' } }).then(r => r.json()).then(j => {
      document.getElementById('dt-ifsc-info').textContent = j.data && j.data.BANK ? j.data.BANK + ', ' + j.data.BRANCH + ', ' + j.data.CITY : 'IFSC not found';
    });
  });
  const add = document.getElementById('dt-add-nom');
  if (add) add.addEventListener('click', () => {
    const boxes = document.querySelectorAll('.dt-nom'); if (boxes.length >= 10) return;
    const copy = boxes[boxes.length - 1].cloneNode(true), i = boxes.length;
    copy.querySelectorAll('input,select').forEach(el => { el.name = el.name.replace(/nominees\[\d+\]/, 'nominees[' + i + ']'); if (el.tagName === 'INPUT') el.value = ''; });
    boxes[boxes.length - 1].after(copy);
  });
})();
</script>
@endpush
