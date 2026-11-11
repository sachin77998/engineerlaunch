@extends('layouts.site')
@section('title', 'My home loans — ' . config('properties.brand'))
@section('content')
@include('properties._nav')
@include('home-loan._styles')
@php($PF = \App\Services\Property\PropertyFormat::class)
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin:22px 0 14px;flex-wrap:wrap"><h1 style="margin:0">My home loans</h1><a class="pr-btn" href="{{ route('home-loan.index') }}#apply">New application</a></div>
    @forelse($items as $a)
        @php($next = collect(array_keys(\App\Models\HomeLoan\HomeLoanApplication::STEPS))->first(fn ($s) => !$a->isDone($s)))
        <div class="pr-card-box" style="margin-bottom:12px;display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;align-items:center">
            <div>
                <b>{{ $a->reference() }} · {{ $a->loan_amount ? $PF::full($a->loan_amount) : 'Amount not set' }}@if($a->tenure) for {{ $a->tenure }} years @endif</b>
                <div class="pr-mute" style="font-size:13.5px">{{ $a->bank?->name ?? 'Bank not chosen' }} · {{ $a->city }} · {{ $a->documents_count }} {{ $a->documents_count === 1 ? 'document' : 'documents' }} · started {{ $a->created_at->format('d M Y') }}</div>
                <span class="pr-tag {{ in_array($a->status, ['sanctioned', 'submitted', 'contacted'], true) ? 'ok' : ($a->status === 'new' ? 'amber' : '') }}" style="margin-top:6px;display:inline-block">{{ $a->statusLabel() }}</span>
            </div>
            <a class="pr-btn ghost sm" href="{{ route('home-loan.step', [$a, $next ?? 'documents']) }}">{{ $a->submitted_at ? 'View / add documents' : 'Continue' }}</a>
        </div>
    @empty
        <div class="pr-card-box" style="text-align:center;padding:30px"><b>No home loan applications yet.</b><p class="pr-mute">Compare lenders and check your eligibility in a minute.</p><a class="pr-btn" href="{{ route('home-loan.index') }}">Check eligibility</a></div>
    @endforelse
</div></div>
@endsection
