<?php

// Property marketplace (Buy, Rent, Commercial): every option list used by the post-property wizard, the search
// filters and the listing page. Keys are stored; labels are shown. Add a city, unit or option here and it appears
// everywhere.
return [
    'brand' => env('PROPERTY_BRAND', 'Ascendia Homes'),

    // Country → currency used for every price on a listing in that country.
    'countries' => [
        'India' => ['dial' => '+91', 'currency' => 'INR', 'symbol' => '₹', 'unit' => 'sqft'],
        'United Arab Emirates' => ['dial' => '+971', 'currency' => 'AED', 'symbol' => 'AED ', 'unit' => 'sqft'],
        'United States' => ['dial' => '+1', 'currency' => 'USD', 'symbol' => '$', 'unit' => 'sqft'],
        'United Kingdom' => ['dial' => '+44', 'currency' => 'GBP', 'symbol' => '£', 'unit' => 'sqft'],
        'Singapore' => ['dial' => '+65', 'currency' => 'SGD', 'symbol' => 'S$', 'unit' => 'sqft'],
        'Canada' => ['dial' => '+1', 'currency' => 'CAD', 'symbol' => 'C$', 'unit' => 'sqft'],
        'Australia' => ['dial' => '+61', 'currency' => 'AUD', 'symbol' => 'A$', 'unit' => 'sqm'],
        'Germany' => ['dial' => '+49', 'currency' => 'EUR', 'symbol' => '€', 'unit' => 'sqm'],
        'Saudi Arabia' => ['dial' => '+966', 'currency' => 'SAR', 'symbol' => 'SAR ', 'unit' => 'sqm'],
        'Qatar' => ['dial' => '+974', 'currency' => 'QAR', 'symbol' => 'QAR ', 'unit' => 'sqm'],
    ],

    // City dropdown on the search page, by country. Listings may name any city; cities with live listings join this list.
    'cities' => [
        'India' => ['Bengaluru', 'Mumbai', 'Navi Mumbai', 'Thane', 'Pune', 'Hyderabad', 'Chennai', 'Delhi', 'New Delhi', 'Gurugram', 'Noida', 'Greater Noida', 'Ghaziabad',
            'Faridabad', 'Kolkata', 'Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Jaipur', 'Lucknow', 'Kanpur', 'Chandigarh', 'Mohali', 'Zirakpur', 'Panchkula', 'Ludhiana',
            'Amritsar', 'Jalandhar', 'Patiala', 'Hoshiarpur', 'Phagwara', 'Pathankot', 'Bathinda', 'Kapurthala', 'Dehradun', 'Indore', 'Bhopal', 'Nagpur', 'Nashik', 'Aurangabad', 'Goa', 'Kochi', 'Thiruvananthapuram', 'Kozhikode', 'Coimbatore',
            'Madurai', 'Tiruchirappalli', 'Mysuru', 'Mangaluru', 'Hubballi', 'Visakhapatnam', 'Vijayawada', 'Guntur', 'Warangal', 'Bhubaneswar', 'Cuttack', 'Raipur', 'Ranchi',
            'Jamshedpur', 'Patna', 'Guwahati', 'Varanasi', 'Prayagraj', 'Agra', 'Meerut', 'Jammu', 'Srinagar', 'Shimla', 'Udaipur', 'Jodhpur', 'Kota', 'Siliguri', 'Durgapur'],
        'United Arab Emirates' => ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman'],
        'United States' => ['New York', 'San Francisco', 'Los Angeles', 'Seattle', 'Austin', 'Chicago', 'Dallas', 'Boston'],
        'United Kingdom' => ['London', 'Manchester', 'Birmingham', 'Leeds'],
        'Singapore' => ['Singapore'],
        'Canada' => ['Toronto', 'Vancouver', 'Calgary', 'Montreal'],
        'Australia' => ['Sydney', 'Melbourne', 'Brisbane', 'Perth'],
        'Germany' => ['Berlin', 'Munich', 'Frankfurt', 'Hamburg'],
        'Saudi Arabia' => ['Riyadh', 'Jeddah', 'Dammam'],
        'Qatar' => ['Doha'],
    ],

    // Area units and their size in square feet. Land units differ by state; these are the usual values: marla, kanal,
    // bigha and biswa as used in Punjab / Haryana, ground and aankadam in Tamil Nadu, kottah (katha) and chatak in West
    // Bengal, guntha in Maharashtra / Karnataka / Telangana, cent in the south.
    'area_units' => [
        'sqft' => ['Sq. ft', 1], 'sqyd' => ['Sq. yard', 9], 'sqm' => ['Sq. metre', 10.7639], 'acre' => ['Acre', 43560], 'marla' => ['Marla', 272.25],
        'cent' => ['Cent', 435.6], 'bigha' => ['Bigha', 9070], 'katha' => ['Kottah', 720], 'kanal' => ['Kanal', 5445], 'ground' => ['Ground', 2400],
        'are' => ['Are', 1076.39], 'biswa' => ['Biswa', 453.5], 'guntha' => ['Guntha', 1089], 'aankadam' => ['Aankadam', 72], 'hectare' => ['Hectare', 107639.1],
        'rood' => ['Rood', 10890], 'chatak' => ['Chatak', 45], 'perch' => ['Perch', 272.25],
    ],

    // What a listing is: segment → kinds. Residential sale kinds are "Full house" and "Land / plot"; rent adds PG and flatmates.
    'kinds' => [
        'residential' => ['full_house' => 'Full house', 'land' => 'Land / plot', 'pg' => 'PG / hostel', 'flatmate' => 'Flatmates'],
        'commercial' => ['office_space' => 'Office space', 'co_working' => 'Co-working', 'shop' => 'Shop', 'showroom' => 'Showroom', 'industrial_building' => 'Industrial building',
            'industrial_shed' => 'Industrial shed', 'warehouse' => 'Godown / warehouse', 'restaurant_cafe' => 'Restaurant / cafe', 'other_business' => 'Other business'],
    ],
    // Which kinds each search tab offers.
    'modes' => [
        'buy' => ['label' => 'Buy', 'segment' => 'residential', 'listing' => 'sale', 'kinds' => ['full_house', 'land']],
        'rent' => ['label' => 'Rent', 'segment' => 'residential', 'listing' => 'rent', 'kinds' => ['full_house', 'pg', 'flatmate']],
        'commercial-rent' => ['label' => 'Commercial rent', 'segment' => 'commercial', 'listing' => 'rent'],
        'commercial-buy' => ['label' => 'Commercial buy', 'segment' => 'commercial', 'listing' => 'sale'],
    ],

    'bhk' => ['1rk' => '1 RK', '1' => '1 BHK', '2' => '2 BHK', '3' => '3 BHK', '4' => '4 BHK', '5' => '5 BHK', '6' => '6 BHK', '7' => '7 BHK', '8' => '8 BHK', '9' => '9 BHK'],
    // Bedroom chips in the search box ("4+ BHK" = 5 to 9) and in the results filter ("1 RK / 1 BHK" together).
    'bhk_search' => ['1rk' => '1 RK', '1' => '1 BHK', '2' => '2 BHK', '3' => '3 BHK', '4' => '4 BHK', '5plus' => '4+ BHK'],
    'bhk_filter' => ['1rk1' => '1 RK / 1 BHK', '2' => '2 BHK', '3' => '3 BHK', '4' => '4 BHK', '5' => '5 BHK', '6' => '6 BHK', '7' => '7 BHK', '8' => '8 BHK', '9' => '9 BHK'],
    'bhk_groups' => ['1rk1' => ['1rk', '1'], '5plus' => ['5', '6', '7', '8', '9']],
    'building_types' => [
        'residential' => ['apartment' => 'Apartment', 'independent_house' => 'Independent house', 'villa' => 'Villa', 'builder_floor' => 'Builder floor', 'gated_community' => 'Gated community villa', 'penthouse' => 'Penthouse', 'studio' => 'Studio apartment', 'farm_house' => 'Farm house', 'service_apartment' => 'Service apartment'],
        'commercial' => ['independent_house' => 'Independent house', 'business_park' => 'Business park', 'mall' => 'Mall', 'standalone_building' => 'Standalone building', 'independent_shop' => 'Independent shop'],
    ],
    // "Type of property" filter for homes: each type covers these building types (a plot is the land kind).
    'home_types' => [
        'independent_house' => ['Independent house / villa', 'Independent houses', ['independent_house', 'villa', 'gated_community']],
        'plot' => ['Residential plot', 'Plots', null],
        'farm_house' => ['Farm house', 'Farm houses', ['farm_house']],
        'apartment' => ['Residential apartment', 'Flats', ['apartment', 'studio', 'penthouse']],
        'builder_floor' => ['Independent / builder floor', 'Builder floors', ['builder_floor']],
        'service_apartment' => ['Service apartment', 'Service apartments', ['service_apartment']],
    ],
    // "Posted by" filter; a featured dealer is an agent the site owner has marked as featured.
    'posted_by' => ['owner' => 'Owner', 'builder' => 'Builder', 'dealer' => 'Dealer', 'featured' => 'Featured dealer'],
    // Budget dropdown steps (INR); other currencies use the plain steps.
    'budgets' => [
        'sale_inr' => [500000, 1000000, 1500000, 2000000, 2500000, 3000000, 4000000, 5000000, 6000000, 7500000, 9000000, 10000000, 15000000, 20000000, 30000000, 50000000, 100000000, 200000000],
        'rent_inr' => [5000, 10000, 15000, 20000, 25000, 30000, 40000, 50000, 75000, 100000, 150000, 200000, 500000],
        'sale' => [50000, 100000, 200000, 300000, 500000, 750000, 1000000, 1500000, 2000000, 3000000, 5000000, 10000000],
        'rent' => [500, 1000, 1500, 2000, 3000, 4000, 5000, 7500, 10000, 15000, 25000],
    ],
    // Localities offered in the search box and filters before listings exist there (listings add their own localities).
    'localities' => [
        'Hoshiarpur' => ['Gautam Nagar', 'Garshankar', 'Tagore Nagar', 'Piplan Wala', 'Pur Hiran', 'Tibba Sahib', 'Model Colony', 'Mount Avenue', 'Dasuya', 'Rajpur Bhaian', 'Krishna Nagar'],
    ],
    'ages' => ['lt1' => 'Less than a year', '1_5' => '1 to 5 years', '5_10' => '5 to 10 years', 'gt10' => 'More than 10 years'],
    'furnishing' => ['full' => 'Fully furnished', 'semi' => 'Semi furnished', 'none' => 'Unfurnished'],
    'facing' => ['north' => 'North', 'south' => 'South', 'east' => 'East', 'west' => 'West', 'north_east' => 'North-east', 'north_west' => 'North-west', 'south_east' => 'South-east', 'south_west' => 'South-west'],
    'flooring' => ['vitrified' => 'Vitrified tiles', 'marble' => 'Marble', 'granite' => 'Granite', 'wooden' => 'Wooden', 'ceramic' => 'Ceramic tiles', 'mosaic' => 'Mosaic', 'cement' => 'Cement', 'other' => 'Other'],
    'possession' => ['ready' => 'Ready to move', 'under_construction' => 'Under construction'],
    'ownership' => ['freehold' => 'Freehold', 'leasehold' => 'Leasehold', 'cooperative' => 'Co-operative society', 'poa' => 'Power of attorney'],
    'preferred_tenants' => ['family' => 'Family', 'bachelor_male' => 'Bachelors (men)', 'bachelor_female' => 'Bachelors (women)', 'company' => 'Company lease', 'any' => 'Anyone'],
    'lease_years' => ['1' => '1 year', '2' => '2 years', '3' => '3 years', '5' => '5 years', '9' => '9 years', '10plus' => '10+ years'],
    'lockin_years' => ['0' => 'None', '1' => '1 year', '2' => '2 years', '3' => '3 years', '5' => '5 years'],

    // Furnishing items with a count on the listing page.
    'furnishings' => ['water_purifier' => 'Water purifier', 'fan' => 'Fan', 'fridge' => 'Fridge', 'dining_table' => 'Dining table', 'geyser' => 'Geyser', 'stove' => 'Stove', 'light' => 'Light',
        'chimney' => 'Chimney', 'tv' => 'TV', 'modular_kitchen' => 'Modular kitchen', 'ac' => 'AC', 'bed' => 'Bed', 'sofa' => 'Sofa', 'washing_machine' => 'Washing machine',
        'microwave' => 'Microwave', 'wardrobe' => 'Wardrobe', 'curtains' => 'Curtains', 'exhaust_fan' => 'Exhaust fan'],

    // "Key highlights" chips the owner ticks; shown at the top of the listing.
    'highlights' => ['two_side_open' => '2 sides open', 'furnished' => 'Furnished', 'vaastu' => 'Vaastu compliant', 'renovated' => 'Recently renovated', 'covered_parking' => 'Covered parking',
        'central_ac' => 'Centrally air-conditioned', 'modular_kitchen' => 'Modular kitchen', 'visitor_parking' => 'Visitor parking', 'near_metro' => 'Near metro station',
        'corner' => 'Corner property', 'main_road' => 'On main road', 'gated' => 'Gated society', 'park_facing' => 'Park facing', 'lift' => 'Lift', 'pet_friendly' => 'Pet friendly',
        'power_backup' => 'Power backup', 'garden' => 'Private garden / terrace', 'servant_room' => 'Servant room', 'pooja_room' => 'Pooja room', 'study_room' => 'Study room',
        'close_market' => 'Close to market', 'close_school' => 'Close to school', 'close_hospital' => 'Close to hospital', 'close_railway' => 'Close to railway station',
        'close_bus' => 'Close to bus stand', 'close_airport' => 'Close to airport', 'close_highway' => 'Close to highway'],

    // Society / building amenities for residential listings.
    'amenities' => ['private_lift' => 'Private lift', 'lift' => 'Lift', 'parking' => 'Parking', 'gated' => 'Gated society', 'security' => 'Security guard', 'cctv' => 'CCTV', 'power_backup' => 'Power backup',
        'gym' => 'Gymnasium', 'pool' => 'Swimming pool', 'club' => 'Club house',
        'park' => 'Park', 'play_area' => "Children's play area", 'intercom' => 'Intercom', 'gas_pipeline' => 'Gas pipeline', 'rain_water' => 'Rain water harvesting', 'fire_safety' => 'Fire safety',
        'internet' => 'Internet', 'sewage' => 'Sewage treatment', 'shopping' => 'Shopping centre', 'house_keeping' => 'House keeping', 'visitor_parking' => 'Visitor parking'],

    // Commercial wizard dropdowns.
    'commercial' => [
        'floors' => ['lower_basement' => 'Lower basement', 'upper_basement' => 'Upper basement', 'ground' => 'Ground', 'full_building' => 'Full building'],
        'total_floors' => ['ground_only' => 'Ground only'],
        'other_features' => ['main_road' => 'On main road', 'corner' => 'Corner property'],
        'ideal_for' => ['retail' => 'Retail store', 'office' => 'Corporate / business office', 'restaurant' => 'Restaurant / food business', 'clinic' => 'Clinic / healthcare lab',
            'warehouse' => 'Storage / warehouse', 'garments' => 'Garments / clothing store', 'electronics' => 'Electronics store / repair', 'cloud_kitchen' => 'Cloud kitchen',
            'pharmacy' => 'Pharmacy', 'grocery' => 'Grocery / supermarket', 'salon' => 'Salon / spa', 'gym' => 'Gym / fitness', 'education' => 'Coaching / education', 'bank_atm' => 'Bank / ATM'],
        'power_backup' => ['full' => 'Full', 'dg' => 'DG backup', 'arrange' => 'Need to arrange'],
        'lift' => ['none' => 'None', 'personal' => 'Personal', 'common' => 'Common'],
        'parking' => ['none' => 'None', 'public_reserved' => 'Public and reserved', 'public' => 'Public', 'reserved' => 'Reserved'],
        'washrooms' => ['shared' => 'Shared', 'none' => 'No washroom', 'private' => 'Private'],
        'condition' => ['vacant' => 'Vacant', 'rented' => 'Currently rented / leased', 'own_business' => 'Own business', 'new' => 'New property'],
        'current_business' => ['office' => 'Office', 'restaurant' => 'Restaurant / cafe', 'salon' => 'Salon / spa', 'store' => 'Store / showroom', 'cloud_kitchen' => 'Cloud kitchen',
            'warehouse' => 'Warehouse / godown', 'clinic' => 'Clinic', 'school' => 'School / institute', 'gym' => 'Gym / yoga centre', 'industrial' => 'Industrial use', 'other' => 'Other business'],
    ],

    // Residential "Additional details".
    'power_backup' => ['full' => 'Full', 'partial' => 'Partial', 'none' => 'None'],
    'water_supply' => ['corporation' => 'Corporation', 'borewell' => 'Borewell', 'both' => 'Corporation and borewell'],
    'parking' => ['none' => 'None', 'bike' => 'Two-wheeler', 'car' => 'Car', 'both' => 'Car and two-wheeler'],

    // PG / hostel and flatmates.
    'pg' => [
        'gender' => ['male' => 'Men', 'female' => 'Women', 'any' => 'Anyone'],
        'rooms' => ['single' => 'Single room', 'double' => 'Double sharing', 'triple' => 'Triple sharing', 'four' => 'Four sharing'],
        'tenant_type' => ['students' => 'Students', 'professionals' => 'Working professionals', 'any' => 'Anyone'],
        'meals' => ['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner'],
        'rules' => ['no_smoking' => 'No smoking', 'no_alcohol' => 'No alcohol', 'no_guests' => 'No overnight guests', 'no_non_veg' => 'No non-veg', 'no_pets' => 'No pets', 'gate_closing' => 'Gate closing time'],
        'services' => ['laundry' => 'Laundry', 'room_cleaning' => 'Room cleaning', 'wifi' => 'Wi-Fi', 'warden' => 'Warden', 'tv' => 'Common TV', 'fridge' => 'Fridge', 'cooking' => 'Self cooking allowed'],
    ],

    // Site-visit schedule (last wizard step).
    'availability' => ['everyday' => ['Everyday', 'Mon–Sun'], 'weekday' => ['Weekdays', 'Mon–Fri'], 'weekend' => ['Weekend', 'Sat, Sun']],

    // Amenities offered as filters in the results sidebar.
    'amenity_filters' => ['private_lift', 'parking', 'gated', 'security', 'club', 'pool', 'gym'],

    // Indicative EMI on the listing page: this share of the price borrowed at this rate over these years.
    'emi' => ['loan_share' => 0.8, 'rate' => 8.5, 'years' => 20],
    'report_reasons' => ['sold' => 'Already sold / rented out', 'price' => 'Wrong price', 'details' => 'Wrong details or photos', 'fake' => 'Fake or fraud listing',
        'duplicate' => 'Duplicate listing', 'broker' => 'Broker posted as owner', 'other' => 'Something else'],

    // Locality reviews: who the reviewer is and the four rated features.
    'reviewer_roles' => ['resident_owner' => 'Resident (owner)', 'tenant' => 'Resident (tenant)', 'former' => 'Former resident', 'property_owner' => 'Owner of property', 'visitor' => 'Visits often'],
    'review_features' => ['connectivity' => ['Connectivity', '🚌'], 'lifestyle' => ['Lifestyle', '🛍'], 'safety' => ['Safety', '🛡'], 'green_area' => ['Green area', '🌳']],

    // Plots: what the plot overlooks and the layout's amenities (shown as tiles on the listing).
    'overlooking' => ['park' => 'Park', 'main_road' => 'Main road', 'garden' => 'Garden', 'club' => 'Club', 'pool' => 'Pool', 'farm' => 'Farm / fields', 'river' => 'River / canal', 'hills' => 'Hills'],
    'plot_amenities' => ['paved_roads' => ['Internal paved roads', '🛣'], 'street_lights' => ['Street lights', '💡'], 'water' => ['Water connection', '🚰'], 'sewerage' => ['Sewerage', '🚿'],
        'electricity' => ['Electricity connection', '⚡'], 'gated' => ['Gated layout', '🚧'], 'security' => ['Security', '🛡'], 'park' => ['Park', '🌳'], 'drainage' => ['Storm water drainage', '🌧'], 'noc' => ['NOC available', '📄']],

    'owner_types' => ['owner' => 'Owner', 'agent' => 'Agent / dealer', 'builder' => 'Builder'],
    'media' => ['photos' => 20, 'videos' => 2, 'photo_kb' => 8192, 'video_kb' => 40960],
];
