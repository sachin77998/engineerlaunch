<?php

// ICRA sector rating methodologies (seed for credit_agency_criteria; the owner edits live values at /admin/methodology).
// Each factor carries ICRA's published "strongest" and "weakest" indicative levels. ICRA grades factors on a five-step
// scale (Strong, Comfortable, Adequate, Moderate, Weak; see each methodology's annexure); only the two ends are published,
// so the three middle cut points are spaced evenly between them (`spacing` linear for percentages, log for multiples and
// scale). `metric` names the figure the assessment supplies; factors without one are qualitative and shown, not graded.
// ICRA states its ratings are set by rating committees, not a formula: the grid is a factor view, not an ICRA rating.

return [
    'icra_auto_components' => [
        'agency' => 'ICRA', 'sector_key' => 'auto_components', 'name' => 'Auto components',
        'criteria' => 'ICRA Rating Methodology – Auto Components (October 2024)', 'published_on' => '2024-10-01',
        'factors' => [
            // Industry risk (Cyclicality, Regulatory risk, Competitive intensity) — assessed by the analyst.
            ['key' => 'industry_position', 'group' => 'industry', 'label' => 'Industry position (cyclicality, regulation, competition)',
                'strongest' => 'High value-added products with technology or capital entry barriers (e.g. cold forged, engine components); low exposure to cyclical CV demand',
                'weakest' => 'Commoditised, low entry-barrier products (e.g. sheet metal) facing unorganised and import competition; heavy CV-cycle exposure'],
            // Business risk — summary of the salient business risk factors (page 6).
            ['key' => 'scale', 'group' => 'business', 'label' => 'Scale (operating income)', 'metric' => 'toi_crore', 'dir' => 'up', 'unit' => '₹ cr', 'spacing' => 'log',
                'strongest_value' => 2500, 'weakest_value' => 250, 'strongest' => '> ₹2,500 crore', 'weakest' => 'Less than ₹250 crore'],
            ['key' => 'geographic', 'group' => 'business', 'label' => 'Geographic diversification (largest region, % of revenue)', 'metric' => 'largest_region_share', 'dir' => 'down', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 50, 'weakest_value' => 85, 'strongest' => 'No region accounts for > 50% of revenues', 'weakest' => 'One region accounts for > 85% of revenues'],
            ['key' => 'customer', 'group' => 'business', 'label' => 'Customer diversification (largest customer, % of revenue)', 'metric' => 'largest_customer_share', 'dir' => 'down', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 30, 'weakest_value' => 90, 'strongest' => 'Largest customer accounts for < 30% of revenues', 'weakest' => 'Largest customer accounts for > 90% of revenues'],
            ['key' => 'market_share', 'group' => 'business', 'label' => 'Share of business with OEMs and market share of OEMs',
                'strongest' => "Industry market share of the OEMs supplied is > 40% and the entity's share of business of the OEM is > 70%",
                'weakest' => "Industry market share of the OEMs supplied is < 5% and the entity's share of business of the OEMs is < 30%"],
            ['key' => 'segment', 'group' => 'business', 'label' => 'Segment diversification (largest of M&HCV / PV / 2W / tractors, % of revenue)', 'metric' => 'largest_segment_share', 'dir' => 'down', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 30, 'weakest_value' => 99.99, 'strongest' => 'No single segment accounts for > 30% of revenues', 'weakest' => 'Presence in a single segment'],
            ['key' => 'aftermarket', 'group' => 'business', 'label' => 'OEM supplies vs replacement mix (aftermarket, % of sales)', 'metric' => 'aftermarket_share', 'dir' => 'up', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 30, 'weakest_value' => 5, 'strongest' => '> 30% of sales derived from aftermarket', 'weakest' => '< 5% of sales derived from aftermarket'],
            ['key' => 'technology', 'group' => 'business', 'label' => 'Technological capabilities and barriers to entry',
                'strongest' => 'Strong in-house design, engineering and development, or subsidiary of a leading foreign player with ready access to technical know-how',
                'weakest' => 'Commoditised products are the majority of revenues; mature technologies with structurally high competitive intensity'],
            // Financial risk — indicative metrics (pages 8–9).
            ['key' => 'roce', 'group' => 'financial', 'sub' => 'Profitability and earnings stability', 'label' => 'RoCE (%)', 'metric' => 'roce', 'dir' => 'up', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 25, 'weakest_value' => 10, 'strongest' => '>= 25%', 'weakest' => '< 10%'],
            ['key' => 'roce_volatility', 'group' => 'financial', 'sub' => 'Profitability and earnings stability', 'label' => 'Volatility in RoCE (%)', 'metric' => 'roce_volatility', 'dir' => 'down', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 10, 'weakest_value' => 55, 'strongest' => '<= 10%', 'weakest' => '> 55%'],
            ['key' => 'indebtedness', 'group' => 'financial', 'sub' => 'Leverage', 'label' => 'Total indebtedness ratio (TOL / TNW, x)', 'metric' => 'tol_tnw', 'dir' => 'down', 'unit' => 'x', 'spacing' => 'log',
                'strongest_value' => 0.9, 'weakest_value' => 3.0, 'strongest' => '<= 0.9x', 'weakest' => '> 3.0x'],
            ['key' => 'debt_profit', 'group' => 'financial', 'sub' => 'Leverage', 'label' => 'Debt to profit (total debt / OPBDITA, x)', 'metric' => 'debt_pbildt', 'dir' => 'down', 'unit' => 'x', 'spacing' => 'log',
                'strongest_value' => 0.5, 'weakest_value' => 5.0, 'strongest' => '<= 0.5x', 'weakest' => '> 5.0x'],
            ['key' => 'interest_coverage', 'group' => 'financial', 'sub' => 'Coverage', 'label' => 'Interest coverage (OPBDITA / interest, x)', 'metric' => 'icr', 'dir' => 'up', 'unit' => 'x', 'spacing' => 'log',
                'strongest_value' => 18, 'weakest_value' => 2, 'strongest' => '>= 18.0x', 'weakest' => '< 2.0x'],
            ['key' => 'dscr', 'group' => 'financial', 'sub' => 'Coverage', 'label' => 'DSCR (x)', 'metric' => 'dscr', 'dir' => 'up', 'unit' => 'x', 'spacing' => 'log',
                'strongest_value' => 4, 'weakest_value' => 1.1, 'strongest' => '>= 4.0x', 'weakest' => '< 1.1x'],
        ],
        // "Do these factors enhance or hinder the credit profile?" (annexure) and parent support.
        'adjustments' => [
            ['key' => 'asset_diversification', 'label' => 'Diversification (manufacturing locations near OEM hubs)'],
            ['key' => 'liquidity', 'label' => 'Refinancing dependence, liquidity and financial flexibility', 'metric' => 'liquidity'],
            ['key' => 'pledge', 'label' => 'Pledged promoter shareholding (financial flexibility)'],
            ['key' => 'forex', 'label' => 'Foreign exchange risk (net forex earnings or unhedged exposure vs profits)'],
            ['key' => 'financial_policy', 'label' => 'Financial policy'],
            ['key' => 'management', 'label' => 'Management, governance and reporting', 'metric' => 'rpt_revenue'],
            ['key' => 'parent_support', 'label' => 'Likelihood of parent support (rating of parent)', 'metric' => 'parent_support'],
        ],
        'notes' => [
            'Indicative metrics are what ICRA generally considers relatively strong or weak; weaker metrics on one parameter can be offset by others, a healthy business profile, financial flexibility or a strong promoter group.',
            'Receivables and debt are adjusted for bills discounted off-balance sheet; supplier bills discounted with recourse are added to debt.',
            'Promoters as individuals and private equity or other financial investors are not treated as parents for extraordinary support.',
        ],
    ],
];
