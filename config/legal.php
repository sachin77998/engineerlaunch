<?php

// Property legal services: who they are for, categories, and city-specific naming. Prices and turnaround live on each service row (/admin/legal).
return [
    'default_city' => 'Hoshiarpur',
    'audiences' => ['buyer' => 'Buyer', 'seller' => 'Seller', 'owner' => 'Owner', 'general' => 'General'],
    'categories' => [
        'agreements' => ['Agreements', '🤝'],
        'verification' => ['Property verification', '🏠'],
        'deeds' => ['Transfer deeds', '📜'],
        'registration' => ['Registration & notarisation', '🗂️'],
        'ca' => ['CA services', '🧾'],
        'khata' => ['Khata & mutation', '🏛️'],
        'consult' => ['Lawyer consultation', '⚖️'],
    ],
    // Local name of the municipal ownership record, shown in the khata category title.
    'khata_names' => ['Bengaluru' => 'BBMP Khata', 'Mysuru' => 'Khata', 'Mumbai' => 'Property card', 'Pune' => 'Property card', 'Hoshiarpur' => 'Intkal / mutation', 'Ludhiana' => 'Intkal / mutation', 'Amritsar' => 'Intkal / mutation', 'Jalandhar' => 'Intkal / mutation', 'Patiala' => 'Intkal / mutation', 'Phagwara' => 'Intkal / mutation', 'Pathankot' => 'Intkal / mutation', 'Bathinda' => 'Intkal / mutation', 'Kapurthala' => 'Intkal / mutation'],
    'statuses' => ['requested' => 'Requested', 'confirmed' => 'Confirmed', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
    'cart_max' => 15,
    // FAQ on /legal, grouped by the chips above it. Keep answers true to how bookings work on this site.
    'faq' => [
        'price' => [
            ['How much does a service cost?', 'Where a fixed price is set it is shown on the service card and in your cart. Services marked "Price on call" are quoted after our team understands your case — you pay nothing to place the booking.'],
            ['Are government fees included?', 'No. Stamp duty, registration fees and other government charges are paid separately at actuals; our team tells you the amount before anything is paid.'],
        ],
        'rescheduling' => [
            ['Can I change the date or time?', 'Yes. Pick a preferred time when you book, and to move it just tell the team when they call you or reply to their email.'],
            ['Can I cancel a booking?', 'You can cancel from My bookings while the booking is still in "Requested" status. Once work has started, talk to the team about it.'],
        ],
        'process' => [
            ['What happens after I book?', 'Our legal team calls you on the number you gave to confirm the services, the documents needed and the price. Your booking status in My bookings moves from Requested to Confirmed, In progress and Completed.'],
            ['Which documents do I need?', 'Each service page lists the documents usually needed. Keep copies ready; the team will tell you if anything else is required for your case.'],
        ],
        'service' => [
            ['Who handles my case?', 'Property lawyers and chartered accountants who work with us. Each booking has one point of contact from our team.'],
            ['Can I book more than one service?', 'Yes. Add as many services as you need to the cart and book them together — they are handled as one booking.'],
            ['Is this service available in my city?', 'Choose your city at the top of the page; only services available there are listed.'],
        ],
    ],
];
