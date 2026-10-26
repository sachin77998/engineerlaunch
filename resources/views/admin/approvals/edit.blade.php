@extends('admin.approvals.layout')
@section('manager')
<p><a href="{{ route('admin.approvals.services', $state->id) }}">&larr; {{ $state->name }} services</a></p>
<h1>{{ $service->name }}</h1>
<p class="im-note">{{ $service->department }} · {{ $service->external_id }}@if($service->checklist_url) · <a href="{{ $service->checklist_url }}" target="_blank" rel="noopener">Official checklist PDF</a>@endif @if($service->locked) · <b>Edited — automatic updates paused</b>@endif</p>
<form method="post" action="{{ route('admin.approvals.update', $service->id) }}">@csrf @method('PUT')
    <div class="im-panel im-table"><table>
        <thead><tr><th style="width:38%">Document</th><th>Condition / checks</th><th>Group (any-one / NOC list)</th><th>Kind</th><th>Required</th></tr></thead>
        <tbody>
        @foreach($docs->values()->concat(array_fill(0, 5, null)) as $i => $d)
            <tr><td><textarea class="field" rows="2" name="docs[{{ $i }}][name]">{{ $d->name ?? '' }}</textarea></td>
                <td><input class="field" name="docs[{{ $i }}][condition]" value="{{ $d->condition ?? '' }}" maxlength="500"></td>
                <td><input class="field" name="docs[{{ $i }}][group_label]" value="{{ $d->group_label ?? '' }}" maxlength="500"></td>
                <td><select class="field" name="docs[{{ $i }}][kind]">@foreach(['document' => 'Document', 'option' => 'Any-one option', 'noc' => 'Department NOC', 'note' => 'Note'] as $k => $l)<option value="{{ $k }}" @selected(($d->kind ?? 'document') === $k)>{{ $l }}</option>@endforeach</select></td>
                @php($rv = isset($d->is_required) ? ((int) $d->is_required ? '1' : '0') : '')
                <td><select class="field" name="docs[{{ $i }}][is_required]">@foreach(['' => 'Unmarked', '1' => 'Yes', '0' => 'No'] as $v => $l)<option value="{{ $v }}" @selected($rv === (string) $v)>{{ $l }}</option>@endforeach</select></td></tr>
        @endforeach
        </tbody>
    </table></div>
    <p class="im-note">Clear a document's text to remove it. Five blank rows are added for new documents each time you save.</p>
    <div class="im-actions"><label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($service->is_active)> Shown publicly</label><button class="primary">Save checklist</button>
        @if($service->locked)<button name="unlock" value="1" formnovalidate>Unlock (use portal version again)</button>@endif</div>
</form>
@endsection
