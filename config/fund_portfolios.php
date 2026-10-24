<?php

// AMC monthly portfolio disclosure pages that publish plain file links (seed for mf_portfolio_sources), and the
// fund credit quality (CQR) parameters (seed for credit_methodology_settings). Both are edited in the database.
return [
    'sources' => [
        'DSP Mutual Fund' => ['https://www.dspim.com/mandatory-disclosures/portfolio-disclosures', '/month-?end[-_ ]portfolio|monthend-portfolio/i'],
        'Tata Mutual Fund' => ['https://www.tatamutualfund.com/schemes-related/portfolio', '/monthly portfolio as on/i'],
        'Union Mutual Fund' => ['https://www.unionmf.com/about-us/downloads/monthly-portfolio', '/monthly-portfolio-report-/i'],
        'PPFAS Mutual Fund' => ['https://amc.ppfas.com/downloads/portfolio-disclosure/', '/monthly_portfolio_report.*\.xlsx$/i'],
        'Helios Mutual Fund' => ['https://www.heliosmf.in/portfolio-disclosure/', '/monthly-portfolio-as-on/i'],
        'The Wealth Company Mutual Fund' => ['https://www.wealthcompanyamc.in/literature-forms/portfolio-documents/monthly/', '/monthly_portfolio_/i'],
        'Baroda BNP Paribas Mutual Fund' => ['https://www.barodabnpparibasmf.in/downloads/monthly-portfolio-scheme', '/monthly_portfolio.*\.xlsx$/i'],
        'Groww Mutual Fund' => ['https://growwmf.in/statutory-disclosure/portfolio', '/monthly portfolio/i'],
        'Monarch Mutual Fund' => ['https://www.monarchamc.in/forms-downloads/portfolio-holdings/monthly', '/monthly_portfolio.*\.xlsx$/i'],
    ],

    // Annual default rate (%) by rating, from long-run rating default studies; a security's credit score is the cumulative
    // default probability over its residual maturity, in basis points.
    'fund_pd_annual' => [
        'AAA' => 0.01, 'AA+' => 0.03, 'AA' => 0.06, 'AA-' => 0.1, 'A+' => 0.18, 'A' => 0.3, 'A-' => 0.45, 'BBB+' => 0.8, 'BBB' => 1.2, 'BBB-' => 1.8,
        'BB+' => 3, 'BB' => 4.5, 'BB-' => 6, 'B+' => 9, 'B' => 12, 'B-' => 16, 'C+' => 25, 'C' => 30, 'C-' => 35, 'D' => 100,
    ],

    // Short-term ratings scored at the top of the long-term range they map to (A1+ covers AAA to AA-).
    'fund_short_equivalent' => ['A1+' => 'AAA', 'A1' => 'AA-', 'A2+' => 'A', 'A2' => 'BBB+', 'A3+' => 'BBB', 'A3' => 'BBB-', 'A4+' => 'BB+', 'A4' => 'BB', 'D' => 'D'],

    // SEBI single-issuer limits (% of NAV) by the issuer's rating band; exposure above the limit is scored one notch lower.
    'fund_rules' => [
        'limit_aaa' => 10, 'limit_aa' => 8, 'limit_a_below' => 6, 'excess_notch' => 1, 'watch_notch' => 1,
        'unrated_as' => 'BBB-', 'min_debt_share' => 65, 'short_term_max_years' => 1, 'lookback_months' => 3,
    ],
];
