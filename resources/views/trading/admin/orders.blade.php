@extends('layouts.site')
@section('title', 'Orders — Back office')
@push('styles')@include('trading.partials.styles')@endpush
@section('content')
@include('trading.partials.hero', ['title' => 'Orders', 'subtitle' => number_format($orders->total()) . ' orders'])
<main class="dt-wrap dt-main">
    <section class="dt-card">
        <form method="get" class="dt-filters">
            <input name="q" value="{{ request('q') }}" placeholder="Order no or client code">
            <label class="dt-muted">From <input type="date" name="from" value="{{ request('from', now('Asia/Kolkata')->toDateString()) }}"></label>
            <label class="dt-muted">To <input type="date" name="to" value="{{ request('to', now('Asia/Kolkata')->toDateString()) }}"></label>
            <select name="status"><option value="">All statuses</option>@foreach($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_', ' ', $s) }}</option>@endforeach</select>
            <button class="dt-btn" type="submit">Filter</button>
            <button class="dt-btn light" type="submit" formaction="{{ route('trading.admin.orders.export', 'xlsx') }}">Export Excel</button>
            <button class="dt-btn light" type="submit" formaction="{{ route('trading.admin.orders.export', 'pdf') }}">Export PDF</button>
        </form>
    </section>
    <section class="dt-card">
        <div class="dt-table-wrap"><table class="dt-table">
            <thead><tr><th>Order</th><th>Client</th><th>Fund</th><th>Side</th><th class="num">Amount</th><th class="num">Fees</th><th>NAV date</th><th>Status</th><th>Update</th></tr></thead>
            <tbody>
            @forelse($orders as $o)
                <tr><td><b>{{ $o->order_no }}</b><br><small class="dt-muted">{{ $o->created_at->timezone('Asia/Kolkata')->format('d M H:i') }} · {{ $o->channel }}</small></td>
                    <td>{{ $o->client?->client_code }}<br><small class="dt-muted">{{ $o->client?->full_name }}</small></td>
                    <td style="min-width:200px">{{ $o->scheme->name ?? $o->scheme_code }}<br><small class="dt-muted">{{ $o->scheme?->plan }} · {{ $o->scheme?->option }}</small></td>
                    <td>{{ strtoupper($o->side) }}</td><td class="num">₹{{ number_format($o->amount, 2) }}@if($o->allotted_units)<br><small>{{ number_format($o->allotted_units, 4) }} u @ {{ number_format($o->nav, 4) }}</small>@endif</td>
                    <td class="num">₹{{ number_format($o->total_fees, 2) }}</td><td>{{ $o->applicable_nav_date?->format('d M Y') }}</td>
                    <td><span class="dt-pill {{ $o->status }}">{{ str_replace('_', ' ', $o->status) }}</span>@if($o->status_reason)<br><small class="dt-muted">{{ $o->status_reason }}</small>@endif</td>
                    <td>@if(\App\Services\Trading\OrderService::TRANSITIONS[$o->status])
                        <form method="post" action="{{ route('trading.admin.order', $o) }}" class="dt-filters" style="flex-wrap:nowrap">@csrf
                            <select name="status">@foreach(\App\Services\Trading\OrderService::TRANSITIONS[$o->status] as $to)<option value="{{ $to }}">{{ $to }}</option>@endforeach</select>
                            <input name="nav" placeholder="NAV" style="width:80px"><input name="note" placeholder="Note" style="width:110px"><button class="dt-btn sm" type="submit">Apply</button>
                        </form>@endif</td></tr>
            @empty
                <tr><td colspan="9">No orders.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <div style="margin-top:12px">{{ $orders->links() }}</div>
    </section>
</main>
@endsection
