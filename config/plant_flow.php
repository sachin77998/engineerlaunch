<?php

// How material moves through a typical plant, and the departments and roles hired at each stage.
// Shown on manufacturing sector pages; every role links to matching openings.
return [
    'sectors' => [
        'sector-automobile-manufacturers', 'sector-auto-components-forging-casting', 'sector-steel-metals-pipes',
        'sector-cement-building-materials-polymers', 'sector-heavy-engineering-capital-goods', 'sector-food-beverages',
        'sector-fmcg-personal-home-care', 'sector-pharmaceuticals-healthcare', 'sector-apparel-textiles-footwear',
        'sector-semiconductors-electronics', 'sector-electrical-equipment-appliances',
    ],
    'stages' => [
        ['name' => 'Gate & Material Inward', 'departments' => ['Security', 'Stores (Inward)', 'Incoming Quality'],
            'roles' => ['Security Guard', 'Gate Entry Clerk', 'Store Keeper', 'Store Executive (ITI)', 'Incoming Quality Inspector']],
        ['name' => 'Stores & PPC', 'departments' => ['Stores', 'PPC (Production Planning & Control)'],
            'roles' => ['Store Assistant', 'PPC Engineer', 'Production Planner', 'Material Handler', 'Forklift Operator']],
        ['name' => 'Furnace / Melting', 'departments' => ['Melting Shop', 'Heat Treatment'],
            'roles' => ['Melter', 'Furnace Operator', 'Ladle Operator', 'Heat Treatment Operator', 'Spectro Lab Chemist']],
        ['name' => 'Production Shop', 'departments' => ['Production', 'Moulding / Forging / Press Shop', 'Assembly'],
            'roles' => ['Production Supervisor', 'Mould Master Operator', 'Core Shooter Machine Operator', 'Forging Hammer Operator', 'Press Operator', 'Die Setter Technician', 'Assembly Line Operator', 'Welder', 'Fitter']],
        ['name' => 'Machine Shop & Tool Room', 'departments' => ['Machine Shop', 'Tool Room'],
            'roles' => ['VMC Operator', 'CNC Operator', 'Turner', 'Milling Machinist', 'Grinding Operator', 'Tool Room Machinist', 'Tool, Die & Fixture Technician', 'Pattern Maker']],
        ['name' => 'Quality & Testing', 'departments' => ['Quality Assurance', 'Testing Lab', 'Metrology'],
            'roles' => ['Quality Inspector', 'Casting Inspector', 'Core Inspector', 'CMM Operator', 'Lab Attendant', 'QA Engineer', 'NDT Technician']],
        ['name' => 'Packaging & Dispatch', 'departments' => ['Packaging', 'Dispatch & Logistics'],
            'roles' => ['Packing Operator', 'Dispatch Executive', 'Driver', 'Loader / Helper']],
        ['name' => 'Support Departments', 'departments' => ['Maintenance', 'Purchase', 'R&D / Engineering', 'Accounts', 'HR & Admin', 'EHS'],
            'roles' => ['Maintenance Fitter', 'Die Maintenance Fitter', 'Electrician', 'Conveyor Technician', 'Purchase Executive', 'Commodity Buyer', 'Design Engineer', 'Accountant', 'HR Executive', 'Safety Officer', 'Peon']],
    ],
];
