@include('properties._styles')
<div class="pr-sub"><div class="pr-wrap">
    <a class="pr-brand" href="{{ route('properties.index') }}"><i>⌂</i>{{ config('properties.brand') }}</a>
    <nav aria-label="Property">
        @foreach(['buy' => 'Buy', 'rent' => 'Rent', 'commercial-rent' => 'Commercial'] as $m => $label)
            <a href="{{ route('properties.search', $m) }}" class="{{ request()->route('mode') === $m || ($m === 'commercial-rent' && request()->route('mode') === 'commercial-buy') ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
        <a href="{{ route('real-estate.index') }}" class="{{ request()->routeIs('real-estate.*') ? 'on' : '' }}">Cities</a>
        <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*', 'builders.*') ? 'on' : '' }}">Projects</a>
        <a href="{{ route('home-loan.index') }}" class="{{ request()->routeIs('home-loan.*') ? 'on' : '' }}">Home loan</a>
        <a href="{{ route('legal.index') }}" class="{{ request()->routeIs('legal.*') ? 'on' : '' }}">Legal services</a>
        <a href="{{ route('rent-agreement.index') }}" class="{{ request()->routeIs('rent-agreement.*') ? 'on' : '' }}">Rent agreement</a>
        <a href="{{ route('pay-rent.index') }}" class="{{ request()->routeIs('pay-rent.*') ? 'on' : '' }}">Pay rent</a>
        <a href="{{ route('interiors.index') }}" class="{{ request()->routeIs('interiors.*') ? 'on' : '' }}">Interiors</a>
        <a href="{{ route('home-services.index') }}" class="{{ request()->routeIs('home-services.*') ? 'on' : '' }}">Home services</a>
        @auth
            <a href="{{ route('properties.shortlist') }}" class="{{ request()->routeIs('properties.shortlist') ? 'on' : '' }}">♡ Shortlist</a>
            <a href="{{ route('saved-searches.index') }}" class="{{ request()->routeIs('saved-searches.*') ? 'on' : '' }}">🔔 My searches</a>
            <a href="{{ route('site-visits.index') }}" class="{{ request()->routeIs('site-visits.*') ? 'on' : '' }}">📅 My visits</a>
            <a href="{{ route('properties.mine') }}" class="{{ request()->routeIs('properties.mine', 'properties.step') ? 'on' : '' }}">My properties</a>
        @endauth
    </nav>
    <a class="pr-post" href="{{ route('properties.post') }}">Post free property ad</a>
</div></div>
