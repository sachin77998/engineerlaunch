@extends('layouts.site')
@section('title', 'Alert stopped')
@section('content')
@include('properties._nav')
<div class="pr"><div class="pr-wrap" style="max-width:640px;margin-top:40px">
    <div class="pr-card-box" style="text-align:center;display:grid;gap:10px">
        <h1 style="margin:0">Alert stopped</h1>
        <p class="pr-mute" style="margin:0">We won't email you about “{{ $search->name }}” any more. The search stays saved — you can turn emails back on any time.</p>
        <p style="margin:0"><a class="pr-btn" href="{{ route('saved-searches.index') }}">My saved searches</a></p>
    </div>
</div></div>
@endsection
