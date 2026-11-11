@extends('layouts.site')
@section('title', 'Pay ' . strtolower($p[0]) . ' by credit card — ' . config('properties.brand'))
@section('content')
@include('pay-rent._layout', ['title' => 'Pay ' . strtolower($p[0]), 'back' => route('pay-rent.index')])
<div class="pr"><div class="pr-wrap pz-body">
    <div class="pz-methods">@foreach(config('pay_rent.methods') as $k => [$label, $icon])<a class="pz-method" href="{{ route('pay-rent.form', [$purpose, $k]) }}"><i>{{ $icon }}</i>{{ $label }}</a>@endforeach</div>
    @unless($live)<div class="pz-off">Card payments are not switched on yet — nothing will be charged.</div>@endunless
</div></div>
@endsection
