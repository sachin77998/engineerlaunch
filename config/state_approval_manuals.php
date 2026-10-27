<?php

// Documents and processing steps from the departments' own user manuals, kept apart from the portal / checklist data the
// importer refreshes (stored with source 'manual'). Services are matched by state + external_id; 'new' services are added
// (and locked, so the importer never deactivates them). Document '*' = mandatory on the form.
//
// Punjab — Invest Punjab FastTrack portal, Department of Labour (Director of Factories) user manuals by NIC Punjab, 2026.
$caf = 'Before applying: the project\'s Common Application Form (CAF) must be verified by the Sector Officer at Invest Punjab or the GMDIC at the District Industries Centre. Apply from My Projects → Manage Project Applications → Apply for New Clearance.';
$download = ['Investor', 'Downloads the issued clearance from the project page after entering an OTP sent to the registered mobile, and can rate the department\'s service.'];
$preScrutiny = [
    ['Investor', 'Fills the form, uploads the documents and clicks Save and Submit. The application goes to the Dealing Hand under Pre-Scrutiny, with a scrutiny due date.'],
    ['Dealing Hand', 'Checks the form, attachments and logs, confirms "I have checked all fields of Application Form with attachments", and forwards it to the Nodal Officer with comments.'],
    ['Nodal Officer', 'Raises a pre-scrutiny observation, completes pre-scrutiny, or marks the application incomplete.'],
    ['Investor', 'If an observation is raised: edits the form (Update Application Form), saves it and replies with a comment to resolve the observation.'],
    ['Nodal Officer', 'Reviews the reply and completes pre-scrutiny (or marks the application incomplete).'],
];
$licenceDocs = [
    ['Signature of Occupier', true], ['Scan copy of undertaking duly signed and stamped by the Occupier', true], ['Scan copy of old licence / certificate', true],
    ['Name and values of principal products manufactured during the last twelve months', true], ['Acceptance letter (DOF building plan)', true],
    ['Signature of Manager', true], ['Approval letter of building plan (building bye-laws)', true], ['Stability certificate', true],
    ['NOC from the Department of Fire', true], ['Latest electricity bill', true], ['Payment receipt (if the fee is already paid)', null],
];
$licenceNotes = [
    'The form asks for: applicant (Aadhaar, PAN), project and factory details — whether the building was constructed before 29 June 2018, changes to the building plan, old licence number and validity, licence period, employees and connected load (kW) under the old licence, manufacturing processes and products of the last 12 and next 12 months, NIC code, maximum and ordinary workers, installed and proposed kW; manager, occupier and building-owner details; stability certificate number and date and the stability DOF number; whether the building plan is approved, Labour Welfare Fund paid, annual return filed and stability certificate attached.',
    'Processing fee is shown on the form and paid online; attachments are PDF.',
    'Department timeline shown on the dashboard: 45 days (DOF).',
];

