@extends('layouts.site')
@section('title', 'Legal services — admin')
@section('content')
@include('properties._nav')
@php
    $PF = \App\Services\Property\PropertyFormat::class;
    $st = config('legal.statuses');
@endphp
<div class="pr"><div class="pr-wrap">
    @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
    <h1 style="margin:22px 0 10px">Legal service bookings</h1>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
        <a class="pr-btn sm {{ $status ? 'ghost' : '' }}" href="{{ route('admin.legal.index') }}">All ({{ $counts->sum() }})</a>
        @foreach($st as $k => $label)<a class="pr-btn sm {{ $status === $k ? '' : 'ghost' }}" href="{{ route('admin.legal.index', ['status' => $k]) }}">{{ $label }} ({{ $counts[$k] ?? 0 }})</a>@endforeach
        <a class="pr-btn sm ghost" href="#services">Services &amp; prices</a><a class="pr-btn sm ghost" href="#partners">Partners</a><a class="pr-btn sm ghost" href="#reviews">Reviews</a>
    </div>
    @forelse($bookings as $b)
        <div class="pr-card-box" style="margin-bottom:12px;display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:16px">
            <div>
                <b>{{ $b->reference() }} · {{ $b->name }}</b> <span class="pr-tag">{{ $b->statusLabel() }}</span>
                <div style="font-size:14px"><a href="tel:+91{{ $b->phone }}">+91 {{ $b->phone }}</a>@if($b->email) · <a href="mailto:{{ $b->email }}">{{ $b->email }}</a>@endif · {{ $b->city }}@if($b->address) · {{ $b->address }}@endif</div>
                <div class="pr-mute" style="font-size:13px">Booked {{ $b->created_at->format('d M Y H:i') }}@if($b->preferred_at) · preferred {{ $b->preferred_at->format('d M Y, g:i A') }}@endif</div>
                <ul style="margin:6px 0 0;padding-left:18px;font-size:14px">@foreach($b->items as $i)<li>{{ $i->name }} — {{ $i->price ? $PF::full($i->price) : 'quote' }}</li>@endforeach</ul>
                <div style="font-size:14px;margin-top:4px"><b>Total {{ $PF::full($b->total) }}</b>@if($b->has_quote_items) + quoted items @endif</div>
                @if($b->rentAgreement)<div style="margin-top:6px">📄 <a href="{{ route('rent-agreement.preview', $b->rentAgreement) }}" target="_blank">Agreement {{ $b->rentAgreement->reference() }}</a>@if($b->rentAgreement->draft_path) · <a href="{{ route('rent-agreement.draft', $b->rentAgreement) }}">customer's draft</a>@endif</div>@endif
                @if($b->notes)<p class="pr-mute" style="margin:6px 0 0;white-space:pre-line">{{ $b->notes }}</p>@endif
            </div>
            <form method="post" action="{{ route('admin.legal.update', $b) }}" style="display:grid;gap:8px">@csrf
                <select name="status">@foreach($st as $k => $label)<option value="{{ $k }}" @selected($b->status === $k)>{{ $label }}</option>@endforeach</select>
                <textarea name="admin_note" rows="2" maxlength="2000" placeholder="Note shown to the customer">{{ $b->admin_note }}</textarea>
                <button class="pr-btn sm">Save</button>
            </form>
        </div>
    @empty
        <div class="pr-card-box">No bookings.</div>
    @endforelse
    {{ $bookings->links() }}

    <h2 id="services" style="margin:30px 0 10px">Services &amp; prices</h2>
    <p class="pr-mute" style="margin-top:0">Leave price empty to show "Price on call".</p>
    <div style="overflow-x:auto"><table class="pr-card-box" style="width:100%;border-collapse:collapse;padding:0">
        <thead><tr style="text-align:left;font-size:13px" class="pr-mute"><th style="padding:10px">Service</th><th>Price ₹</th><th>Price note</th><th>Turnaround</th><th>Order</th><th>Live</th><th></th></tr></thead>
        <tbody>
        @foreach($services as $s)
            <tr style="border-top:1px solid var(--pr-line)">
                <td style="padding:8px 10px"><b>{{ $s->name }}</b><div class="pr-mute" style="font-size:12px">{{ $s->categoryLabel() }}@if($s->cities) · {{ implode(', ', $s->cities) }} only @endif</div></td>
                <td><input form="sv-{{ $s->id }}" type="number" name="price" min="0" value="{{ $s->price }}" style="width:110px"></td>
                <td><input form="sv-{{ $s->id }}" type="text" name="price_note" maxlength="120" value="{{ $s->price_note }}" placeholder="onwards" style="width:120px"></td>
                <td><input form="sv-{{ $s->id }}" type="text" name="turnaround" maxlength="60" value="{{ $s->turnaround }}" placeholder="3–5 working days" style="width:150px"></td>
                <td><input form="sv-{{ $s->id }}" type="number" name="sort" value="{{ $s->sort }}" style="width:64px"></td>
                <td><input form="sv-{{ $s->id }}" type="checkbox" name="is_active" value="1" @checked($s->is_active)></td>
                <td><form id="sv-{{ $s->id }}" method="post" action="{{ route('admin.legal.service', $s->id) }}">@csrf<button class="pr-btn sm">Save</button></form></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>

    <h2 id="partners" style="margin:30px 0 10px">Legal partners</h2>
    <p class="pr-mute" style="margin-top:0">Shown on /legal only when at least one is live. Cities: comma separated, empty = all.</p>
    @foreach([...$partners, null] as $p)
        <form method="post" action="{{ $p ? route('admin.legal.partner.update', $p) : route('admin.legal.partner.create') }}" class="pr-card-box" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:8px;margin-bottom:10px;align-items:end">
            @csrf
            <input type="text" name="name" required maxlength="120" value="{{ $p?->name }}" placeholder="{{ $p ? '' : 'New partner name' }}">
            <input type="text" name="focus" required maxlength="300" value="{{ $p?->focus }}" placeholder="Specialisation" style="grid-column:span 2">
            <input type="text" name="affiliation" maxlength="80" value="{{ $p?->affiliation }}" placeholder="Affiliation (e.g. High Court)">
            <input type="number" name="rating" step="0.1" min="1" max="5" value="{{ $p?->rating }}" placeholder="Rating">
            <input type="number" name="cases_resolved" min="0" value="{{ $p?->cases_resolved }}" placeholder="Cases resolved">
            <input type="number" name="documents_reviewed" min="0" value="{{ $p?->documents_reviewed }}" placeholder="Documents reviewed">
            <input type="number" name="years_experience" min="0" max="70" value="{{ $p?->years_experience }}" placeholder="Years">
            <input type="text" name="cities" value="{{ $p?->cities ? implode(', ', $p->cities) : '' }}" placeholder="Cities">
            <input type="number" name="sort" value="{{ $p?->sort ?? 0 }}" placeholder="Order">
            <label><input type="checkbox" name="is_active" value="1" @checked($p ? $p->is_active : true)> Live</label>
            <button class="pr-btn sm">{{ $p ? 'Save' : 'Add partner' }}</button>
        </form>
    @endforeach

    <h2 id="reviews" style="margin:30px 0 10px">Customer reviews</h2>
    @forelse($reviews as $r)
        <div class="pr-card-box" style="margin-bottom:8px;display:flex;justify-content:space-between;gap:12px">
            <div><b>{{ $r->rating }} ★ · {{ $r->title }}</b> <span class="pr-mute">— {{ $r->user?->name }}, {{ $r->created_at->format('d M Y') }}</span><p style="margin:4px 0 0">{{ $r->body }}</p></div>
            <form method="post" action="{{ route('admin.legal.review', $r) }}">@csrf<button class="pr-btn sm {{ $r->is_visible ? 'grey' : '' }}">{{ $r->is_visible ? 'Hide' : 'Show' }}</button></form>
        </div>
    @empty
        <div class="pr-card-box">No reviews yet.</div>
    @endforelse
</div></div>
@endsection
