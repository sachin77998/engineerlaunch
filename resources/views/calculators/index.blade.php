@extends('layouts.site')
@section('title', 'Financial Calculators — gold, silver, EMI, SIP, PPF, FD, NPS, GST and more')
@push('styles')@include('calculators.styles')@endpush
@section('content')
<section class="cx-hero">
    <div class="cx-wrap">
        <h1>Calculators</h1>
        <p>Gold, silver and copper prices, loan EMIs, SIP and mutual fund returns, government savings schemes, retirement, salary and tax: {{ collect($groups)->sum(fn ($g) => $g['items']->count()) }} calculators.</p>
    </div>
</section>
<main class="cx-wrap cx-main">
    @foreach($groups as $key => $group)
        @continue($group['items']->isEmpty())
        <section class="cx-group" id="{{ $key }}">
            <h2>{{ $group['title'] }}</h2>
            <div class="cx-grid">
                @foreach($group['items'] as $slug => $item)
                    <a class="cx-tile" href="{{ route('calculators.show', $slug) }}">
                        <div><h3>{{ $item['title'] }}</h3><p>{{ $item['text'] }}</p></div>
                        <div class="cx-foot"><span class="cx-go" aria-hidden="true">›</span><span class="cx-icon" aria-hidden="true">{{ $item['icon'] }}</span></div>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</main>
@endsection
