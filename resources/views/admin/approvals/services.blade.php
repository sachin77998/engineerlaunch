@extends('admin.approvals.layout')
@section('manager')
<h1>{{ $state->name }} services</h1>
<form class="im-actions" method="get"><input class="field" name="q" value="{{ $q }}" placeholder="Search name, department or ID" maxlength="120"><button class="primary">Search</button></form>
<p class="im-note">{{ number_format($services->total()) }} services</p>
<div class="im-panel im-table"><table>
    <thead><tr><th>ID</th><th>Service</th><th>Department</th><th>Status</th><th></th></tr></thead>
    <tbody>@foreach($services as $s)<tr>
        <td>{{ $s->external_id }}</td><td>{{ $s->name }}</td><td>{{ $s->department }}</td>
        <td>{{ $s->is_active ? 'Active' : 'Hidden' }}@if($s->locked)<br><b>Edited (locked)</b>@endif</td>
        <td><a href="{{ route('admin.approvals.edit', $s->id) }}">Edit</a> · <a href="{{ route('approvals.service', [$state->slug, $s->external_id]) }}">View</a></td></tr>@endforeach</tbody>
</table></div>
@if($services->hasPages())<div class="im-pages"><span>@if($services->previousPageUrl())<a href="{{ $services->previousPageUrl() }}">&larr; Previous</a>@endif</span><span>Page {{ $services->currentPage() }} of {{ $services->lastPage() }}</span><span>@if($services->nextPageUrl())<a href="{{ $services->nextPageUrl() }}">Next &rarr;</a>@endif</span></div>@endif
@endsection
