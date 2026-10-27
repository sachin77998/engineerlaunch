@extends('layouts.employer')
@section('title','Onboarding')
@include('employer.hiring._style')
@section('content')
<div class="hs-head"><div><h1>Onboarding</h1><div class="muted">Joining documents collected online, reviewed in one place. Start onboarding from a candidate's page, or let a workflow start it when an offer is accepted.</div></div>
    <a class="hs-mini primary" href="{{ route('employer.hiring.workflows') }}">Automate with workflows</a></div>
<div class="chips">
    @foreach(['in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $k => $v)
        <a href="{{ route('employer.hiring.onboarding', ['status' => $k]) }}" class="{{ $status === $k ? 'on' : '' }}">{{ $v }} ({{ $counts[$k] ?? 0 }})</a>
    @endforeach
</div>
<section class="hs-card hs-scroll" style="padding:6px">
    <table class="hs-table">
        <thead><tr><th>New hire</th><th>Job</th><th>Progress</th><th>To review</th><th>Joining by</th><th>Started</th></tr></thead>
        <tbody>
        @forelse($list as $o)
            @php($toReview = $o->items->where('status', 'submitted')->count())
            <tr>
                <td><a href="{{ route('employer.hiring.onboarding.show', $o) }}"><strong>{{ $o->application?->candidate?->name ?? 'Candidate' }}</strong></a><div class="muted" style="font-size:12px">{{ $o->application?->candidate?->email }}</div></td>
                <td>{{ $o->application?->job?->title }}</td>
                <td style="min-width:140px"><div style="height:8px;border-radius:8px;background:#e6eefa;overflow:hidden"><div style="height:100%;width:{{ $o->progress }}%;background:{{ $o->progress === 100 ? '#0b7a43' : '#2563eb' }}"></div></div><div class="muted" style="font-size:12px">{{ $o->progress }}%</div></td>
                <td>@if($toReview)<span class="pill wait">{{ $toReview }} document{{ $toReview > 1 ? 's' : '' }}</span>@else<span class="muted">—</span>@endif</td>
                <td>{{ $o->due_on?->format('d M Y') ?? '—' }}</td>
                <td class="muted">{{ $o->created_at->format('d M') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;padding:40px" class="muted">No onboarding here yet. Open a candidate who accepted an offer and choose “Start onboarding”.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
{{ $list->links() }}

<section class="hs-card">
    <h2>Checklist templates</h2>
    <p class="muted" style="margin-top:-6px">The standard checklist asks for PAN, masked Aadhaar, photo, qualification, relieving letter, salary slips, bank proof and the signed offer, plus three team tasks. Save your own for other roles.</p>
    @foreach($templates as $t)
        <div class="hs-row" style="justify-content:space-between;border-bottom:1px solid #e6eefa;padding:8px 0">
            <div><strong>{{ $t->name }}</strong> <span class="muted">· {{ count($t->items) }} items</span></div>
            @if($canEdit)<form method="post" action="{{ route('employer.hiring.onboarding.templates.destroy', $t) }}" onsubmit="return confirm('Delete this template?')">@csrf @method('DELETE')<button class="hs-mini danger">Delete</button></form>@endif
        </div>
    @endforeach
    @if($canEdit)
    <details style="margin-top:12px"><summary class="hs-mini" style="display:inline-flex">＋ New template</summary>
        <form method="post" action="{{ route('employer.hiring.onboarding.templates.store') }}" style="margin-top:12px">@csrf
            <div class="hs-field" style="max-width:360px"><label>Template name</label><input name="name" required maxlength="120" placeholder="e.g. Plant operator joining"></div>
            <table class="hs-table" style="margin-top:10px"><thead><tr><th>Type</th><th>Item</th><th>Help for the candidate</th><th>Required</th></tr></thead>
                <tbody id="tpl-rows">
                @foreach($defaultItems as $i => $item)
                    <tr><td><select class="hs-in" name="items[{{ $i }}][kind]"><option value="document" @selected($item['kind'] === 'document')>Document</option><option value="task" @selected($item['kind'] === 'task')>Team task</option></select></td>
                        <td><input class="hs-in" name="items[{{ $i }}][label]" value="{{ $item['label'] }}" maxlength="150"></td>
                        <td><input class="hs-in" name="items[{{ $i }}][help]" value="{{ $item['help'] }}" maxlength="300"></td>
                        <td><input type="hidden" name="items[{{ $i }}][required]" value="0"><input type="checkbox" name="items[{{ $i }}][required]" value="1" @checked($item['required'])></td></tr>
                @endforeach
                </tbody></table>
            <div class="hs-row" style="margin-top:10px"><button type="button" class="hs-mini" id="tpl-add">＋ Add row</button><span class="muted" style="font-size:12px">Clear an item's name to leave it out.</span><button class="hs-mini primary" style="margin-left:auto">Save template</button></div>
        </form>
    </details>
    @endif
</section>
@push('scripts')<script>
document.getElementById('tpl-add')?.addEventListener('click', function () {
    var body = document.getElementById('tpl-rows'), i = body.children.length;
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><select class="hs-in" name="items[' + i + '][kind]"><option value="document">Document</option><option value="task">Team task</option></select></td>'
        + '<td><input class="hs-in" name="items[' + i + '][label]" maxlength="150"></td><td><input class="hs-in" name="items[' + i + '][help]" maxlength="300"></td>'
        + '<td><input type="hidden" name="items[' + i + '][required]" value="0"><input type="checkbox" name="items[' + i + '][required]" value="1" checked></td>';
    body.appendChild(tr);
});
// Rows left without a name are not sent.
document.querySelector('form[action$="onboarding-templates"]')?.addEventListener('submit', function () {
    document.querySelectorAll('#tpl-rows tr').forEach(function (tr) { var l = tr.querySelector('input[name$="[label]"]'); if (l && !l.value.trim()) tr.querySelectorAll('input,select').forEach(function (el) { el.disabled = true; }); });
});
</script>@endpush
@endsection
