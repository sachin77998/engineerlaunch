<?php

// Home Building: construction materials, prices and build-cost calculators.
// Products, prices, stock and bulk tiers are read from the store's public product pages by `materials:sync`
// (sitemap → product page → embedded product data). Lines without a store product use the standard rates below.
return [
    'source' => [
        'name' => 'HomeRun', 'city' => 'Bengaluru',
        'base' => env('MATERIALS_SOURCE_URL', 'https://home-run.co'),
        'delay_ms' => 300,  // pause between product pages
    ],

    // Groups and the store collections in each (same order as the store's menu). A product's category is the first match.
    'groups' => [
        'civil' => 'Civil & Interiors', 'hardware' => 'Furniture & Architectural Hardware', 'electrical' => 'Electrical',
        'plumbing' => 'Plumbing, Sanitary & Bath', 'tools' => 'Tools & Safety',
    ],
    'categories' => [
        'cement' => ['Cement', 'civil', '🧱'],
        'tiling' => ['Tiling & Adhesives', 'civil', '🔲'],
        'plywood-mdf-hdhmr' => ['Plywood, MDF & HDHMR', 'civil', '🪵'],
        'fevicol' => ['Fevicol & Adhesives', 'civil', '🧴'],
        'waterproofing' => ['Waterproofing', 'civil', '💧'],
        'painting' => ['Painting', 'civil', '🎨'],
        'putty-primer' => ['Putty & Primer', 'civil', '🪣'],
        'gypsum-drywall-false-ceiling' => ['Gypsum & False Ceiling', 'civil', '⬜'],
        'sand-plaster-mortars' => ['Sand, Plaster & Mortars', 'civil', '⛏️'],
        'hinges-channels-handles' => ['Hinges, Channels & Handles', 'hardware', '🔩'],
        'kitchen-systems-accessories' => ['Kitchen Systems', 'hardware', '🍳'],
        'wardrobe-bed-fittings' => ['Wardrobe & Bed Fittings', 'hardware', '🚪'],
        'door-locks-hardware' => ['Door Locks & Hardware', 'hardware', '🔐'],
        'glass-hardware' => ['Glass Door Hardware', 'hardware', '🪟'],
        'general-hardware-tools' => ['General Hardware', 'hardware', '🧰'],
        'wires' => ['Wires & Cables', 'electrical', '🔌'],
        'switches-sockets' => ['Switches & Sockets', 'electrical', '🎛️'],
        'db-mcb-protection' => ['MCB & Distribution Boards', 'electrical', '⚡'],
        'electrical-conduit-pipes' => ['Electrical Conduits', 'electrical', '➰'],
        'lighting' => ['Lighting', 'electrical', '💡'],
        'ceilingfans-exhaust' => ['Fans & Exhaust', 'electrical', '🌀'],
        'home-appliances-power-backup' => ['Appliances & Power Backup', 'electrical', '🔋'],
        'cctv-surveillance' => ['CCTV & Surveillance', 'electrical', '📹'],
        'plumbing' => ['Plumbing & Pipes', 'plumbing', '🚰'],
        'sanitary-bath-fittings' => ['Sanitary & Bath Fittings', 'plumbing', '🛁'],
        'kitchen-sinks-faucets' => ['Kitchen Sinks & Faucets', 'plumbing', '🚿'],
        'overhead-tanks' => ['Overhead Tanks', 'plumbing', '🛢️'],
        'power-tools-accessories' => ['Power Tools', 'tools', '🛠️'],
        'safety-tools-workwear' => ['Safety & Workwear', 'tools', '🦺'],
        'welding-consumables' => ['Welding', 'tools', '🔥'],
    ],

    // Store-side collections that are not categories (search indexes, tax rules, promotions, carousels).
    'hidden_collections' => ['tax-override', 'globofilter', 'home-page', 'add-ons', 'non-2-w', 'sale', 'bulk-prices', 'copy', 'cloud-search', 'avada',
        'wizzy', 'best-selling', 'newest-products', 'deals-of-the-week', 'new-launches', 'carousel', 'clearance', 'use-code', 'collection', 'price'],

    // The store: the site sells these products itself. Prices include GST. Delivery and offers are the owner's rules.
    'store' => [
        'name' => env('MATERIALS_STORE_NAME', 'Home Building Store'),
        'delivery' => [
            // [order value from, fee]: the first matching slab from the top applies.
            'slabs' => [[10000, 0], [5000, 99], [500, 199], [0, 299]],
            'express_pins' => [],         // PIN codes with fast delivery; empty = use the catalogue's 60-minute list
            'express_label' => '60 Mins',
            'serviceable_prefixes' => [], // PIN code prefixes you deliver to, e.g. ['560', '562']; empty = everywhere
            'slots' => ['Within 60 minutes', 'Today 2 PM – 6 PM', 'Today 6 PM – 9 PM', 'Tomorrow 8 AM – 12 PM', 'Tomorrow 12 PM – 4 PM'],
        ],
        'offer' => ['min_order' => 50000, 'percent' => 5],  // instant discount on large orders
        'cod' => ['enabled' => true, 'max_order' => 50000],
        'cancel_minutes' => 10,
        'max_qty' => 1000,
    ],

    // Construction cost calculator. qty = quantity per sq ft of built-up area. A "live" line is priced from store products
    // (basic / medium / premium = 25th / 50th / 85th percentile of their unit prices); "rates" are used when it has none.
    // local = priced in the city (labour, sand, aggregate, bricks), so the city's factor applies.
    'calculator' => [
        'cement' => ['Cement', 0.4, 'bag', ['live' => ['collections' => ['cement'], 'type' => ['ppc', 'opc'], 'per' => 'pack'], 'rates' => [380, 420, 470]]],
        'steel' => ['TMT steel', 4.0, 'kg', ['rates' => [62, 68, 78]]],
        'bricks' => ['Bricks / blocks', 8.0, 'nos', ['rates' => [8, 10, 13], 'local' => true]],
        'sand' => ['Sand (M-sand / P-sand)', 1.8, 'cft', ['rates' => [55, 65, 80], 'local' => true]],
        'aggregate' => ['Aggregate (20 mm)', 1.35, 'cft', ['rates' => [45, 52, 60], 'local' => true]],
        'tiles' => ['Tiles & flooring', 1.3, 'sq ft', ['rates' => [45, 85, 160]]],
        'tile_adhesive' => ['Tile adhesive', 0.45, 'kg', ['live' => ['collections' => ['tiling'], 'type' => 'adhesive', 'title' => 'tile', 'per' => 'kg'], 'rates' => [12, 16, 24]]],
        'putty' => ['Wall putty', 0.29, 'kg', ['live' => ['collections' => ['putty-primer', 'painting'], 'type' => 'putty', 'per' => 'kg'], 'rates' => [18, 22, 30]]],
        'primer' => ['Wall primer', 0.035, 'L', ['live' => ['collections' => ['putty-primer', 'painting'], 'type' => 'primer', 'title' => 'wall', 'per' => 'L'], 'rates' => [150, 220, 320]]],
        'paint' => ['Paint (emulsion, 2 coats)', 0.054, 'L', ['live' => ['collections' => ['painting'], 'type' => 'emulsion', 'per' => 'L'], 'rates' => [180, 320, 580]]],
        'waterproofing' => ['Waterproofing', 0.03, 'L', ['live' => ['collections' => ['waterproofing'], 'type' => 'coating', 'per' => 'L'], 'rates' => [220, 300, 420]]],
        'wires' => ['Electrical wires (1.5 sq mm)', 1.8, 'm', ['live' => ['collections' => ['wires'], 'type' => 'wire', 'variant' => '1.5 Sqmm', 'per' => 'm'], 'rates' => [22, 28, 36]]],
        'electrical' => ['Switches, MCB & fittings', 1, 'sq ft', ['rates' => [70, 110, 180]]],
        'plumbing' => ['Plumbing & sanitary', 1, 'sq ft', ['rates' => [90, 150, 260]]],
        'doors' => ['Doors', 0.01, 'nos', ['rates' => [9000, 16000, 30000]]],
        'windows' => ['Windows & grills', 0.12, 'sq ft', ['rates' => [450, 750, 1200]]],
        'kitchen' => ['Kitchen', 0.001, 'nos', ['rates' => [60000, 150000, 350000], 'min' => 1]],
        'labour' => ['Labour', 1, 'sq ft', ['rates' => [280, 350, 450], 'local' => true]],
    ],
    // City factor for local lines (labour, sand, aggregate, bricks). Cities not listed use "other".
    'city_tiers' => [
        'metro' => [1.0, ['Bengaluru', 'Mumbai', 'Navi Mumbai', 'Thane', 'Delhi', 'New Delhi', 'Gurugram', 'Noida', 'Greater Noida', 'Ghaziabad', 'Faridabad', 'Chennai', 'Hyderabad', 'Kolkata', 'Pune']],
        'tier2' => [0.92, ['Ahmedabad', 'Surat', 'Jaipur', 'Lucknow', 'Kanpur', 'Nagpur', 'Nashik', 'Indore', 'Bhopal', 'Chandigarh', 'Mohali', 'Panchkula', 'Ludhiana', 'Amritsar', 'Jalandhar', 'Kochi', 'Coimbatore', 'Mysuru', 'Mangaluru', 'Hubballi', 'Visakhapatnam', 'Vijayawada', 'Warangal', 'Bhubaneswar', 'Patna', 'Dehradun', 'Vadodara', 'Rajkot', 'Madurai', 'Thiruvananthapuram', 'Guwahati', 'Raipur', 'Ranchi']],
        'other' => [0.86, []],
    ],
    'tiers' => ['basic' => 'Basic', 'medium' => 'Medium', 'premium' => 'Premium'],

    // Room estimators. Coverage figures are per coat unless noted.
    'estimators' => [
        'paint' => ['Paint estimator', '🎨', 'Paint, putty and primer for a room or a whole house, in the cheapest packs.'],
        'tiles' => ['Tile estimator', '🔲', 'Tiles, boxes, adhesive and grout for a floor or wall.'],
        'waterproofing' => ['Waterproofing estimator', '💧', 'Coating for a terrace, bathroom or wall, in the cheapest packs.'],
    ],
    'paint' => ['coverage_sqft_per_l' => 120, 'putty_sqft_per_kg' => 12, 'primer_sqft_per_l' => 100, 'door_sqft' => 21, 'window_sqft' => 15],
    'tiles' => [
        'sizes' => ['300x300' => [300, 300, 10], '600x600' => [600, 600, 4], '600x1200' => [600, 1200, 2], '800x800' => [800, 800, 3], '800x1600' => [800, 1600, 2], '300x600' => [300, 600, 8]],
        'adhesive_kg_per_sqft' => 0.4, 'grout_kg_per_sqft' => 0.05,
    ],
    'waterproofing' => ['surfaces' => ['terrace' => ['Terrace / roof', 2, 0.03], 'bathroom' => ['Bathroom floor & walls', 2, 0.025], 'wall' => ['External wall', 2, 0.015]]], // [label, coats, L per sq ft per coat]
];
