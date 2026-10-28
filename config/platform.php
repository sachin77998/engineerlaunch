<?php

return [
    'name' => env('PLATFORM_NAME', 'EngineerLaunch'),
    'tagline' => env('PLATFORM_TAGLINE', 'Engineering and manufacturing jobs across India and the world — 1 lakh+ companies, industrial areas, salaries, interview prep and company financials in one place.'),
    'logo' => 'images/ascendia-logo.png',
    // Image shown when a page is shared on WhatsApp / LinkedIn / X (1200×630 works best).
    'share_image' => env('PLATFORM_SHARE_IMAGE', 'images/ascendia-logo.png'),
    // Official profiles, listed in search results' knowledge panel (set the ones you create).
    'social' => [
        'linkedin' => env('SOCIAL_LINKEDIN'), 'instagram' => env('SOCIAL_INSTAGRAM'), 'x' => env('SOCIAL_X'),
        'youtube' => env('SOCIAL_YOUTUBE'), 'facebook' => env('SOCIAL_FACEBOOK'),
    ],
    // Search Console / Bing ownership codes (the content="" value of their meta-tag method).
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    'bing_site_verification' => env('BING_SITE_VERIFICATION'),
    // Explanatory "how it works" panels were removed from pages at the owner's request; set true to restore them.
    'show_explainers' => (bool) env('SHOW_PAGE_EXPLAINERS', false),
    'contact' => [
        'email' => env('PLATFORM_CONTACT_EMAIL', 'sachinsoni77998@gmail.com'),
        'phone' => env('PLATFORM_CONTACT_PHONE', '6283570676'),
        'address' => env('PLATFORM_CONTACT_ADDRESS', 'Ludhiana, Punjab, India'),
    ],
    'benefits' => [
        'Discover verified engineering opportunities',
        'Explore companies with synchronized active openings',
        'Learn, practise and prepare for your next role',
    ],
];
