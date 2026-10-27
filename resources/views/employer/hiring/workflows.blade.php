@extends('layouts.employer')
@section('title','Workflows')
@include('employer.hiring._style')
@section('content')
<div class="hs-head"><div><h1>Workflows</h1><div class="muted">Automate the repetitive steps: when something happens to a candidate, the portal sends the emails, moves the stage, starts document collection and tells your team.</div></div>
    <a class="hs-mini" href="{{ route('employer.hiring.onboarding') }}">Onboarding</a></div>

<div class="hs-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr));margin-bottom:16px">
    @foreach(['month' => 'This month', 'total' => 'Since you started'] as $k => $label)
        <div class="hs-card" style="margin:0"><div class="muted" style="font-size:12px;font-weight:700;text-transform:uppercase">{{ $label }}</div>
            <div style="font-size:26px;font-weight:800">{{ $savings[$k]['hours'] }} h saved</div>
            <div class="muted">≈ ₹{{ number_format($savings[$k]['rupees']) }} of recruiter time · {{ $savings[$k]['runs'] }} automated run{{ $savings[$k]['runs'] === 1 ? '' : 's' }} · {{ $savings[$k]['documents'] }} document{{ $savings[$k]['documents'] === 1 ? '' : 's' }} collected online</div></div>
    @endforeach
</div>
<p class="muted" style="font-size:12px;margin-top:-6px">Each automated step counts the manual minutes it replaces (email {{ \App\Services\Hiring\Workflows::MINUTES['send_email'] }}, stage update {{ \App\Services\Hiring\Workflows::MINUTES['move_stage'] }}, onboarding set-up {{ \App\Services\Hiring\Workflows::MINUTES['start_onboarding'] }}, team update {{ \App\Services\Hiring\Workflows::MINUTES['notify_team'] }}; each joining document collected online {{ \App\Services\Hiring\Workflows::MINUTES_PER_DOCUMENT }} min), priced at ₹{{ number_format($savings['cost_per_recruiter']) }} per recruiter per month (<a href="{{ route('employer.hiring.analytics') }}">change in Hiring Analytics</a>).</p>

<section class="hs-card hs-scroll">
    <h2>Your workflows</h2>
    <table class="hs-table">
        <thead><tr><th>Name</th><th>When</th><th>Then</th><th>Job</th><th>Runs</th><th></th></tr></thead>
        <tbody>
        @forelse($workflows as $w)
            <tr style="{{ $w->is_active ? '' : 'opacity:.6' }}">
                <td><strong>{{ $w->name }}</strong>@unless($w->is_active) <span class="pill withdrawn">Paused</span>@endunless</td>
                <td>{{ $triggers[$w->trigger] ?? $w->trigger }}</td>
                <td>@foreach($w->steps as $i => $s)<div>{{ $i + 1 }}. {{ $actions[$s['action']] ?? $s['action'] }}@if(($s['action'] ?? '') === 'move_stage') — {{ $stages[$s['config']['stage'] ?? ''] ?? '' }}@elseif(($s['action'] ?? '') === 'add_tag') — “{{ $s['config']['tag'] ?? '' }}”@endif</div>@endforeach</td>
                <td>{{ $w->job?->title ?? 'All jobs' }}</td>
                <td>{{ $w->runs_count }}@if($w->last_run_at)<div class="muted" style="font-size:12px">last {{ $w->last_run_at->diffForHumans() }}</div>@endif</td>
                <td style="white-space:nowrap">@if($canEdit)
                    <a class="hs-mini" href="{{ route('employer.hiring.workflows', ['edit' => $w->id]) }}#builder">Edit</a>
                    <form method="post" action="{{ route('employer.hiring.workflows.toggle', $w) }}" style="display:inline">@csrf @method('PATCH')<button class="hs-mini">{{ $w->is_active ? 'Pause' : 'Switch on' }}</button></form>
                    <form method="post" action="{{ route('employer.hiring.workflows.destroy', $w) }}" style="display:inline" onsubmit="return confirm('Delete this workflow?')">@csrf @method('DELETE')<button class="hs-mini danger">Delete</button></form>
                @endif</td>
            </tr>
        @empty
            <tr><td colspan="6" style="padding:30px;text-align:center" class="muted">No workflows yet. Start with a ready-made one below.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

