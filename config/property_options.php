<?php

// Registry of database-backed option lists. Each group gets its own table (popt_<group with dots as underscores>),
// seeded from config/properties.php (or 'defaults' here) and edited at /admin/property-options.
// At boot the rows are cached in Redis and written back into config('properties.<group>'), so code reading config keeps working.
//
// shape:  label → key => label        list → key => [label, ...extra]   (extra: icon, factor, hint, plural, nested keys…)
// To add a list: add a group here and run `php artisan property-options:sync` — no other code changes.
return [
    'cache_key' => 'property-options:v1',
    'groups' => [
        // Listing basics
        'kinds.residential' => ['Residential kinds', 'label'],
        'kinds.commercial' => ['Commercial kinds', 'label'],
        'building_types.residential' => ['Residential building types', 'label'],
        'building_types.commercial' => ['Commercial building types', 'label'],
        'home_types' => ['Home types (search filter)', 'list'],
        'bhk' => ['BHK', 'label'],
        'bhk_search' => ['BHK chips (search box)', 'label'],
        'bhk_filter' => ['BHK chips (results filter)', 'label'],
        'posted_by' => ['Posted by', 'label'],
        'owner_types' => ['Owner types', 'label'],
        'area_units' => ['Area units (label, sq ft factor)', 'list'],
        'ages' => ['Property age', 'label'],
        'furnishing' => ['Furnishing', 'label'],
        'facing' => ['Facing', 'label'],
        'flooring' => ['Flooring', 'label'],
        'possession' => ['Possession status', 'label'],
        'ownership' => ['Ownership', 'label'],
        'preferred_tenants' => ['Preferred tenants', 'label'],
        'lease_years' => ['Lease years', 'label'],
        'lockin_years' => ['Lock-in years', 'label'],
        'furnishings' => ['Furnishing items', 'label'],
        'highlights' => ['Key highlights', 'label'],
        'amenities' => ['Amenities', 'label'],
        'power_backup' => ['Power backup', 'label'],
        'water_supply' => ['Water supply', 'label'],
        'parking' => ['Parking', 'label'],
        'overlooking' => ['Overlooking', 'label'],
        'plot_amenities' => ['Plot amenities (label, icon)', 'list'],
        'availability' => ['Visit availability (label, days)', 'list'],
        'report_reasons' => ['Listing report reasons', 'label'],
        'reviewer_roles' => ['Locality reviewer roles', 'label'],
        'review_features' => ['Locality review features (label, icon)', 'list'],
        // Commercial wizard
        'commercial.floors' => ['Commercial floors', 'label'],
        'commercial.other_features' => ['Commercial other features', 'label'],
        'commercial.ideal_for' => ['Commercial ideal for', 'label'],
        'commercial.power_backup' => ['Commercial power backup', 'label'],
        'commercial.lift' => ['Commercial lift', 'label'],
        'commercial.parking' => ['Commercial parking', 'label'],
        'commercial.washrooms' => ['Commercial washrooms', 'label'],
        'commercial.condition' => ['Commercial condition', 'label'],
        'commercial.current_business' => ['Commercial current business', 'label'],
        // PG
        'pg.gender' => ['PG gender', 'label'], 'pg.rooms' => ['PG room types', 'label'], 'pg.tenant_type' => ['PG tenant type', 'label'],
        'pg.meals' => ['PG meals', 'label'], 'pg.rules' => ['PG rules', 'label'], 'pg.services' => ['PG services', 'label'],
        // New lists (commercial details, projects, nearby places)
        'facilities' => ['Commercial facilities (label, icon)', 'list', [
            'reception' => ['Reception area', '🛎️'], 'conference' => ['Conference room', '👥'], 'furnished' => ['Furnished', '🪑'], 'central_ac' => ['Central air conditioning', '❄️'],
            'oxygen_duct' => ['Oxygen duct', '🫁'], 'ups' => ['UPS', '⚡'], 'pantry' => ['Pantry', '☕'], 'fire_extinguisher' => ['Fire extinguisher', '🧯'], 'fire_sensors' => ['Fire sensors', '🚨'],
            'sprinklers' => ['Sprinklers', '💦'], 'fire_hose' => ['Fire hose', '🚒'], 'cctv' => ['CCTV', '📹'], 'lift' => ['Lift', '🛗'], 'dg_backup' => ['DG backup', '🔋'],
        ]],
        'situated_in' => ['Commercial: situated in', 'label', [
            'it_park' => 'IT park', 'business_park' => 'Business park', 'commercial_complex' => 'Commercial complex', 'mall' => 'Mall', 'standalone' => 'Standalone building',
            'residential_complex' => 'Residential complex', 'industrial_area' => 'Industrial area', 'market' => 'Market / high street',
        ]],
        'office_types' => ['Commercial: office type', 'label', [
            'ready_to_move' => 'Ready to move office', 'bare_shell' => 'Bare shell office', 'co_working' => 'Co-working space',
        ]],
        'transaction_types' => ['Transaction type', 'label', ['new' => 'New booking', 'resale' => 'Resale']],
        'project_statuses' => ['Project status', 'label', ['new_launch' => 'New launch', 'under_construction' => 'Under construction', 'ready' => 'Ready to move']],
        'image_kinds' => ['Project image kinds', 'label', ['exterior' => 'Exterior', 'interior' => 'Interior', 'amenity' => 'Amenity', 'floor_plan' => 'Floor plan', 'site_plan' => 'Site / master plan', 'location' => 'Location map']],
        // Icon shown for each amenity key on project pages (keys match popt_amenities).
        'amenity_icons' => ['Amenity icons (key = amenity key, label = icon)', 'label', [
            'cctv' => '📹', 'club' => '🥂', 'fire_safety' => '🧯', 'gas_pipeline' => '🔥', 'gated' => '🚧', 'gym' => '🏋️', 'house_keeping' => '🧹', 'intercom' => '☎️',
            'internet' => '📶', 'lift' => '🛗', 'park' => '🌳', 'parking' => '🅿️', 'play_area' => '🛝', 'pool' => '🏊', 'power_backup' => '🔋', 'private_lift' => '🛗',
            'rain_water' => '🌧️', 'security' => '💂', 'sewage' => '♻️', 'shopping' => '🛍️', 'visitor_parking' => '🚗',
        ]],
        'area_types' => ['Unit area types', 'label', ['super_built_up' => 'Super built-up area', 'built_up' => 'Built-up area', 'carpet' => 'Carpet area', 'plot' => 'Plot area']],
        'nearby_categories' => ['Places nearby (label, icon, OSM tag)', 'list', [
            'school' => ['School', '🏫', 'amenity=school'], 'hospital' => ['Hospital', '🏥', 'amenity=hospital'], 'bank' => ['Bank', '🏦', 'amenity=bank'], 'atm' => ['ATM', '💳', 'amenity=atm'],
            'metro' => ['Metro station', '🚇', 'railway=station'], 'bus' => ['Bus stop', '🚌', 'highway=bus_stop'], 'temple' => ['Place of worship', '🛕', 'amenity=place_of_worship'],
            'mall' => ['Shopping', '🛍️', 'shop=mall'], 'supermarket' => ['Supermarket', '🛒', 'shop=supermarket'], 'park' => ['Park', '🌳', 'leisure=park'], 'pharmacy' => ['Pharmacy', '💊', 'amenity=pharmacy'],
        ]],
    ],
];
