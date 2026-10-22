<?php

// Money planner rules and defaults. Tax figures are for FY 2025-26 (AY 2026-27); update them here after each Budget.
return [
    'tax_year' => 'FY 2025-26',

    'tax' => [
        'cess' => 4,
        'new' => [
            'slabs' => [[400000, 0], [800000, 5], [1200000, 10], [1600000, 15], [2000000, 20], [2400000, 25], [null, 30]],
            'standard_deduction' => 75000,
            'rebate_limit' => 1200000, 'rebate_max' => 60000,
            'surcharge' => [[5000000, 10], [10000000, 15], [20000000, 25]],
        ],
        'old' => [
            'slabs' => [[250000, 0], [500000, 5], [1000000, 20], [null, 30]],
            // Seniors (60-79) and super seniors (80+) have a higher nil slab under the old regime.
            'slabs_senior' => [[300000, 0], [500000, 5], [1000000, 20], [null, 30]],
            'slabs_super_senior' => [[500000, 0], [1000000, 20], [null, 30]],
            'standard_deduction' => 50000,
            'rebate_limit' => 500000, 'rebate_max' => 12500,
            'surcharge' => [[5000000, 10], [10000000, 15], [20000000, 25], [50000000, 37]],
        ],
        // Surcharge on equity capital gains (s.111A / s.112A) and dividends never exceeds 15%.
        'capital_gains_surcharge_cap' => 15,
        'capital_gains' => [
            'equity' => ['ltcg_months' => 12, 'stcg_rate' => 20, 'ltcg_rate' => 12.5, 'ltcg_exemption' => 125000, 'section_st' => '111A', 'section_lt' => '112A'],
            'property' => ['ltcg_months' => 24, 'stcg_rate' => null, 'ltcg_rate' => 12.5, 'ltcg_exemption' => 0, 'section_st' => 'slab', 'section_lt' => '112'],
            'gold' => ['ltcg_months' => 24, 'stcg_rate' => null, 'ltcg_rate' => 12.5, 'ltcg_exemption' => 0, 'section_st' => 'slab', 'section_lt' => '112'],
            // Debt funds bought on/after 1 Apr 2023 are taxed at slab rate whatever the holding period (s.50AA).
            'debt' => ['ltcg_months' => null, 'stcg_rate' => null, 'ltcg_rate' => null, 'ltcg_exemption' => 0, 'section_st' => '50AA', 'section_lt' => '50AA'],
            'fd' => ['ltcg_months' => null, 'stcg_rate' => null, 'ltcg_rate' => null, 'ltcg_exemption' => 0, 'section_st' => 'slab', 'section_lt' => 'slab'],
        ],
        'deductions' => [
            '80C' => ['limit' => 150000, 'regimes' => ['old'], 'label' => 'EPF, PPF, ELSS, life insurance premium, 5-yr tax-saver FD, SSY, NSC, home-loan principal, tuition fees'],
            '80CCD(1B)' => ['limit' => 50000, 'regimes' => ['old'], 'label' => 'Own NPS Tier-I contribution over and above 80C'],
            '80CCD(2)' => ['limit_pct_basic' => 14, 'regimes' => ['old', 'new'], 'label' => 'Employer NPS contribution (up to 14% of basic in new regime, 10% in old)'],
            '80D' => ['limit' => 25000, 'limit_senior' => 50000, 'parents' => 25000, 'parents_senior' => 50000, 'regimes' => ['old'], 'label' => 'Health insurance premium for self/family and parents'],
            '24(b)' => ['limit' => 200000, 'regimes' => ['old'], 'label' => 'Interest on home loan for a self-occupied house'],
            '80E' => ['limit' => null, 'regimes' => ['old'], 'label' => 'Interest on education loan (no cap, 8 years)'],
            '80TTA' => ['limit' => 10000, 'regimes' => ['old'], 'label' => 'Savings-account interest (non-seniors)'],
            '80TTB' => ['limit' => 50000, 'regimes' => ['old'], 'label' => 'All bank/post-office interest for senior citizens'],
        ],
        'gift_exempt_relatives' => ['spouse', 'adult_child', 'minor_child', 'parent', 'sibling'],
        '54EC_cap' => 5000000,
    ],

    // Default expected annual returns (%) per instrument; the user can override every value.
    'instruments' => [
        'equity_mf' => ['label' => 'Equity mutual fund (SIP)', 'return' => 12, 'tax' => 'equity', 'liquid' => true],
        'hybrid_mf' => ['label' => 'Hybrid / balanced fund', 'return' => 10, 'tax' => 'equity', 'liquid' => true],
        'debt_mf' => ['label' => 'Debt mutual fund', 'return' => 7, 'tax' => 'debt', 'liquid' => true],
        'elss' => ['label' => 'ELSS tax-saver fund', 'return' => 12, 'tax' => 'equity', 'liquid' => false],
        'fd' => ['label' => 'Fixed deposit', 'return' => 6.75, 'tax' => 'fd', 'liquid' => true],
        'rd' => ['label' => 'Recurring deposit', 'return' => 6.5, 'tax' => 'fd', 'liquid' => true],
        'ppf' => ['label' => 'PPF', 'return' => 7.1, 'tax' => 'exempt', 'liquid' => false],
        'epf' => ['label' => 'EPF / VPF', 'return' => 8.25, 'tax' => 'exempt', 'liquid' => false],
        'nps' => ['label' => 'NPS', 'return' => 10, 'tax' => 'nps', 'liquid' => false],
        'endowment' => ['label' => 'Endowment / money-back policy', 'return' => 5, 'tax' => 'exempt', 'liquid' => false],
        'ulip' => ['label' => 'ULIP', 'return' => 8, 'tax' => 'exempt', 'liquid' => false],
        'term' => ['label' => 'Term insurance premium (pure cover)', 'return' => 0, 'tax' => 'none', 'liquid' => false],
        'health' => ['label' => 'Health insurance premium', 'return' => 0, 'tax' => 'none', 'liquid' => false],
        'gold' => ['label' => 'Gold / SGB / gold ETF', 'return' => 8, 'tax' => 'gold', 'liquid' => true],
        'ssy' => ['label' => 'Sukanya Samriddhi', 'return' => 8.2, 'tax' => 'exempt', 'liquid' => false],
    ],

    // Retirement income products (annual yield %, monthly payout, limits). Verify current rates before acting.
    'income_products' => [
        'scss' => ['label' => 'Senior Citizens Savings Scheme (SCSS)', 'rate' => 8.2, 'max_per_person' => 3000000, 'min_age' => 60, 'payout' => 'quarterly', 'tax' => 'Interest taxable; 80C on deposit (old regime)', 'risk' => 'Sovereign'],
        'pomis' => ['label' => 'Post Office Monthly Income Scheme', 'rate' => 7.4, 'max_per_person' => 900000, 'max_joint' => 1500000, 'payout' => 'monthly', 'tax' => 'Interest taxable', 'risk' => 'Sovereign'],
        'rbi_frsb' => ['label' => 'RBI Floating Rate Savings Bonds', 'rate' => 8.05, 'max_per_person' => null, 'payout' => 'half-yearly', 'tax' => 'Interest taxable', 'risk' => 'Sovereign, 7-yr lock-in'],
        'annuity' => ['label' => 'Immediate life annuity (with return of purchase price)', 'rate' => 6.8, 'max_per_person' => null, 'payout' => 'monthly', 'tax' => 'Annuity taxable as income', 'risk' => 'Insurer, lifelong, illiquid'],
        'annuity_life' => ['label' => 'Immediate life annuity (no return of purchase price)', 'rate' => 7.6, 'max_per_person' => null, 'payout' => 'monthly', 'tax' => 'Annuity taxable as income', 'risk' => 'Insurer, lifelong, corpus not returned'],
        'swp' => ['label' => 'SWP from balanced advantage / conservative hybrid fund', 'rate' => 8.5, 'withdrawal' => 6, 'max_per_person' => null, 'payout' => 'monthly', 'tax' => 'Only the gain part of each withdrawal is taxed', 'risk' => 'Market-linked, flexible'],
        'fd_monthly' => ['label' => 'Bank FD with monthly interest (senior rate)', 'rate' => 7.25, 'max_per_person' => null, 'payout' => 'monthly', 'tax' => 'Interest taxable; 80TTB up to ₹50,000 for seniors', 'risk' => 'DICGC cover up to ₹5 lakh per bank'],
    ],

    // Example retirement-income and protection plans by category (names only; premiums and rates change).
    'plans' => [
        'immediate_annuity' => ['LIC Jeevan Akshay VII', 'HDFC Life Pension Guaranteed Plan', 'SBI Life Annuity Plus'],
        'deferred_annuity' => ['LIC New Jeevan Shanti', 'ICICI Pru Guaranteed Pension Plan', 'Max Life Guaranteed Lifetime Income'],
        'nps' => ['NPS Tier-I (any Point of Presence or eNPS)'],
        'term' => ['Pure term plan from an insurer with claim settlement ratio above 98%'],
        'health' => ['Family floater with ₹10 lakh+ base cover and a super top-up'],
    ],

    'defaults' => [
        'age' => 30, 'retirement_age' => 60, 'life_expectancy' => 85, 'monthly_salary' => 39000, 'salary_growth' => 7,
        'monthly_expenses' => 25000, 'current_savings' => 0, 'risk' => 'moderate', 'target_monthly_income' => 150000,
        'target_in_todays_money' => false, 'inflation' => 6, 'post_retirement_return' => 8, 'step_up' => 5, 'regime' => 'new',
        'existing_life_cover' => 0, 'existing_health_cover' => 0, 'liabilities' => 0, 'city_tier' => 'metro',
        'investments' => [
            ['type' => 'equity_mf', 'monthly' => 5000], ['type' => 'endowment', 'monthly' => 2000], ['type' => 'fd', 'monthly' => 1000],
            ['type' => 'rd', 'monthly' => 1000], ['type' => 'ulip', 'monthly' => 3000],
        ],
        'family' => [],
    ],
];
