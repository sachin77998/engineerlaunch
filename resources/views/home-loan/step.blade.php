@extends('layouts.site')
@section('title', $steps[$step] . ' — Home loan ' . $app->reference())
@section('content')
@include('properties._styles')
@include('home-loan._styles')
@php
    $keys = array_keys($steps);
    $pos = array_search($step, $keys, true);
    $prev = $pos > 0 ? $keys[$pos - 1] : null;
@endphp
<div class="pr"><div class="hl-app">
    <aside class="hl-side">
        <h2>Home loan <small style="display:block;font-size:13px;font-weight:500;opacity:.75">{{ $app->reference() }}</small></h2>
        <ol>
            @foreach($steps as $k => $label)
                @php($reach = $loop->index <= $pos || $app->isDone($k) || ($loop->index > 0 && $app->isDone($keys[$loop->index - 1])))
                <li class="{{ $k === $step ? 'on' : ($app->isDone($k) ? 'done' : '') }}"><span>{{ $app->isDone($k) && $k !== $step ? '✓' : $loop->iteration }}</span>
                    <div><small>Step {{ $loop->iteration }}</small>@if($reach && $k !== $step)<a href="{{ route('home-loan.step', [$app, $k]) }}">{{ $label }}</a>@else<b>{{ $label }}</b>@endif</div></li>
            @endforeach
        </ol>
    </aside>
    <main class="hl-main">
        <h1>@if($prev)<a href="{{ route('home-loan.step', [$app, $prev]) }}" aria-label="Back">←</a>@else<a href="{{ route('home-loan.index') }}" aria-label="Back">←</a>@endif {{ $steps[$step] }}</h1>
        @if(session('success'))<div class="pr-flash ok">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="pr-flash err">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="pr-flash err">{{ $errors->first() }}</div>@endif
        @include('home-loan.steps.' . $step)
    </main>
</div></div>
@endsection
