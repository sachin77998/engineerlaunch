<?php

// Product cards on the home page (3 per row, 6 per slide). "details" is the product's main page; "features" are the
// modules shown in Quick View. Routes that do not exist are skipped, so a card never shows a broken link.
return [
    [
        'key' => 'jobs', 'title' => 'Job Search', 'badge' => 'JOBS', 'color' => '#1769ff', 'icon' => '💼',
        'summary' => 'Engineering, manufacturing and IT jobs from 1 lakh+ companies across India and the world — search by role, skill, city or industrial area and apply in one place.',
        'details' => 'career-explorer',
        'features' => [
            ['Search all jobs', 'career-explorer'], ['Jobs posted by HR', 'jobs.hr'], ['Companies hiring now', 'companies.index'],
            ['Jobs by technology', 'technologies.index'], ['Track my applications', 'applications.track'], ['Interview experiences', 'company.experiences.index'],
            ['Post a job (employers)', 'employer.register'],
        ],
    ],
    [
        'key' => 'insights', 'title' => 'Insights into Company', 'badge' => 'INSIGHTS', 'color' => '#f4b400', 'icon' => '📊',
        'summary' => 'Financials, credit ratings, registry details, people, competitors and vendor checks for listed and private companies.',
        'details' => 'insights.index',
        'features' => [
            ['Company insights', 'insights.index'], ['Companies directory', 'companies.index'], ['Sectors & sub-sectors', 'sectors.index'],
            ['Industrial areas', 'industrial.index'], ['State approvals & licences', 'approvals.index'], ['Industry news', 'news.index'],
        ],
    ],
    [
        'key' => 'trading', 'title' => 'Do Trading', 'badge' => 'TRADE', 'color' => '#0b7a43', 'icon' => '📈',
        'summary' => 'Mutual funds, stocks, ETFs and US stocks with research, calculators, loans and insurance — open an account with online KYC.',
        'details' => 'trading.index',
        'features' => [
            ['Mutual funds', 'trading.index'], ['Stocks', 'trading.stocks'], ['ETFs', 'trading.etfs'], ['US stocks: NYSE & Nasdaq', 'markets.us'],
            ['All calculators', 'calculators.index'], ['Insurance', 'insurance.index'], ['Loans & amortization', 'trading.loans'],
            ['Orders & holdings', 'trading.orders'], ['Account opening (KYC)', 'trading.onboarding'], ['Money planner', 'money.index'],
        ],
    ],
    [
        'key' => 'learning', 'title' => 'Learning', 'badge' => 'LEARN', 'color' => '#7c3aed', 'icon' => '🎓',
        'summary' => 'Interview preparation, DSA practice, technology tracks and quizzes — learn, practise and get ready for your next role.',
        'details' => 'learning.index',
        'features' => [
            ['Learning tracks', 'learning.index'], ['Practice questions', 'practice'], ['Interview experiences', 'company.experiences.index'],
            ['Technologies', 'technologies.index'], ['Premium', 'premium.index'],
        ],
    ],
    [
        'key' => 'bills', 'title' => 'Bills & Payments', 'badge' => 'PAY', 'color' => '#00b9f5', 'icon' => '🧾',
        'summary' => 'Electricity, water, gas, mobile, DTH, FASTag, metro, loan EMI, insurance and property tax — every biller and city, on the official sites.',
        'details' => 'bills.index',
        'features' => [
            ['All bills', 'bills.index'], ['Electricity', ['bills.category', 'electricity']], ['Water', ['bills.category', 'water']], ['Mobile recharge', ['bills.category', 'mobile']],
            ['FASTag', ['bills.category', 'fastag']], ['Loan EMI', ['bills.category', 'loan']], ['Property tax & municipal', ['bills.category', 'municipal']],
        ],
    ],
    [
        'key' => 'hiring', 'title' => 'Hiring Suite', 'badge' => 'HIRE', 'color' => '#c2410c', 'icon' => '🤝',
        'summary' => 'For employers: post jobs, screen and shortlist, schedule interviews, send offers and collect joining documents with automated workflows.',
        'details' => 'employer.register',
        'features' => [
            ['Register your company', 'employer.register'], ['HR login', 'employer.login'], ['Employer dashboard', 'employer.dashboard'],
            ['Workflows', 'employer.hiring.workflows'], ['Onboarding & documents', 'employer.hiring.onboarding'],
        ],
    ],
    [
        'key' => 'property', 'title' => 'Property: Buy, Rent & Commercial', 'badge' => 'PROPERTY', 'color' => '#4338ca', 'icon' => '🏠',
        'summary' => 'Flats, houses, plots, PG and flatmates, offices, shops and warehouses for rent or sale, straight from owners — or post your own property ad free.',
        'details' => 'properties.index',
        'features' => [
            ['Buy a home or plot', ['properties.search', 'buy']], ['Rent a home', ['properties.search', 'rent']], ['PG / hostel & flatmates', ['properties.search', ['rent', 'kind' => 'pg']]],
            ['Commercial for rent', ['properties.search', 'commercial-rent']], ['Commercial for sale', ['properties.search', 'commercial-buy']], ['Post free property ad', 'properties.post'],
            ['Home loan: compare banks & EMI', 'home-loan.index'], ['Property legal services', 'legal.index'], ['Online rent agreement', 'rent-agreement.index'], ['Pay rent by credit card', 'pay-rent.index'], ['Home interiors & cost estimator', 'interiors.index'], ['Refer & earn', 'referrals.index'], ['Home services: cleaning, movers, repairs', 'home-services.index'], ['Property forum: ask & answer', 'forum.index'], ['My properties', 'properties.mine'], ['Shortlist', 'properties.shortlist'],
        ],
    ],
    [
        'key' => 'build', 'title' => 'Home Building', 'badge' => 'BUILD', 'color' => '#ea580c', 'icon' => '🏗️',
        'summary' => 'Buy cement, plywood, paint, wires, plumbing and hardware online — bulk prices, pay online or on delivery, a construction cost calculator and estimators.',
        'details' => 'materials.index',
        'features' => [
            ['Construction cost calculator', 'materials.calculator'], ['Paint estimator', ['materials.estimator', 'paint']], ['Tile estimator', ['materials.estimator', 'tiles']],
            ['Waterproofing estimator', ['materials.estimator', 'waterproofing']], ['Cement prices', ['materials.category', 'cement']], ['Plywood, MDF & HDHMR', ['materials.category', 'plywood-mdf-hdhmr']],
            ['Wires & cables', ['materials.category', 'wires']], ['All categories', 'materials.categories'], ['Price lists by brand', 'materials.price-lists'], ['Cart', 'materials.list'], ['My orders', 'materials.orders'],
        ],
    ],
];
