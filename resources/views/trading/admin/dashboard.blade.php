@extends('layouts.site')
@section('title', 'Back office — Do Trading')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'Back office', 'subtitle' => number_format($stats['schemes']) . ' active schemes · NAV ' . ($stats['nav_date'] ? \Carbon\Carbon::parse($stats['nav_date'])->format('d M Y') : '—')])
<main class="dt-wrap dt-main">
    <section class="dt-card">
        <div class="dt-filters" style="margin-bottom:12px">
            <a class="dt-btn light sm" href="{{ route('trading.admin.orders') }}">Orders</a>
            <a class="dt-btn light sm" href="{{ route('trading.admin.settings') }}">Fees, cut-offs and holidays</a>
            <a class="dt-btn light sm" href="{{ route('trading.admin.audit') }}">Audit log</a>
            <form method="post" action="{{ route('trading.admin.sync') }}">@csrf<button class="dt-btn light sm" type="submit">Refresh NAVs, holidays and margins</button></form>
        </div>
        <div class="dt-stats">
            @foreach(['draft' => 'Drafts', 'submitted' => 'Awaiting verification', 'verified' => 'Verified', 'rejected' => 'Rejected'] as $k => $l)
                <div class="dt-stat"><span>{{ $l }}</span><b>{{ number_format($stats['clients'][$k] ?? 0) }}</b></div>
            @endforeach
            <div class="dt-stat"><span>Orders today</span><b>{{ number_format($stats['orders_today']->sum('c')) }}</b><small>₹{{ number_format($stats['orders_today']->sum('amt'), 2) }}</small></div>
            <div class="dt-stat"><span>Allotted today</span><b>{{ number_format($stats['orders_today']['allotted']->c ?? 0) }}</b></div>
            <div class="dt-stat"><span>Minor → major pending</span><b>{{ $stats['pending_minor_major'] }}</b></div>
        </div>
    </section>
    <section class="dt-card">
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;align-items:center">
            <h2 style="margin:0">Clients</h2>
            <form method="get" class="dt-filters"><select name="status" onchange="this.form.submit()">@foreach(['submitted' => 'Awaiting verification', 'draft' => 'Drafts', 'verified' => 'Verified', 'rejected' => 'Rejected', 'suspended' => 'Suspended', 'all' => 'All'] as $k => $l)<option value="{{ $k }}" @selected(request('status', 'submitted') === $k)>{{ $l }}</option>@endforeach</select></form>
        </div>
        <div class="dt-table-wrap" style="margin-top:12px"><table class="dt-table">
            <thead><tr><th>Client</th><th>Name</th><th>Email</th><th>Status</th><th>Step</th><th>Updated</th></tr></thead>
            <tbody>
            @forelse($clients as $c)
                <tr><td><a href="{{ route('trading.admin.client', $c) }}"><b>{{ $c->client_code }}</b></a></td><td>{{ $c->full_name }}@if($c->is_minor) <span class="dt-pill">minor</span>@endif</td><td>{{ $c->email }}</td>
                    <td><span class="dt-pill {{ $c->status }}">{{ $c->status }}</span></td><td>{{ $c->step }}/10</td><td>{{ $c->updated_at->timezone('Asia/Kolkata')->format('d M, H:i') }}</td></tr>
            @empty
                <tr><td colspan="6">No clients in this list.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div style="margin-top:12px">{{ $clients->links() }}</div>
    </section>
</main>
@endsection
