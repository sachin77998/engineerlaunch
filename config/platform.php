<?php

return [
    'name' => env('PLATFORM_NAME', 'EngineerLaunch'),
    'tagline' => 'Discover. Learn. Practise. Apply.',
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
