@extends('layouts.site')
@section('title', 'Store back office — Home Building')
@section('content')
@push('styles')<style>
.ma{display:grid;gap:18px;padding:24px 0 48px}
.ma-kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}
.ma-kpi a{padding:14px;border:1px solid #ece7e1;border-radius:14px;background:#fff;color:#292524;text-decoration:none}.ma-kpi a.on{border-color:#ea580c;background:#fff7ed}
.ma-kpi b{display:block;font-size:22px}
.ma-tabs{display:flex;gap:6px;flex-wrap:wrap}.ma-tabs a{padding:9px 16px;border-radius:20px;background:#f5f5f4;color:#292524;text-decoration:none;font-weight:600}.ma-tabs a.on{background:#1c1917;color:#fff}
.ma table{width:100%;border-collapse:collapse;font-size:13.5px;background:#fff}
.ma th,.ma td{padding:10px 8px;border-bottom:1px solid #f1ece6;text-align:left;vertical-align:top}.ma th{background:#fafaf9;color:#78716c;font-size:12px}
.ma input,.ma select,.ma textarea{padding:7px 8px;border:1px solid #e7d8c9;border-radius:8px;font:inherit;font-size:13px}
.ma button{padding:7px 12px;border:0;border-radius:8px;background:#ea580c;color:#fff;font-weight:700;cursor:pointer}
.ma .wrap{overflow-x:auto;border:1px solid #ece7e1;border-radius:14px}
</style>@endpush
<div class="container ma">
    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:baseline">
        <h1 style="margin:0">Store back office</h1>
        <a href="{{ route('materials.index') }}" style="color:#9a3412">Open the store →</a>
    </div>
    <div class="ma-kpi">
        <a href="{{ route('admin.materials.index') }}" class="{{ $tab === 'orders' && !$status ? 'on' : '' }}"><b>₹{{ number_format($revenue) }}</b>Collected</a>
        @foreach(\App\Models\MaterialOrder::STATUSES as $k => $label)
            <a href="{{ route('admin.materials.index', ['status' => $k]) }}" class="{{ $status === $k ? 'on' : '' }}"><b>{{ $counts[$k] ?? 0 }}</b>{{ $label }}</a>
        @endforeach
    </div>
    <div class="ma-tabs">
        @foreach(['orders' => 'Orders', 'feedback' => 'Feedback' . ($openFeedback ? " ($openFeedback open)" : ''), 'reviews' => 'Reviews', 'products' => 'Products & prices'] as $k => $label)
            <a class="{{ $tab === $k ? 'on' : '' }}" href="{{ route('admin.materials.index', ['tab' => $k]) }}">{{ $label }}</a>
        @endforeach
    </div>
    @if(in_array($tab, ['orders', 'products'], true))
        <form method="get" style="display:flex;gap:8px"><input type="hidden" name="tab" value="{{ $tab }}">@if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input name="q" value="{{ $q }}" placeholder="{{ $tab === 'orders' ? 'Order no, name or phone' : 'Product name' }}" style="flex:1;max-width:360px"><button>Search</button></form>
    @endif

    <div class="wrap">
    @if($tab === 'orders')
        <table><tr><th>Order</th><th>Customer &amp; address</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th></tr>
        @forelse($rows as $o)
            <tr>
                <td><a href="{{ route('materials.orders.show', $o) }}"><b>{{ $o->order_no }}</b></a><br><span style="color:#78716c">{{ $o->created_at->format('d M, H:i') }}</span>@if($o->slot)<br>{{ $o->slot }}@endif</td>
                <td><b>{{ $o->name }}</b> · {{ $o->phone }}<br>{{ $o->address_line1 }}{{ $o->address_line2 ? ', ' . $o->address_line2 : '' }}<br>{{ $o->city }} {{ $o->pincode }}@if($o->notes)<br><i>{{ $o->notes }}</i>@endif</td>
                <td>@foreach($o->items as $i){{ $i->qty }} × {{ Str::limit($i->title, 40) }}{{ $i->variant_title ? ' (' . $i->variant_title . ')' : '' }}<br>@endforeach</td>
                <td><b>₹{{ number_format($o->total) }}</b></td>
                <td>{{ $o->payment_method === 'cod' ? 'On delivery' : 'Paytm' }}<br><b style="color:{{ $o->payment_status === 'paid' ? '#15803d' : '#b45309' }}">{{ $o->payment_status }}</b></td>
                <td><form method="post" action="{{ route('admin.materials.orders.update', $o) }}" style="display:grid;gap:6px;min-width:190px">@csrf
                    <select name="status">@foreach(\App\Models\MaterialOrder::STATUSES as $k => $label)<option value="{{ $k }}" @selected($o->status === $k)>{{ $label }}</option>@endforeach</select>
                    <input name="tracking" value="{{ $o->tracking }}" placeholder="Vehicle / driver / tracking">
                    @if($o->payment_method === 'cod' && $o->payment_status !== 'paid')<label style="font-size:12px"><input type="checkbox" name="cod_paid" value="1"> Cash collected</label>@endif
                    <button>Save</button></form></td>
            </tr>
        @empty <tr><td colspan="6">No orders.</td></tr> @endforelse
        </table>
    @elseif($tab === 'feedback')
        <table><tr><th>From</th><th>About</th><th>Message</th><th>Reply &amp; status</th></tr>
        @forelse($rows as $f)
            <tr>
                <td>{{ $f->name ?? 'Guest' }}<br><a href="mailto:{{ $f->email }}">{{ $f->email }}</a><br><span style="color:#78716c">{{ $f->created_at->format('d M, H:i') }}</span></td>
                <td>{{ \App\Models\MaterialFeedback::KINDS[$f->kind] ?? $f->kind }}@if($f->material)<br><a href="{{ route('materials.show', $f->material) }}">{{ Str::limit($f->material->title, 40) }}</a>@endif @if($f->order)<br>Order <a href="{{ route('materials.orders.show', $f->order) }}">{{ $f->order->order_no }}</a>@endif</td>
                <td style="max-width:360px;white-space:pre-line">{{ $f->message }}</td>
                <td><form method="post" action="{{ route('admin.materials.feedback.update', $f) }}" style="display:grid;gap:6px;min-width:240px">@csrf
                    <textarea name="reply" rows="2" placeholder="Note / reply sent">{{ $f->reply }}</textarea>
                    <select name="status">@foreach(['open', 'answered', 'closed'] as $s)<option @selected($f->status === $s)>{{ $s }}</option>@endforeach</select><button>Save</button></form></td>
            </tr>
        @empty <tr><td colspan="4">No feedback yet.</td></tr> @endforelse
        </table>
    @elseif($tab === 'reviews')
        <table><tr><th>Product</th><th>Rating</th><th>Review</th><th>By</th><th></th></tr>
        @forelse($rows as $r)
            <tr><td>@if($r->material)<a href="{{ route('materials.show', $r->material) }}">{{ Str::limit($r->material->title, 50) }}</a>@endif</td><td>{{ str_repeat('★', $r->rating) }}</td>
                <td><b>{{ $r->title }}</b><br>{{ Str::limit($r->body, 200) }}</td><td>{{ $r->user?->name }}{{ $r->verified ? ' ✓' : '' }}<br><span style="color:#78716c">{{ $r->created_at->format('d M Y') }}</span></td>
                <td><form method="post" action="{{ route('admin.materials.reviews.update', $r) }}">@csrf<input type="hidden" name="status" value="{{ $r->status === 'published' ? 'hidden' : 'published' }}"><button style="background:{{ $r->status === 'published' ? '#78716c' : '#16a34a' }}">{{ $r->status === 'published' ? 'Hide' : 'Publish' }}</button></form></td></tr>
        @empty <tr><td colspan="5">No reviews yet.</td></tr> @endforelse
        </table>
    @else
        <table><tr><th>Product</th><th>Catalogue price</th><th>Your selling price</th><th>For sale</th><th></th></tr>
        @foreach($rows as $m)
            <tr><td><a href="{{ route('materials.show', $m) }}">{{ Str::limit($m->title, 70) }}</a><br><span style="color:#78716c">{{ $m->brand }} · {{ $m->in_stock ? 'in stock' : 'out of stock' }}</span></td>
                <td>₹{{ number_format($m->price) }}@if($m->mrp > $m->price) <s style="color:#a8a29e">₹{{ number_format($m->mrp) }}</s>@endif</td>
                <td colspan="3"><form method="post" action="{{ route('admin.materials.products.update', $m) }}" style="display:flex;gap:8px;align-items:center">@csrf
                    <input name="sell_price" type="number" step="0.01" min="1" value="{{ $m->sell_price }}" placeholder="Same as catalogue" style="width:150px">
                    <label><input type="checkbox" name="sellable" value="1" @checked($m->sellable)> For sale</label><button>Save</button></form></td></tr>
        @endforeach
        </table>
    @endif
    </div>
    <div>{{ $rows->links() }}</div>
</div>
@endsection
