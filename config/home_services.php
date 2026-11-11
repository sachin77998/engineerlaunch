<?php

// Home services: categories, booking slots and packers & movers options. Services and prices live in home_services (/admin/home-services).
return [
    'categories' => [
        'cleaning' => ['Home cleaning', '🧽'],
        'packers' => ['Packers & movers', '🚚'],
        'painting' => ['Painting', '🎨'],
        'repairs' => ['Electrician, plumber & carpenter', '🔧'],
        'appliances' => ['AC & appliance repair', '❄️'],
        'pest' => ['Pest control', '🐜'],
    ],
    // Our other services shown as tiles next to the categories.
    'links' => [
        ['Rental agreement', '📄', 'rent-agreement.index'],
        ['Sales agreement', '✍️', 'legal.show', ['{city}', 'agreement-to-sell']],
        ['Interiors & renovation', '🛋️', 'interiors.index'],
        ['Legal services', '⚖️', 'legal.index'],
        ['Pay rent', '💳', 'pay-rent.index'],
    ],
    // Round quick-pick icons for the cleaning and repair rows on the city page.
    'icons' => [
        'full-home-deep-cleaning' => '🏠', 'kitchen-deep-cleaning' => '🍳', 'bathroom-deep-cleaning' => '🚿', 'sofa-cleaning' => '🛋️', 'water-tank-cleaning' => '💧', 'move-in-cleaning' => '📦',
        'electrician-visit' => '💡', 'fan-installation' => '🌀', 'plumber-visit' => '🔧', 'tap-repair' => '🚰', 'carpenter-visit' => '🪚', 'furniture-assembly' => '🪑',
        'ac-service' => '❄️', 'geyser-repair' => '♨️', 'ro-service' => '🥛', 'washing-machine-repair' => '🧺', 'fridge-repair' => '🧊', 'chimney-cleaning' => '🔥',
    ],
    // Category cards with a "Starting @ ₹" line taken from the lowest live price.
    'featured' => ['cleaning' => ['Deep cleaning', '🧹'], 'painting' => ['Home painting', '🖌️'], 'repairs' => ['Home repairs', '🔨']],
    // "Services we offer" link directory: [label, kind, target]. kind: category (+ optional service slug to pre-add), route (route name taking the city slug), legal (legal service slug).
    'directory' => [
        ['Painting services', 'category', 'painting'], ['Electrician services', 'category', 'repairs', 'electrician-visit'], ['Plumbing services', 'category', 'repairs', 'plumber-visit'],
        ['Carpentry services', 'category', 'repairs', 'carpenter-visit'], ['Cleaning services', 'category', 'cleaning'], ['Home cleaning services', 'category', 'cleaning', 'full-home-deep-cleaning'],
        ['Sofa cleaning services', 'category', 'cleaning', 'sofa-cleaning'], ['Kitchen cleaning services', 'category', 'cleaning', 'kitchen-deep-cleaning'], ['Bathroom cleaning services', 'category', 'cleaning', 'bathroom-deep-cleaning'],
        ['AC repair services', 'category', 'appliances', 'ac-service'], ['Appliance services', 'category', 'appliances'], ['Fridge repair services', 'category', 'appliances', 'fridge-repair'],
        ['Geyser repair', 'category', 'appliances', 'geyser-repair'], ['Water purifier repair', 'category', 'appliances', 'ro-service'], ['Washing machine repair', 'category', 'appliances', 'washing-machine-repair'],
        ['Pest control services', 'category', 'pest'], ['Packers and movers', 'category', 'packers'], ['Interior designers', 'route', 'interiors.index'],
        ['Rental agreement services', 'route', 'rent-agreement.index'], ['Notary services', 'legal', 'notarisation'], ['Affidavit services', 'legal', 'affidavit'], ['Property legal services', 'route', 'legal.index'],
    ],
    'directory_cities' => ['Bengaluru', 'Mumbai', 'Delhi', 'Chennai', 'Hyderabad', 'Pune', 'Gurugram', 'Noida', 'Kolkata', 'Ahmedabad', 'Chandigarh', 'Ludhiana'],
    'faq' => [
        ['How do I book a service?', 'Choose your city, pick a service, add how many you need, choose a date and a 2-hour time slot, and enter your address. You get a booking number at once and our team calls to confirm.'],
        ['Who does the work?', 'Professionals who work with us in your city. Their name is shared in My bookings once one is assigned to you.'],
        ['When do I pay?', 'After the service. Where a price is shown, that is what you pay; items marked "Price after inspection" are quoted by the professional on site before any work starts.'],
        ['Can I reschedule or cancel?', 'You can cancel from My bookings until a professional is assigned. To move the time, tell our team when they call or reply to their message.'],
        ['Which services are available?', 'Home cleaning, packers and movers, painting, electrician, plumber and carpenter visits, AC and appliance repair and pest control — plus rental agreements, interiors and property legal services.'],
        ['What if I am not happy with the work?', 'Leave a review from My bookings after the job is completed, and tell our team when they call so they can follow up.'],
    ],
    'slots' => ['08-10' => '8 – 10 AM', '10-12' => '10 AM – 12 PM', '12-14' => '12 – 2 PM', '14-16' => '2 – 4 PM', '16-18' => '4 – 6 PM', '18-20' => '6 – 8 PM'],
    'days_ahead' => 14,
    'lead_hours' => 2, // a slot must start at least this many hours from now
    'max_qty' => 10,
    'statuses' => ['requested' => 'Requested', 'confirmed' => 'Confirmed', 'assigned' => 'Professional assigned', 'completed' => 'Completed', 'cancelled' => 'Cancelled'],
    'move' => [
        'types' => ['within' => 'Within city', 'intercity' => 'Between cities'],
        'sizes' => ['1rk' => '1 RK', '1bhk' => '1 BHK', '2bhk' => '2 BHK', '3bhk' => '3 BHK', '4bhk' => '4+ BHK', 'few' => 'Few items'],
        'extras' => ['packing' => 'Packing material', 'unpacking' => 'Unpacking', 'dismantle' => 'Furniture dismantle & fix', 'vehicle' => 'Car / bike transport', 'storage' => 'Storage'],
    ],
];
