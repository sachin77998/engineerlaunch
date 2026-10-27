<?php

// ICRA sector rating methodologies (seed for credit_agency_criteria; the owner edits live values at /admin/methodology).
// Each factor carries ICRA's published "strongest" and "weakest" indicative levels. ICRA grades factors on a five-step
// scale (Strong, Comfortable, Adequate, Moderate, Weak; see each methodology's annexure); only the two ends are published,
// so the three middle cut points are spaced evenly between them (`spacing` linear for percentages, log for multiples and
// scale). `metric` names the figure the assessment supplies; factors without one are qualitative and shown, not graded.
// ICRA states its ratings are set by rating committees, not a formula: the grid is a factor view, not an ICRA rating.

return [
    'icra_life_insurance' => [
        'agency' => 'ICRA', 'sector_key' => 'life_insurance', 'name' => 'Life insurance',
        'criteria' => 'ICRA Rating Methodology – Life Insurance (September 2026)', 'published_on' => '2026-09-01',
        'factors' => [
            // Business risk — franchise (page 4) and governance / reserving variance.
            ['key' => 'nbp_share', 'group' => 'business', 'sub' => 'Franchise', 'label' => 'Market share in overall NBP (%)', 'metric' => 'nbp_share', 'dir' => 'up', 'unit' => '%', 'spacing' => 'log',
                'strongest_value' => 5.0, 'weakest_value' => 0.3, 'strongest' => '≥ 5.0%', 'weakest' => '≤ 0.3%'],
            ['key' => 'ape_share', 'group' => 'business', 'sub' => 'Franchise', 'label' => 'Market share in individual APE (%)', 'metric' => 'ape_share', 'dir' => 'up', 'unit' => '%', 'spacing' => 'log',
                'strongest_value' => 6.0, 'weakest_value' => 1.0, 'strongest' => '≥ 6.0%', 'weakest' => '≤ 1.0%'],
            ['key' => 'par_ulip', 'group' => 'business', 'sub' => 'Franchise', 'label' => 'Par + ULIP share of individual APE (%)', 'metric' => 'par_ulip_share', 'dir' => 'up', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 50, 'weakest_value' => 10, 'strongest' => '≥ 50%', 'weakest' => '≤ 10%'],
            ['key' => 'top_bank', 'group' => 'business', 'sub' => 'Franchise', 'label' => 'Topmost bank partner contribution to NBP (%)', 'metric' => 'top_bank_share', 'dir' => 'down', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 30, 'weakest_value' => 70, 'strongest' => '≤ 30%', 'weakest' => '≥ 70%'],
            ['key' => 'governance', 'group' => 'business', 'sub' => 'Governance and reserving variance', 'label' => 'Board, audit and disclosures; operating variance in the EV walk',
                'strongest' => 'Independent board beyond the minimum, unmodified audit opinions, arm’s-length related-party dealings; consistently favourable operating variance (actual experience better than assumptions)',
                'weakest' => 'Regulatory lapses, unexplained resignations, material deviations; persistent adverse operating variance (optimistic assumptions)'],
            // Financial risk — earnings strength and stability (page 6).
            ['key' => 'persistency', 'group' => 'financial', 'sub' => 'Earnings strength and stability', 'label' => '13th month persistency (%)', 'metric' => 'persistency_13m', 'dir' => 'up', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 87, 'weakest_value' => 80, 'strongest' => '≥ 87.0%', 'weakest' => '≤ 80.0%'],
            ['key' => 'expense_ratio', 'group' => 'financial', 'sub' => 'Earnings strength and stability', 'label' => 'Total expense ratio incl. commission (%)', 'metric' => 'life_expense_ratio', 'dir' => 'down', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 18, 'weakest_value' => 26, 'strongest' => '≤ 18.0%', 'weakest' => '≥ 26.0%'],
            ['key' => 'roev', 'group' => 'financial', 'sub' => 'Earnings strength and stability', 'label' => 'Return on EV (%)', 'metric' => 'roev', 'dir' => 'up', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 18, 'weakest_value' => 10, 'strongest' => '≥ 18.0%', 'weakest' => '≤ 10.0%'],
            ['key' => 'ronw', 'group' => 'financial', 'sub' => 'Earnings strength and stability', 'label' => 'Return on average net worth (%, if RoEV is not available)', 'metric' => 'ronw', 'fallback_for' => 'roev', 'dir' => 'up', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 8, 'weakest_value' => 4, 'strongest' => '≥ 8.0%', 'weakest' => '≤ 4.0%'],
            // Capitalisation (page 7).
            ['key' => 'core_solvency', 'group' => 'financial', 'sub' => 'Capitalisation', 'label' => 'Core solvency (x)', 'metric' => 'core_solvency', 'dir' => 'up', 'unit' => 'x', 'spacing' => 'log',
                'strongest_value' => 2.10, 'weakest_value' => 1.40, 'strongest' => '≥ 2.10x', 'weakest' => '≤ 1.40x'],
            ['key' => 'solvency', 'group' => 'financial', 'sub' => 'Capitalisation', 'label' => 'Solvency (x; IRDAI minimum 1.50x)', 'metric' => 'solvency_ratio', 'dir' => 'up', 'unit' => 'x', 'spacing' => 'log',
                'strongest_value' => 2.20, 'weakest_value' => 1.50, 'strongest' => '≥ 2.20x', 'weakest' => '≤ 1.50x'],
            ['key' => 'capital_need', 'group' => 'financial', 'sub' => 'Capitalisation', 'label' => 'Capital required over next two years / existing ASM excl. sub-debt (%)', 'metric' => 'capital_need_asm', 'dir' => 'down', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 0, 'weakest_value' => 25, 'strongest' => '0%', 'weakest' => '≥ 25%'],
            // Mortality risk and reinsurance (page 7).
            ['key' => 'asm_nsar', 'group' => 'financial', 'sub' => 'Mortality risk and reinsurance', 'label' => 'ASM / risk retained (total net sum assured, %)', 'metric' => 'asm_nsar', 'dir' => 'up', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 1.4, 'weakest_value' => 0.6, 'strongest' => '≥ 1.4%', 'weakest' => '≤ 0.6%'],
            ['key' => 'claims_gsa', 'group' => 'financial', 'sub' => 'Mortality risk and reinsurance', 'label' => 'Gross mortality claims / previous year-end gross sum assured (%)', 'metric' => 'claims_gsa', 'dir' => 'down', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 0.1, 'weakest_value' => 0.4, 'strongest' => '≤ 0.1%', 'weakest' => '≥ 0.4%'],
            ['key' => 'reinsurance', 'group' => 'financial', 'sub' => 'Mortality risk and reinsurance', 'label' => 'Mortality claims received from reinsurers, last five years (%)', 'metric' => 'reinsurance_claims_share', 'dir' => 'up', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 40, 'weakest_value' => 10, 'strongest' => '≥ 40%', 'weakest' => '≤ 10%'],
            // Investment risk (page 8).
            ['key' => 'nnpa_asm', 'group' => 'financial', 'sub' => 'Investment risk', 'label' => 'Net NPAs in investment book (excl. ULIP) / ASM (%)', 'metric' => 'nnpa_asm', 'dir' => 'down', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 0, 'weakest_value' => 8, 'strongest' => '≤ 0%', 'weakest' => '≥ 8%'],
            ['key' => 'nonlinked_asm', 'group' => 'financial', 'sub' => 'Investment risk', 'label' => 'Non-linked investments / ASM (x)', 'metric' => 'nonlinked_asm', 'dir' => 'down', 'unit' => 'x', 'spacing' => 'linear',
                'strongest_value' => 9.0, 'weakest_value' => 13.0, 'strongest' => '≤ 9.0x', 'weakest' => '≥ 13.0x'],
            ['key' => 'surrender_aum', 'group' => 'financial', 'sub' => 'Investment risk', 'label' => 'Surrender benefits paid / AUM (%)', 'metric' => 'surrender_aum', 'dir' => 'down', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 2, 'weakest_value' => 8, 'strongest' => '≤ 2%', 'weakest' => '≥ 8%'],
            ['key' => 'mv_bv', 'group' => 'financial', 'sub' => 'Investment risk', 'label' => 'MV less BV of non-linked debt investments / ASM (%)', 'metric' => 'mv_bv_asm', 'dir' => 'up', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 30, 'weakest_value' => -10, 'strongest' => '≥ 30%', 'weakest' => '≤ -10%'],
            // Liquidity (page 8), graded Superior … Poor.
            ['key' => 'npw_claims', 'group' => 'liquidity', 'label' => 'Net premium written last year / max net claims paid in last three years (x)', 'metric' => 'npw_claims', 'dir' => 'up', 'unit' => 'x', 'spacing' => 'log',
                'strongest_value' => 3.0, 'weakest_value' => 0.9, 'strongest' => '≥ 3.0x', 'weakest' => '≤ 0.9x'],
            ['key' => 'gov_sec', 'group' => 'liquidity', 'label' => 'Central + state government securities / total investments (%)', 'metric' => 'gov_sec_share', 'dir' => 'up', 'unit' => '%', 'spacing' => 'linear',
                'strongest_value' => 65, 'weakest_value' => 40, 'strongest' => '≥ 65%', 'weakest' => '≤ 40%'],
            ['key' => 'sh_inv_subdebt', 'group' => 'liquidity', 'label' => 'Shareholders’ investments / sub-debt (x)', 'metric' => 'sh_inv_subdebt', 'dir' => 'up', 'unit' => 'x', 'spacing' => 'log',
                'strongest_value' => 6.0, 'weakest_value' => 2.5, 'strongest' => '≥ 6.0x', 'weakest' => '≤ 2.5x'],
        ],
        'adjustments' => [
            ['key' => 'parent_support', 'label' => 'Likelihood of parent support (intent, reputation sensitivity, strategic importance)', 'metric' => 'parent_support'],
            ['key' => 'hybrid_servicing', 'label' => 'Sub-debt / preference shares: solvency buffer for servicing and call', 'metric' => 'hybrid_servicing'],
            ['key' => 'management', 'label' => 'Governance: related-party transactions', 'metric' => 'rpt_revenue'],
            ['key' => 'esg', 'label' => 'Environmental and social risks (data security, mis-selling, regional concentration)'],
        ],
        'notes' => [
            'APE = regular annualised premium from new business + 10% of first-year single premium; RoEV = (VNB + unwind + operating assumption changes + operating variance) / opening EV.',
            'Hybrid instruments are notched down from the Issuer Rating and can at best equal it; the notch depends on the solvency buffer, the promoter’s ability to bring capital and its record of doing so.',
            'Sub-debt may not exceed the lower of 50% of paid-up equity plus premium and 50% of net worth excluding FVCA (December 2022 regulations).',
        ],
    ],
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
