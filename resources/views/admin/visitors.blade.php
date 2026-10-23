@extends('layouts.site')
@section('title', 'Visitor log')
@push('styles')<style>
.vl{width:min(1240px,calc(100% - 32px));margin:24px auto 60px}.vl h1{margin:0 0 4px}.vl .sub{color:var(--muted);margin:0 0 16px}
.vl-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px;margin-bottom:16px;box-shadow:0 8px 24px rgba(16,33,62,.05)}.vl-card h2{margin:0 0 10px;font-size:17px}
.vl-form{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;align-items:end}.vl-form label{font-size:12px;font-weight:700;color:var(--muted);display:block;margin-bottom:4px}
.vl-form input,.vl-form select{width:100%;padding:9px;border:1px solid #d5deeb;border-radius:9px;font:inherit}
.vl-btn{padding:10px 16px;border-radius:9px;border:0;background:#2563eb;color:#fff;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;text-align:center}.vl-btn.ghost{background:#fff;color:#2563eb;border:1px solid #bfd3f5}
.vl-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px}.vl-kpi{background:#f6f9ff;border:1px solid #e3ebf8;border-radius:14px;padding:14px}.vl-kpi span{font-size:12px;color:var(--muted);font-weight:700}.vl-kpi b{display:block;font-size:24px;margin-top:4px}
.vl-t{width:100%;border-collapse:collapse;font-size:13.5px}.vl-t th{text-align:left;font-size:11.5px;text-transform:uppercase;color:var(--muted);padding:8px;border-bottom:1px solid var(--line);white-space:nowrap}.vl-t td{padding:9px 8px;border-bottom:1px solid #eef2f7;vertical-align:top}
.vl-scroll{overflow-x:auto}.vl-pill{display:inline-block;padding:2px 8px;border-radius:999px;background:#eef4ff;color:#1d4ed8;font-size:12px;font-weight:700}.vl-pill.g{background:#f2f4f7;color:#475467}
.vl-two{display:grid;grid-template-columns:2fr 1fr;gap:16px}@media(max-width:900px){.vl-two{grid-template-columns:1fr}}
</style>@endpush
@section('content')
<div class="vl">
    <h1>Visitor log</h1>
    <p class="sub">Who visited which pages. Signed-in members are shown by name; guests cannot be identified, so they appear with their approximate state and device. New visits are recorded automatically.</p>

    <form class="vl-card vl-form" method="get">
        <div><label>From</label><input type="date" name="from" value="{{ $f['from'] }}"></div>
        <div><label>To</label><input type="date" name="to" value="{{ $f['to'] }}"></div>
        <div><label>Visitors</label><select name="who"><option value="all">Everyone</option><option value="members" @selected($f['who'] === 'members')>Signed-in members</option><option value="guests" @selected($f['who'] === 'guests')>Guests</option></select></div>
        <div><label>Member name / email</label><input name="q" value="{{ $f['q'] }}" placeholder="e.g. rahul"></div>
        <div><label>Page contains</label><input name="page" value="{{ $f['page'] }}" placeholder="e.g. gna-axles"></div>
        <div style="display:flex;gap:8px"><button class="vl-btn">Apply</button><a class="vl-btn ghost" href="{{ route('admin.visitors.export', request()->query()) }}">⬇ Excel</a></div>
    </form>

    <div class="vl-kpis">
        <div class="vl-kpi"><span>Page views</span><b>{{ number_format($totals->views ?? 0) }}</b></div>
        <div class="vl-kpi"><span>Signed-in members</span><b>{{ number_format($totals->members ?? 0) }}</b></div>
        <div class="vl-kpi"><span>Guest visitors</span><b>{{ number_format($totals->guests ?? 0) }}</b></div>
        <div class="vl-kpi"><span>Top guest state</span><b style="font-size:18px">{{ $states->keys()->first() ?? '—' }}</b></div>
    </div>

    <div class="vl-two">
        <section class="vl-card vl-scroll"><h2>Members who visited</h2>
            <table class="vl-t"><thead><tr><th>Member</th><th>Role</th><th>Views</th><th>Pages</th><th>Most viewed</th><th>First seen</th><th>Last seen</th></tr></thead><tbody>
            @forelse($members as $m)
                <tr><td><a href="{{ route('admin.visitors', array_merge(request()->query(), ['q' => $m->email, 'who' => 'members'])) }}"><b>{{ $m->name }}</b></a><div style="font-size:12px;color:var(--muted)">{{ $m->email }}</div></td>
                    <td><span class="vl-pill">{{ $m->role ?: 'member' }}</span></td><td>{{ $m->views }}</td><td>{{ $m->pages }}</td>
                    <td style="font-size:12.5px">{!! collect($topPagesByUser[$m->user_id] ?? [])->map(fn ($p) => e($p))->implode('<br>') !!}</td>
                    <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($m->first_seen)->format('d M, h:i A') }}</td><td style="white-space:nowrap">{{ \Carbon\Carbon::parse($m->last_seen)->format('d M, h:i A') }}</td></tr>
            @empty<tr><td colspan="7" style="color:var(--muted);text-align:center;padding:20px">No signed-in visits in this period.</td></tr>@endforelse
            </tbody></table></section>
        <section class="vl-card"><h2>Guests by state</h2>
            <table class="vl-t"><tbody>@forelse($states as $state => $n)<tr><td>{{ $state }}</td><td style="text-align:right"><b>{{ number_format($n) }}</b> views</td></tr>@empty<tr><td style="color:var(--muted)">Location appears for visits recorded from now on.</td></tr>@endforelse</tbody></table></section>
    </div>

    <section class="vl-card vl-scroll"><h2>Company pages viewed</h2>
        <table class="vl-t"><thead><tr><th>Company page</th><th>Views</th><th>Members</th><th>Guests</th><th>Members who viewed</th></tr></thead><tbody>
        @forelse($companies as $c)<tr><td><a href="{{ url($c->path) }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::after($c->path, '/company-insights/') }}</a></td><td>{{ $c->views }}</td><td>{{ $c->members }}</td><td>{{ $c->guests }}</td><td style="font-size:12.5px">{{ $c->member_names ?: '—' }}</td></tr>
        @empty<tr><td colspan="5" style="color:var(--muted);text-align:center;padding:20px">No company pages viewed in this period.</td></tr>@endforelse
        </tbody></table></section>

    <section class="vl-card vl-scroll"><h2>Recent visits</h2>
        <table class="vl-t"><thead><tr><th>Time</th><th>Visitor</th><th>Page</th><th>Device</th></tr></thead><tbody>
        @forelse($recent as $v)<tr><td style="white-space:nowrap">{{ \Carbon\Carbon::parse($v->created_at)->format('d M, h:i:s A') }}</td>
            <td>@if($v->user_id)<b>{{ $v->name }}</b><div style="font-size:12px;color:var(--muted)">{{ $v->email }}</div>@else<span class="vl-pill g">Guest</span> <span style="font-size:12.5px;color:var(--muted)">{{ collect([$v->state, $v->country_code])->filter()->implode(', ') }}</span>@endif</td>
            <td style="word-break:break-all"><a href="{{ url($v->path) }}" target="_blank" rel="noopener">{{ $v->path }}</a></td><td style="font-size:12.5px;white-space:nowrap">{{ $device($v->user_agent) }}</td></tr>
        @empty<tr><td colspan="4" style="color:var(--muted);text-align:center;padding:20px">No visits in this period.</td></tr>@endforelse
        </tbody></table></section>
</div>
@endsection
