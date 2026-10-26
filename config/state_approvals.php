<?php

// Each state's single-window portal (seed for state_approval_sources; the owner edits the table). A driver reads the
// portal's published service list, document checklists, policies and acts; states without one link to their portal.
return [
    'sources' => [
        'UK' => ['Invest Uttarakhand (SWCS)', 'https://investuttarakhand.uk.gov.in', 'swcs_uk', [
            'services_url' => 'https://investuttarakhand.uk.gov.in/site/ServiceListingNew/iw/N/id/ALL',
            'policies_url' => 'https://investuttarakhand.uk.gov.in/site/Policies',
            'acts_url' => 'https://investuttarakhand.uk.gov.in/site/ActsRulesNotifications',
        ]],
        'UP' => ['Nivesh Mitra', 'https://niveshmitra.up.nic.in', 'nsws,policy_pages', []],
        'RJ' => ['Rajasthan Single Window (RajNivesh)', 'https://rajnivesh.rajasthan.gov.in', 'nsws,policy_pages', []],
        'OD' => ['GO-SWIFT Odisha', 'https://investodisha.gov.in/GOSWIFT/', 'nsws,policy_pages', []],
        'PB' => ['Invest Punjab', 'https://investpunjab.gov.in', 'nsws,policy_pages', []],
        'HR' => ['Invest Haryana (HEPC)', 'https://investharyana.in', 'nsws,policy_pages', []],
        'TS' => ['TS-iPASS', 'https://ipass.telangana.gov.in', 'nsws,policy_pages', []],
        'TN' => ['Tamil Nadu Single Window Portal', 'https://tnswp.com', 'nsws,policy_pages', []],
        'KL' => ['K-SWIFT Kerala', 'https://kswift.kerala.gov.in', 'nsws,policy_pages', []],
        'WB' => ['Silpasathi West Bengal', 'https://silpasathi.wb.gov.in', 'silpasathi_wb', [
            'services_url' => 'https://silpasathi.wb.gov.in/comprehensive-information-sheet',
        ]],
        'HP' => ['Emerging Himachal', 'https://emerginghimachal.hp.gov.in', 'nsws,policy_pages', []],
        'ML' => ['Invest Meghalaya', 'https://investmeghalaya.gov.in', 'meghalaya', [
            'services_url' => 'https://investmeghalaya.gov.in/resources/homePage/17/megeodb/services.html',
            'policies_url' => 'https://investmeghalaya.gov.in',
        ]],
    ],

    // Shown for every state: the national portal that routes central and state approvals.
    'national' => ['National Single Window System', 'https://www.nsws.gov.in'],

    // Every other state / UT: approvals from NSWS, which lists each state's approvals with their documents and timelines.
    'default_driver' => 'nsws',

    // Pause between portal requests (milliseconds) so a full fetch stays polite.
    'request_gap_ms' => 250,
];
