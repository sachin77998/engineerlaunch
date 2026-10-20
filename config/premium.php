<?php

/*
 * Premium plans sold on /premium. Prices are the owner's: change them here (or in .env) before going live.
 * GST on online information/database services in India is 18%.
 */
return [
    'gst_rate' => (float) env('PREMIUM_GST_RATE', 18),
    'plans' => [
        'monthly' => ['name' => 'Monthly', 'months' => 1, 'price' => (float) env('PREMIUM_PRICE_MONTHLY', 299)],
        'quarterly' => ['name' => 'Quarterly', 'months' => 3, 'price' => (float) env('PREMIUM_PRICE_QUARTERLY', 799), 'tag' => 'Popular'],
        'yearly' => ['name' => 'Yearly', 'months' => 12, 'price' => (float) env('PREMIUM_PRICE_YEARLY', 2499), 'tag' => 'Best value'],
    ],
    // What Premium unlocks (Insights into Company).
    'features' => [
        'Revenue, EBITDA, profit, net worth, debt and assets for every filing year',
        '15+ financial ratios, standalone and consolidated',
        'Financial health score with risks and strengths',
        'Growth trends, raw-material consumption forecast and payment behaviour charts',
        'Charges & borrowings by lender, directors\' tenure timeline and ownership charts',
        'Peer comparison within the sector',
        'Vendor checks & payment capacity assessment',
        'US-listed companies: annual results from SEC filings',
    ],
    // Razorpay (https://dashboard.razorpay.com → Account & Settings → API keys). Without keys, orders wait for the owner to activate.
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
    ],
];
