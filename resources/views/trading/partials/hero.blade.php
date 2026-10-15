<section class="dt-hero">
    <div class="dt-wrap">
        <h1>{{ $title }}</h1>
        @isset($subtitle)<p>{{ $subtitle }}</p>@endisset
        <nav class="dt-nav" aria-label="Do Trading">
            <a href="{{ route('trading.index') }}" class="{{ request()->routeIs('trading.index', 'trading.fund') ? 'on' : '' }}">Mutual funds</a>
            <a href="{{ route('trading.calculators') }}" class="{{ request()->routeIs('trading.calculators') ? 'on' : '' }}">Calculators</a>
            <a href="{{ route('trading.orders') }}" class="{{ request()->routeIs('trading.orders') ? 'on' : '' }}">My orders &amp; holdings</a>
            <a href="{{ route('trading.onboarding') }}" class="{{ request()->routeIs('trading.onboarding') ? 'on' : '' }}">Account opening (KYC)</a>
            @if(auth()->user()?->isOwner())<a href="{{ route('trading.admin.dashboard') }}" class="{{ request()->routeIs('trading.admin.*') ? 'on' : '' }}">Back office</a>@endif
        </nav>
    </div>
</section>
<div class="dt-wrap" style="margin-top:14px;display:grid;gap:8px">
    @if(session('success'))<p class="dt-flash ok">{{ session('success') }}</p>@endif
    @if($errors->any())<p class="dt-flash bad">{{ $errors->first() }}</p>@endif
</div>