@if($canEdit)
@php($presets = [
    ['name' => 'Offer accepted → start joining documents', 'trigger' => 'offer.accepted', 'steps' => [['action' => 'start_onboarding'], ['action' => 'notify_team', 'message' => '{candidate_name} accepted the offer for {job_title}. Joining documents have been requested.']]],
    ['name' => 'Shortlisted → tell the candidate', 'trigger' => 'stage.shortlisted', 'steps' => [['action' => 'send_email', 'subject' => 'Good news about your application for {job_title}', 'body' => "Hi {first_name},\n\nYou have been shortlisted for {job_title} at {company}. We will be in touch shortly to schedule the next round.\n\nRegards,\n{recruiter_name}"]]],
    ['name' => 'Rejected → polite update', 'trigger' => 'stage.rejected', 'steps' => [['action' => 'send_email', 'subject' => 'Your application for {job_title}', 'body' => "Hi {first_name},\n\nThank you for your interest in {job_title} at {company}. We have decided to move ahead with other candidates this time, and we will keep your profile for future openings.\n\nRegards,\n{recruiter_name}"]]],
    ['name' => 'New application → acknowledge', 'trigger' => 'application.new', 'steps' => [['action' => 'send_email', 'subject' => 'We received your application for {job_title}', 'body' => "Hi {first_name},\n\nThank you for applying for {job_title} at {company}. Our team will review your profile and get back to you.\n\nRegards,\n{recruiter_name}"]]],
])
@php($current = $edit ? ['name' => $edit->name, 'trigger' => $edit->trigger, 'job_id' => $edit->job_id, 'steps' => collect($edit->steps)->map(fn ($s) => ['action' => $s['action']] + ($s['action'] === 'start_onboarding' ? ['onboarding_template_id' => $s['config']['template_id'] ?? null] : ($s['config'] ?? [])))->all()] : null)
<section class="hs-card" id="builder">
    <h2>{{ $edit ? 'Edit “' . $edit->name . '”' : 'New workflow' }}</h2>
    @unless($edit)<div class="hs-row" style="margin-bottom:12px"><span class="muted">Ready-made:</span>@foreach($presets as $i => $p)<button type="button" class="hs-mini" data-preset="{{ $i }}">{{ $p['name'] }}</button>@endforeach</div>@endunless
    <form method="post" action="{{ $edit ? route('employer.hiring.workflows.update', $edit) : route('employer.hiring.workflows.store') }}" id="wf-form">@csrf @if($edit) @method('PUT') @endif
        <div class="hs-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr))">
            <div><label>Name</label><input name="name" required maxlength="120" value="{{ old('name', $current['name'] ?? '') }}"></div>
            <div><label>When</label><select name="trigger" required>@foreach($triggers as $k => $v)<option value="{{ $k }}" @selected(old('trigger', $current['trigger'] ?? '') === $k)>{{ $v }}</option>@endforeach</select></div>
            <div><label>For</label><select name="job_id"><option value="">All jobs</option>@foreach($jobs as $j)<option value="{{ $j->id }}" @selected((string) old('job_id', $current['job_id'] ?? '') === (string) $j->id)>{{ $j->title }}</option>@endforeach</select></div>
        </div>
        <h3 style="font-size:15px;margin:16px 0 8px">Then do</h3>
        <div id="wf-steps"></div>
        <div class="hs-row" style="margin-top:10px"><button type="button" class="hs-mini" id="wf-add">＋ Add step</button>
            <span class="muted" style="font-size:12px">Placeholders: {{ implode(' ', \App\Services\Hiring\Messenger::PLACEHOLDERS) }}</span>
            <span style="margin-left:auto" class="hs-row">@if($edit)<a class="hs-mini" href="{{ route('employer.hiring.workflows') }}">Cancel</a>@endif<button class="hs-mini primary">{{ $edit ? 'Save changes' : 'Save workflow' }}</button></span></div>
    </form>
