<?php

// Refer & Earn: users share a property owner's contact (with the owner's OK) or a photo of a TO-LET / FOR SALE board.
// The owner reviews each one; verified referrals and owners who then list on the site earn rewards, which users withdraw to UPI.
// Reward amounts are the site owner's own — 0 hides the amount on the page.
return [
    'rewards' => [
        'verified_contact' => (int) env('REFER_REWARD_CONTACT', 0), // owner reachable and genuinely renting / selling
        'verified_photo' => (int) env('REFER_REWARD_PHOTO', 0),     // board photo with a usable number
        'listed' => (int) env('REFER_REWARD_LISTED', 0),            // bonus when that owner lists the property on the site
    ],
    'min_withdrawal' => (int) env('REFER_MIN_WITHDRAWAL', 100),
    'daily_limit' => 20,          // submissions per user per day
    'duplicate_days' => 180,      // the same owner number can't be referred again within this window
    'photo_max_kb' => 8192,
    'property_types' => ['flat' => 'Flat / apartment', 'house' => 'Independent house / villa', 'floor' => 'Builder floor', 'plot' => 'Plot / land', 'pg' => 'PG / hostel', 'commercial' => 'Shop / office / commercial', 'other' => 'Other'],
    'listings' => ['rent' => 'For rent', 'sale' => 'For sale'],
    'statuses' => ['pending' => 'Under review', 'verified' => 'Verified', 'listed' => 'Owner listed', 'rejected' => 'Rejected', 'duplicate' => 'Already referred'],
    'reject_reasons' => ['unreachable' => 'Owner could not be reached', 'not_available' => 'Property is not available', 'broker' => 'Number belongs to a broker / agent', 'no_consent' => 'Owner did not agree to be contacted', 'unreadable' => 'Number on the photo is not readable', 'wrong' => 'Details were incorrect'],
    'withdrawal_statuses' => ['requested' => 'Requested', 'paid' => 'Paid', 'rejected' => 'Rejected'],
    'faq' => [
        ['My referral was rejected. Why?', 'Each referral is checked by calling the owner. It is rejected if the owner cannot be reached, the property is not available, the number belongs to a broker, the owner did not agree to be contacted, or the number on a photo cannot be read. The reason is shown next to the referral.'],
        ['How will I get the reward money?', 'Rewards are added to your Refer & Earn balance when a referral is verified, and again if the owner lists the property with us. Once your balance reaches the minimum, request a withdrawal to your UPI ID.'],
        ['Can I get the reward directly in my account?', 'Yes — withdrawals are paid to the UPI ID you enter, usually within a few working days of the request.'],
        ['I submitted a referral but have not received the reward.', 'Rewards are credited only after our team verifies the owner. Check the status in My referrals: "Under review" means it has not been checked yet; "Already referred" means someone shared that owner before you.'],
        ['Can I refer an owner who has already listed here?', 'No. Owners already listed on the site, and numbers referred by someone else in the last 6 months, are marked "Already referred".'],
    ],
];
