<?php

// Home loans: calculator defaults, eligibility rule and the option lists used by the application steps.
return [
    // Share of net monthly income banks allow towards all EMIs (FOIR).
    'foir' => 0.65,
    'defaults' => ['amount' => 3000000, 'rate' => 8.4, 'years' => 20, 'income' => 100000],
    'tenures' => [10, 15, 20, 25, 30],
    'amounts' => [1000000 => 'Up to ₹10 Lac', 2500000 => '₹10 – 25 Lac', 5000000 => '₹25 – 50 Lac', 7500000 => '₹50 – 75 Lac', 10000000 => '₹75 Lac – 1 Cr', 20000000 => '₹1 – 2 Cr', 50000000 => 'Above ₹2 Cr'],
    'employment' => ['salaried' => ['Salaried', '💼'], 'self_employed' => ['Self-employed', '🏪']],
    'property_types' => ['apartment' => 'Flat / apartment', 'house' => 'Independent house / villa', 'builder_floor' => 'Builder floor', 'plot' => 'Plot', 'plot_construction' => 'Plot + construction', 'construction' => 'Construction on own plot', 'renovation' => 'Home renovation'],
    'property_stages' => ['ready' => 'Ready to move', 'under_construction' => 'Under construction', 'resale' => 'Resale', 'self_construction' => 'Self construction'],
    // Document kinds per employment type. Uploads are optional; banks may still ask for originals.
    'documents' => [
        'pan' => ['PAN card', null],
        'aadhaar' => ['Aadhaar / address proof', null],
        'salary_slips' => ['Last 3 salary slips', 'salaried'],
        'form16' => ['Form 16 (last 2 years)', 'salaried'],
        'itr' => ['ITR with computation (last 2–3 years)', 'self_employed'],
        'business_proof' => ['Business registration / GST', 'self_employed'],
        'bank_statement' => ['Bank statement (last 6 months)', null],
        'property_papers' => ['Property papers / allotment letter', null],
    ],
    'doc_mimes' => 'pdf,jpg,jpeg,png',
    'doc_max_kb' => 8192,
    'statuses' => ['new' => 'Started', 'submitted' => 'Submitted', 'contacted' => 'Contacted', 'sanctioned' => 'Sanctioned', 'rejected' => 'Not eligible', 'closed' => 'Closed'],
];
