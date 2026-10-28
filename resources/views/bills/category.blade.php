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
                <label class="bp-muted" for="bp-biller">{{ $key === 'mobile' || $key === 'dth' || $key === 'broadband' ? 'Operator' : ($key === 'municipal' ? 'Municipal body' : 'Biller') }}</label>
                <select id="bp-biller" class="bp-in" style="font-weight:700">
                    @foreach($billers as $b)<option value="{{ $b['url'] }}" data-id="{{ $b['id'] }}" data-slug="{{ $b['slug'] }}" @selected($selected && $selected['slug'] === $b['slug'])>{{ $b['name'] }}{{ $b['state'] !== 'All India' ? ' — ' . $b['state'] : '' }}</option>@endforeach
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
    sel.addEventListener('change', function () { var o = sel.selectedOptions[0]; label.textContent = o.dataset.id; id.placeholder = o.dataset.id; err.textContent = ''; });
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
