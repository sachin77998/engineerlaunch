<?php

// Sector profiles for the credit assessment on company pages, built from CareEdge rating methodologies
// (manufacturing, services, financial ratios for non-financial and financial sectors, and the sector papers:
// non-ferrous metals, mobile service providers, manmade yarn, cotton textile, hotels, hospitals, fertilisers,
// education, cut & polished diamonds, commercial vehicles, cement, auto components, investment holding companies).
//
// Each ratio band is [excellent, good, adequate, weak]; values past "weak" score lowest. `dir` says whether higher
// or lower is better. Thresholds are indicative calibration points, not published agency cut-offs.
// Pillar weights add to 100: business, financial (leverage + coverage), liquidity, management & governance.

return [
    'ratios' => [
        'toi_crore' => ['label' => 'Total operating income (₹ cr)', 'dir' => 'up', 'band' => [5000, 1000, 250, 50], 'pillar' => 'business'],
        'growth_toi' => ['label' => 'Growth in TOI (%)', 'dir' => 'up', 'band' => [15, 8, 0, -10], 'pillar' => 'business'],
        'pbildt_margin' => ['label' => 'PBILDT margin (%)', 'dir' => 'up', 'band' => [15, 10, 6, 3], 'pillar' => 'business'],
        'pat_margin' => ['label' => 'PAT margin (%)', 'dir' => 'up', 'band' => [10, 6, 3, 0], 'pillar' => 'business'],
        'margin_stability' => ['label' => 'PBILDT margin swing, 3 yrs (pp)', 'dir' => 'down', 'band' => [2, 4, 7, 12], 'pillar' => 'business'],
        'roce' => ['label' => 'ROCE (%)', 'dir' => 'up', 'band' => [20, 15, 10, 6], 'pillar' => 'business'],
        'ronw' => ['label' => 'RONW (%)', 'dir' => 'up', 'band' => [18, 12, 8, 3], 'pillar' => 'business'],
        'lt_debt_equity' => ['label' => 'Long-term debt / TNW (x)', 'dir' => 'down', 'band' => [0.3, 0.7, 1.2, 2], 'pillar' => 'financial'],
        'gearing' => ['label' => 'Overall gearing (x)', 'dir' => 'down', 'band' => [0.5, 1, 1.5, 2.5], 'pillar' => 'financial'],
        'tol_tnw' => ['label' => 'TOL / TNW (x)', 'dir' => 'down', 'band' => [1, 1.5, 2.5, 4], 'pillar' => 'financial'],
        'debt_pbildt' => ['label' => 'Total debt / PBILDT (x)', 'dir' => 'down', 'band' => [1.5, 2.5, 3.5, 5], 'pillar' => 'financial'],
        'debt_gca' => ['label' => 'Total debt / GCA (years)', 'dir' => 'down', 'band' => [2, 3.5, 5, 8], 'pillar' => 'financial'],
        'icr' => ['label' => 'Interest coverage (PBILDT / interest, x)', 'dir' => 'up', 'band' => [8, 5, 3, 1.5], 'pillar' => 'financial'],
        'dscr' => ['label' => 'DSCR (x)', 'dir' => 'up', 'band' => [2, 1.5, 1.2, 1], 'pillar' => 'financial'],
        'current_ratio' => ['label' => 'Current ratio (x)', 'dir' => 'up', 'band' => [2, 1.5, 1.2, 1], 'pillar' => 'liquidity'],
        'quick_ratio' => ['label' => 'Quick ratio (x)', 'dir' => 'up', 'band' => [1.5, 1.1, 0.8, 0.5], 'pillar' => 'liquidity'],
        'cash_st_debt' => ['label' => 'Cash / short-term debt (x)', 'dir' => 'up', 'band' => [1.5, 0.8, 0.4, 0.15], 'pillar' => 'liquidity'],
        'collection_days' => ['label' => 'Average collection period (days)', 'dir' => 'down', 'band' => [30, 60, 90, 130], 'pillar' => 'liquidity'],
        'inventory_days' => ['label' => 'Average inventory period (days)', 'dir' => 'down', 'band' => [30, 60, 90, 130], 'pillar' => 'liquidity'],
        'creditor_days' => ['label' => 'Average creditors period (days)', 'dir' => 'down', 'band' => [45, 75, 110, 160], 'pillar' => 'liquidity'],
        'wc_cycle' => ['label' => 'Working capital cycle (days)', 'dir' => 'down', 'band' => [45, 75, 110, 150], 'pillar' => 'liquidity'],
        'fixed_asset_turnover' => ['label' => 'Fixed asset turnover (x)', 'dir' => 'up', 'band' => [3, 2, 1.2, 0.6], 'pillar' => 'business'],
        'nwc_ca' => ['label' => 'Net working capital / current assets (%)', 'dir' => 'up', 'band' => [40, 25, 12, 0], 'pillar' => 'liquidity'],
        // Financial sector (banks / NBFCs / HFCs)
        'rota' => ['label' => 'Return on total assets (%)', 'dir' => 'up', 'band' => [2.5, 1.8, 1.2, 0.5], 'pillar' => 'business'],
        'fin_gearing' => ['label' => 'Gearing (total debt / TNW, x)', 'dir' => 'down', 'band' => [3, 5, 7, 9], 'pillar' => 'financial'],
        'cost_to_income' => ['label' => 'Cost to income (%)', 'dir' => 'down', 'band' => [35, 45, 55, 70], 'pillar' => 'business'],
        'cost_of_borrowing' => ['label' => 'Cost of borrowing (%)', 'dir' => 'down', 'band' => [7.5, 9, 11, 13], 'pillar' => 'financial'],
        // Governance
        'promoter_holding' => ['label' => 'Promoter holding (%)', 'dir' => 'up', 'band' => [60, 45, 30, 15], 'pillar' => 'management'],
        'rpt_revenue' => ['label' => 'Related-party transactions / revenue (%)', 'dir' => 'down', 'band' => [5, 15, 30, 50], 'pillar' => 'management'],
    ],

    'default_weights' => ['business' => 30, 'financial' => 35, 'liquidity' => 20, 'management' => 15],

    'sectors' => [
        'manufacturing' => [
            'name' => 'Manufacturing', 'criteria' => 'Rating Methodology – Manufacturing Companies (Mar 2025)',
            'ratios' => ['toi_crore', 'growth_toi', 'pbildt_margin', 'margin_stability', 'roce', 'lt_debt_equity', 'gearing', 'tol_tnw', 'debt_pbildt', 'debt_gca', 'icr', 'dscr', 'current_ratio', 'quick_ratio', 'cash_st_debt', 'wc_cycle', 'fixed_asset_turnover', 'promoter_holding', 'rpt_revenue'],
            'checks' => ['capex_vs_networth', 'negative_cfo_proxy', 'group_support'],
        ],
        'non_ferrous' => [
            'name' => 'Non-ferrous metals', 'criteria' => 'Rating Methodology – Non-Ferrous Metals (Sep 2024)',
            'match' => ['industry' => '/aluminium|copper|zinc|non[- ]?ferrous/i', 'name' => '/alumin|copper|zinc|hindalco|vedanta|nalco|hindustan zinc|metal/i', 'nic' => ['2420']],
            'override' => ['pbildt_margin' => [18, 12, 8, 4], 'debt_pbildt' => [1.5, 2.5, 3.5, 5]],
            'weights' => ['business' => 35, 'financial' => 35, 'liquidity' => 15, 'management' => 15],
            'checks' => ['commodity_margin_volatility', 'forex_exposure', 'capex_vs_networth'],
        ],
        'telecom' => [
            'name' => 'Mobile service providers', 'criteria' => 'Rating Methodology – Mobile Service Providers (Mar 2025)',
            'match' => ['industry' => '/^telecom|telecommunications/i', 'name' => '/telecom|airtel|vodafone|jio|tele ?services|tata communications/i', 'nic' => ['61']],
            'override' => ['pbildt_margin' => [45, 35, 25, 12], 'gearing' => [1, 2, 3, 4.5], 'debt_pbildt' => [2, 3, 4, 6], 'fixed_asset_turnover' => [1, 0.6, 0.4, 0.2]],
            'notes' => ['Spectrum is counted in net worth (CareEdge does not deduct it as an intangible for operators).'],
            'weights' => ['business' => 35, 'financial' => 40, 'liquidity' => 10, 'management' => 15],
            'checks' => ['capex_vs_networth', 'group_support'],
        ],
        'manmade_yarn' => [
            'name' => 'Manmade yarn', 'criteria' => 'Rating Methodology – Manmade Yarn Manufacturing (Sep 2026)',
            'match' => ['name' => '/polyester|viscose|synthetic|nylon|acrylic|filament|spandex|\bpolyfil|texturis|(?<!opti)\bfibres?\b/i', 'nic' => ['2030', '1312']],
            'override' => ['pbildt_margin' => [13, 10, 7, 4], 'wc_cycle' => [60, 90, 120, 160], 'inventory_days' => [35, 60, 90, 120]],
            'checks' => ['commodity_margin_volatility', 'inventory_build', 'forex_exposure'],
        ],
        'cotton_textile' => [
            'name' => 'Cotton textile', 'criteria' => 'Rating Methodology – Cotton Textile (Sep 2026)',
            'match' => ['industry' => '/textile|apparel|garment/i', 'name' => '/cotton|spinning|textile|spintex|weav|denim|apparel|garment|fabrics|mills/i', 'nic' => ['13', '14']],
            'override' => ['pbildt_margin' => [13, 10, 7, 4], 'wc_cycle' => [70, 100, 140, 180], 'inventory_days' => [45, 75, 110, 150]],
            'checks' => ['commodity_margin_volatility', 'inventory_build', 'forex_exposure'],
        ],
        'hotel' => [
            'name' => 'Hotels', 'criteria' => 'Rating Methodology – Hotel Industry (Feb 2025)',
            'match' => ['industry' => '/hotel|hospitality|resort/i', 'name' => '/hotel|resort|hospitality|inns?\b|palace/i', 'nic' => ['55']],
            'override' => ['pbildt_margin' => [35, 27, 18, 10], 'collection_days' => [15, 30, 45, 70], 'fixed_asset_turnover' => [0.8, 0.5, 0.3, 0.15]],
            'skip' => ['inventory_days', 'wc_cycle', 'quick_ratio'],
            'notes' => ['Lease liabilities are part of total debt.'],
            'weights' => ['business' => 35, 'financial' => 35, 'liquidity' => 15, 'management' => 15],
            'checks' => ['capex_vs_networth', 'seasonality', 'group_support'],
        ],
        'hospital' => [
            'name' => 'Hospitals', 'criteria' => 'Rating Methodology – Hospitals (Feb 2025)',
            'match' => ['industry' => '/hospital|healthcare services/i', 'name' => '/hospital|medicare|clinic|medical (?:centre|center|college)|health ?city/i', 'nic' => ['861', '862']],
            'override' => ['pbildt_margin' => [24, 17, 11, 6], 'collection_days' => [30, 50, 75, 100], 'fixed_asset_turnover' => [1.5, 1, 0.6, 0.3]],
            'skip' => ['inventory_days'],
            'checks' => ['capex_vs_networth', 'receivable_stretch', 'keyman'],
        ],
        'fertiliser' => [
            'name' => 'Fertilisers', 'criteria' => 'CARE Ratings’ Criteria for Rating Fertilizer Companies (Aug 2026)',
            'match' => ['name' => '/fertili[sz]er|chambal|coromandel|rashtriya chemicals|deepak fert|gnfc|gsfc|nfl\b|urea|phosphat|zuari/i', 'nic' => ['2012']],
            'override' => ['collection_days' => [60, 100, 150, 210], 'wc_cycle' => [70, 110, 160, 220], 'pbildt_margin' => [12, 8, 5, 3]],
            'notes' => ['Collection period includes subsidy receivables (adjusted collection period).'],
            'checks' => ['receivable_stretch', 'forex_exposure', 'commodity_margin_volatility'],
        ],
        'education' => [
            'name' => 'Education', 'criteria' => 'Rating Methodology – Education Sector (Dec 2024)',
            'match' => ['industry' => '/education/i', 'name' => '/education|school|college|university|academy|institute|learning|edu/i', 'nic' => ['85']],
            'override' => ['pbildt_margin' => [30, 20, 12, 5], 'collection_days' => [10, 25, 50, 80]],
            'skip' => ['inventory_days', 'creditor_days', 'wc_cycle', 'quick_ratio', 'current_ratio'],
            'notes' => ['Surplus (PBILDT) margin is the operating margin; traditional current ratios are not used.'],
            'weights' => ['business' => 35, 'financial' => 35, 'liquidity' => 10, 'management' => 20],
            'checks' => ['capex_vs_networth', 'seasonality'],
        ],
        'diamond' => [
            'name' => 'Cut & polished diamonds', 'criteria' => 'Rating Methodology – Cut and Polished Diamond Industry (Sep 2026)',
            'match' => ['industry' => '/gems|jewel|diamond/i', 'name' => '/diamond|gems|jewel|exports? .*surat/i', 'nic' => ['3211', '2399']],
            'override' => ['pbildt_margin' => [8, 6, 4, 2], 'wc_cycle' => [120, 160, 200, 250], 'inventory_days' => [90, 130, 170, 220], 'collection_days' => [60, 90, 120, 160], 'gearing' => [0.7, 1.2, 1.8, 2.5]],
            'weights' => ['business' => 25, 'financial' => 30, 'liquidity' => 30, 'management' => 15],
            'checks' => ['inventory_build', 'receivable_stretch', 'forex_exposure', 'related_party_heavy'],
        ],
        'commercial_vehicles' => [
            'name' => 'Commercial vehicles', 'criteria' => 'Rating Methodology – Commercial Vehicles (Sep 2026)',
            'match' => ['name' => '/ashok leyland|eicher|commercial vehicle|truck|bus\b|sml isuzu|force motors|tata motors|volvo|bharatbenz|olectra/i', 'nic' => ['2920']],
            'override' => ['pbildt_margin' => [12, 9, 6, 3], 'toi_crore' => [20000, 5000, 1000, 250]],
            'weights' => ['business' => 35, 'financial' => 35, 'liquidity' => 15, 'management' => 15],
            'checks' => ['commodity_margin_volatility', 'capex_vs_networth', 'forex_exposure'],
        ],
        'auto_components' => [
            'name' => 'Auto components', 'criteria' => 'Rating Methodology – Auto Components and Equipment (Oct 2026)',
            'match' => ['industry' => '/auto components|forging|casting|bearing/i', 'name' => '/auto|forg|axle|gear|bearing|brake|piston|castings|components|tyre|wheels?\b/i', 'nic' => ['2930', '2814', '2211']],
            'override' => ['pbildt_margin' => [15, 11, 8, 5]],
            'checks' => ['ice_obsolescence', 'capex_vs_networth', 'group_support', 'negative_cfo_proxy'],
        ],
        'cement' => [
            'name' => 'Cement', 'criteria' => 'CARE Ratings’ Criteria for Rating Cement Companies (Sep 2024)',
            'match' => ['industry' => '/cement/i', 'name' => '/cement|ultratech|ambuja|acc\b|shree|dalmia|ramco/i', 'nic' => ['2394']],
            'override' => ['pbildt_margin' => [22, 17, 12, 8], 'debt_pbildt' => [1.5, 2.5, 3.5, 5], 'fixed_asset_turnover' => [1.2, 0.9, 0.6, 0.35]],
            'weights' => ['business' => 35, 'financial' => 35, 'liquidity' => 15, 'management' => 15],
            'checks' => ['capex_vs_networth', 'seasonality', 'commodity_margin_volatility'],
        ],
        'services' => [
            'name' => 'Services', 'criteria' => 'Rating Methodology – Service Sector Companies',
            'match' => ['industry' => '/it services|software|services|logistics|media|e-commerce|technology|data/i', 'nic' => ['62', '63', '49', '52', '73']],
            'override' => ['fixed_asset_turnover' => [6, 4, 2, 1]],
            'skip' => ['inventory_days'],
            'checks' => ['receivable_stretch', 'group_support'],
        ],
        'bank' => [
            'name' => 'Banks', 'criteria' => 'Financial Ratios – Financial Sector (Feb 2025)',
            'match' => ['industry' => '/^banks?$|^banking$/i', 'name' => '/\bbank\b/i', 'nic' => ['6419']],
            'ratios' => ['toi_crore', 'growth_toi', 'pat_margin', 'rota', 'ronw', 'fin_gearing', 'cost_to_income', 'cost_of_borrowing', 'promoter_holding'],
            'override' => ['toi_crore' => [50000, 10000, 2000, 500], 'fin_gearing' => [10, 13, 16, 20], 'rota' => [1.3, 1, 0.7, 0.3], 'cost_of_borrowing' => [5, 6, 7, 8.5]],
            'weights' => ['business' => 40, 'financial' => 40, 'liquidity' => 0, 'management' => 20],
            'checks' => [],
        ],
        'financial' => [
            'name' => 'Banks, NBFCs & HFCs', 'criteria' => 'Financial Ratios – Financial Sector (Feb 2025)',
            'match' => ['industry' => '/bank|financ|nbfc|lending|insurance|capital markets/i', 'name' => '/bank|finance|financial|credit|capital|leasing|housing fin|fincorp|microfin/i', 'nic' => ['64', '65', '66']],
            'ratios' => ['toi_crore', 'growth_toi', 'pat_margin', 'rota', 'ronw', 'fin_gearing', 'cost_to_income', 'cost_of_borrowing', 'promoter_holding'],
            'override' => ['toi_crore' => [10000, 2000, 500, 100]],
            'weights' => ['business' => 40, 'financial' => 40, 'liquidity' => 0, 'management' => 20],
            'checks' => ['group_support'],
        ],
        'investment_holding' => [
            'name' => 'Investment holding company', 'criteria' => 'CARE Ratings’ Criteria on Rating of Investment Holding Companies’ Debt (Mar 2025)',
            'match' => ['name' => '/investments? (?:ltd|limited|pvt|private)|holdings? (?:ltd|limited)|investment company|core investment/i', 'nic' => ['6420']],
            'ratios' => ['toi_crore', 'pat_margin', 'ronw', 'gearing', 'icr', 'cash_st_debt', 'promoter_holding'],
            'notes' => ['Assessed standalone; refinancing ability rests on investment values and promoter support.'],
            'weights' => ['business' => 25, 'financial' => 45, 'liquidity' => 15, 'management' => 15],
            'checks' => ['group_support'],
        ],
    ],

    // Indicative score → long-term rating band (CARE scale).
    'bands' => [92 => 'AAA', 86 => 'AA+', 81 => 'AA', 76 => 'AA-', 71 => 'A+', 66 => 'A', 61 => 'A-', 56 => 'BBB+', 51 => 'BBB', 46 => 'BBB-', 41 => 'BB+', 36 => 'BB', 31 => 'BB-', 26 => 'B+', 21 => 'B', 16 => 'B-', 0 => 'C'],

    // Indicative short-term mapping: [usual, with superior liquidity].
    'short_term' => [
        'AAA' => ['A1+', 'A1+'], 'AA+' => ['A1+', 'A1+'], 'AA' => ['A1+', 'A1+'], 'AA-' => ['A1+', 'A1+'], 'A+' => ['A1', 'A1+'],
        'A' => ['A1', 'A1+'], 'A-' => ['A2+', 'A1'], 'BBB+' => ['A2', 'A2+'], 'BBB' => ['A3+', 'A2'], 'BBB-' => ['A3', 'A3+'],
        'BB+' => ['A4+', 'A4+'], 'BB' => ['A4', 'A4+'], 'BB-' => ['A4', 'A4+'], 'B+' => ['A4', 'A4'], 'B' => ['A4', 'A4'],
        'B-' => ['A4', 'A4'], 'C+' => ['A4', 'A4'], 'C' => ['A4', 'A4'], 'C-' => ['A4', 'A4'], 'D' => ['D', 'D'],
    ],
];
