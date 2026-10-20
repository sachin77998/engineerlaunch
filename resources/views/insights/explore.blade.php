@extends('layouts.site')
@section('title', $databases[$db] . ' — Explore databases')
@push('styles')@include('insights.partials.db-styles')@endpush
@section('content')
@php
    $inr = fn ($v) => \App\Services\Insights\CompanyInsights::money($v);
    $usd = fn ($v, $cur = 'USD') => \App\Services\Insights\UsMarketData::money($v === null ? null : (float) $v, $cur ?: 'USD');
    $chg = fn ($v) => $v === null ? '' : '<small class="' . ($v >= 0 ? 'db-up' : 'db-down') . '"> ' . ($v >= 0 ? '+' : '') . number_format($v, 1) . '%' . ($v >= 0 ? '↑' : '↓') . '</small>';
    $flags = ['India' => '🇮🇳', 'United States' => '🇺🇸', 'USA' => '🇺🇸', 'United Kingdom' => '🇬🇧', 'China' => '🇨🇳', 'Canada' => '🇨🇦', 'Germany' => '🇩🇪', 'Japan' => '🇯🇵', 'Israel' => '🇮🇱',
        'Netherlands' => '🇳🇱', 'Switzerland' => '🇨🇭', 'France' => '🇫🇷', 'Ireland' => '🇮🇪', 'Singapore' => '🇸🇬', 'Brazil' => '🇧🇷', 'Taiwan' => '🇹🇼', 'Hong Kong' => '🇭🇰', 'Bermuda' => '🇧🇲',
        'Cayman Islands' => '🇰🇾', 'Australia' => '🇦🇺', 'South Korea' => '🇰🇷', 'Mexico' => '🇲🇽', 'Argentina' => '🇦🇷', 'Greece' => '🇬🇷', 'Luxembourg' => '🇱🇺', 'Sweden' => '🇸🇪', 'Finland' => '🇫🇮'];
    $logo = fn ($row) => $row->logo_url ?? ($row->website ?? null ? 'https://www.google.com/s2/favicons?domain_url=' . rawurlencode($row->website) . '&sz=64' : null);
    $domain = fn ($w) => $w ? preg_replace('~^www\.~', '', (string) parse_url($w, PHP_URL_HOST)) : null;
    $shown = $premium ? $items->count() : min($freeRows, $total);
