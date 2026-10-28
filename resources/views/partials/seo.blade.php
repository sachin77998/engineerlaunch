@php
    // Search and social-share tags for every page. A page can set: @section('meta_description'), @section('og_image'),
    // @section('robots') (e.g. "noindex"), @section('canonical'); JSON-LD blocks go in @push('jsonld').
    $brand = config('platform.name');
    $title = trim($__env->yieldContent('title')) ?: $brand;
    $fullTitle = str_contains($title, $brand) ? $title : $title . ' | ' . $brand;
    $desc = trim($__env->yieldContent('meta_description')) ?: config('platform.tagline');
    $desc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($desc)))), 158, '…');
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
    $image = trim($__env->yieldContent('og_image')) ?: asset(config('platform.share_image'));
    // Private and personal areas never belong in search results.
    $private = request()->is('admin*', 'employer*', 'dashboard*', 'profile*', 'joining/*', 'offer/*', 'hiring/*', 'verify-otp*', 'reset-password*', 'trading/admin*', 'trading/onboarding*');
    $robots = trim($__env->yieldContent('robots')) ?: ($private || request()->query('page') > 20 ? 'noindex, nofollow' : 'index, follow, max-image-preview:large');
@endphp
<meta name="description" content="{{ $desc }}">
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:site_name" content="{{ $brand }}">
<meta property="og:type" content="{{ trim($__env->yieldContent('og_type')) ?: 'website' }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:locale" content="en_IN">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $desc }}">
<meta name="twitter:image" content="{{ $image }}">
<meta name="theme-color" content="#1d4ed8">
@if(config('platform.google_site_verification'))<meta name="google-site-verification" content="{{ config('platform.google_site_verification') }}">@endif
@if(config('platform.bing_site_verification'))<meta name="msvalidate.01" content="{{ config('platform.bing_site_verification') }}">@endif
@if(request()->routeIs('home'))
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        ['@type' => 'Organization', '@id' => url('/') . '#org', 'name' => $brand, 'url' => url('/'), 'logo' => asset(config('platform.logo')),
            'sameAs' => array_values(array_filter(config('platform.social', [])))],
        ['@type' => 'WebSite', '@id' => url('/') . '#site', 'name' => $brand, 'url' => url('/'), 'publisher' => ['@id' => url('/') . '#org']],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endif
@stack('jsonld')
