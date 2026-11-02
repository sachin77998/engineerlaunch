@extends('layouts.site')
@section('title', 'My orders — Home Building store')
@section('content')
@include('materials._nav')
<div class="container mt-wrap">
    <h1 class="mt-h1" style="margin:0">My orders</h1>
    @forelse($orders as $o)
        <a class="mt-card" href="{{ route('materials.orders.show', $o) }}" style="display:grid;grid-template-columns:auto 1fr auto;gap:18px;align-items:center;text-decoration:none;color:inherit">
            <div style="display:flex">@foreach($o->items->take(3) as $i)@if($i->image_url)<img src="{{ $i->image_url }}" alt="" style="width:58px;height:58px;object-fit:contain;border:1px solid var(--mt-line);border-radius:10px;background:#fff;margin-left:{{ $loop->first ? 0 : -18 }}px">@endif @endforeach</div>
            <div><b>{{ $o->order_no }}</b> <span class="mt-tag {{ $o->status === 'cancelled' ? 'out' : ($o->status === 'delivered' ? 'ok' : '') }}">{{ $o->statusLabel() }}</span>
                <div class="mt-muted">{{ $o->created_at->format('d M Y, H:i') }} · {{ $o->items->count() }} {{ Str::plural('item', $o->items->count()) }} · {{ $o->payment_method === 'cod' ? 'Pay on delivery' : 'Paid online' }}{{ $o->payment_status === 'paid' ? ' ✓' : '' }}</div>
                <div class="mt-muted">{{ Str::limit($o->items->pluck('title')->implode(', '), 110) }}</div></div>
            <b style="font-size:18px">₹{{ number_format($o->total) }}</b>
        </a>
    @empty
        <section class="mt-card" style="text-align:center;padding:40px"><div style="font-size:54px">📦</div><p>No orders yet.</p><a class="mt-btn" href="{{ route('materials.index') }}">Start shopping</a></section>
    @endforelse
    <div>{{ $orders->links() }}</div>
</div>
@endsection
