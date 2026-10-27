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
    // ICRA Rating Methodology – Mutual Funds (June 2026). Same default-rate table and security scores as above; benchmarks
    // sit between grades and are read at the higher of the fund's WAM and its category's benchmark maturity — ICRA's
    // "typical residual maturity" of the category: the median WAM of that category's schemes each month unless a value is
    // set here (corporate bond: 4 years, the methodology's example). Short-term-only paper takes the issuer's long-term
    // rating, else the median long-term rating of same-grade issuers that month, else the lowest it maps to (below).
    'icra' => [
        'category_maturity' => ['Corporate Bond Fund' => 4],
        'short_lowest_long' => ['A1+' => 'A', 'A1' => 'A-', 'A2+' => 'BBB+', 'A2' => 'BBB', 'A3+' => 'BBB-', 'A3' => 'BBB-', 'A4+' => 'BB+', 'A4' => 'BB', 'D' => 'D'],
        'long_to_short' => ['AAA' => 'A1+', 'AA+' => 'A1+', 'AA' => 'A1+', 'AA-' => 'A1+', 'A+' => 'A1+', 'A' => 'A1', 'A-' => 'A2+', 'BBB+' => 'A2', 'BBB' => 'A3+', 'BBB-' => 'A3'],
        'unrated_as' => 'BBB-', 'invit_notch' => 1, 'invit_years' => 5,
        // CDMDF units: notional AAA at the fund's own 7-day paper in normal times; in a market dislocation A2 units are
        // A+ (or lower) at the CDMDF's residual tenor and A3 units at least one notch lower at three years.
        'cdmdf_dislocation' => false, 'cdmdf_dislocation_rating' => 'A+', 'cdmdf_a3_notch' => 1, 'cdmdf_a3_years' => 3,
        'lookback_months' => 3, 'upgrade_months' => 3, 'sharp_breach_notches' => 2,
    ],

    'fund_rules' => [
        'limit_aaa' => 10, 'limit_aa' => 8, 'limit_a_below' => 6, 'excess_notch' => 1, 'watch_notch' => 1,
        'unrated_as' => 'BBB-', 'min_debt_share' => 65, 'short_term_max_years' => 1, 'lookback_months' => 3,
    ],
];
