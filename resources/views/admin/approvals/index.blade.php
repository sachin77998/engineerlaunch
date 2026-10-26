@extends('admin.approvals.layout')
@section('manager')
<h1>State approvals</h1>
<datalist id="sa-drivers">@foreach($drivers as $d)<option value="{{ $d }}">@endforeach<option value="nsws,policy_pages"></datalist>
@foreach($rows as $r)
    <details class="im-panel" @if($r->services_count) open @endif>
        <summary><b>{{ $r->name }}</b> — {{ $r->portal_name ?: 'No portal set' }} · {{ number_format((int) $r->services_count) }} services @if($r->last_fetched_at)<span class="im-note">· {{ \Carbon\Carbon::parse($r->last_fetched_at)->format('d M Y H:i') }}: {{ $r->last_status }}</span>@endif</summary>
        <form method="post" action="{{ route('admin.approvals.source', $r->state_id) }}" style="margin-top:12px">@csrf @method('PUT')
            <div class="im-grid">
                <div><label>Portal name</label><input class="field" name="portal_name" value="{{ $r->portal_name }}" maxlength="255"></div>
                <div><label>Portal URL</label><input class="field" name="portal_url" type="url" value="{{ $r->portal_url }}" maxlength="500"></div>
                <div><label>Readers (comma-separated)</label><input class="field" name="driver" value="{{ $r->driver }}" maxlength="40" list="sa-drivers" placeholder="empty = link only"></div>
                <div><label>Service list URL</label><input class="field" name="services_url" type="url" value="{{ $r->services_url }}" maxlength="500"></div>
                <div><label>Policies URL</label><input class="field" name="policies_url" type="url" value="{{ $r->policies_url }}" maxlength="500"></div>
                <div><label>Acts / rules URL</label><input class="field" name="acts_url" type="url" value="{{ $r->acts_url }}" maxlength="500"></div>
            </div>
            <div class="im-actions"><label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($r->is_active ?? true)> Active</label><button class="primary">Save</button>
                @if($r->services_count)<a href="{{ route('admin.approvals.services', $r->state_id) }}">Services &amp; checklists</a> · <a href="{{ route('approvals.state', $r->slug) }}">Public page</a>@endif</div>
        </form>
        @if($r->driver)<form method="post" action="{{ route('admin.approvals.fetch', $r->state_id) }}">@csrf<button>Fetch now</button></form>@endif
    </details>
@endforeach
@endsection
