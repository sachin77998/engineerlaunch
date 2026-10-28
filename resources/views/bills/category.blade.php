@extends('layouts.site')
@section('title', 'Pay ' . $cat['name'] . ' online' . ($state ? ' — ' . $state : ''))
@section('meta_description', 'Pay your ' . mb_strtolower($cat['name']) . ' on the official site: ' . collect($cat['billers'])->pluck('name')->take(6)->implode(', ') . ' and more.')
@section('content')
@include('bills._nav')
<div class="container bp-wrap">
    <div class="bp-two">
        <section class="bp-card" id="pay">
            <h1>Pay {{ $cat['name'] }}</h1>
            <form id="bp-form" onsubmit="return false">
                @php($label = ['mobile' => 'Operator', 'dth' => 'DTH operator', 'broadband' => 'Provider', 'electricity' => 'Electricity board', 'water' => 'Water board', 'gas' => 'Gas provider', 'cylinder' => 'Gas agency', 'loan' => 'Lender', 'insurance' => 'Insurer', 'creditcard' => 'Card issuer', 'municipal' => 'Municipal body', 'fastag' => 'FASTag issuer', 'challan' => 'Challan authority'][$key] ?? 'Biller')
                @php($allStates = collect($cat['billers'])->pluck('state')->unique()->sort()->values())
                @if($allStates->count() > 1)
                    <label class="bp-muted" for="bp-state">State</label>
                    <select id="bp-state" class="bp-in" style="font-weight:700">
                        @foreach($allStates as $s)<option value="{{ $s }}" @selected(($selected['state'] ?? null) === $s)>{{ $s }}</option>@endforeach
                    </select>
                    <div style="height:18px"></div>
                @endif
                <label class="bp-muted" for="bp-biller">{{ $label }}</label>
                <select id="bp-biller" class="bp-in" style="font-weight:700">
                    @foreach($cat['billers'] as $b)<option value="{{ $b['url'] }}" data-id="{{ $b['id'] }}" data-slug="{{ $b['slug'] }}" data-state="{{ $b['state'] }}" @selected($selected && $selected['slug'] === $b['slug'])>{{ $b['name'] }}</option>@endforeach
                </select>
                <div style="height:18px"></div>
                <label class="bp-muted" for="bp-id" id="bp-id-label">{{ $selected['id'] ?? $cat['id'] }}</label>
                <input id="bp-id" class="bp-in" autocomplete="off" maxlength="30" placeholder="{{ $selected['id'] ?? $cat['id'] }}" inputmode="{{ in_array($key, ['mobile'], true) ? 'numeric' : 'text' }}">
                <div class="bp-muted" id="bp-err" style="color:#b42318;min-height:18px;margin-top:6px"></div>
                <button type="button" class="bp-btn" id="bp-go">Proceed</button>
            </form>
            <div class="bp-note">You pay on the biller's official website — keep your {{ mb_strtolower($cat['id']) }} handy (it is copied for you). We never see your payment details.</div>
        </section>
        <section class="bp-card">
            <h2>{{ $cat['name'] }} billers</h2>
            @if($states->count() > 1)
            <div class="bp-chips"><a href="{{ route('bills.category', $key) }}" class="{{ $state ? '' : 'on' }}">All</a>@foreach($states as $s)<a href="{{ route('bills.category', [$key, 'state' => $s]) }}" class="{{ $state === $s ? 'on' : '' }}">{{ $s }}</a>@endforeach</div>
            @endif
            <div class="bp-grid">
                @foreach($billers as $b)@php([$ini, $bg] = \App\Support\Monogram::for($b['name']))
                    <a class="bp-tile" href="{{ route('bills.category', [$key, 'state' => $state, 'biller' => $b['slug']]) }}#pay"><span class="bp-mono" style="background:{{ $bg }}">{{ $ini }}</span>{{ $b['name'] }}<span class="bp-muted">{{ $b['state'] }}</span></a>
                @endforeach
            </div>
        </section>
    </div>
</div>
@push('scripts')<script>
(function () {
    var sel = document.getElementById('bp-biller'), id = document.getElementById('bp-id'), label = document.getElementById('bp-id-label'), err = document.getElementById('bp-err');
    var st = document.getElementById('bp-state');
    function syncId() { var o = sel.selectedOptions[0]; if (!o) return; label.textContent = o.dataset.id; id.placeholder = o.dataset.id; err.textContent = ''; }
    // State first: only that state's boards are offered (All India providers are offered everywhere).
    function syncState() {
        if (!st) return;
        var first = null;
        [].forEach.call(sel.options, function (o) { var show = o.dataset.state === st.value || o.dataset.state === 'All India'; o.hidden = !show; o.disabled = !show; if (show && !first) first = o; });
        if (sel.selectedOptions[0] && sel.selectedOptions[0].disabled && first) first.selected = true;
        syncId();
    }
    sel.addEventListener('change', syncId);
    if (st) { st.addEventListener('change', syncState); syncState(); }
    document.getElementById('bp-go').addEventListener('click', function () {
        var v = id.value.trim();
        if (v && !/^[A-Za-z0-9\/ -]{3,30}$/.test(v)) { err.textContent = 'Use letters, numbers, spaces, - or / only.'; return; }
        // The ID is copied so it can be pasted on the official page, which opens in a new tab.
        if (v && navigator.clipboard) navigator.clipboard.writeText(v).catch(function () {});
        window.open(sel.value, '_blank', 'noopener');
        err.style.color = '#087443'; err.textContent = v ? 'Opened the official site — your ID is copied, paste it there.' : 'Opened the official site.';
    });
})();
</script>@endpush
@endsection
