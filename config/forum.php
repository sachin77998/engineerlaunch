<?php

// Property forum: categories (each with a call-to-action to the matching service), limits and report reasons.
return [
    'categories' => [
        'home-services' => ['Home services', 'Cleaning, painting, repairs, appliances and pest control', ['Book home services', 'home-services.index']],
        'renting' => ['Renting a home', 'Rent agreements, deposits, tenants and landlords', ['Create rent agreement', 'rent-agreement.index']],
        'buying-selling' => ['Buying & selling', 'Property search, prices, builders and projects', ['Search properties', 'properties.index']],
        'legal' => ['Property legal', 'Title, registration, khata / mutation, deeds and disputes', ['Property legal services', 'legal.index']],
        'home-loans' => ['Home loans', 'Eligibility, interest rates, EMIs and balance transfer', ['Compare home loans', 'home-loan.index']],
        'interiors-vastu' => ['Interiors & vastu', 'Design ideas, materials, budgets and vastu', ['Interior cost estimator', 'interiors.estimator']],
        'society-living' => ['Society & apartment living', 'Maintenance, associations, parking and neighbours', ['Pay maintenance by card', 'pay-rent.index']],
        'packers-movers' => ['Shifting & moving', 'Packers and movers, transport and relocation', ['Get a moving quote', 'home-services.index']],
    ],
    'per_page' => 15,
    'title_min' => 10, 'title_max' => 200, 'body_max' => 3000,
    'answer_min' => 20, 'answer_max' => 5000,
    'daily_questions' => 10, 'daily_answers' => 30,
    'report_reasons' => ['spam' => 'Spam or advertising', 'abuse' => 'Abusive or offensive', 'wrong' => 'Misleading or wrong', 'personal' => 'Shares personal details'],
    'auto_hide_reports' => 3, // hidden until reviewed once this many different users report it
];
