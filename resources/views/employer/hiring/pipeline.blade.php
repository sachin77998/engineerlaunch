@extends('layouts.employer')
@section('title','Pipeline · '.$job->title)
@include('employer.hiring._style')
@push('styles')<style>
.board{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(250px,1fr);gap:12px;overflow-x:auto;padding-bottom:10px}
.col{background:#f4f8ff;border:1px solid var(--line);border-radius:12px;padding:10px;min-height:200px}.col h3{margin:2px 4px 10px;font-size:14px;display:flex;justify-content:space-between}
.cand{background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px;margin-bottom:8px}.cand .top{display:flex;justify-content:space-between;gap:6px;align-items:flex-start}
.cand .meta{font-size:12px;color:var(--muted);margin-top:4px}.cand form{margin-top:8px}.cand select{width:100%;padding:6px;border:1px solid var(--line);border-radius:7px;font-size:12px}
</style>@endpush
@section('content')
@php($stages = \App\Models\JobApplication::STAGES)
<div class="hs-head">
    <div><h1>{{ $job->title }}</h1><div class="muted">{{ $apps->flatten()->count() }} applications · sorted by match score</div></div>
    <div class="hs-row">
        <a class="btn" href="{{ route('employer.hiring.talent', ['job_id' => $job->id]) }}">Filter in database</a>
        <a class="btn" href="{{ route('employer.jobs.applicants', $job) }}">List view</a>
        <a class="btn" href="{{ route('employer.jobs.edit', $job) }}">Edit job</a>
    </div>
</div>

<details class="hs-card" @if(!$rule->exists) open @endif>
    <summary>Auto-screening rules {!! $rule->exists && $rule->is_active ? '<span class="pill ok">On</span>' : '<span class="pill wait">Off</span>' !!}</summary>
    <form method="post" action="{{ route('employer.hiring.rules', $job) }}">@csrf
        <div class="hs-grid">
            <div><label>Min experience (yrs)</label><input type="number" step="0.5" min="0" name="min_experience" value="{{ old('min_experience', $rule->min_experience ?? $job->experience_min) }}"></div>
            <div><label>Max experience (yrs)</label><input type="number" step="0.5" min="0" name="max_experience" value="{{ old('max_experience', $rule->max_experience) }}"></div>
            <div><label>Max notice (days)</label><input type="number" min="0" name="max_notice_days" value="{{ old('max_notice_days', $rule->max_notice_days) }}"></div>
            <div><label>Max expected CTC</label><input type="number" min="0" name="max_expected_salary" value="{{ old('max_expected_salary', $rule->max_expected_salary ?? $job->salary_max) }}"></div>
            <div><label>Min skill match %</label><input type="number" min="0" max="100" name="min_skill_match" value="{{ old('min_skill_match', $rule->min_skill_match) }}"></div>
            <div><label>Allowed locations</label><input name="locations" value="{{ old('locations', implode(', ', (array) $rule->locations)) }}" placeholder="Blank = anywhere"></div>
            <div><label>Auto-shortlist at score ≥</label><input type="number" min="1" max="100" name="auto_shortlist_score" value="{{ old('auto_shortlist_score', $rule->auto_shortlist_score) }}" placeholder="e.g. 75"></div>
        </div>
        <div class="hs-row" style="margin-top:12px;gap:18px">
            <label class="hs-row"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active ?? true)> Rules active</label>
            <label class="hs-row"><input type="checkbox" name="auto_reject" value="1" @checked($rule->auto_reject)> Auto-reject applicants who fail a rule</label>
            <label class="hs-row"><input type="checkbox" name="require_verified_phone" value="1" @checked($rule->require_verified_phone)> Require OTP-verified phone</label>
            <label class="hs-row"><input type="checkbox" name="rescreen" value="1" checked> Re-screen applications still in New / Screening</label>
            <button class="hs-mini primary">Save rules</button>
        </div>
        <div class="muted" style="margin-top:8px;font-size:12px">Job skills scored: {{ $job->skills->pluck('name')->implode(', ') ?: 'none added to this job yet' }}</div>
    </form>
</details>

<form method="post" action="{{ route('employer.hiring.bulk') }}">@csrf
<div class="board">
    @foreach($stages as $key => $label)
        @php($list = $apps->get($key, collect()))
        <section class="col">
            <h3><span>{{ $label }}</span><span class="pill {{ $key }}">{{ $list->count() }}</span></h3>
            @foreach($list as $a)
                @php($p = $a->candidate?->candidateProfile)
                <div class="cand">
                    <div class="top">
                        <label class="hs-row" style="gap:6px;align-items:flex-start"><input type="checkbox" name="ids[]" value="{{ $a->id }}" class="row-check">
                            <a href="{{ route('employer.hiring.candidate', $a) }}"><strong>{{ $a->candidate?->name }}</strong></a></label>
                        @if($a->match_score !== null)<span class="score {{ $a->match_score >= 70 ? 'hi' : ($a->match_score >= 45 ? 'mid' : 'lo') }}">{{ (int) $a->match_score }}</span>@endif
                    </div>
                    <div class="meta">{{ $p?->experience_years !== null ? (float) $p->experience_years.' yrs' : 'Exp —' }} · {{ $p?->notice_period ?: 'Notice —' }} · {{ $p?->city ?: ($p?->location ?: '—') }}</div>
                    <div class="meta">Applied {{ $a->applied_at?->diffForHumans() }}{{ $a->rating ? ' · '.str_repeat('★', $a->rating) : '' }}</div>
                    @if(!empty($a->screening_result['failed']))<div class="meta" style="color:#b42318">✕ {{ implode('; ', $a->screening_result['failed']) }}</div>@endif
                    @if(!empty($a->screening_result['missing_skills']))<div class="meta">Missing: {{ implode(', ', array_slice($a->screening_result['missing_skills'], 0, 4)) }}</div>@endif
                    @foreach((array) $a->tags as $t)<span class="tag">{{ $t }}</span>@endforeach
                    <select onchange="quickMove({{ $a->id }}, this.value)" aria-label="Move stage"><option value="">Move to…</option>@foreach($stages as $k => $v)@if($k !== $key)<option value="{{ $k }}">{{ $v }}</option>@endif @endforeach</select>
                </div>
            @endforeach
        </section>
    @endforeach
</div>
<div class="bulkbar" id="bulkbar">
    <strong><span id="sel-count">0</span> selected</strong>
    <input type="hidden" name="action" value="stage">
    <select name="stage" id="bulk-stage">@foreach($stages as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
    <input name="reason" placeholder="Rejection reason (optional)">
    <button class="hs-mini primary">Move</button>
</div>
</form>
<form method="post" id="quick-move" style="display:none">@csrf @method('PATCH')<input type="hidden" name="stage"></form>
@endsection
@push('scripts')<script>
const moveBase=@json(url('employer/candidates'));
function quickMove(id,stage){if(!stage)return;let reason='';if(stage==='rejected'){reason=prompt('Rejection reason','Not a fit');if(reason===null)return;}
const f=document.getElementById('quick-move');f.action=moveBase+'/'+id+'/stage';f.stage.value=stage;
if(reason){const r=document.createElement('input');r.type='hidden';r.name='reason';r.value=reason;f.appendChild(r)}f.submit()}
(function(){const bar=document.getElementById('bulkbar'),cnt=document.getElementById('sel-count'),checks=[...document.querySelectorAll('.row-check')];
checks.forEach(c=>c.addEventListener('change',()=>{const n=checks.filter(c=>c.checked).length;cnt.textContent=n;bar.classList.toggle('on',n>0)}));})();
</script>@endpush
