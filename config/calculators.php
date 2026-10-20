<?php

/*
 * Financial calculators (/calculators). Each slug has a matching formula in resources/views/calculators/engine.blade.php.
 * group: card section on the hub page. metal: the commodity whose price history is charted (commodity_prices).
 */
return [
    'groups' => ['metals' => 'Gold, Silver & Copper', 'loans' => 'Loans & EMI', 'investments' => 'Investments & Returns',
        'savings' => 'Savings & Government Schemes', 'retirement' => 'Retirement & Salary', 'tax' => 'Tax & Business'],
    'items' => [
        'gold' => ['title' => 'Gold Calculator', 'group' => 'metals', 'icon' => '🥇', 'metal' => 'gold', 'text' => 'Price of gold jewellery or coins with purity, making charges and GST, and the gold price trend'],
        'silver' => ['title' => 'Silver Calculator', 'group' => 'metals', 'icon' => '🥈', 'metal' => 'silver', 'text' => 'Value of silver by weight and purity with making charges and GST, and the silver price trend'],
        'copper' => ['title' => 'Copper Calculator', 'group' => 'metals', 'icon' => '🟠', 'metal' => 'copper', 'text' => 'Cost of copper by weight with GST, and the copper price trend'],
        'personal-loan-emi' => ['title' => 'Personal Loan EMI', 'group' => 'loans', 'icon' => '💳', 'text' => 'Monthly EMI, total interest and repayment schedule of a personal loan'],
        'home-loan-emi' => ['title' => 'Home Loan EMI', 'group' => 'loans', 'icon' => '🏠', 'text' => 'Monthly EMI, total interest and repayment schedule of a home loan'],
        'car-loan-emi' => ['title' => 'Car Loan EMI', 'group' => 'loans', 'icon' => '🚗', 'text' => 'Monthly EMI and total interest of a car loan'],
        'bike-loan-emi' => ['title' => 'Bike Loan EMI', 'group' => 'loans', 'icon' => '🏍️', 'text' => 'Monthly EMI and total interest of a two-wheeler loan'],
        'home-loan-prepayment' => ['title' => 'Home Loan Prepayment', 'group' => 'loans', 'icon' => '⏩', 'text' => 'Interest and months saved by a part-prepayment of your home loan'],
        'mutual-fund' => ['title' => 'Mutual Fund', 'group' => 'investments', 'icon' => '📈', 'text' => 'Estimated returns on a one-time mutual fund investment'],
        'sip' => ['title' => 'SIP', 'group' => 'investments', 'icon' => '🗓️', 'text' => 'Future value of a monthly SIP, with an optional yearly step-up'],
        'lumpsum' => ['title' => 'Lumpsum', 'group' => 'investments', 'icon' => '💰', 'text' => 'Growth of a one-time investment at a fixed annual return'],
        'swp' => ['title' => 'SWP', 'group' => 'investments', 'icon' => '🔁', 'text' => 'Monthly withdrawals from an investment and the balance left'],
        'cagr' => ['title' => 'CAGR', 'group' => 'investments', 'icon' => '📊', 'text' => 'Compound annual growth rate between a starting and ending value'],
        'roi' => ['title' => 'Return on Investment', 'group' => 'investments', 'icon' => '🎯', 'text' => 'Absolute and annualised return on an investment'],
        'npv' => ['title' => 'NPV', 'group' => 'investments', 'icon' => '🧮', 'text' => 'Net present value of yearly cash flows at a discount rate'],
        'fixed-deposit' => ['title' => 'Fixed Deposit (FD)', 'group' => 'savings', 'icon' => '🏦', 'text' => 'Maturity value and interest of a fixed deposit'],
        'recurring-deposit' => ['title' => 'Recurring Deposit (RD)', 'group' => 'savings', 'icon' => '🪙', 'text' => 'Maturity value of monthly recurring deposits'],
        'ppf' => ['title' => 'PPF', 'group' => 'savings', 'icon' => '🛡️', 'text' => 'Maturity value of Public Provident Fund yearly deposits'],
        'nsc' => ['title' => 'NSC', 'group' => 'savings', 'icon' => '📜', 'text' => 'Maturity value of National Savings Certificates'],
        'ssy' => ['title' => 'Sukanya Samriddhi (SSY)', 'group' => 'savings', 'icon' => '👧', 'text' => 'Maturity value of Sukanya Samriddhi Yojana deposits for a girl child'],
        'post-office-mis' => ['title' => 'Post Office Monthly Income', 'group' => 'savings', 'icon' => '📮', 'text' => 'Monthly income from the Post Office Monthly Income Scheme'],
        'simple-interest' => ['title' => 'Simple Interest', 'group' => 'savings', 'icon' => '➗', 'text' => 'Interest on a principal at a simple annual rate'],
        'compound-interest' => ['title' => 'Compound Interest', 'group' => 'savings', 'icon' => '✖️', 'text' => 'Interest compounded yearly, half-yearly, quarterly or monthly'],
        'epf' => ['title' => 'PF / EPF', 'group' => 'retirement', 'icon' => '🧾', 'text' => 'EPF balance at retirement from your and your employer\'s contributions'],
        'nps' => ['title' => 'NPS', 'group' => 'retirement', 'icon' => '🧓', 'text' => 'National Pension System corpus, lump sum and monthly pension'],
        'apy' => ['title' => 'Atal Pension Yojana (APY)', 'group' => 'retirement', 'icon' => '🪪', 'text' => 'Monthly APY contribution for the pension you want'],
        'pension' => ['title' => 'Retirement Pension', 'group' => 'retirement', 'icon' => '🌅', 'text' => 'Corpus needed for retirement and the monthly SIP to build it'],
        'gratuity' => ['title' => 'Gratuity', 'group' => 'retirement', 'icon' => '🎁', 'text' => 'Gratuity payable under the Payment of Gratuity Act'],
        'hra' => ['title' => 'HRA Exemption', 'group' => 'retirement', 'icon' => '🏘️', 'text' => 'Tax-exempt and taxable part of your house rent allowance'],
        'gst' => ['title' => 'GST', 'group' => 'tax', 'icon' => '🧾', 'text' => 'GST amount, CGST/SGST split and price including or excluding GST'],
        'depreciation' => ['title' => 'Depreciation', 'group' => 'tax', 'icon' => '📉', 'text' => 'Yearly depreciation by straight-line or written-down value method'],
    ],
];
