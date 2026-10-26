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
        'UP' => ['Nivesh Mitra', 'https://niveshmitra.up.nic.in', null, []],
        'RJ' => ['Rajasthan Single Window (RajNivesh)', 'https://rajnivesh.rajasthan.gov.in', null, []],
        'OD' => ['GO-SWIFT Odisha', 'https://investodisha.gov.in/GOSWIFT/', null, []],
        'PB' => ['Invest Punjab', 'https://investpunjab.gov.in', null, []],
        'HR' => ['Invest Haryana (HEPC)', 'https://investharyana.in', null, []],
        'TS' => ['TS-iPASS', 'https://ipass.telangana.gov.in', null, []],
        'TN' => ['Tamil Nadu Single Window Portal', 'https://tnswp.com', null, []],
        'KL' => ['K-SWIFT Kerala', 'https://kswift.kerala.gov.in', null, []],
        'WB' => ['Silpasathi West Bengal', 'https://silpasathi.wb.gov.in', null, []],
        'HP' => ['Emerging Himachal', 'https://emerginghimachal.hp.gov.in', null, []],
        'ML' => ['Invest Meghalaya', 'https://investmeghalaya.gov.in', null, []],
    ],

    // Shown for every state: the national portal that routes central and state approvals.
    'national' => ['National Single Window System', 'https://www.nsws.gov.in'],

    // Pause between portal requests (milliseconds) so a full fetch stays polite.
    'request_gap_ms' => 250,
];
