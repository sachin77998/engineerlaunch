<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\{
    Company,
    IndustrialDepartment,
    IndustrialJobRole,
    IndustrialJobRoleAlias,
    IndustrialSector,
    IndustrialProcess
};

class IndustrialRecruitmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('industrial_sources.feeds') as $source) {
            $company = $this->upsertCompany($source['name'], $source + [
                'slug' => Str::slug($source['name']),
                'country' => 'India',
                'sector' => 'Manufacturing',
                'sync_enabled' => true,
                'is_active' => true,
            ]);

            $company->save();
        }

        /*
        |--------------------------------------------------------------------------
        | Jalandhar Industrial Area
        |--------------------------------------------------------------------------
        |
        | Only official company/career URLs that are actually known are stored.
        | Do not create fake career URLs.
        |
        */
        $this->seedJalandharIndustrialCompanies();

        $this->seedAjayIndustriesJalandhar();

        /*
        |--------------------------------------------------------------------------
        | Hoshiarpur
        |--------------------------------------------------------------------------
        */
        $state = \App\Models\IndustrialState::firstOrCreate(
            ['slug' => 'punjab'],
            ['name' => 'Punjab', 'is_active' => true]
        );

        $source = 'https://www.sonalika.com/manufacturing-excellence.html';

        $area = \App\Models\IndustrialArea::firstOrCreate(
            [
                'state_id' => $state->id,
                'slug' => 'hoshiarpur-manufacturing-cluster'
            ],
            [
                'name' => 'Hoshiarpur Manufacturing Cluster',
                'city' => 'Hoshiarpur',
                'district' => 'Hoshiarpur',
                'area_type' => 'industrial_cluster',
                'description' => 'City-level manufacturing cluster; individual facilities are not assumed to belong to a PSIEC estate.',
                'is_active' => true,
                'source_name' => 'Sonalika official manufacturing facility',
                'source_url' => $source,
                'last_verified_at' => '2026-09-16',
                'verification_status' => 'company_source'
            ]
        );

        $plant = \App\Models\IndustrialCompany::firstOrCreate(
            [
                'industrial_area_id' => $area->id,
                'slug' => 'sonalika'
            ],
            [
                'name' => 'Sonalika',
                'plant_name' => 'International Tractors Limited, Hoshiarpur',
                'website' => 'https://www.sonalika.com',
                'industry' => 'Tractor Manufacturing',
                'is_active' => true,
                'is_verified' => true,
                'source_name' => 'Sonalika official manufacturing facility',
                'source_url' => $source,
                'last_verified_at' => '2026-09-16',
                'verification_status' => 'company_source'
            ]
        );

        $sectorIds = IndustrialSector::whereIn(
            'slug',
            ['automobile', 'auto-components', 'heavy-engineering']
        )->pluck('id');

        $plant->sectors()->syncWithoutDetaching($sectorIds);
        $area->sectorCatalog()->syncWithoutDetaching($sectorIds);

        /*
        |--------------------------------------------------------------------------
        | Uttarakhand
        |--------------------------------------------------------------------------
        */
        foreach (
            [
                [
                    'Tata Motors',
                    'Pantnagar',
                    'https://www.tatamotors.com',
                    'https://www.tatamotors.com/press-releases/tata-motors-flags-off-electric-buses-for-workforce-transportation-in-pantnagar-reiterates-its-commitment-towards-carbon-neutrality/'
                ],
                [
                    'Mahindra and Mahindra',
                    'Rudrapur',
                    'https://www.mahindra.com',
                    'https://www.mahindra.com/annual-report-FY2026/46/'
                ],
            ] as [$name, $city, $website, $evidence]
        ) {

            $state = \App\Models\IndustrialState::firstOrCreate(
                ['slug' => 'uttarakhand'],
                ['name' => 'Uttarakhand', 'is_active' => true]
            );

            $area = \App\Models\IndustrialArea::where(
                'state_id',
                $state->id
            )->where(
                'city',
                $city
            )->first();

            if (!$area) {
                $area = \App\Models\IndustrialArea::create([
                    'state_id' => $state->id,
                    'name' => $city . ' Manufacturing Cluster',
                    'slug' => Str::slug($city . ' Manufacturing Cluster'),
                    'city' => $city,
                    'district' => 'Udham Singh Nagar',
                    'area_type' => 'industrial_cluster',
                    'is_active' => true,
                    'source_name' => 'Company facility evidence',
                    'source_url' => $evidence,
                    'last_verified_at' => '2026-09-16',
                    'verification_status' => 'company_source'
                ]);
            }

            $plant = \App\Models\IndustrialCompany::firstOrCreate(
                [
                    'industrial_area_id' => $area->id,
                    'slug' => Str::slug($name)
                ],
                [
                    'name' => $name,
                    'plant_name' => $city . ' facility',
                    'website' => $website,
                    'industry' => 'Manufacturing',
                    'is_active' => true,
                    'is_verified' => true,
                    'source_name' => 'Official company facility evidence',
                    'source_url' => $evidence,
                    'last_verified_at' => '2026-09-16',
                    'verification_status' => 'company_source'
                ]
            );

            $plant->sectors()->syncWithoutDetaching($sectorIds);
            $area->sectorCatalog()->syncWithoutDetaching($sectorIds);
        }

        /*
        |--------------------------------------------------------------------------
        | Punjab Manufacturer Directory
        |--------------------------------------------------------------------------
        */
        foreach (
            [
                [
                    'Happy Forgings',
                    'Ludhiana',
                    'Ludhiana',
                    'https://happyforgingsltd.com',
                    'https://happyforgingsltd.com/opportunities/',
                    'Multiple Ludhiana facilities; exact site depends on the opening'
                ],
                [
                    'Euro Cast & Forge',
                    'Ludhiana',
                    'Ludhiana',
                    'https://www.eurocastforge.com',
                    'https://www.eurocastforge.com/',
                    'Casting and forging manufacturer; vacancy feed not yet verified'
                ],
                [
                    'GNA Axles',
                    'Mehtiana',
                    'Hoshiarpur',
                    'https://www.gnaaxles.in',
                    'https://www.gnaaxles.in/',
                    'Mehtiana facility, Hoshiarpur district; vacancy feed not yet verified'
                ],
            ] as [$name, $city, $district, $website, $evidence, $note]
        ) {

            $state = \App\Models\IndustrialState::firstOrCreate(
                ['slug' => 'punjab'],
                ['name' => 'Punjab', 'is_active' => true]
            );

            $area = \App\Models\IndustrialArea::firstOrCreate(
                [
                    'state_id' => $state->id,
                    'slug' => Str::slug($city . ' Manufacturing Cluster')
                ],
                [
                    'name' => $city . ' Manufacturing Cluster',
                    'city' => $city,
                    'district' => $district,
                    'area_type' => 'industrial_cluster',
                    'is_active' => true,
                    'description' => 'City-level manufacturer directory; not an assertion of membership in a specific industrial estate.',
                    'source_name' => 'Official manufacturer website',
                    'source_url' => $evidence,
                    'last_verified_at' => '2026-09-16',
                    'verification_status' => 'company_source'
                ]
            );

            $plant = \App\Models\IndustrialCompany::firstOrCreate(
                [
                    'industrial_area_id' => $area->id,
                    'slug' => Str::slug($name)
                ],
                [
                    'name' => $name,
                    'website' => $website,
                    'description' => $note,
                    'industry' => 'Auto Components and Manufacturing',
                    'is_active' => true,
                    'is_verified' => true,
                    'source_name' => 'Official manufacturer website',
                    'source_url' => $evidence,
                    'last_verified_at' => '2026-09-16',
                    'verification_status' => 'company_source'
                ]
            );

            $plant->sectors()->syncWithoutDetaching($sectorIds);
            $area->sectorCatalog()->syncWithoutDetaching($sectorIds);
        }

        /*
        |--------------------------------------------------------------------------
        | Departments
        |--------------------------------------------------------------------------
        */
        foreach (
            [
                'PPC',
                'Material Inward',
                'Packaging',
                'Research and Development',
                'Service',
                'Customer Care'
            ] as $name
        ) {
            IndustrialDepartment::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'category' => 'Manufacturing support',
                    'is_active' => true
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Industrial Job Roles
        |--------------------------------------------------------------------------
        */
        $rows = [
            [
                'VMC Operator',
                'VMC',
                ['VMC Machine Operator', 'Vertical Machining Centre Operator'],
                ['Tool Offset', 'Drawing Reading', 'Vernier', 'Micrometer'],
                ['cnc-milling']
            ],
            [
                'Die Maintenance Fitter',
                'Die Maintenance',
                ['Die Repair Fitter'],
                ['Die Repair', 'Fitting', 'Drawing Reading'],
                ['die-maintenance']
            ],
            [
                'Heavy Machine Shop Technician',
                'Machine Shop',
                ['Heavy Machining Technician'],
                ['Machining', 'Measuring Instruments', 'Drawing Reading'],
                ['cnc-turning', 'cnc-milling']
            ],
            [
                'Conveyor Technician',
                'Maintenance',
                ['Conveyor Maintenance Technician'],
                ['Conveyor Maintenance', 'Alignment', 'Bearings'],
                ['mechanical-maintenance']
            ],
            [
                'Tool or Die Fixture Technician',
                'Tool Room',
                ['Tool Fixture Technician', 'Die Fixture Technician'],
                ['Jigs and Fixtures', 'Fitting', 'Tool Maintenance'],
                ['tool-making']
            ],
            [
                'Die Setter Technician',
                'Tool Room',
                ['Die Setter', 'Press Die Setter'],
                ['Die Setting', 'Press Setup', 'Tool Alignment'],
                ['closed-die-forging']
            ],
            [
                'Tractor Mechanic',
                'Maintenance',
                ['Agricultural Tractor Mechanic'],
                ['Diesel Engines', 'Hydraulics', 'Transmission'],
                ['mechanical-maintenance']
            ],
            [
                'Maintenance Fitter',
                'Maintenance',
                ['Mechanical Maintenance Fitter'],
                ['Fitting', 'Bearings', 'Alignment'],
                ['mechanical-maintenance']
            ],
            [
                'Diesel Mechanic',
                'Diesel Mechanic',
                ['Diesel Engine Mechanic'],
                ['Diesel Engines', 'Fuel Injection', 'Engine Diagnostics'],
                ['mechanical-maintenance']
            ],
        ];

        foreach ($rows as [$name, $department, $aliases, $skills, $processes]) {
            $dept = IndustrialDepartment::firstOrCreate(
                ['slug' => Str::slug($department)],
                [
                    'name' => $department,
                    'is_active' => true
                ]
            );

            $role = IndustrialJobRole::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'department_id' => $dept->id,
                    'is_active' => true
                ]
            );

            foreach ($aliases as $alias) {
                IndustrialJobRoleAlias::firstOrCreate(
                    ['slug' => Str::slug($alias)],
                    [
                        'name' => $alias,
                        'job_role_id' => $role->id
                    ]
                );
            }

            $profile = $role->profile()->firstOrCreate(
                [],
                [
                    'source_name' => 'Industrial trade learning dictionary; requirements vary by employer'
                ]
            );

            $profile->update([
                'skills' => array_values(
                    array_unique(
                        array_merge(
                            $profile->skills ?? [],
                            $skills
                        )
                    )
                ),
                'qualifications' => array_values(
                    array_unique(
                        array_merge(
                            $profile->qualifications ?? [],
                            ['ITI', 'Diploma']
                        )
                    )
                )
            ]);

            $role->sectors()->syncWithoutDetaching(
                IndustrialSector::whereIn(
                    'slug',
                    [
                        'automobile',
                        'auto-components',
                        'forging',
                        'heavy-engineering'
                    ]
                )->pluck('id')
            );

            $role->processes()->syncWithoutDetaching(
                IndustrialProcess::whereIn(
                    'slug',
                    $processes
                )->pluck('id')
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Jalandhar Industrial Companies
    |--------------------------------------------------------------------------
    */
    private function seedJalandharIndustrialCompanies(): void
    {
        $state = \App\Models\IndustrialState::firstOrCreate(
            ['slug' => 'punjab'],
            [
                'name' => 'Punjab',
                'is_active' => true
            ]
        );

        $area = \App\Models\IndustrialArea::updateOrCreate(
            [
                'state_id' => $state->id,
                'slug' => 'jalandhar-industrial-area'
            ],
            [
                'name' => 'Jalandhar Industrial Area',
                'city' => 'Jalandhar',
                'district' => 'Jalandhar',
                'area_type' => 'industrial_area',
                'description' => 'Jalandhar industrial manufacturing cluster covering hand tools, forgings, engineering and related manufacturing companies.',
                'is_active' => true,
                'source_name' => 'Industrial company directory',
                'last_verified_at' => now()->toDateString(),
                'verification_status' => 'directory'
            ]
        );

        $companies = [
            [
                'name' => 'Ajay Industries',
                'website' => 'https://www.ajayind.com/',
                'careers_url' => 'https://www.ajayind.com/career/',
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Manufacturer and exporter of hand tools, vices, automobile tools, plumbing tools and lubricating equipment.',
                'source_url' => 'https://www.ajayind.com/career/',
                'sync_enabled' => true,
            ],
            [
                'name' => 'Surindera Tools & Forgings',
                'website' => 'https://realhandtools.com/',
                'careers_url' => null,
                'industry' => 'Hand Tools and Forgings',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Hand tools and forgings manufacturer located in Jalandhar.',
                'source_url' => 'https://realhandtools.com/',
                'sync_enabled' => false,
            ],
            [
                'name' => 'PARADISE TOOLS',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar industrial hand tools company. Official career source requires verification before job synchronization.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'PUSHPA HAND TOOLS',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar hand tools manufacturer.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'REAL HAND TOOLS',
                'website' => 'https://www.realhandtools.com/',
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Hand tools manufacturing company operating in Jalandhar.',
                'source_url' => 'https://www.realhandtools.com/',
                'sync_enabled' => false,
            ],
            [
                'name' => 'VARINDERA TOOLS PVT LTD',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar hand tools manufacturer.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'FINE TOOLS CORPORATION',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar tools manufacturing company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'VISHAL TOOLS & FORGINGS',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Tools and Forgings',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar tools and forgings company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'ACTIVE TOOLS PVT LTD',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar tools manufacturing company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'ADITYA HAND TOOLS',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar hand tools company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'KL HAND TOOLS',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar hand tools company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'RS INDUSTRIES',
                'website' => 'https://www.rsitools.com/',
                'careers_url' => null,
                'industry' => 'Hand Tools and Scaffoldings',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Hand tools and scaffolding manufacturer at Focal Point Extension, Jalandhar.',
                'source_url' => 'https://www.rsitools.com/',
                'sync_enabled' => false,
            ],
            [
                'name' => 'HR INDUSTRIES',
                'website' => 'https://www.hritools.com/',
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Hand tools manufacturer based in Jalandhar.',
                'source_url' => 'https://www.hritools.com/',
                'sync_enabled' => false,
            ],
            [
                'name' => 'MILLION TOOLS INDUSTRIES',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar tools manufacturing company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'GREEN STARS SA PVT LTD',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Manufacturing',
                'sector' => 'Industrial Manufacturing',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar industrial company requiring official source verification before career synchronization.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'PADMAWATI MFG CO',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Manufacturing',
                'sector' => 'Industrial Manufacturing',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar manufacturing company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'Hira Tools Corporation (REGD.)',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar hand tools company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'BHOLA KAINCHI WALA',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar tools manufacturing company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'HAMCO ISPAT PVT LTD',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Steel and Metal Manufacturing',
                'sector' => 'Industrial Manufacturing',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar industrial steel and metal company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'HR INTERNATIONAL',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Industrial Manufacturing',
                'sector' => 'Industrial Manufacturing',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar industrial company requiring official career source verification.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'ESS PEE INDUSTRIAL CORPORATION',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Industrial Manufacturing',
                'sector' => 'Industrial Manufacturing',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar industrial manufacturing company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'EVEREST HAND TOOL',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar hand tools company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'SMITH TOOLS CO',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar tools company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'NAGI TOOLS INDUSTRIES',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar tools manufacturing company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
            [
                'name' => 'SUNRISE TOOLS',
                'website' => null,
                'careers_url' => null,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Jalandhar tools manufacturing company.',
                'source_url' => null,
                'sync_enabled' => false,
            ],
        ];

        $sectorIds = IndustrialSector::whereIn(
            'slug',
            [
                'forging',
                'tool-room',
                'cnc-machining',
                'auto-components',
                'heavy-engineering'
            ]
        )->pluck('id');

        foreach ($companies as $data) {
            $name = trim($data['name']);
            $slug = Str::slug($name);

            /*
            |--------------------------------------------------------------------------
            | Main company directory
            |--------------------------------------------------------------------------
            */
            $company = $this->upsertCompany(
                $name,
                [
                    'slug' => $slug,
                    'website' => $data['website'],
                    'careers_url' => $data['careers_url'],
                    'country' => 'India',
                    'industry' => $data['industry'],
                    'sector' => $data['sector'],
                    'sync_enabled' => $data['sync_enabled'],
                    'is_active' => true,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Industrial company / plant directory
            |--------------------------------------------------------------------------
            */
            $plant = \App\Models\IndustrialCompany::updateOrCreate(
                [
                    'industrial_area_id' => $area->id,
                    'slug' => $slug
                ],
                [
                    'name' => $name,
                    'plant_name' => $name . ' - Jalandhar',
                    'website' => $data['website'],
                    'careers_url' => $data['careers_url'],
                    'industry' => $data['industry'],
                    'sector' => $data['sector'],
                    'facility_type' => $data['facility_type'],
                    'description' => $data['description'],
                    'is_active' => true,
                    'is_verified' => !empty($data['website']),
                    'source_name' => $data['source_url']
                        ? 'Company website'
                        : 'Industrial company directory',
                    'source_url' => $data['source_url'],
                    'last_verified_at' => now()->toDateString(),
                    'verification_status' => !empty($data['website'])
                        ? 'company_source'
                        : 'directory'
                ]
            );

            $plant->sectors()->syncWithoutDetaching($sectorIds);
            $area->sectorCatalog()->syncWithoutDetaching($sectorIds);

            /*
            |--------------------------------------------------------------------------
            | Official career source
            |--------------------------------------------------------------------------
            */
            if (!empty($data['careers_url'])) {
                $plant->sources()->updateOrCreate(
                    [
                        'source_key' => 'official-careers'
                    ],
                    [
                        'title' => 'Official careers page',
                        'url' => $data['careers_url'],
                        'source_period' => now()->toDateString(),
                        'checked_at' => now()->toDateString(),
                        'evidence_note' => 'Official career source configured. Job synchronization must only publish vacancies actually found on the official source.'
                    ]
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate-safe company upsert
    |--------------------------------------------------------------------------
    */
    private function upsertCompany(string $name, array $attributes): Company
    {
        $normalized = mb_strtolower(
            preg_replace('/\s+/', ' ', trim($name))
        );

        $company = Company::whereRaw(
            'LOWER(TRIM(name)) = ?',
            [$normalized]
        )->first();

        if (!$company) {
            $company = Company::where(
                'slug',
                $attributes['slug'] ?? Str::slug($name)
            )->first();
        }

        if (!$company) {
            $company = new Company();
            $company->name = $name;
        }

        $company->fill(
            array_merge(
                [
                    'slug' => Str::slug($name),
                    'country' => 'India',
                    'is_active' => true,
                ],
                $attributes
            )
        );

        $company->save();

        return $company;
    }

    /*
    |--------------------------------------------------------------------------
    | Ajay Industries - Jalandhar
    |--------------------------------------------------------------------------
    */
    private function seedAjayIndustriesJalandhar(): void
    {
        $website = 'https://www.ajayind.com/';
        $careers = 'https://www.ajayind.com/career/';
        $checked = '2026-09-22';

        $this->upsertCompany(
            'Ajay Industries',
            [
                'slug' => 'ajay-industries',
                'website' => $website,
                'careers_url' => $careers,
                'country' => 'India',
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Manufacturing',
                'sync_enabled' => true,
                'is_active' => true,
            ]
        );

        $state = \App\Models\IndustrialState::firstOrCreate(
            ['slug' => 'punjab'],
            [
                'name' => 'Punjab',
                'is_active' => true
            ]
        );

        $area = \App\Models\IndustrialArea::updateOrCreate(
            [
                'state_id' => $state->id,
                'slug' => 'jalandhar-industrial-area'
            ],
            [
                'name' => 'Jalandhar Industrial Area',
                'city' => 'Jalandhar',
                'district' => 'Jalandhar',
                'area_type' => 'industrial_area',
                'description' => 'Jalandhar hand tools and engineering manufacturing cluster.',
                'is_active' => true,
                'source_name' => 'Ajay Industries official website',
                'source_url' => $website,
                'last_verified_at' => $checked,
                'verification_status' => 'company_source'
            ]
        );

        $plant = \App\Models\IndustrialCompany::updateOrCreate(
            [
                'industrial_area_id' => $area->id,
                'slug' => 'ajay-industries'
            ],
            [
                'name' => 'Ajay Industries',
                'plant_name' => 'Ajay Nagar, behind Industrial Estate, Pathankot Bye Pass Road',
                'website' => $website,
                'careers_url' => $careers,
                'industry' => 'Hand Tools Manufacturing',
                'sector' => 'Tools and Engineering',
                'facility_type' => 'Manufacturing unit',
                'description' => 'Manufacturer and exporter of hand tools, vices, automobile tools, plumbing tools and lubricating equipment.',
                'is_active' => true,
                'is_verified' => true,
                'source_name' => 'Ajay Industries official website and careers page',
                'source_url' => $website,
                'last_verified_at' => $checked,
                'verification_status' => 'company_source'
            ]
        );

        $sectorIds = IndustrialSector::whereIn(
            'slug',
            [
                'forging',
                'tool-room',
                'cnc-machining'
            ]
        )->pluck('id');

        $plant->sectors()->syncWithoutDetaching($sectorIds);
        $area->sectorCatalog()->syncWithoutDetaching($sectorIds);

        $plant->sources()->updateOrCreate(
            [
                'source_key' => 'official-careers'
            ],
            [
                'title' => 'Official recruitment form',
                'url' => $careers,
                'source_period' => $checked,
                'checked_at' => $checked,
                'evidence_note' => 'Official page accepts resumes for general recruitment. No named vacancy, department, requirements or closing date was published when checked.'
            ]
        );
    }
}
