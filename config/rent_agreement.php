<?php

// Online rent agreements: stamp duty / registration estimates per state, city → state, and the wizard's option lists.
// Stamp figures are estimates; the legal team confirms the exact duty before buying the e-stamp. Edit the rules here when a state changes them.
return [
    'durations' => [11, 12],
    'max_months' => 60,
    // Legal services (slugs in legal_services) that make up an order. 'agreement' is always included.
    'agreement_service' => 'rent-agreement',
    'addons' => ['notarisation', 'aadhaar-esign', 'doorstep-delivery', 'tenant-verification'],
    'registration_service' => 'registration',

    // Stamp duty brackets: first bracket whose 'upto' (months, null = no limit) covers the term wins.
    // Bases: annual_rent, annual_rent_plus_deposit, total_rent_plus_deposit, consideration (total rent + 10% a year notional interest on the deposit).
    'states' => [
        'Karnataka' => ['stamp' => [['upto' => 11, 'pct' => 0.5, 'base' => 'annual_rent_plus_deposit'], ['upto' => null, 'pct' => 1, 'base' => 'annual_rent_plus_deposit']], 'registration' => ['pct' => 1, 'base' => 'annual_rent']],
        'Maharashtra' => ['stamp' => [['upto' => null, 'pct' => 0.25, 'base' => 'consideration']], 'registration' => ['fixed' => 1000], 'registration_required' => true],
        'Delhi' => ['stamp' => [['upto' => 11, 'fixed' => 100], ['upto' => 60, 'pct' => 2, 'base' => 'annual_rent']], 'registration' => ['fixed' => 1100]],
        'Tamil Nadu' => ['stamp' => [['upto' => null, 'pct' => 1, 'base' => 'total_rent_plus_deposit']], 'registration' => null],
        'Gujarat' => ['stamp' => [['upto' => null, 'pct' => 1, 'base' => 'annual_rent']], 'registration' => ['pct' => 1, 'base' => 'annual_rent']],
        'Haryana' => ['stamp' => [['upto' => 59, 'pct' => 1.5, 'base' => 'annual_rent_plus_deposit'], ['upto' => 120, 'pct' => 3, 'base' => 'annual_rent_plus_deposit']], 'registration' => null],
        'Uttar Pradesh' => ['stamp' => [['upto' => null, 'pct' => 2, 'base' => 'annual_rent']], 'registration' => null],
        'West Bengal' => ['stamp' => [['upto' => 11, 'fixed' => 100]], 'registration' => null],
    ],
    'min_stamp' => 100,

    'cities' => [
        'Bengaluru' => 'Karnataka', 'Mysuru' => 'Karnataka', 'Mangaluru' => 'Karnataka', 'Hubballi' => 'Karnataka',
        'Mumbai' => 'Maharashtra', 'Navi Mumbai' => 'Maharashtra', 'Thane' => 'Maharashtra', 'Pune' => 'Maharashtra', 'Nagpur' => 'Maharashtra', 'Nashik' => 'Maharashtra', 'Aurangabad' => 'Maharashtra',
        'Delhi' => 'Delhi', 'New Delhi' => 'Delhi',
        'Gurugram' => 'Haryana', 'Faridabad' => 'Haryana', 'Panchkula' => 'Haryana',
        'Noida' => 'Uttar Pradesh', 'Greater Noida' => 'Uttar Pradesh', 'Ghaziabad' => 'Uttar Pradesh', 'Lucknow' => 'Uttar Pradesh', 'Kanpur' => 'Uttar Pradesh', 'Varanasi' => 'Uttar Pradesh', 'Prayagraj' => 'Uttar Pradesh', 'Agra' => 'Uttar Pradesh', 'Meerut' => 'Uttar Pradesh',
        'Hyderabad' => 'Telangana', 'Warangal' => 'Telangana',
        'Chennai' => 'Tamil Nadu', 'Coimbatore' => 'Tamil Nadu', 'Madurai' => 'Tamil Nadu', 'Tiruchirappalli' => 'Tamil Nadu',
        'Kolkata' => 'West Bengal', 'Siliguri' => 'West Bengal', 'Durgapur' => 'West Bengal',
        'Ahmedabad' => 'Gujarat', 'Surat' => 'Gujarat', 'Vadodara' => 'Gujarat', 'Rajkot' => 'Gujarat',
        'Jaipur' => 'Rajasthan', 'Udaipur' => 'Rajasthan', 'Jodhpur' => 'Rajasthan', 'Kota' => 'Rajasthan',
        'Chandigarh' => 'Chandigarh',
        'Mohali' => 'Punjab', 'Zirakpur' => 'Punjab', 'Ludhiana' => 'Punjab', 'Amritsar' => 'Punjab', 'Jalandhar' => 'Punjab', 'Patiala' => 'Punjab', 'Hoshiarpur' => 'Punjab', 'Phagwara' => 'Punjab', 'Pathankot' => 'Punjab', 'Bathinda' => 'Punjab', 'Kapurthala' => 'Punjab',
        'Dehradun' => 'Uttarakhand', 'Indore' => 'Madhya Pradesh', 'Bhopal' => 'Madhya Pradesh', 'Goa' => 'Goa',
        'Kochi' => 'Kerala', 'Thiruvananthapuram' => 'Kerala', 'Kozhikode' => 'Kerala',
        'Visakhapatnam' => 'Andhra Pradesh', 'Vijayawada' => 'Andhra Pradesh', 'Guntur' => 'Andhra Pradesh',
        'Bhubaneswar' => 'Odisha', 'Cuttack' => 'Odisha', 'Raipur' => 'Chhattisgarh', 'Ranchi' => 'Jharkhand', 'Jamshedpur' => 'Jharkhand',
        'Patna' => 'Bihar', 'Guwahati' => 'Assam', 'Jammu' => 'Jammu & Kashmir', 'Srinagar' => 'Jammu & Kashmir', 'Shimla' => 'Himachal Pradesh',
    ],

    'types' => ['notarised' => ['Notarised', 'On e-stamp paper, signed and notarised. Common for 11-month rentals.'], 'registered' => ['Registered', 'Registered at the Sub-Registrar. Needed for longer terms and in some states.']],
    'usage' => ['residential' => 'Residential', 'commercial' => 'Commercial'],
    'property_types' => ['flat' => 'Flat / apartment', 'house' => 'Independent house', 'floor' => 'Builder floor', 'room' => 'Room / PG', 'shop' => 'Shop / showroom', 'office' => 'Office', 'warehouse' => 'Godown / warehouse'],
    'documents' => ['Aadhaar of landlord and tenant', 'PAN of landlord and tenant', 'Property ownership proof (tax receipt or deed)', 'Passport-size photos (registered agreements)', 'Two witnesses (registered agreements)'],
    'steps' => ['parties' => 'Landlord & tenant', 'property' => 'Property', 'terms' => 'Rent & terms', 'review' => 'Review & order'],
];
