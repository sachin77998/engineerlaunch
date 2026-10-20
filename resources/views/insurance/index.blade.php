@extends('layouts.site')
@section('title', $products[$product] . ' — which insurer, which plan and what it costs')
@push('styles')@include('calculators.styles')
<style>
.in-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px}.in-tabs a{padding:9px 16px;border:1px solid #dbe3ef;border-radius:999px;background:#fff;color:#0b2545;font-weight:700;text-decoration:none}
.in-tabs a.on{background:#0b2545;color:#fff;border-color:#0b2545}
.in-form .cx-field select,.in-form .cx-field input[type=number]{width:100%;padding:10px;border:1px solid #d5deea;border-radius:8px;font:inherit;background:#fff}
.in-form .cx-field label{display:block;margin-bottom:6px}.in-check{display:flex;gap:8px;align-items:center;font-weight:600;color:#0b2545}
.in-go{width:100%;padding:12px;border:0;border-radius:10px;background:#f5b800;color:#0b2545;font:800 15px inherit;cursor:pointer}
.in-premium{font-size:32px;font-weight:800;color:#0b2545;margin:4px 0}.in-range{color:#5b6b82}
.in-pick{display:inline-block;padding:2px 8px;border-radius:999px;background:#f5b800;color:#0b2545;font-size:11px;font-weight:800;margin-left:6px}
.in-rec{display:grid;gap:10px;margin:0;padding:0;list-style:none}.in-rec li{padding:12px 14px;border:1px solid #e8edf5;border-radius:10px;background:#f9fbfe}.in-rec b{display:block;color:#0b2545;margin-bottom:3px}
</style>@endpush
@section('content')
<section class="cx-hero">
    <div class="cx-wrap">
        <h1>Insurance</h1>
        <p>Which plan to buy, how much cover you need, what it costs with the full fee structure, and which insurers to consider.</p>
    </div>
</section>
<main class="cx-wrap cx-main">
    <nav class="in-tabs">@foreach($products as $key => $label)<a class="{{ $key === $product ? 'on' : '' }}" href="{{ route('insurance.index', $key) }}">{{ $label }}</a>@endforeach</nav>

    <section class="cx-card">
        <div class="cx-calc">
            <form class="cx-inputs in-form" method="get" action="{{ route('insurance.index', $product) }}">
                @if($product === 'health')
                    <div class="cx-field"><label for="adults">Adults</label><select id="adults" name="adults">@foreach([1, 2] as $n)<option @selected(($in['adults'] ?? 1) == $n)>{{ $n }}</option>@endforeach</select></div>
                    <div class="cx-field"><label for="children">Children</label><select id="children" name="children">@foreach(range(0, 4) as $n)<option @selected(($in['children'] ?? 0) == $n)>{{ $n }}</option>@endforeach</select></div>
                    <div class="cx-field"><label for="age">Age of the eldest member</label><input id="age" type="number" name="age" min="18" max="90" value="{{ $in['age'] ?? 30 }}"></div>
                    <div class="cx-field"><label for="city">City</label><select id="city" name="city">@foreach(['metro' => 'Metro city', 'tier2' => 'Tier-2 city', 'tier3' => 'Tier-3 city / town'] as $v => $l)<option value="{{ $v }}" @selected(($in['city'] ?? 'metro') === $v)>{{ $l }}</option>@endforeach</select></div>
                    <div class="cx-field"><label for="cover">Sum insured</label><select id="cover" name="cover"><option value="0">Recommended for me</option>@foreach([500000 => '₹5 lakh', 1000000 => '₹10 lakh', 1500000 => '₹15 lakh', 2500000 => '₹25 lakh', 5000000 => '₹50 lakh', 10000000 => '₹1 crore'] as $v => $l)<option value="{{ $v }}" @selected(($in['cover'] ?? 0) == $v)>{{ $l }}</option>@endforeach</select></div>
                    <div class="cx-field"><label class="in-check"><input type="checkbox" name="ped" value="1" @checked(!empty($in['ped']))> Someone has a pre-existing illness (diabetes, BP…)</label></div>
                @elseif($product === 'term')
                    <div class="cx-field"><label for="age">Your age</label><input id="age" type="number" name="age" min="18" max="65" value="{{ $in['age'] ?? 30 }}"></div>
                    <div class="cx-field"><label for="gender">Gender</label><select id="gender" name="gender">@foreach(['male' => 'Male', 'female' => 'Female'] as $v => $l)<option value="{{ $v }}" @selected(($in['gender'] ?? 'male') === $v)>{{ $l }}</option>@endforeach</select></div>
                    <div class="cx-field"><label for="income">Annual income (₹)</label><input id="income" type="number" name="income" min="0" step="10000" value="{{ $in['income'] ?? 1000000 }}"></div>
                    <div class="cx-field"><label for="existing">Existing life cover (₹)</label><input id="existing" type="number" name="existing" min="0" step="100000" value="{{ $in['existing'] ?? 0 }}"></div>
                    <div class="cx-field"><label for="till">Cover till age</label><select id="till" name="till">@foreach([60, 65, 70, 75, 85] as $v)<option @selected(($in['till'] ?? 60) == $v)>{{ $v }}</option>@endforeach</select></div>
                    <div class="cx-field"><label for="cover">Cover amount</label><select id="cover" name="cover"><option value="0">Recommended for me</option>@foreach([2500000 => '₹25 lakh', 5000000 => '₹50 lakh', 7500000 => '₹75 lakh', 10000000 => '₹1 crore', 15000000 => '₹1.5 crore', 20000000 => '₹2 crore', 30000000 => '₹3 crore', 50000000 => '₹5 crore'] as $v => $l)<option value="{{ $v }}" @selected(($in['cover'] ?? 0) == $v)>{{ $l }}</option>@endforeach</select></div>
                    <div class="cx-field"><label class="in-check"><input type="checkbox" name="smoker" value="1" @checked(!empty($in['smoker']))> I smoke or use tobacco</label></div>
                @else
                    @php $bands = $product === 'car' ? \App\Services\InsuranceAdvisor::CAR_TP : \App\Services\InsuranceAdvisor::BIKE_TP; @endphp
                    <div class="cx-field"><label for="cc">Engine capacity</label><select id="cc" name="cc">@foreach($bands as $v => $b)<option value="{{ $v }}" @selected(($in['cc'] ?? array_key_first($bands)) == $v)>{{ $b[0] }}</option>@endforeach</select></div>
                    <div class="cx-field"><label for="price">Ex-showroom price (₹)</label><input id="price" type="number" name="price" min="10000" step="1000" value="{{ $in['price'] ?? ($product === 'car' ? 800000 : 100000) }}"></div>
                    <div class="cx-field"><label for="vehicle_age">Vehicle age (years)</label><input id="vehicle_age" type="number" name="vehicle_age" min="0" max="30" step="0.5" value="{{ $in['vehicle_age'] ?? 0 }}"></div>
                    <div class="cx-field"><label for="ncb">No-claim bonus</label><select id="ncb" name="ncb">@foreach([0, 20, 25, 35, 45, 50] as $v)<option value="{{ $v }}" @selected(($in['ncb'] ?? 0) == $v)>{{ $v }}%</option>@endforeach</select></div>
                    <div class="cx-field"><label class="in-check"><input type="checkbox" name="zero_dep" value="1" @checked(!empty($in['zero_dep']))> Zero-depreciation add-on (vehicles up to 5 years)</label></div>
                @endif
                <button class="in-go" type="submit">Show my plan</button>
            </form>
            <div>
                <span class="cx-note" style="margin:0">Estimated yearly premium</span>
                <div class="in-premium">{{ \App\Services\InsuranceAdvisor::inr($total) }}</div>
                <div class="in-range">Typical range {{ \App\Services\InsuranceAdvisor::inr($total * $result['range'][0]) }} – {{ \App\Services\InsuranceAdvisor::inr($total * $result['range'][1]) }} · about {{ \App\Services\InsuranceAdvisor::inr($total / 12) }} a month</div>
                <h3 style="margin:18px 0 6px;color:#0b2545">Fee structure</h3>
                <div class="cx-results" style="margin-top:0">
                    @foreach($result['breakdown'] ?? [['Base premium', $result['premium']]] as [$label, $amount])<div><span>{{ $label }}</span><b>{{ \App\Services\InsuranceAdvisor::inr($amount) }}</b></div>@endforeach
                    <div><span>GST ({{ rtrim(rtrim(number_format($result['gst_rate'], 2), '0'), '.') }}%){{ $result['gst_rate'] == 0 ? ' · individual health and life policies are GST-exempt' : '' }}</span><b>{{ \App\Services\InsuranceAdvisor::inr($gst) }}</b></div>
                    <div><span>Total yearly premium</span><b>{{ \App\Services\InsuranceAdvisor::inr($total) }}</b></div>
                </div>
                <p class="cx-note">{{ in_array($product, ['car', 'bike'], true) ? 'Third-party premium is fixed by IRDAI. Own-damage premium varies by insurer, city and add-ons: compare quotes.' : 'Estimated from typical market premiums for this profile. Each insurer prices differently and medical tests can change the premium: get quotes for an exact figure.' }}</p>
            </div>
        </div>
    </section>

    <section class="cx-card">
        <div class="cx-head"><h2>What to buy</h2></div>
        <ul class="in-rec">@foreach($result['recommendation'] as [$k, $v])<li><b>{{ $k }}</b>{{ $v }}</li>@endforeach</ul>
    </section>

    <section class="cx-card">
        <div class="cx-head"><h2>Insurers for {{ strtolower($products[$product]) }}</h2></div>
        <div class="cx-scroll" style="max-height:none"><table class="cx-table">
            <thead><tr><th>Insurer</th><th>Claim settlement ratio</th><th>Complaints / 10,000 claims</th>@if($product === 'health')<th>Incurred claim ratio</th><th>Network hospitals</th>@endif<th>Solvency</th><th>Figures for</th><th></th></tr></thead>
            <tbody>
            @foreach($result['insurers'] as $i => $insurer)
                <tr>
                    <td>{{ $insurer->name }}@if($ranked && $i < 3 && $insurer->claim_settlement_ratio)<span class="in-pick">Top pick</span>@endif{{ $insurer->is_public_sector ? ' · PSU' : '' }}</td>
                    <td>{{ $insurer->claim_settlement_ratio !== null ? number_format($insurer->claim_settlement_ratio, 2) . '%' : '—' }}</td>
                    <td>{{ $insurer->complaints_per_10k !== null ? number_format($insurer->complaints_per_10k, 1) : '—' }}</td>
                    @if($product === 'health')<td>{{ $insurer->incurred_claim_ratio !== null ? number_format($insurer->incurred_claim_ratio, 1) . '%' : '—' }}</td><td>{{ $insurer->network_hospitals ? number_format($insurer->network_hospitals) : '—' }}</td>@endif
                    <td>{{ $insurer->solvency_ratio !== null ? number_format($insurer->solvency_ratio, 2) : '—' }}</td>
                    <td>{{ $insurer->metrics_year ?? '—' }}</td>
                    <td>@if($insurer->website)<a href="{{ $insurer->website }}" target="_blank" rel="noopener nofollow">Get a quote ›</a>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <p class="cx-note">{{ $ranked ? 'Ranked by claim settlement ratio, then fewest complaints' . ($product === 'health' ? ', then network hospitals' : '') . '. Source: IRDAI annual report and insurers\' public disclosures.' : 'Insurer figures from the IRDAI annual report have not been entered yet, so insurers are listed alphabetically.' }}
            @if(auth()->user()?->isOwner()) <a href="{{ route('admin.insurers') }}">Edit insurer figures</a>@endif</p>
    </section>
</main>
@endsection