return [
    'Punjab' => [
        'manual_base' => 'https://fasttrack.punjab.gov.in/assets/docs/sampleforms/',
        'services' => [
            // Factory licence — fresh.
            ['external_id' => 'NS1879-0',
                'sop_url' => 'https://fasttrack.punjab.gov.in/assets/docs/sampleforms/Registration_and_renewal_of_license_under_The_Factories_Act_UserManual.pdf',
                'documents' => $licenceDocs,
                'notes' => array_merge([$caf], $licenceNotes),
                'steps' => array_merge($preScrutiny, [
                    ['Dealing Hand', 'After pre-scrutiny, processes the application and forwards it to the Nodal Officer (Director of Factories).'],
                    ['Nodal Officer', 'Raises fee, forwards to the Dealing Hand, forwards to the HOD (Additional CEO) for further processing, rejects, raises an observation / clarification, or issues the clearance.'],
                    ['Additional CEO (HOD)', 'Raises fee, forwards to the Nodal Officer or the CEO, rejects, or raises an observation.'],
                    ['CEO', 'Returns the file to the HOD with observations or for further processing.'],
                    ['Additional CEO (HOD)', 'Sends the file back to the Nodal Officer.'],
                    ['Nodal Officer', 'Raises any final observation (the investor edits the form and sends a reply), then selects Issue Clearance: clearance ID, issue date, optional expiry, generated or uploaded certificate.'],
                    $download,
                ])],
            // Factory licence — renewal (its own service on FastTrack).
            ['external_id' => 'PBDOF-RENEWAL', 'new' => true, 'department' => 'Department of Labour (Director of Factories)',
                'name' => 'Application for Registration / Grant of Licence under the Factories Act 1948 (Renewal)', 'service_type' => 'Statutory', 'stages' => ['Pre-construction'],
                'sop_url' => 'https://fasttrack.punjab.gov.in/assets/docs/sampleforms/Registration_and_renewal_of_license_under_The_Factories_Act_UserManual.pdf',
                'documents' => $licenceDocs,
                'notes' => array_merge([$caf, 'Choose Renewal against "Application for Registration / Grant of Licence Under The Factory Act - 1948 (Renewal)" and enter the renewal-from date.'], $licenceNotes),
                'steps' => array_merge($preScrutiny, [
                    ['Department', 'After pre-scrutiny the file follows the same departmental flow as a fresh licence (Nodal Officer → HOD → CEO → issue of the renewed licence).'],
                    $download,
                ])],
            // Building plan — existing building with stability certificate (addition / amendment).
            ['external_id' => 'NS4034-0',
                'sop_url' => 'https://fasttrack.punjab.gov.in/assets/docs/sampleforms/Approval_of_plan_and_permission_to_construct_Building_Plan_UserManual.pdf',
                'documents' => [
                    ['Old scan copy of approved site plan and approval letter (site plan with approval letter, one PDF)', true, 'For an existing building with a stability certificate (amendment of the building plan)'],
                    ['Appointment letter of competent person', true], ['Site land pattern', true], ['Form No. 1-C', true], ['Process flow chart', true], ['Form No. 1-D', true],
                    ['List of machineries', null], ['Form No. 1-E', true], ['C.A. certificate', true], ['Form No. 1-F', null], ['Undertaking', true],
                    ['Copy of registration / licence under the Factories Act 1948', true], ['Other documents', null], ['Site plan', null], ['Main plan', null],
                    ['Sections', null], ['Elevations and others', null], ['AutoCAD (.dwg) version of the drawings', null],
                ],
                'notes' => [$caf,
                    'Choose Addition (approval of building plan of an existing building with stability certificate) or Amendment as applicable; give the dispatch / reference number and issue date of the earlier approval.',
                    'The form records the proposed and actual building cost (₹ lakh) and 1% of each — the fee is computed on building cost.',
                    'Attachments are PDF (up to 20 MB); drawings may also be uploaded in .dwg.'],
                'steps' => [
                    ['Investor', 'Fills the form and clicks Save and Submit.'],
                    ['Department', 'Processed as in the departmental user manual for approval of proposed building plans under the Factories Act (pre-scrutiny, observations, approval).'],
                    $download,
                ]],
            // Stability certificate.
            ['external_id' => 'NS4035-0',
                'documents' => [
                    ['Latest approved site plan', true], ['Approval letter of building plan (all references)', true], ['Stability certificate (to be submitted)', true],
                    ['Undertaking given by the competent person (specimen given in the form)', true], ['Detail plan (all approved)', true],
                    ['Undertaking given by the employer (specimen given in the form)', true], ['Copy of last stability certificate', null, 'If any'],
                ],
                'notes' => [$caf,
                    'Service: "Submission of Stability Certificate Under Factories Act-1948". The form asks for the business entity and project address, the authority that approved the building plan and its stability DOF number and date, the authority that gave the building-plan stability approval and its DOF number, and whether the competent person has reported changes in building structure, plan, machinery, raw material, fuel or chemicals.',
                    'Declarations: the applicant applies for fresh plans if the structure has changed since approval; the competent person certifies the building is structurally stable with no variation from the approved plan. False information leads to rejection.',
                    'Attachments: PDF, under 20 MB.'],
                'steps' => [
                    ['Investor', 'Fills the form, accepts the terms and clicks Save and Submit.'],
                    ['Department', 'Records the submission; it appears under Already Issued Clearances with its clearance ID and dates.'],
                    $download,
                ]],
            // Notice of completion — Director of Factories (Form F).
            ['external_id' => 'PBDOF-COMPLETION', 'new' => true, 'department' => 'Department of Labour (Director of Factories)',
                'name' => 'Notice of Completion and Part Completion - DOF', 'service_type' => 'Statutory', 'stages' => ['Pre-construction'],
                'sop_url' => 'https://fasttrack.punjab.gov.in/assets/docs/sampleforms/Approval_of_plan_and_permission_to_construct_Building_Plan_UserManual.pdf',
                'documents' => [
                    ['Completion certificate by a registered Architect / Engineer', true], ['Plumber certificate', true],
                    ['Copy of approved building plans', true], ['Completion plans or as-built drawings (.dwg)', true],
                ],
                'notes' => [$caf,
                    'Form F [rule 7]: notice to the competent authority (DOF) that the building, or part of it, sanctioned under the earlier order (number and date) was completed on the stated date as per the Punjab Regional and Town Planning and Development Act 1995 and its rules, with permission sought to occupy the building under Rule 14 of the Punjab Urban Planning and Development Building Rules 2021.',
                    'Enter the application PIN (and the rejected application ID when re-applying) and click Get Details to fill the project.'],
                'steps' => [
                    ['Investor', 'Fills Form F, attaches the certificates and drawings and clicks Save and Submit.'],
                    ['Department', 'Processed as in the departmental user manual for building plans under the Factories Act.'],
                ]],
        ],
    ],
];
