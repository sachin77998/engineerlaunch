<?php

/*
 * Category cards on "Companies by Sector". Each card groups catalog sectors (all their sub-categories) or chosen
 * sub-categories: [catalog sector name, null] or [catalog sector name, [sub-category names]].
 * A sub-category may appear in more than one card. Catalog sectors that no card mentions get a card automatically,
 * so new sectors added to resources/data/sector-catalog show up without editing this file.
 */
return [
    'groups' => [
        'automobile' => ['name' => 'Automobile & Ancillaries', 'icon' => '🚗', 'color' => '#0b2545', 'image' => 'images/industries/automobile-hero.png',
            'members' => [['Automobile Manufacturers', ['Passenger Cars', 'Luxury & Global Car Brands', 'Commercial Vehicles & Buses', 'Tractors & Farm Machinery']], ['Auto Components, Forging & Casting', null]]],
        'clothing-apparel' => ['name' => 'Clothing & Apparel', 'icon' => '👔', 'color' => '#7c2d12', 'image' => 'images/industries/textiles-hero.png',
            'members' => [['Apparel, Textiles & Footwear', null]]],
        'it-services' => ['name' => 'IT Services & Consulting', 'icon' => '💻', 'color' => '#1d4ed8',
            'members' => [['IT Services & Software', null]]],
        'food-fmcg' => ['name' => 'Food & FMCG Products', 'icon' => '🍪', 'color' => '#b45309',
            'members' => [['Food & Beverages', null], ['FMCG - Personal & Home Care', null]]],
        'ac-appliances' => ['name' => 'AC, Cooling & Home Appliances', 'icon' => '❄️', 'color' => '#0369a1',
            'members' => [['Electrical Equipment & Appliances', ['Consumer Appliances & Air Conditioning', 'Fans & Lighting']]]],
        'hotels-tourism' => ['name' => 'Hotels, Tourism & Hospitality', 'icon' => '🏨', 'color' => '#9d174d',
            'members' => [['Restaurants, Hotels & Travel', null]]],
        'refineries' => ['name' => 'Refineries, Oil & Gas', 'icon' => '🛢️', 'color' => '#334155',
            'members' => [['Oil, Gas & Energy', ['Oil Refining & Marketing', 'Natural Gas & City Gas']]]],
        'green-energy' => ['name' => 'Green Energy', 'icon' => '🌱', 'color' => '#15803d',
            'members' => [['Oil, Gas & Energy', ['Renewable Power', 'Solar Panel Manufacturing', 'Batteries & Energy Storage']]]],
        'power-utilities' => ['name' => 'Power & Utilities', 'icon' => '🔋', 'color' => '#b45309',
            'members' => [['Oil, Gas & Energy', ['Power Generation & Utilities', 'Renewable Power']]]],
        'education' => ['name' => 'Education & Training', 'icon' => '🎓', 'color' => '#6d28d9',
            'members' => [['Education & Training', null]]],
        'banking-finance' => ['name' => 'Banking, Finance & Insurance', 'icon' => '🏦', 'color' => '#0f766e',
            'members' => [['Banking & Financial Services', ['Public Sector & Government Banks', 'Indian Banks', 'Global Banks in India', 'United States Banks', 'Swiss Banks', 'UK & Scotland Banks', 'NBFC, Lending & Housing Finance']], ['Insurance', null]]],
        'payments' => ['name' => 'Payment Systems & Fintech', 'icon' => '💳', 'color' => '#4338ca',
            'members' => [['Banking & Financial Services', ['Payments, UPI & Cards', 'Fintech, Broking & Market Infrastructure']]]],
        'mobiles-laptops' => ['name' => 'Mobiles, Chargers & Laptops', 'icon' => '📱', 'color' => '#111827', 'image' => 'images/industries/electronics-hero.png',
            'members' => [['Consumer Electronics & Devices', ['Mobile Phones', 'Chargers, Cables & Accessories', 'Laptops & Computers']]]],
        'watches-jewellery' => ['name' => 'Watches & Jewellery', 'icon' => '⌚', 'color' => '#a16207',
            'members' => [['Consumer Electronics & Devices', ['Watches & Wearables', 'Jewellery']]]],
        'bikes' => ['name' => 'Bikes & Two-Wheelers', 'icon' => '🏍️', 'color' => '#b91c1c', 'image' => 'images/industries/tyres-hero.png',
            'members' => [['Automobile Manufacturers', ['Two-Wheelers & Three-Wheelers']]]],
        'semiconductors' => ['name' => 'Semiconductors & Electronics', 'icon' => '🔌', 'color' => '#0e7490', 'image' => 'images/industries/electronics-hero.png',
            'members' => [['Semiconductors & Electronics', null]]],
        'bottles-packaging' => ['name' => 'Water Bottles, Glass & Packaging', 'icon' => '🍶', 'color' => '#0284c7',
            'members' => [['Cement, Building Materials & Polymers', ['Water Bottles & Containers', 'Glass & Glassware', 'Paper, Pulp & Packaging']]]],
        'electrical' => ['name' => 'Wires, Cables & Electrical Equipment', 'icon' => '⚡', 'color' => '#ca8a04',
            'members' => [['Electrical Equipment & Appliances', ['Electrical Equipment, Switches & Cables', 'Wires & Cables', 'Transformers & Switchgear']]]],
        'building-materials' => ['name' => 'Cement, Paint, Wood & Building Materials', 'icon' => '🏗️', 'color' => '#57534e',
            'members' => [['Cement, Building Materials & Polymers', ['Cement', 'Tiles & Ceramics', 'Sanitaryware & Bath Fittings', 'Paints & Coatings', 'Paints, Panels & Construction Chemicals', 'Wallpaper & Wall Decor', 'Wood, Plywood & Furniture', 'Plastic Pipes & Water Tanks', 'Polymers & Petrochemicals']]]],
        'steel-metals' => ['name' => 'Steel, Metals & Pipes', 'icon' => '🔩', 'color' => '#475569', 'image' => 'images/industries/steel/steel1.jpeg',
            'members' => [['Steel, Metals & Pipes', null]]],
        'pharma' => ['name' => 'Pharma & Healthcare', 'icon' => '💊', 'color' => '#0891b2', 'image' => 'images/industries/pharmaceuticals-hero.png',
            'members' => [['Pharmaceuticals & Healthcare', null]]],
        'psu-government' => ['name' => 'PSU & Government Companies', 'icon' => '🏛️', 'color' => '#7f1d1d',
            'members' => [['Public Sector (PSU) & Government', null]]],
        'logistics' => ['name' => 'Logistics & Supply Chain', 'icon' => '🚚', 'color' => '#9a3412', 'image' => 'images/industries/warehouse-logistics-hero.png',
            'members' => [['Logistics & Supply Chain', null]]],
    ],

    // Sub-category photos, matched by keyword in the sub-category name.
    'images' => [
        'gear' => 'images/industries/gear/gear1.jpeg', 'axle' => 'images/industries/axle/axle1.jpeg', 'forging' => 'images/industries/forging/forging1.jpeg',
        'casting' => 'images/industries/casting/casting1.jpeg', 'brake' => 'images/industries/brake/brake1.jpeg', 'steel' => 'images/industries/steel/steel2.jpeg',
        'pipes' => 'images/industries/steel/steel3.jpeg', 'aluminium' => 'images/industries/steel/steel3.jpeg', 'passenger car' => 'images/industries/automobile/hero.png',
        'textile' => 'images/industries/textiles/hero.png', 'denim' => 'images/industries/textiles-hero.png', 'pharma' => 'images/industries/pharma/hero.png',
        'electronics' => 'images/industries/electronics/hero.png', 'semiconductor' => 'images/industries/electronics-hero.png', 'courier' => 'images/industries/logistics/hero.png',
        'tyre' => 'images/industries/tyres/hero.png', 'two-wheeler' => 'images/industries/tyres-hero.png', 'machine' => 'images/industries/cnc-machining-hero.jpeg',
        'heat treatment' => 'images/industries/forging/forging2.jpeg', 'material procurement' => 'images/industries/steel/steel1.jpeg', 'component' => 'images/industries/auto-components-hero.jpeg',
    ],
];
