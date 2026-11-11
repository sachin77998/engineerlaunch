<?php

// Pay rent / tuition / maintenance by credit card: the card is charged through Paytm (card-only) and the money is sent to the payee
// through the owner's own Cashfree Payouts account. Nothing is charged unless PAY_RENT_LIVE=true AND both Paytm and Cashfree keys are set —
// turn it on only after Cashfree has approved this use case for your account.
return [
    'live' => (bool) env('PAY_RENT_LIVE', false),
    'fee_pct' => (float) env('PAY_RENT_FEE_PCT', 0), // convenience fee on the card charge, %
    'gst_on_fee_pct' => 18,
    'min' => 100,
    'max' => (int) env('PAY_RENT_MAX', 100000),         // per transfer
    'daily_max' => (int) env('PAY_RENT_DAILY_MAX', 200000), // per user per day, counting paid transfers
    'purposes' => [
        'rent' => ['Rent payment', '🏠', 'Landlord', ['property' => 'Rented property address', 'period' => 'Rent for (month)']],
        'tuition' => ['Tuition fee', '🎓', 'Tutor / institute', ['student' => 'Student name', 'institute' => 'School / coaching / tutor']],
        'maintenance' => ['Society maintenance', '🏢', 'Society', ['property' => 'Flat / unit', 'period' => 'Maintenance for (month)']],
        'deposit' => ['Security deposit', '🔑', 'Landlord', ['property' => 'Rented property address']],
    ],
    'methods' => ['contact' => ['to Contact', '📇'], 'upi' => ['to UPI', '⚡'], 'bank' => ['to Bank Account', '🏦']],
    'statuses' => [
        'draft' => 'Not paid yet', 'not_processed' => 'Not processed — payouts not set up', 'awaiting_payment' => 'Waiting for card payment',
        'paid' => 'Card charged — sending', 'payout_pending' => 'Sending to payee', 'completed' => 'Paid to payee', 'payment_failed' => 'Card payment failed',
        'payout_failed' => 'Transfer failed — refund due', 'refunded' => 'Refunded',
    ],
];
