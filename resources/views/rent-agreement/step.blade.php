@extends('layouts.site')
@section('title', $steps[$step] . ' — Rent agreement ' . $a->reference())
@section('content')
@include('properties._styles')
@include('home-loan._styles')
@php
    $keys = array_keys($steps);
    $pos = array_search($step, $keys, true);
    $prev = $pos > 0 ? $keys[$pos - 1] : null;
@endphp
@push('styles')<style>
.ra-person{border:1px solid var(--pr-line);border-radius:14px;padding:16px;display:grid;gap:10px;margin-bottom:14px}
.ra-person h3{margin:0}.ra-fix{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px}.ra-fix label{display:flex;justify-content:space-between;align-items:center;gap:6px;border:1px solid var(--pr-line);border-radius:10px;padding:6px 10px;font-size:14px}.ra-fix input{width:64px!important;padding:6px!important}
.hl-main{max-width:760px}
</style>@endpush
<div class="pr"><div class="hl-app">
    <aside class="hl-side">
        <h2>Rent agreement <small style="display:block;font-size:13px;font-weight:500;opacity:.75">{{ $a->reference() }} · {{ $a->city }}</small></h2>
        <ol>
            @foreach($steps as $k => $label)
                @php($reach = $a->isDone($k) || $loop->index === 0 || $a->isDone($keys[$loop->index - 1]))
                <li class="{{ $k === $step ? 'on' : ($a->isDone($k) ? 'done' : '') }}"><span>{{ $a->isDone($k) && $k !== $step ? '✓' : $loop->iteration }}</span>
                    <div><small>Step {{ $loop->iteration }}</small>@if($reach && $k !== $step && $a->status === 'draft')<a href="{{ route('rent-agreement.step', [$a, $k]) }}">{{ $label }}</a>@else<b>{{ $label }}</b>@endif</div></li>
            @endforeach
        </ol>
        <p style="margin-top:20px"><a href="{{ route('rent-agreement.preview', $a) }}" target="_blank" style="color:#fff;font-weight:700">Preview agreement ↗</a></p>
    </aside>
    <main class="hl-main">
        <h1><a href="{{ $prev ? route('rent-agreement.step', [$a, $prev]) : route('rent-agreement.index') }}" aria-label="Back">←</a> {{ $steps[$step] }}</h1>
        @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="pr-flash err">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
        @include('rent-agreement.steps.' . $step)
    </main>
</div></div>
@endsection
