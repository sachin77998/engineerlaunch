@extends('layouts.site')
@section('title', 'Fund picks & buy / exit signals')
@php($F = \App\Services\Money\Format::class)
@php($S = \App\Services\Money\FundAdvisor::SIGNALS)
@section('content')
<div class="mp">
@include('money._layout', ['mpTitle' => 'Funds for you, and when to buy or exit', 'mpSub' => 'A machine-learning model ranks every fund against its category each week and flags good times to add, book profit or switch out.'])
<div class="mp-wrap">
<div class="mp-kpis">
    <div class="mp-kpi hl"><span>Your monthly SIP</span><b>₹{{ $F::inr($rec['monthly']) }}</b><small>split across {{ count($rec['slots']) }} funds below</small></div>
    <div class="mp-kpi"><span>Equity / Debt / Gold</span><b>{{ $rec['mix']['equity'] }} / {{ $rec['mix']['debt'] }} / {{ $rec['mix']['gold'] }}</b><small>age {{ $p['age'] }}, {{ $p['risk'] }} risk</small></div>
    @if($model)
        <div class="mp-kpi"><span>Model trained on</span><b>{{ number_format($model['samples']) }} samples</b><small>{{ $model['funds'] }} funds · {{ \Carbon\Carbon::parse($model['trained_at'])->format('d M Y') }}</small></div>
        <div class="mp-kpi ok"><span>Back-test hit rate</span><b>{{ $model['test_top_quintile_hit_rate'] ?? '—' }}%</b><small>top-ranked funds that beat their category next year</small></div>
    @else
        <div class="mp-kpi"><span>Model</span><b>Collecting data</b><small>ranked by 3-year return until trained</small></div>
    @endif
</div>
<form class="mp-card" method="get" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
    <div class="mp-f" style="margin:0;flex:1;min-width:200px"><label>Monthly amount to invest in funds ₹</label><input type="number" name="monthly" min="500" step="500" value="{{ (int) $rec['monthly'] }}"></div>
    <button class="mp-btn">Update picks</button><a class="mp-btn ghost" href="{{ route('money.index') }}">Change age / risk</a>
</form>

<section class="mp-card"><h2>Your fund plan</h2><p class="sub">Top-ranked Direct-Growth funds in each slot. Two choices per slot — pick one.</p>
    @foreach($rec['slots'] as $s)
        <div class="mp-opt" style="grid-template-columns:200px 1fr"><div><h3>{{ $s['category'] }}</h3><p>{{ $s['bucket'] }} · {{ round($s['weight']) }}%</p><p style="font-size:18px;font-weight:800;color:var(--mp-ink);margin-top:4px">₹{{ $F::inr($s['monthly']) }}<small style="font-size:12px;color:var(--mp-muted)">/month</small></p></div>
            <div>@forelse($s['funds'] as $f)
                <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;padding:6px 0;border-bottom:1px solid #eef2f7">
                    <span style="display:flex;gap:10px;align-items:center">@include('trading.partials.logo', ['name' => $f->amc, 'amc' => true])<span><a href="{{ route('trading.fund', $f->scheme_code) }}"><b>{{ $f->name }}</b></a><br><small style="color:var(--mp-muted)">1Y {{ $f->return_1y !== null ? round($f->return_1y, 1) . '%' : '—' }} · 3Y {{ $f->return_3y !== null ? round($f->return_3y, 1) . '%' : '—' }}{{ $f->score !== null ? ' · model ' . $f->score . '/100' : '' }}</small></span></span>
                    <span class="mp-pill {{ $f->signal ?? 'pending' }}">{{ $S[$f->signal ?? 'pending'] }}</span>
                </div>
            @empty<p>No funds found in this category.</p>@endforelse</div></div>
    @endforeach
</section>

@if($holdings->isNotEmpty())
<section class="mp-card"><h2>Your holdings — what to do now</h2>
    <div class="mp-scroll"><table class="mp-table"><thead><tr><th>Fund</th><th class="num">Value</th><th class="num">Gain</th><th>Signal</th><th>Why</th></tr></thead><tbody>
    @foreach($holdings as $h)<tr><td><a href="{{ route('trading.fund', $h->scheme_code) }}"><b>{{ $h->name }}</b></a></td><td class="num">₹{{ $F::inr($h->value) }}</td><td class="num" style="color:{{ $h->gain >= 0 ? 'var(--mp-green)' : 'var(--mp-red)' }}">₹{{ $F::inr($h->gain) }}</td>
        <td><span class="mp-pill {{ $h->signal ?? 'pending' }}">{{ $S[$h->signal ?? 'pending'] }}</span></td><td style="font-size:13px">{{ implode(' · ', (array) json_decode($h->reasons ?? '[]', true)) }}@if($h->tax_note)<div style="color:var(--mp-amber)">{{ $h->tax_note }}</div>@endif</td></tr>@endforeach
    </tbody></table></div>
</section>
@endif

<section class="mp-card"><h2>This week's signals</h2>
    <div class="mp-nav" style="margin:0 0 12px;box-shadow:none">
        <a href="{{ route('money.funds', request()->except('signal')) }}" class="{{ !$signal ? 'on' : '' }}">All ({{ $counts->sum() }})</a>
        @foreach(['buy', 'hold', 'book_profit', 'exit', 'watch'] as $k)<a href="{{ route('money.funds', array_merge(request()->except('signal'), ['signal' => $k])) }}" class="{{ $signal === $k ? 'on' : '' }}">{{ $S[$k] }} ({{ $counts[$k] ?? 0 }})</a>@endforeach
    </div>
    <div class="mp-scroll"><table class="mp-table"><thead><tr><th>Fund</th><th>Category</th><th class="num">1Y</th><th class="num">3Y</th><th class="num">Model</th><th>Signal</th><th>Why</th></tr></thead><tbody>
    @forelse($signals as $f)<tr><td><span style="display:flex;gap:10px;align-items:center">@include('trading.partials.logo', ['name' => $f->amc, 'amc' => true])<a href="{{ route('trading.fund', $f->scheme_code) }}"><b>{{ $f->name }}</b></a></span></td><td style="font-size:13px">{{ app(\App\Services\Money\FundModel::class)->group($f->category) }}</td>
        <td class="num">{{ $f->return_1y !== null ? round($f->return_1y, 1) . '%' : '—' }}</td><td class="num">{{ $f->return_3y !== null ? round($f->return_3y, 1) . '%' : '—' }}</td><td class="num"><b>{{ $f->score }}</b></td>
        <td><span class="mp-pill {{ $f->signal }}">{{ $S[$f->signal] ?? $f->signal }}</span></td><td style="font-size:12.5px;color:var(--mp-muted)">{{ implode(' · ', (array) json_decode($f->reasons ?? '[]', true)) }}</td></tr>
    @empty<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--mp-muted)">Signals appear after the nightly model run.</td></tr>@endforelse
    </tbody></table></div>
    <p class="mp-disc">How signals work: <b>Good time to add</b> = top-ranked fund trading below its 40-week average or 8%+ off its high. <b>Book partial profit</b> = RSI above 75 and 15%+ above average. <b>Review / exit</b> = bottom quarter of its category and behind the median over both 1 and 3 years. Past performance does not guarantee future returns; check exit load and tax (STCG under 12 months) before redeeming. Educational guidance, not personalised investment advice.</p>
</section>
</div>
</div>
@endsection
