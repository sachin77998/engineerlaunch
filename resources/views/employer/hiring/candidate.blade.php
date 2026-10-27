@extends('layouts.employer')
@section('title', ($a->candidate?->name ?? 'Candidate').' · '.$a->job->title)
@include('employer.hiring._style')
@push('styles')<style>
.cw{display:grid;grid-template-columns:1.25fr 1fr;gap:16px}.facts{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}.facts div span{display:block;font-size:12px;color:var(--muted);font-weight:700}
.bar{height:8px;background:#e6eefa;border-radius:8px;overflow:hidden;margin-top:4px}.bar i{display:block;height:100%;background:var(--blue)}
.item{border-top:1px solid #e6eefa;padding:10px 0}.item:first-child{border-top:0}.hs-field{margin-bottom:8px}
@media(max-width:1000px){.cw{grid-template-columns:1fr}}
</style>@endpush
@section('content')
@php($stages = \App\Models\JobApplication::STAGES)
@php($sr = $a->screening_result ?? [])
@php($can = fn ($ability) => app(\App\Services\Hiring\Team::class)->can(auth()->user(), $ability))
<div class="hs-head">
    <div>
        <div class="muted"><a href="{{ route('employer.hiring.pipeline', $a->job) }}">← {{ $a->job->title }}</a></div>
        <h1>{{ $a->candidate?->name }} @if($a->match_score !== null)<span class="score {{ $a->match_score >= 70 ? 'hi' : ($a->match_score >= 45 ? 'mid' : 'lo') }}">{{ (int) $a->match_score }}</span>@endif</h1>
        <div class="muted">{{ $p?->headline ?: $p?->current_designation }} · {{ $a->candidate?->email }}{{ $p?->phone ? ' · '.$p->phone : '' }}</div>
        <div style="margin-top:6px"><span class="pill {{ $a->stage }}">{{ $stages[$a->stage] ?? $a->stage }}</span> <span class="muted">Applied {{ $a->applied_at?->format('d M Y, h:i A') }} via {{ $a->application_source ?: 'portal' }}</span></div>
        @if($a->rejection_reason)<div class="muted" style="margin-top:4px">Reason: {{ $a->rejection_reason }}</div>@endif
    </div>
    <div class="hs-row">
        <a class="btn" href="{{ route('employer.hiring.resume', $a) }}">⬇ Resume</a>
        @if($p?->phone)<a class="btn" href="tel:{{ preg_replace('/[^\d+]/', '', $p->phone) }}">Call</a><a class="btn" target="_blank" rel="noopener" href="https://wa.me/{{ preg_replace('/\D/', '', ($p->phone_country_code ?: '').$p->phone) }}">WhatsApp</a>@endif
        <a class="btn" href="mailto:{{ $a->candidate?->email }}?subject={{ rawurlencode($a->job->title) }}">Email</a>
    </div>
</div>

@if($can('edit'))<form class="hs-card hs-row" method="post" action="{{ route('employer.hiring.stage', $a) }}">@csrf @method('PATCH')
    <strong>Move to</strong>
    <select class="hs-in" name="stage" style="width:auto" onchange="document.getElementById('rej').style.display=this.value==='rejected'?'':'none'">@foreach($stages as $k => $v)<option value="{{ $k }}" @selected($a->stage === $k)>{{ $v }}</option>@endforeach</select>
    <input class="hs-in" id="rej" name="reason" placeholder="Rejection reason" style="width:220px;display:none">
    <input class="hs-in" name="note" placeholder="Note for history (optional)" style="flex:1;min-width:180px">
    <button class="hs-mini primary">Update stage</button>
</form>@endif

<div class="cw">
<div>
    <section class="hs-card">
        <h2>Profile</h2>
        <div class="facts">
            <div><span>Experience</span>{{ $p?->experience_years !== null ? (float) $p->experience_years.' years' : '—' }}</div>
            <div><span>Current company</span>{{ $p?->current_company ?: '—' }}</div>
            <div><span>Designation</span>{{ $p?->current_designation ?: '—' }}</div>
            <div><span>Current CTC</span>{{ $p?->current_salary ? number_format($p->current_salary).' '.$p->current_salary_currency : '—' }}</div>
            <div><span>Expected CTC</span>{{ $p?->expected_salary ? number_format($p->expected_salary).' '.$p->expected_salary_currency : '—' }}</div>
            <div><span>Notice period</span>{{ $p?->notice_period ?: '—' }}</div>
            <div><span>Location</span>{{ collect([$p?->city, $p?->state])->filter()->implode(', ') ?: ($p?->location ?: '—') }}</div>
            <div><span>Preferred role</span>{{ $p?->preferred_role ?: '—' }}</div>
            <div><span>Job search</span>{{ $p?->job_search_status ? ucwords(str_replace('_', ' ', $p->job_search_status)) : '—' }}</div>
        </div>
        @if($p?->education)<div class="item"><span class="muted" style="font-size:12px;font-weight:700">EDUCATION</span><div>{!! nl2br(e($p->education)) !!}</div></div>@endif
        @if($skills->isNotEmpty())<div class="item">@foreach($skills as $s)<span class="tag">{{ $s->name }}{{ $s->years ? ' · '.$s->years.'y' : '' }}</span>@endforeach</div>@endif
        @if($p?->bio)<div class="item muted">{!! nl2br(e($p->bio)) !!}</div>@endif
    </section>

    <section class="hs-card">
        <h2>Match against this job</h2>
        @foreach(['skill_match' => 'Skills', 'experience' => 'Experience', 'location' => 'Location', 'salary' => 'Salary fit'] as $k => $label)
            @if(isset($sr['breakdown'][$k]) && $sr['breakdown'][$k] !== null)
                <div style="margin-bottom:8px"><div class="hs-row" style="justify-content:space-between"><span>{{ $label }}</span><strong>{{ (int) $sr['breakdown'][$k] }}%</strong></div><div class="bar"><i style="width:{{ (int) $sr['breakdown'][$k] }}%"></i></div></div>
            @endif
        @endforeach
        @if(!empty($a->matched_skills))<div class="item"><strong>Has:</strong> @foreach($a->matched_skills as $s)<span class="tag" style="background:#e8f7ef">{{ $s }}</span>@endforeach</div>@endif
        @if(!empty($sr['missing_skills']))<div class="item"><strong>Missing:</strong> @foreach($sr['missing_skills'] as $s)<span class="tag" style="background:#fdecea">{{ $s }}</span>@endforeach</div>@endif
        @if(!empty($sr['failed']))<div class="item" style="color:#b42318"><strong>Failed rules:</strong> {{ implode('; ', $sr['failed']) }}</div>@endif
        @if(!$sr)<div class="muted">Not screened yet. Save screening rules on the pipeline page to score it.</div>@endif
    </section>

    @if($answers->isNotEmpty() || $a->cover_letter)
    <section class="hs-card">
        <h2>Screening answers</h2>
        @foreach($answers as $ans)<div class="item"><strong>{{ $ans->question }}</strong><div>{!! nl2br(e($ans->answer)) !!}</div></div>@endforeach
        @if($a->cover_letter)<div class="item"><strong>Cover letter</strong><div>{!! nl2br(e($a->cover_letter)) !!}</div></div>@endif
    </section>
    @endif

    @if($others->isNotEmpty())
    <section class="hs-card">
        <h2>Other applications to your jobs</h2>
        @foreach($others as $o)<div class="item hs-row" style="justify-content:space-between"><a href="{{ route('employer.hiring.candidate', $o) }}">{{ $o->job?->title }}</a><span><span class="pill {{ $o->stage }}">{{ $stages[$o->stage] ?? $o->stage }}</span> <span class="muted">{{ $o->applied_at?->format('d M Y') }}</span></span></div>@endforeach
    </section>
    @endif

    <section class="hs-card">
        <h2>History</h2>
        @foreach($history as $h)<div class="item"><strong>{{ ucwords(str_replace('_', ' ', $h->to_status)) }}</strong> <span class="muted">· {{ \Carbon\Carbon::parse($h->created_at)->format('d M Y, h:i A') }} · {{ $h->by_name ?? 'System' }}</span>@if($h->note)<div class="muted">{{ $h->note }}</div>@endif</div>@endforeach
    </section>
</div>

<div>
    <form class="hs-card" method="post" action="{{ route('employer.hiring.update', $a) }}">@csrf @method('PATCH')
        <h2>Rating &amp; tags</h2>
        <div class="hs-row">
            <select class="hs-in" name="rating" style="width:auto"><option value="">No rating</option>@foreach([5,4,3,2,1] as $s)<option value="{{ $s }}" @selected($a->rating == $s)>{{ str_repeat('★', $s) }}</option>@endforeach</select>
            <input class="hs-in" name="tags" value="{{ implode(', ', (array) $a->tags) }}" placeholder="e.g. strong-communicator, relocate, referral" style="flex:1;min-width:160px">
            <button class="hs-mini primary">Save</button>
        </div>
    </form>

    <section class="hs-card">
        <h2>Notes</h2>
        <form method="post" action="{{ route('employer.hiring.note', $a) }}">@csrf<div class="hs-field"><textarea name="body" rows="3" placeholder="Phone screen notes, salary discussion, red flags…" required></textarea></div><button class="hs-mini primary">Add note</button></form>
        @foreach($a->notes as $n)<div class="item"><div>{!! nl2br(e($n->body)) !!}</div><div class="muted" style="font-size:12px">{{ $n->author?->name }} · {{ $n->created_at->format('d M Y, h:i A') }}</div></div>@endforeach
    </section>


    @if($can('email'))
    <section class="hs-card" id="email">
        <h2>Email candidate</h2>
        <form method="post" action="{{ route('employer.hiring.email', $a) }}">@csrf
            @if($templates->isNotEmpty())<div class="hs-field"><select class="tpl-pick" data-target="cand-mail"><option value="">Use a template…</option>@foreach($templates as $t)<option value="{{ $t->id }}" data-subject="{{ $t->subject }}" data-body="{{ $t->body }}">{{ $t->name }}</option>@endforeach</select></div>@endif
            <div class="hs-field"><input id="cand-mail-subject" name="subject" placeholder="Subject" required maxlength="190" value="{{ $a->job->title }} at {{ $companyName }}"></div>
            <div class="hs-field"><textarea id="cand-mail-body" name="body" rows="5" required placeholder="Hi {first_name}, …">Hi {first_name},&#10;&#10;&#10;&#10;Regards,&#10;{recruiter_name}</textarea></div>
            <div class="hs-row" style="justify-content:space-between"><span class="muted" style="font-size:12px">{{ implode(' ', \App\Services\Hiring\Messenger::PLACEHOLDERS) }}</span><button class="hs-mini primary">Send email</button></div>
        </form>
        @foreach($a->messages as $msg)
            <details class="item"><summary><strong>{{ $msg->subject }}</strong> <span class="muted" style="font-size:12px">· {{ $msg->sender?->name }} · {{ $msg->created_at->format('d M Y, h:i A') }}</span> <span class="pill {{ $msg->status === 'sent' ? 'ok' : ($msg->status === 'failed' ? 'bad' : 'wait') }}">{{ ucfirst($msg->status) }}</span></summary><div class="muted" style="white-space:pre-wrap;margin-top:6px">{{ $msg->body }}</div>@if($msg->error)<div style="color:#b42318;font-size:12px">{{ $msg->error }}</div>@endif</details>
        @endforeach
    </section>
    @endif

    @php($onb = \App\Models\HiringOnboarding::with('items')->where('job_application_id', $a->id)->where('status', '!=', 'cancelled')->first())
    @if($onb || ($can('edit') && in_array($a->stage, ['offered', 'hired'], true)))
    <section class="hs-card" id="onboarding">
        <h2>Onboarding</h2>
        @if($onb)
            <div class="hs-row" style="justify-content:space-between">
                <span>{{ \App\Models\HiringOnboarding::STATUSES[$onb->status] ?? $onb->status }} · {{ $onb->progress }}% done · {{ $onb->items->where('status', 'submitted')->count() }} to review</span>
                <a class="hs-mini primary" href="{{ route('employer.hiring.onboarding.show', $onb) }}">Open checklist</a>
            </div>
        @else
            <p class="muted" style="margin-top:-4px">Email the new hire a private link to upload joining documents; review them here and the candidate moves to Hired when everything is approved.</p>
            <form method="post" action="{{ route('employer.hiring.onboarding.start', $a) }}" class="hs-row">@csrf
                <select class="hs-in" name="template_id" style="width:auto"><option value="">Standard joining checklist</option>@foreach(\App\Models\HiringOnboardingTemplate::where('owner_id', app(\App\Services\Hiring\Team::class)->ownerId(auth()->user()))->orderBy('name')->get() as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
                <label class="muted" style="font-size:12px">Joining by <input class="hs-in" type="date" name="due_on" min="{{ today()->toDateString() }}" style="width:auto"></label>
                <button class="hs-mini primary">Start onboarding</button>
            </form>
        @endif
    </section>
    @endif

    @if($can('offer') || $a->offers->isNotEmpty())
    <section class="hs-card" id="offer">
        <h2>Offer letter</h2>
        @foreach($a->offers as $of)
            <div class="item hs-row" style="justify-content:space-between">
                <span><strong>{{ $of->designation }}</strong> · {{ $of->currency }} {{ number_format($of->annual_ctc) }} · joins {{ $of->joining_date->format('d M Y') }}
                    <span class="pill {{ ['accepted' => 'ok', 'declined' => 'bad', 'withdrawn' => 'withdrawn', 'expired' => 'bad', 'sent' => 'wait'][$of->effective_status] ?? '' }}">{{ \App\Models\HiringOffer::STATUSES[$of->effective_status] }}</span>
                    @if($of->response_note)<div class="muted" style="font-size:12px">“{{ $of->response_note }}”</div>@endif</span>
                <span class="hs-row">
                    <a class="hs-mini" target="_blank" rel="noopener" href="{{ route('employer.hiring.offers.pdf', $of) }}">PDF</a>
                    @if($can('offer') && $of->status === 'draft')<form method="post" action="{{ route('employer.hiring.offers.send', $of) }}">@csrf<button class="hs-mini primary">Send</button></form>@endif
                    @if($can('offer') && in_array($of->status, ['draft', 'sent'], true))<form method="post" action="{{ route('employer.hiring.offers.withdraw', $of) }}" onsubmit="return confirm('Withdraw this offer?')">@csrf<button class="hs-mini danger">Withdraw</button></form>@endif
                </span>
            </div>
        @endforeach
        @if($can('offer'))
        <details @if($a->offers->isEmpty() && in_array($a->stage, ['interview', 'offered'], true)) open @endif style="margin-top:8px">
            <summary class="hs-mini" style="display:inline-flex">{{ $a->offers->isEmpty() ? 'Create offer' : 'New / revised offer' }}</summary>
            <form method="post" action="{{ route('employer.hiring.offers.store', $a) }}" style="margin-top:10px">@csrf
                <div class="hs-grid">
                    <div style="grid-column:span 2"><label>Designation</label><input name="designation" value="{{ old('designation', $a->job->title) }}" required></div>
                    <div><label>Annual CTC</label><input type="number" min="1" name="annual_ctc" value="{{ old('annual_ctc', $p?->expected_salary ? (int) $p->expected_salary : '') }}" required></div>
                    <div><label>Currency</label><select name="currency">@foreach(['INR','USD','EUR','GBP','AED','SGD','CAD','AUD'] as $cur)<option @selected(old('currency', $a->job->salary_currency ?: 'INR') === $cur)>{{ $cur }}</option>@endforeach</select></div>
                    <div><label>Fixed pay</label><input type="number" min="0" name="fixed_pay" value="{{ old('fixed_pay') }}"></div>
                    <div><label>Variable pay</label><input type="number" min="0" name="variable_pay" value="{{ old('variable_pay') }}"></div>
                    <div><label>Joining bonus</label><input type="number" min="0" name="joining_bonus" value="{{ old('joining_bonus') }}"></div>
                    <div><label>Joining date</label><input type="date" name="joining_date" min="{{ today()->toDateString() }}" value="{{ old('joining_date', today()->addDays(30)->toDateString()) }}" required></div>
                    <div><label>Respond by</label><input type="date" name="expires_on" min="{{ today()->toDateString() }}" value="{{ old('expires_on', today()->addDays(7)->toDateString()) }}" required></div>
                    <div><label>Work location</label><input name="work_location" value="{{ old('work_location', $a->job->location) }}"></div>
                    <div><label>Probation (months)</label><input type="number" min="0" max="24" name="probation_months" value="{{ old('probation_months', 6) }}"></div>
                    <div><label>Signed by</label><input name="signatory_name" value="{{ old('signatory_name', auth()->user()->name) }}" required></div>
                    <div><label>Signatory title</label><input name="signatory_title" value="{{ old('signatory_title', auth()->user()->employerProfile?->designation) }}"></div>
                </div>
                <div class="hs-field" style="margin-top:8px"><label>Additional terms</label><textarea name="terms" rows="3" placeholder="Notice period, benefits, documents required…">{{ old('terms') }}</textarea></div>
                <div class="hs-row"><button class="hs-mini" name="action" value="draft">Save draft</button><button class="hs-mini primary" name="action" value="send">Generate &amp; email offer</button></div>
            </form>
        </details>
        @endif
    </section>
    @endif

    <section class="hs-card">
        <h2>Interviews</h2>
        @foreach($a->interviews as $i)
            <details class="item" @if($i->status === 'scheduled' && $i->scheduled_at->isPast()) open @endif>
                <summary><strong>{{ $i->round }}</strong> · {{ $i->scheduled_at->format('d M Y, h:i A') }} · {{ \App\Models\HiringInterview::MODES[$i->mode] ?? $i->mode }} <span class="pill {{ $i->status === 'completed' ? 'ok' : ($i->status === 'scheduled' ? 'wait' : 'bad') }}">{{ ucwords(str_replace('_', ' ', $i->status)) }}</span>@if($i->rating) {{ str_repeat('★', $i->rating) }}@endif</summary>
                <div class="muted" style="margin:6px 0">{{ $i->interviewer_name }}{{ $i->location ? ' · '.$i->location : '' }}</div>
                <form method="post" action="{{ route('employer.hiring.feedback', $i) }}">@csrf @method('PATCH')
                    <div class="hs-grid">
                        <div><label>Status</label><select name="status">@foreach(['scheduled','completed','cancelled','no_show'] as $s)<option value="{{ $s }}" @selected($i->status === $s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>@endforeach</select></div>
                        <div><label>Rating</label><select name="rating"><option value="">—</option>@foreach([5,4,3,2,1] as $s)<option value="{{ $s }}" @selected($i->rating == $s)>{{ $s }}★</option>@endforeach</select></div>
                        <div><label>Recommendation</label><select name="recommendation"><option value="">—</option>@foreach(\App\Models\HiringInterview::RECOMMENDATIONS as $k => $v)<option value="{{ $k }}" @selected($i->recommendation === $k)>{{ $v }}</option>@endforeach</select></div>
                    </div>
                    <div class="hs-field" style="margin-top:8px"><textarea name="feedback" rows="3" placeholder="Feedback">{{ $i->feedback }}</textarea></div>
                    <button class="hs-mini primary">Save feedback</button>
                </form>
            </details>
        @endforeach
        <form method="post" action="{{ route('employer.hiring.schedule', $a) }}" style="margin-top:10px">@csrf
            <div class="hs-grid">
                <div><label>Round</label><input name="round" list="rounds" value="{{ $a->interviews->isEmpty() ? 'HR screening' : 'Technical round '.$a->interviews->count() }}" required><datalist id="rounds"><option value="HR screening"><option value="Technical round 1"><option value="Technical round 2"><option value="Manager round"><option value="Final / culture fit"></datalist></div>
                <div><label>Date &amp; time</label><input type="datetime-local" name="scheduled_at" required min="{{ now()->format('Y-m-d\TH:i') }}"></div>
                <div><label>Duration (min)</label><input type="number" name="duration_minutes" value="45" min="10" max="480" required></div>
                <div><label>Mode</label><select name="mode">@foreach(\App\Models\HiringInterview::MODES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div style="grid-column:span 2"><label>Meeting link / address</label><input name="location" placeholder="https://meet.google.com/… or office address"></div>
                <div><label>Interviewer</label><input name="interviewer_name"></div>
                <div><label>Interviewer email</label><input type="email" name="interviewer_email"></div>
            </div>
            <div class="hs-row" style="margin-top:8px"><label class="hs-row"><input type="checkbox" name="notify" value="1" checked> Email calendar invite to candidate (and interviewer)</label><button class="hs-mini primary">Schedule</button></div>
        </form>
    </section>

    @if($can('verify'))
    <section class="hs-card">
        <h2>Verification</h2>
        @foreach($checks as $c)<div class="item hs-row" style="justify-content:space-between;padding:7px 0"><span>{{ $c['label'] }} <span class="muted" style="font-size:12px">{{ $c['detail'] }}</span></span><span class="pill {{ $c['ok'] ? 'ok' : 'bad' }}">{{ $c['ok'] ? 'Yes' : 'No' }}</span></div>@endforeach
        @if(!$provider)<div class="muted" style="font-size:12px;margin:8px 0">Live PAN/Aadhaar checks need a provider key: set SUREPASS_TOKEN (or SANDBOX_API_KEY and SANDBOX_API_SECRET) in .env. Format checks run without it.</div>@endif
        <form method="post" action="{{ route('employer.hiring.verify.pan', $a) }}" class="item">@csrf
            <strong>PAN check</strong>
            <div class="hs-grid" style="margin-top:6px"><div><label>PAN</label><input name="pan" maxlength="10" style="text-transform:uppercase" required></div><div><label>Name on PAN</label><input name="name" value="{{ $a->candidate?->name }}" required></div></div>
            <label class="hs-row" style="margin:6px 0;font-size:12px"><input type="checkbox" name="consent" value="1" required> Candidate has given consent for this check</label><button class="hs-mini">Verify PAN</button>
        </form>
        <form method="post" action="{{ route('employer.hiring.verify.uan', $a) }}" class="item">@csrf
            <strong>Employment history (UAN / EPFO)</strong>
            <div class="hs-field" style="margin-top:6px"><input name="uan" inputmode="numeric" maxlength="14" placeholder="12-digit UAN" required></div>
            @unless($uanReady)<div class="muted" style="font-size:12px">Needs SUREPASS_TOKEN in .env for live EPFO data.</div>@endunless
            <label class="hs-row" style="margin:6px 0;font-size:12px"><input type="checkbox" name="consent" value="1" required> Candidate has given consent for this check</label><button class="hs-mini">Check employment</button>
        </form>
        <form method="post" action="{{ route('employer.hiring.verify.aadhaar', $a) }}" class="item">@csrf
            <strong>Aadhaar check (OTP to candidate)</strong>
            <div class="hs-field" style="margin-top:6px"><input name="aadhaar" inputmode="numeric" maxlength="14" placeholder="12-digit Aadhaar" required></div>
            <label class="hs-row" style="margin:6px 0;font-size:12px"><input type="checkbox" name="consent" value="1" required> Candidate has given consent and will share the OTP</label><button class="hs-mini">Send OTP</button>
        </form>
        @foreach($verifications as $v)
            <div class="item">
                <div class="hs-row" style="justify-content:space-between"><span><strong>{{ strtoupper($v->type) }}</strong> {{ $v->reference }} <span class="muted" style="font-size:12px">· {{ $v->provider }} · {{ $v->created_at->format('d M Y') }}</span></span><span class="pill {{ $v->status === 'passed' ? 'ok' : ($v->status === 'pending' ? 'wait' : 'bad') }}">{{ ucfirst($v->status) }}</span></div>
                <div class="muted" style="font-size:12px">{{ $v->message }}</div>
                @if($v->type === 'uan' && !empty($v->payload['employers']))<div style="font-size:12px;margin-top:4px">@foreach($v->payload['employers'] as $emp)<div>• {{ $emp['employer'] }} <span class="muted">{{ $emp['joined'] ?? '?' }} → {{ $emp['left'] ?: 'present' }}</span></div>@endforeach</div>@endif
                @if($v->type === 'aadhaar' && $v->status === 'pending' && $v->provider !== 'format' && $v->job_application_id && $v->created_at->gt(now()->subMinutes(10)))
                    <form class="hs-row" method="post" action="{{ route('employer.hiring.verify.otp', $v) }}" style="margin-top:6px">@csrf<input class="hs-in" name="otp" inputmode="numeric" maxlength="6" placeholder="6-digit OTP" required style="width:130px"><button class="hs-mini primary">Confirm OTP</button></form>
                @endif
            </div>
        @endforeach
    </section>
    @endif
</div>
</div>
@endsection

@push('scripts')<script>document.querySelectorAll('.tpl-pick').forEach(sel=>sel.addEventListener('change',()=>{const o=sel.selectedOptions[0];if(!o.value)return;const t=sel.dataset.target;document.getElementById(t+'-subject').value=o.dataset.subject;document.getElementById(t+'-body').value=o.dataset.body;}));</script>@endpush