@endphp
<div class="db-page">
<div class="db-layout two">
    @include('insights.partials.explore-nav')
    <main style="min-width:0">
        <div class="ex-top">
            <div><small>Explore databases</small><h1>{{ $databases[$db] }}<span>{{ number_format($total) }} records</span></h1></div>
            @if($premium && in_array($db, ['listed', 'companies', 'us'], true))<small>Sorted by {{ $db === 'us' ? 'market cap' : 'revenue' }}, largest first</small>@endif
        </div>
        <form class="ex-filters" method="get">
            <input type="search" name="q" value="{{ $f['q'] ?? '' }}" placeholder="{{ $db === 'people' ? 'Search director, DIN or company' : 'Search company' . ($db === 'us' || $db === 'listed' ? ' or ticker' : '') }}" aria-label="Search">
            <select name="sector" aria-label="Sector"><option value="">All sectors</option>@foreach($sectors as $s)<option @selected(($f['sector'] ?? '') === $s)>{{ $s }}</option>@endforeach</select>
            @if($db === 'us')
                <input name="state" value="{{ $f['state'] ?? '' }}" placeholder="Country" style="min-width:140px;flex:0">
            @else
                <select name="state" aria-label="State"><option value="">All states</option>@foreach(\App\Services\Insights\DataGovMca::STATES as $s)<option @selected(($f['state'] ?? '') === $s)>{{ $s }}</option>@endforeach</select>
            @endif
            <button type="submit">Filter</button>
            @if(array_filter($f))<a href="{{ route('insights.explore', $db) }}">Clear</a>@endif
        </form>

        <div class="ex-table-wrap">
            <table class="ex-table">
                @if($db === 'people')
                    <thead><tr><th>#</th><th>Name</th><th>Designation</th><th>DIN</th><th>Company</th><th>Appointed</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($items as $i => $p)
                        <tr><td>{{ ($premium ? $rows->firstItem() : 1) + $i }}</td><td><b>{{ $p->name }}</b></td><td>{{ $p->designation ?? '—' }}</td><td>{{ $p->din ?? '—' }}</td>
                            <td><a href="{{ route('insights.show', $p->slug) }}">{{ $p->company }}</a></td><td>{{ $p->appointed_on ? \Carbon\Carbon::parse($p->appointed_on)->format('d M Y') : '—' }}</td>
                            <td><span class="ex-stage {{ $p->ceased_on ? 'pvt' : '' }}">{{ $p->ceased_on ? 'Past' : 'Current' }}</span></td></tr>
                    @empty
                        <tr><td colspan="7">No directors on record yet. They are read from MGT-7 filings and the data providers.</td></tr>
                    @endforelse
                    </tbody>
                @elseif($db === 'us')
                    <thead><tr><th>#</th><th>Company</th><th>Description</th><th>Stage</th><th>Sector / Industry</th><th>Location</th><th>Listed</th><th>Market cap</th><th>Revenue</th><th>Operating income</th><th>Net profit</th></tr></thead>
                    <tbody>
                    @forelse($items as $i => $u)
                        @php $g = $growth[$u->cik] ?? []; @endphp
                        <tr>
                            <td>{{ ($premium ? $rows->firstItem() : 1) + $i }}</td>
                            <td><div class="ex-co">@if($logo($u))<img class="db-logo" src="{{ $logo($u) }}" alt="" loading="lazy">@else<span class="db-logo txt">{{ mb_substr($u->name, 0, 1) }}</span>@endif
                                <div>@if($u->slug)<a href="{{ route('insights.show', $u->slug) }}">{{ $u->name }}</a>@else<b>{{ $u->name }}</b>@endif<small>{{ $domain($u->website) }}<span class="ex-badge">{{ strtoupper($u->exchange ?? '') }} {{ $u->ticker }}</span></small></div></div></td>
                            <td><div class="ex-desc" title="{{ $u->industry }}">{{ $u->industry ?? '—' }}</div></td>
                            <td><span class="ex-stage">Public</span></td>
                            <td>{{ $u->sector ?? '—' }}</td>
                            <td>{{ $flags[$u->country] ?? '' }} {{ $u->country ?? '—' }}</td>
                            <td>{{ $u->ipo_year ?? '—' }}</td>
                            <td class="num">{{ $usd($u->market_cap) }}</td>
                            <td class="num">{{ $usd($u->revenue, $u->currency) }}{!! $chg($g['revenue'] ?? null) !!}</td>
                            <td class="num">{{ $usd($u->operating_income, $u->currency) }}{!! $chg($g['operating_income'] ?? null) !!}</td>
                            <td class="num">{{ $usd($u->net_income, $u->currency) }}{!! $chg($g['net_income'] ?? null) !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11">No companies match.</td></tr>
                    @endforelse
                    </tbody>
                @else
                    <thead><tr><th>#</th><th>Company</th><th>Description</th><th>Stage</th><th>Sector / Segment</th><th>Location</th><th>Founded</th><th>Revenue</th><th>EBITDA</th><th>Net profit</th></tr></thead>
                    <tbody>
                    @forelse($items as $i => $c)
                        @php $g = $growth[$c->id] ?? []; $listed = $c->symbol || str_starts_with((string) $c->registry_cin, 'L'); @endphp
                        <tr>
                            <td>{{ ($premium ? $rows->firstItem() : 1) + $i }}</td>
                            <td><div class="ex-co">@if($logo($c))<img class="db-logo" src="{{ $logo($c) }}" alt="" loading="lazy">@else<span class="db-logo txt">{{ mb_substr($c->name, 0, 1) }}</span>@endif
                                <div><a href="{{ route('insights.show', $c->slug) }}">{{ $c->name }}</a><small>{{ $domain($c->website) }}@if($c->symbol)<span class="ex-badge">NSE {{ $c->symbol }}</span>@endif</small></div></div></td>
                            <td><div class="ex-desc" title="{{ $c->description ?: $c->principal_activity }}">{{ \Illuminate\Support\Str::limit($c->description ?: $c->principal_activity ?: '—', 70) }}</div></td>
                            <td><span class="ex-stage {{ $listed ? '' : 'pvt' }}">{{ $listed ? 'Listed' : 'Unlisted' }}</span></td>
                            <td>{{ $c->industry ?? '—' }}@if($c->sector && $c->sector !== $c->industry) › {{ $c->sector }}@endif</td>
                            <td>🇮🇳 {{ collect([$c->city ? ucwords(strtolower($c->city)) : null, $c->registered_state])->filter()->implode(', ') ?: 'India' }}</td>
                            <td>{{ $c->incorporation_date ? substr($c->incorporation_date, 0, 4) : '—' }}</td>
                            <td class="num">{{ $inr($c->revenue) }}{!! $chg($g['revenue'] ?? null) !!}</td>
                            <td class="num">{{ $inr($c->ebitda) }}{!! $chg($g['ebitda'] ?? null) !!}</td>
                            <td class="num">{{ $inr($c->profit_after_tax) }}{!! $chg($g['profit_after_tax'] ?? null) !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10">No companies match.</td></tr>
                    @endforelse
                    </tbody>
                @endif
            </table>
        </div>

        @if($premium)
            <div class="ci-pager" style="margin-top:14px">{{ $rows->links() }}</div>
        @elseif($total > $freeRows)
            @php $from = collect($plans)->min(fn ($p) => $p['per_month']); @endphp
            <section class="ex-gate">
                <div class="ex-gate-bar"><span>You have seen <b>{{ $shown }}</b> of {{ number_format($total) }} {{ strtolower($databases[$db]) }}</span><span class="bar"><span style="width:{{ max(1, round($shown / max(1, $total) * 100, 2)) }}%"></span></span><span>A plan opens every row of every search.</span></div>
                <div>
                    <h4>What you unlock in {{ strtolower($databases[$db]) }}</h4>
                    <h3>All {{ number_format($total) }} records, with the detail behind each row</h3>
                    <ul>
                        <li><b>Financials</b><br><small>Revenue, EBITDA and net profit with year-on-year growth</small></li>
                        <li><b>Sector & competitors</b><br><small>Segment, peers and market position</small></li>
                        <li><b>Borrowings, directors & ownership</b><br><small>Charges by lender, board tenure, shareholding</small></li>
                        <li><b>Every row of every search</b><br><small>Filters by sector, state and country without limits</small></li>
                    </ul>
                </div>
                <div>
                    <h4>Premium opens the explore databases</h4>
                    <div class="ex-dbs">@foreach($databases as $key => $label)<a class="{{ $key === $db ? 'on' : '' }}" href="{{ route('insights.explore', $key) }}"><span>{{ $label }}</span><b>{{ number_format($counts[$key] ?? 0) }}</b></a>@endforeach</div>
                </div>
                <div class="ex-price">
                    <span>Plans from</span> <b>₹{{ number_format($from) }}</b> <span>/ month + GST</span>
                    <p style="color:#5b6b82;margin:8px 0 14px">{{ collect($plans)->pluck('name')->implode(' · ') }}</p>
                    <a class="db-btn" href="{{ route('premium.index') }}">See plans</a>
                    <a class="db-btn alt" href="{{ route('contact') }}?subject=Premium%20plan">Talk to us</a>
                </div>
            </section>
        @endif
    </main>
</div>
</div>
@endsection
