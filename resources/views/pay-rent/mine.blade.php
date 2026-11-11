@extends('layouts.site')
@section('title', 'My payments — ' . config('properties.brand'))
@section('content')
@include('pay-rent._layout', ['title' => 'My payments', 'back' => route('pay-rent.index')])
<div class="pr"><div class="pr-wrap pz-body">
    <div class="pz-card">
        @if(session('success'))<div class="pr-flash ok" style="margin-top:0">{{ session('success') }}</div>@endif
        @forelse($items as $t)
            <a href="{{ route('pay-rent.show', $t) }}" style="display:flex;justify-content:space-between;gap:10px;padding:12px 0;border-top:1px solid var(--pr-line);color:inherit;text-decoration:none;flex-wrap:wrap">
                <span><b>{{ $t->purposeLabel() }} · {{ $t->payee_name }}</b><br><small class="pr-mute">{{ $t->reference }} · {{ $t->payeeLabel() }} · {{ $t->created_at->format('d M Y') }}</small></span>
                <span style="text-align:right"><b>₹{{ number_format($t->amount) }}</b><br><small class="pr-mute">{{ $t->statusLabel() }}</small></span></a>
        @empty
            <p class="pr-mute">No payments yet. <a href="{{ route('pay-rent.index') }}">Pay rent or tuition →</a></p>
        @endforelse
        {{ $items->links() }}
    </div>
</div></div>
@endsection