</section>
<template id="wf-step-tpl">
    <div class="hs-card wf-step" style="padding:12px;margin-bottom:10px;background:#fff">
        <div class="hs-row" style="justify-content:space-between"><strong class="wf-n"></strong><button type="button" class="hs-mini danger wf-del">Remove</button></div>
        <div class="hs-grid" style="grid-template-columns:repeat(auto-fill,minmax(230px,1fr));margin-top:8px">
            <div><label>Action</label><select data-f="action">@foreach($actions as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div data-for="send_email"><label>Email template</label><select data-f="template_id"><option value="">Write below</option>@foreach($templates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
            <div data-for="move_stage"><label>Stage</label><select data-f="stage"><option value="">Choose…</option>@foreach($stages as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div data-for="start_onboarding"><label>Checklist</label><select data-f="onboarding_template_id"><option value="">Standard joining checklist</option>@foreach($onboardingTemplates as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
            <div data-for="add_tag"><label>Tag</label><input data-f="tag" maxlength="40" placeholder="e.g. Fast-track"></div>
        </div>
        <div data-for="send_email" class="hs-field" style="margin-top:8px"><label>Subject</label><input data-f="subject" maxlength="190"><label style="margin-top:8px">Message</label><textarea data-f="body" rows="5" maxlength="5000"></textarea></div>
        <div data-for="notify_team" class="hs-field" style="margin-top:8px"><label>Message to the team</label><input data-f="message" maxlength="1000" placeholder="{candidate_name} — {job_title}: now {stage}."></div>
    </div>
</template>
@push('scripts')<script>
(function () {
    var presets = @json($presets), initial = @json(old('steps', $current['steps'] ?? [['action' => 'send_email']]));
    var box = document.getElementById('wf-steps'), tpl = document.getElementById('wf-step-tpl');
    function renumber() {
        box.querySelectorAll('.wf-step').forEach(function (el, i) {
            el.querySelector('.wf-n').textContent = 'Step ' + (i + 1);
            el.querySelectorAll('[data-f]').forEach(function (f) { f.name = 'steps[' + i + '][' + f.dataset.f + ']'; });
        });
    }
    function sync(el) {
        var action = el.querySelector('[data-f=action]').value;
        el.querySelectorAll('[data-for]').forEach(function (p) { var on = p.dataset.for === action; p.style.display = on ? '' : 'none'; p.querySelectorAll('[data-f]').forEach(function (f) { f.disabled = !on; }); });
    }
    function add(step) {
        var el = tpl.content.firstElementChild.cloneNode(true);
        Object.keys(step || {}).forEach(function (k) { var f = el.querySelector('[data-f="' + k + '"]'); if (f && step[k] !== null) f.value = step[k]; });
        el.querySelector('[data-f=action]').addEventListener('change', function () { sync(el); });
        el.querySelector('.wf-del').addEventListener('click', function () { if (box.children.length > 1) { el.remove(); renumber(); } });
        box.appendChild(el); sync(el); renumber();
    }
    function load(steps) { box.innerHTML = ''; (steps.length ? steps : [{action: 'send_email'}]).forEach(add); }
    document.getElementById('wf-add').addEventListener('click', function () { if (box.children.length < 8) add({action: 'send_email'}); });
    document.querySelectorAll('[data-preset]').forEach(function (b) { b.addEventListener('click', function () {
        var p = presets[b.dataset.preset], f = document.getElementById('wf-form');
        f.elements['name'].value = p.name; f.elements['trigger'].value = p.trigger; load(p.steps);
    }); });
    load(initial);
})();
</script>@endpush
@endif

<section class="hs-card hs-scroll">
    <h2>Recent automated runs</h2>
    <table class="hs-table">
        <thead><tr><th>When</th><th>Workflow</th><th>Candidate</th><th>What happened</th><th>Saved</th></tr></thead>
        <tbody>
        @forelse($runs as $run)
            <tr>
                <td class="muted" style="white-space:nowrap">{{ $run->created_at?->diffForHumans() }}</td>
                <td>{{ $run->workflow?->name }}</td>
                <td>@if($run->application)<a href="{{ route('employer.hiring.candidate', $run->application) }}">{{ $run->application->candidate?->name }}</a><div class="muted" style="font-size:12px">{{ $run->application->job?->title }}</div>@endif</td>
                <td>@foreach($run->log as $l)<div style="color:{{ $l['ok'] ? 'inherit' : '#b42318' }}">{{ $l['ok'] ? '✓' : '✗' }} {{ $l['note'] }}</div>@endforeach</td>
                <td>{{ $run->minutes_saved }} min</td>
            </tr>
        @empty
            <tr><td colspan="5" style="padding:30px;text-align:center" class="muted">Runs appear here as candidates move through your pipeline.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
