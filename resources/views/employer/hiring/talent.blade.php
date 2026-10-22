@extends('layouts.employer')
@section('title','Talent Database')
@include('employer.hiring._style')
@section('content')
@php($stages = \App\Models\JobApplication::STAGES)
<div class="hs-head">
    <div><h1>Talent Database</h1><div class="muted">{{ number_format($results->total()) }} matching of {{ number_format($total) }} applications on record</div></div>
    <div class="hs-row">
        <a class="btn" href="{{ route('employer.hiring.export', request()->query()) }}">⬇ Export CSV</a>
        <a class="btn" href="{{ route('employer.hiring.analytics') }}">Analytics</a>
    </div>
</div>

<div class="chips">
    <a href="{{ route('employer.hiring.talent', array_merge(request()->except(['stage','page']))) }}" class="{{ empty($f['stage']) ? 'on' : '' }}">All stages</a>
    @foreach($stages as $key => $label)
        <a href="{{ route('employer.hiring.talent', array_merge(request()->except(['stage','page']), ['stage' => [$key]])) }}" class="{{ $f['stage'] === [$key] ? 'on' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<form class="hs-card" method="get" action="{{ route('employer.hiring.talent') }}" id="filters">
    <div class="hs-grid">
        <div style="grid-column:span 2"><label>Keyword</label><input name="q" value="{{ $f['q'] ?? '' }}" placeholder="Name, email, phone, designation, company"></div>
        <div><label>Job</label><select name="job_id"><option value="">All jobs</option>@foreach($jobs as $j)<option value="{{ $j->id }}" @selected(($f['job_id'] ?? '') == $j->id)>{{ $j->title }}{{ $j->status !== 'published' ? ' ('.$j->status.')' : '' }}</option>@endforeach</select></div>
        <div><label>Applied</label><select name="period">@foreach(\App\Services\Hiring\TalentSearch::PERIODS as $k => $v)<option value="{{ $k }}" @selected(($f['period'] ?? 'all') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div><label>From date</label><input type="date" name="from" value="{{ $f['from'] ?? '' }}"></div>
        <div><label>To date</label><input type="date" name="to" value="{{ $f['to'] ?? '' }}"></div>
        <div style="grid-column:span 2"><label>Skills (comma separated)</label><input name="skills" value="{{ $f['skills'] ?? '' }}" placeholder="Laravel, MySQL, AWS"></div>
        <div><label>Skill match</label><select name="skill_mode"><option value="any">Any of these</option><option value="all" @selected(($f['skill_mode'] ?? '') === 'all')>All of these</option></select></div>
        <div><label>Experience min (yrs)</label><input type="number" step="0.5" min="0" name="exp_min" value="{{ $f['exp_min'] ?? '' }}"></div>
        <div><label>Experience max (yrs)</label><input type="number" step="0.5" min="0" name="exp_max" value="{{ $f['exp_max'] ?? '' }}"></div>
        <div><label>Expected CTC min</label><input type="number" min="0" name="ctc_min" value="{{ $f['ctc_min'] ?? '' }}"></div>
        <div><label>Expected CTC max</label><input type="number" min="0" name="ctc_max" value="{{ $f['ctc_max'] ?? '' }}"></div>
        <div><label>Current CTC max</label><input type="number" min="0" name="cur_max" value="{{ $f['cur_max'] ?? '' }}"></div>
        <div><label>Notice period</label><select name="notice"><option value="">Any</option>@foreach(\App\Services\Hiring\TalentSearch::NOTICE as $k => $v)<option value="{{ $k }}" @selected(($f['notice'] ?? '') === (string) $k)>{{ $v }}</option>@endforeach</select></div>
        <div><label>Location(s)</label><input name="location" value="{{ $f['location'] ?? '' }}" placeholder="Pune, Mohali"></div>
        <div><label>Current company</label><input name="company" value="{{ $f['company'] ?? '' }}"></div>
        <div><label>Education</label><input name="education" value="{{ $f['education'] ?? '' }}" placeholder="B.Tech, MBA"></div>
        <div><label>Min match score</label><input type="number" min="0" max="100" name="score_min" value="{{ $f['score_min'] ?? '' }}"></div>
        <div><label>Min rating</label><select name="rating_min"><option value="">Any</option>@foreach([5,4,3,2,1] as $s)<option value="{{ $s }}" @selected(($f['rating_min'] ?? '') == $s)>{{ $s }}★ &amp; up</option>@endforeach</select></div>
        <div><label>Tag</label><input name="tag" list="tag-list" value="{{ $f['tag'] ?? '' }}"><datalist id="tag-list">@foreach($tags as $t)<option value="{{ $t }}">@endforeach</datalist></div>
        <div><label>Verified</label><select name="verified"><option value="">Any</option>@foreach(['phone' => 'Phone (OTP)', 'email' => 'Email', 'pan' => 'PAN', 'aadhaar' => 'Aadhaar'] as $k => $v)<option value="{{ $k }}" @selected(($f['verified'] ?? '') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div><label>Source</label><select name="source"><option value="">Any</option><option value="portal" @selected(($f['source'] ?? '') === 'portal')>Applied on portal</option><option value="resume_auto_match" @selected(($f['source'] ?? '') === 'resume_auto_match')>Resume auto-match</option></select></div>
        @foreach($f['stage'] as $st)<input type="hidden" name="stage[]" value="{{ $st }}">@endforeach
        <div><label>Sort by</label><select name="sort">@foreach(\App\Services\Hiring\TalentSearch::SORTS as $k => $v)<option value="{{ $k }}" @selected(($f['sort'] ?? 'newest') === $k)>{{ $v }}</option>@endforeach</select></div>
    </div>
    <div class="hs-row" style="margin-top:12px;justify-content:space-between">
        <label class="hs-row" style="font-weight:600"><input type="checkbox" name="unique" value="1" @checked(!empty($f['unique']))> One row per candidate (latest application)</label>
        <div class="hs-row"><a class="hs-mini" href="{{ route('employer.hiring.talent') }}">Clear</a><button class="hs-mini primary">Search</button></div>
    </div>
</form>

<div class="hs-row" style="margin-bottom:12px;justify-content:space-between">
    <div class="hs-row">
        @foreach($saved as $s)
            <span class="hs-row" style="gap:2px"><a class="hs-mini" href="{{ route('employer.hiring.talent', ['saved' => $s->id]) }}">★ {{ $s->name }}</a>
            <form method="post" action="{{ route('employer.hiring.searches.destroy', $s) }}">@csrf @method('DELETE')<button class="hs-mini" title="Remove saved search">✕</button></form></span>
        @endforeach
    </div>
    <form class="hs-row" method="post" action="{{ route('employer.hiring.searches.store') }}">@csrf
        @foreach(request()->except(['page','_token','name','saved']) as $k => $v)
            @if(is_array($v)) @foreach($v as $vv)<input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">@endforeach @else<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
        @endforeach
        <input class="hs-in" name="name" placeholder="Name this search" required style="width:170px"><button class="hs-mini">Save search</button>
    </form>
</div>

@if(app(\App\Services\Hiring\Team::class)->can(auth()->user(), 'email'))
<details class="hs-card">
    <summary>Email templates ({{ $templates->count() }})</summary>
    @foreach($templates as $t)
        <div class="hs-row" style="justify-content:space-between;border-top:1px solid #e6eefa;padding:8px 0"><span><strong>{{ $t->name }}</strong> <span class="muted">— {{ $t->subject }}</span></span>
        <form method="post" action="{{ route('employer.hiring.templates.destroy', $t) }}">@csrf @method('DELETE')<button class="hs-mini danger">Delete</button></form></div>
    @endforeach
    <form method="post" action="{{ route('employer.hiring.templates.store') }}" style="margin-top:10px">@csrf
        <div class="hs-grid"><div><label>Template name</label><input name="name" required maxlength="80" placeholder="Interview invite"></div><div style="grid-column:span 2"><label>Subject</label><input name="subject" required maxlength="190" placeholder="Interview for {job_title} at {company}"></div></div>
        <div class="hs-field" style="margin-top:8px"><textarea name="body" rows="4" required placeholder="Hi {first_name},&#10;&#10;…&#10;&#10;Regards,&#10;{recruiter_name}"></textarea></div>
        <button class="hs-mini primary">Save template</button>
    </form>
</details>
@endif

<form method="post" action="{{ route('employer.hiring.bulk') }}" id="bulk-form">@csrf
<section class="hs-card hs-scroll" style="padding:6px">
    <table class="hs-table">
        <thead><tr><th><input type="checkbox" id="check-all"></th><th>Candidate</th><th>Job</th><th>Score</th><th>Stage</th><th>Experience</th><th>CTC (cur → exp)</th><th>Notice</th><th>Location</th><th>Applied</th></tr></thead>
        <tbody>
        @forelse($results as $a)
            @php($p = $a->candidate?->candidateProfile)
            <tr>
                <td><input type="checkbox" name="ids[]" value="{{ $a->id }}" class="row-check"></td>
                <td>
                    <a href="{{ route('employer.hiring.candidate', $a) }}"><strong>{{ $a->candidate?->name ?? 'Deleted user' }}</strong></a>
                    @if($p?->phone_confirmed_at)<span title="Phone verified">✔</span>@endif
                    @if($a->rating)<span class="muted">{{ str_repeat('★', $a->rating) }}</span>@endif
                    <div class="muted" style="font-size:12px">{{ $p?->current_designation }}{{ $p?->current_company ? ' · '.$p->current_company : '' }}</div>
                    <div class="muted" style="font-size:12px">{{ $a->candidate?->email }}{{ $p?->phone ? ' · '.$p->phone : '' }}</div>
                    @foreach((array) $a->tags as $t)<span class="tag">{{ $t }}</span>@endforeach
                </td>
                <td><a href="{{ route('employer.hiring.pipeline', $a->job_id) }}">{{ $a->job?->title }}</a></td>
                <td>@if($a->match_score !== null)<span class="score {{ $a->match_score >= 70 ? 'hi' : ($a->match_score >= 45 ? 'mid' : 'lo') }}">{{ (int) $a->match_score }}</span>@else<span class="muted">—</span>@endif</td>
                <td><span class="pill {{ $a->stage }}">{{ $stages[$a->stage] ?? $a->stage }}</span>@if($a->rejection_reason)<div class="muted" style="font-size:11px;max-width:160px">{{ $a->rejection_reason }}</div>@endif</td>
                <td>{{ $p?->experience_years !== null ? (float) $p->experience_years.' yrs' : '—' }}</td>
                <td style="white-space:nowrap">{{ $p?->current_salary ? number_format($p->current_salary) : '—' }} → {{ $p?->expected_salary ? number_format($p->expected_salary) : '—' }}</td>
                <td>{{ $p?->notice_period ?: '—' }}</td>
                <td>{{ $p?->city ?: ($p?->location ?: '—') }}</td>
                <td style="white-space:nowrap">{{ $a->applied_at?->format('d M Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="10" style="text-align:center;padding:40px" class="muted">No applications match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
<div class="bulkbar" id="bulkbar">
    <strong><span id="sel-count">0</span> selected</strong>
    <select name="action" id="bulk-action"><option value="stage">Move to stage</option><option value="tag">Add tag</option><option value="untag">Remove tag</option>@if(app(\App\Services\Hiring\Team::class)->can(auth()->user(), 'email'))<option value="email">Send email</option>@endif</select>
    <select name="stage" id="bulk-stage">@foreach($stages as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
    <input name="reason" id="bulk-reason" placeholder="Rejection reason" style="display:none">
    <input name="tag" id="bulk-tag" placeholder="Tag" list="tag-list" style="display:none">
    <div id="bulk-mail" style="display:none;flex-basis:100%;gap:6px;flex-direction:column">
        @if($templates->isNotEmpty())<select class="tpl-pick" data-target="bulk-mail" style="max-width:260px"><option value="">Use a template…</option>@foreach($templates as $t)<option value="{{ $t->id }}" data-subject="{{ $t->subject }}" data-body="{{ $t->body }}">{{ $t->name }}</option>@endforeach</select>@endif
        <input name="subject" id="bulk-mail-subject" placeholder="Subject, e.g. Next steps for {job_title}" maxlength="190">
        <textarea name="body" id="bulk-mail-body" rows="4" placeholder="Hi {first_name}, …" style="padding:7px;border-radius:7px;border:0;font:inherit"></textarea>
        <small style="opacity:.8">Each candidate gets a personal copy. Placeholders: {{ implode(' ', \App\Services\Hiring\Messenger::PLACEHOLDERS) }}</small>
    </div>
    <button class="hs-mini primary">Apply</button>
</div>
</form>
{{ $results->links() }}
@endsection
@push('scripts')<script>
(function(){const bar=document.getElementById('bulkbar'),cnt=document.getElementById('sel-count'),checks=[...document.querySelectorAll('.row-check')];
const sync=()=>{const n=checks.filter(c=>c.checked).length;cnt.textContent=n;bar.classList.toggle('on',n>0)};
checks.forEach(c=>c.addEventListener('change',sync));document.getElementById('check-all').addEventListener('change',e=>{checks.forEach(c=>c.checked=e.target.checked);sync()});
const act=document.getElementById('bulk-action'),st=document.getElementById('bulk-stage'),rs=document.getElementById('bulk-reason'),tg=document.getElementById('bulk-tag');
const ml=document.getElementById('bulk-mail');
const ui=()=>{st.style.display=act.value==='stage'?'':'none';tg.style.display=['tag','untag'].includes(act.value)?'':'none';ml.style.display=act.value==='email'?'flex':'none';rs.style.display=act.value==='stage'&&st.value==='rejected'?'':'none'};
act.addEventListener('change',ui);st.addEventListener('change',ui);ui();
document.querySelectorAll('.tpl-pick').forEach(sel=>sel.addEventListener('change',()=>{const o=sel.selectedOptions[0];if(!o.value)return;document.getElementById(sel.dataset.target+'-subject').value=o.dataset.subject;document.getElementById(sel.dataset.target+'-body').value=o.dataset.body;}));})();
</script>@endpush
