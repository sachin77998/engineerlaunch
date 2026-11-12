<?php

// Site-visit booking rules. Listings use the owner's visiting days and hours from the posting wizard; projects use the
// schedule set in admin (falling back to the project defaults below).
return [
    'days_ahead' => 14,          // how far ahead visitors can book
    'lead_minutes' => 120,       // earliest bookable slot is this far from now
    'slot_minutes' => 60,
    'all_day' => ['09:00', '19:00'], // "any time" listings are bookable between these hours
    'property_capacity' => 1,    // bookings per slot for a listing
    'project' => ['start' => '10:00', 'end' => '18:00', 'capacity' => 3],
    'max_people' => 6,
    'max_reschedules' => 3,
    'max_open_per_user' => 10,   // upcoming visits a user can hold at once
    'statuses' => [
        'requested' => 'Requested', 'confirmed' => 'Confirmed', 'completed' => 'Visited', 'no_show' => 'Missed', 'cancelled' => 'Cancelled',
    ],
];
