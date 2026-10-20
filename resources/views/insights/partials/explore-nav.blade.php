@php
    $user = auth()->user();
    $navCounts = $counts ?? \Illuminate\Support\Facades\Cache::get('explore:counts', []);
    $short = fn ($n) => $n === null ? '' : ($n >= 10000000 ? round($n / 10000000, 1) . 'Cr' : ($n >= 100000 ? round($n / 100000, 1) . 'L' : ($n >= 1000 ? round($n / 1000, 1) . 'K' : $n)));
@endphp
<aside>
    <div class="db-card db-user">
        <span class="db-avatar">{{ $user ? mb_strtoupper(collect(explode(' ', $user->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('')) : '?' }}</span>
        <div>@if($user)<b>{{ $user->name }}</b><small>{{ $user->hasActivePremium() ? 'Premium' . ($user->premium_expires_at ? ' until ' . $user->premium_expires_at->format('d M Y') : '') : 'Standard account' }}</small>
            @else<b>Welcome</b><small><a href="{{ route('login') }}">Sign in</a> for full access</small>@endif</div>
    </div>
    <nav class="db-card db-nav" aria-label="Explore">
        <h2 style="margin-bottom:6px">Explore</h2>
        <a href="{{ route('insights.index') }}" class="{{ request()->routeIs('insights.index') ? 'on' : '' }}"><span>🏠 Dashboard</span></a>
        @foreach(\App\Http\Controllers\InsightsExploreController::DATABASES as $key => $label)
            <a href="{{ route('insights.explore', $key) }}" class="{{ request()->route('db') === $key ? 'on' : '' }}"><span>{{ ['listed' => '🇮🇳', 'companies' => '🏢', 'us' => '🇺🇸', 'people' => '👥'][$key] }} {{ $label }}</span><small>{{ $short($navCounts[$key] ?? null) }}</small></a>
        @endforeach
        <span class="db-sep">Markets</span>
        <a href="{{ route('trading.stocks') }}"><span>📈 Indian markets</span></a>
        <a href="{{ route('markets.us') }}"><span>💵 US markets</span></a>
        <a href="{{ route('trading.index') }}"><span>🪙 Mutual funds</span></a>
        <span class="db-sep">Industries</span>
        <a href="{{ route('sectors.index') }}"><span>🏭 Companies by sector</span></a>
        <a href="{{ url('/industrial-areas') }}"><span>📍 Industrial areas</span></a>
        <span class="db-sep">Tools</span>
        <a href="{{ route('calculators.index') }}"><span>🧮 Calculators</span></a>
        <a href="{{ route('insurance.index') }}"><span>🛡️ Insurance</span></a>
        <a href="{{ route('premium.index') }}"><span>⭐ Plans & pricing</span></a>
    </nav>
</aside>
