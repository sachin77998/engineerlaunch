@extends('layouts.site')
@section('title', 'Audit log — Back office')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'Audit log', 'subtitle' => 'Every order, KYC and back-office action with user, IP and time'])
<main class="dt-wrap dt-main">
    <section class="dt-card">
        <form method="get" class="dt-filters"><input name="action" value="{{ request('action') }}" placeholder="Action starts with (order., kyc., admin.)"><input name="client" value="{{ request('client') }}" placeholder="Client id" style="width:100px"><button class="dt-btn" type="submit">Filter</button></form>
        <div class="dt-table-wrap" style="margin-top:12px"><table class="dt-table">
            <thead><tr><th>When (IST)</th><th>Action</th><th>User</th><th>Client</th><th>Subject</th><th>IP</th><th>Details</th></tr></thead>
            <tbody>
            @forelse($logs as $l)
                <tr><td>{{ $l->created_at?->timezone('Asia/Kolkata')->format('d M Y H:i:s') }}</td><td><b>{{ $l->action }}</b></td><td>{{ $l->user_id }}</td><td>{{ $l->trading_client_id }}</td>
                    <td>{{ $l->subject_type }} {{ $l->subject_id }}</td><td>{{ $l->ip }}</td><td style="max-width:360px"><small>{{ $l->meta ? \Illuminate\Support\Str::limit(json_encode($l->meta, JSON_UNESCAPED_UNICODE), 200) : '' }}</small></td></tr>
            @empty
                <tr><td colspan="7">No entries.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div style="margin-top:12px">{{ $logs->links() }}</div>
    </section>
</main>
@endsection
