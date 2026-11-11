@extends('layouts.site')
@section('title', 'Home loans — admin')
@section('content')
@include('properties._nav')
@include('home-loan._styles')
@php
    $PF = \App\Services\Property\PropertyFormat::class;
    $o = config('home_loans');
@endphp
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <h1 style="margin:22px 0 10px">Home loan applications</h1>
    <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
        <a class="pr-btn sm {{ $status ? 'ghost' : '' }}" href="{{ route('admin.home-loans.index') }}">All ({{ $counts->sum() }})</a>
        @foreach($o['statuses'] as $k => $label)<a class="pr-btn sm {{ $status === $k ? '' : 'ghost' }}" href="{{ route('admin.home-loans.index', ['status' => $k]) }}">{{ $label }} ({{ $counts[$k] ?? 0 }})</a>@endforeach
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Name or phone" style="max-width:220px">@if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
    </form>
    @forelse($items as $a)
        <div class="pr-card-box" style="margin-bottom:12px">
            <div style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px">
                <div>
                    <b>{{ $a->reference() }} · {{ $a->name }}</b> <span class="pr-tag">{{ $a->statusLabel() }}</span>
                    <div style="font-size:14px;margin-top:4px"><a href="tel:+91{{ $a->phone }}">+91 {{ $a->phone }}</a>@if($a->email) · <a href="mailto:{{ $a->email }}">{{ $a->email }}</a>@endif · {{ $a->city }}</div>
                    <div class="pr-mute" style="font-size:13.5px;margin-top:4px">
                        {{ $a->loan_amount ? $PF::full($a->loan_amount) : '—' }} · {{ $a->tenure }} yrs
                        @if($a->monthly_income) · income {{ $PF::full($a->monthly_income) }}/mo ({{ $o['employment'][$a->employment][0] ?? '—' }})@endif
                        @if($a->current_emis) · EMIs {{ $PF::full($a->current_emis) }}@endif
                        · {{ $a->bank?->name ?? 'no bank chosen' }}
                    </div>
                    <div class="pr-mute" style="font-size:13.5px">
                        Property {{ $a->property_finalized ? 'finalised' : 'not finalised' }}@if($a->property_type) · {{ $o['property_types'][$a->property_type] ?? $a->property_type }}@endif
                        @if($a->property_stage) · {{ $o['property_stages'][$a->property_stage] ?? '' }}@endif
                        @if($a->property_value) · value {{ $PF::full($a->property_value) }}@endif
                        @if($a->property_city) · {{ $a->property_city }}@endif @if($a->property_address) · {{ $a->property_address }}@endif
                        @if($a->property) · <a href="{{ route('properties.show', $a->property) }}">listing</a>@endif
                    </div>
                    <div class="pr-mute" style="font-size:12.5px">Started {{ $a->created_at->format('d M Y H:i') }}@if($a->submitted_at) · submitted {{ $a->submitted_at->format('d M Y H:i') }}@endif · steps {{ implode(', ', $a->steps_done ?? []) ?: 'none' }}</div>
                    @if($a->documents->isNotEmpty())<div style="margin-top:8px;font-size:13.5px">@foreach($a->documents as $doc)<a class="pr-tag" href="{{ route('home-loan.document', $doc) }}" style="margin:0 4px 4px 0;display:inline-block">📄 {{ $doc->label() }}</a>@endforeach</div>@endif
                </div>
                <div>
                    <form method="post" action="{{ route('admin.home-loans.update', $a) }}" style="display:grid;gap:8px">@csrf
                        <select name="status">@foreach($o['statuses'] as $k => $label)<option value="{{ $k }}" @selected($a->status === $k)>{{ $label }}</option>@endforeach</select>
                        <textarea name="admin_note" rows="2" maxlength="2000" placeholder="Note">{{ $a->admin_note }}</textarea>
                        <button class="pr-btn sm">Save</button>
                    </form>
                    <form method="post" action="{{ route('admin.home-loans.destroy', $a) }}" onsubmit="return confirm('Delete this application and its documents?')" style="margin-top:6px">@csrf<button class="pr-btn danger sm">Delete</button></form>
                </div>
            </div>
        </div>
    @empty
        <div class="pr-card-box">No applications.</div>
    @endforelse
    {{ $items->links() }}

    <h2 style="margin:30px 0 10px" id="lenders">Lenders shown on /home-loan</h2>
    <div style="overflow-x:auto"><table class="hl-table" style="background:#fff">
        <thead><tr><th>Name</th><th>Type</th><th>Rate %</th><th>Processing fee</th><th>Tenure</th><th>Funding %</th><th>Max loan</th><th>Colour</th><th>Order</th><th>Live</th><th></th></tr></thead>
        <tbody>
        @foreach([...$banks, null] as $b)
            @php($fid = 'bank-' . ($b?->id ?? 'new'))
            <tr>
                <td><input form="{{ $fid }}" type="text" name="name" required value="{{ $b?->name }}" placeholder="New lender" style="min-width:160px"></td>
                <td><select form="{{ $fid }}" name="kind"><option value="bank" @selected($b?->kind !== 'nbfc')>Bank</option><option value="nbfc" @selected($b?->kind === 'nbfc')>NBFC</option></select></td>
                <td><input form="{{ $fid }}" type="number" step="0.01" name="rate_from" required value="{{ $b?->rate_from }}" style="width:80px"></td>
                <td><input form="{{ $fid }}" type="text" name="processing_fee" value="{{ $b?->processing_fee }}" style="min-width:200px"></td>
                <td><input form="{{ $fid }}" type="number" name="max_tenure" required value="{{ $b?->max_tenure ?? 30 }}" style="width:64px"></td>
                <td><input form="{{ $fid }}" type="number" name="max_funding" required value="{{ $b?->max_funding ?? 80 }}" style="width:64px"></td>
                <td><input form="{{ $fid }}" type="text" name="max_amount" value="{{ $b?->max_amount }}" placeholder="Based on eligibility" style="width:150px"></td>
                <td><input form="{{ $fid }}" type="color" name="color" value="{{ $b?->color ?? '#4338ca' }}"></td>
                <td><input form="{{ $fid }}" type="number" name="sort" value="{{ $b?->sort ?? 0 }}" style="width:60px"></td>
                <td><input form="{{ $fid }}" type="checkbox" name="is_active" value="1" @checked($b ? $b->is_active : true)></td>
                <td><form id="{{ $fid }}" method="post" action="{{ $b ? route('admin.home-loans.bank.update', $b) : route('admin.home-loans.bank.create') }}">@csrf<button class="pr-btn sm">{{ $b ? 'Save' : 'Add' }}</button></form></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</div></div>
@endsection
