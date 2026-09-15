<?php

namespace App\Console\Commands;

use App\Models\IndustrialArea;
use App\Models\IndustrialCompany;
use App\Models\Job;
use App\Services\IndustrialJobSourceService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SyncIndustrialJobs extends Command
{
    protected $signature = 'industrial:sync-jobs
        {--company= : Sync jobs belonging to one generic company ID}
        {--limit= : Maximum number of industrial candidate jobs to process}
        {--only-verified : Process only jobs having a verified company/source}
        {--location= : Restrict candidates to an industrial city/district}
        {--debug : Show candidate matching information}';

    protected $description =
    'Find real industrial job candidates from the live job catalogue and map them into the industrial directory';

    /**
     * Strong industrial job signals.
     *
     * These are used only to FIND candidate jobs.
     * A job is not inserted into industrial_jobs until
     * IndustrialJobSourceService successfully maps it.
     */
    protected array $industrialKeywords = [
        // Manufacturing
        'manufacturing',
        'production',
        'production engineer',
        'production supervisor',
        'production manager',
        'plant',
        'plant engineer',
        'plant manager',
        'factory',
        'assembly',
        'assembly line',

        // Mechanical
        'mechanical engineer',
        'mechanical maintenance',
        'mechanical technician',
        'maintenance engineer',
        'maintenance technician',
        'maintenance fitter',
        'fitter',
        'diesel mechanic',
        'machine maintenance',

        // CNC / machining
        'cnc',
        'cnc operator',
        'cnc programmer',
        'cnc machinist',
        'vmc',
        'vmc operator',
        'machinist',
        'machine operator',
        'turner',
        'lathe',
        'milling',
        'machining',
        'tool room',
        'toolroom',
        'tool maker',
        'toolmaker',

        // Forging / casting
        'forging',
        'forged',
        'forge',
        'forging engineer',
        'forging operator',
        'die',
        'die maker',
        'die maintenance',
        'die engineer',
        'die maintenance engineer',
        'casting',
        'foundry',
        'moulding',
        'molding',
        'furnace',
        'heat treatment',
        'ring rolling',

        // Quality
        'quality engineer',
        'quality inspector',
        'quality inspection',
        'quality control',
        'quality assurance',
        'metrology',
        'inspection engineer',
        'testing engineer',

        // Electrical / instrumentation
        'electrical engineer',
        'electrical technician',
        'electrical maintenance',
        'instrumentation',
        'instrumentation engineer',
        'automation engineer',
        'industrial automation',
        'plc',
        'scada',

        // Welding / fabrication
        'welding',
        'welder',
        'fabrication',
        'fabricator',
        'sheet metal',
        'fabrication engineer',

        // Automotive
        'automotive',
        'automobile',
        'auto components',
        'automotive engineer',
        'vehicle assembly',
        'tractor',
        'tractor manufacturing',
        'transmission',
        'gear manufacturing',
        'gearbox',

        // Industrial engineering
        'industrial engineer',
        'process engineer',
        'process engineering',
        'process improvement',
        'manufacturing engineer',
        'manufacturing engineering',
        'lean manufacturing',
        'six sigma',
        'continuous improvement',

        // Warehouse / supply chain
        'warehouse',
        'warehouse executive',
        'warehouse manager',
        'store executive',
        'stores',
        'inventory',
        'material handling',
        'logistics',
        'dispatch',
        'supply chain',
        'material planner',
        'purchase executive',
        'procurement',

        // Safety
        'safety engineer',
        'safety officer',
        'industrial safety',
        'ehs',
        'environment health safety',

        // Energy / heavy industry
        'steel',
        'steel plant',
        'rolling mill',
        'metallurgy',
        'metallurgical engineer',
        'heavy engineering',
        'power plant',
        'boiler',
        'turbine',

        // Skilled industrial workers
        'technician',
        'operator',
        'helper',
        'production operator',
        'machine operator',
        'maintenance operator',
    ];

    public function handle(
        IndustrialJobSourceService $industrialJobSource
    ): int {
        $limit = $this->option('limit')
            ? max(1, (int) $this->option('limit'))
            : 500;

        $companyId = $this->option('company')
            ? (int) $this->option('company')
            : null;

        $location = trim(
            (string) ($this->option('location') ?? '')
        );

        $this->info(
            'Starting industrial job discovery...'
        );

        $this->line(
            'Candidate limit: ' . $limit
        );

        if ($location !== '') {
            $this->line(
                'Location filter: ' . $location
            );
        }

        /*
         * ---------------------------------------------------------
         * Load industrial directory geography.
         * ---------------------------------------------------------
         */
        $industrialLocations = $this->industrialLocations();

        /*
         * ---------------------------------------------------------
         * Load visible industrial companies.
         *
         * We use these names as strong candidate signals.
         * ---------------------------------------------------------
         */
        $industrialCompanies = IndustrialCompany::query()
            ->visible()
            ->with([
                'area',
                'area.state',
            ])
            ->get();

        $industrialCompanyNames = $industrialCompanies
            ->map(function ($company) {
                return $this->normalizeText(
                    $company->name
                );
            })
            ->filter()
            ->unique()
            ->values();

        /*
         * ---------------------------------------------------------
         * Build candidate query.
         *
         * IMPORTANT:
         *
         * We no longer take:
         *
         *     first 100 generic jobs
         *
         * Instead we search the entire active job catalogue for
         * industrial signals.
         * ---------------------------------------------------------
         */
        $query = Job::query()
            ->active()
            ->with([
                'company',
                'technologies',
            ]);

        if ($companyId) {
            $query->where(
                'company_id',
                $companyId
            );
        }

        if ($this->option('only-verified')) {
            $query->whereHas(
                'company',
                function ($company) {
                    $company->where(
                        'is_active',
                        true
                    );
                }
            );
        }

        /*
         * Location-specific search.
         */
        if ($location !== '') {
            $this->applyLocationFilter(
                $query,
                $location
            );
        }

        /*
         * ---------------------------------------------------------
         * Industrial keyword search.
         *
         * Search title first, then supporting job fields.
         * ---------------------------------------------------------
         */
        $query->where(function (Builder $q) {
            foreach (
                $this->industrialKeywords
                as $keyword
            ) {
                $like = '%' .
                    str_replace(
                        ['%', '_'],
                        ['\\%', '\\_'],
                        $keyword
                    ) .
                    '%';

                $q->orWhere(
                    'title',
                    'like',
                    $like
                );

                $q->orWhere(
                    'role',
                    'like',
                    $like
                );

                $q->orWhere(
                    'category',
                    'like',
                    $like
                );

                $q->orWhere(
                    'department',
                    'like',
                    $like
                );

                $q->orWhere(
                    'description',
                    'like',
                    $like
                );

                $q->orWhere(
                    'requirements',
                    'like',
                    $like
                );
            }
        });

        /*
         * Company-name candidates are also important.
         *
         * A company may publish:
         *
         *     HR Executive
         *
         * rather than:
         *
         *     Industrial HR Executive
         *
         * So keyword matching alone is insufficient.
         */
        if ($industrialCompanyNames->isNotEmpty()) {
            $query->orWhere(function (Builder $companyQuery) use (
                $industrialCompanyNames
            ) {
                foreach ($industrialCompanyNames as $name) {
                    if (mb_strlen($name) < 3) {
                        continue;
                    }

                    $companyQuery->orWhereHas(
                        'company',
                        function (Builder $q) use ($name) {
                            $q->where(
                                'name',
                                'like',
                                '%' . $name . '%'
                            );
                        }
                    );
                }
            });
        }

        /*
         * Process candidates by ID so chunkById remains reliable.
         */
        $query->orderBy('id');

        $processed = 0;
        $imported = 0;
        $skipped = 0;
        $failed = 0;

        $candidateCount = 0;

        /*
         * ---------------------------------------------------------
         * Process candidates.
         * ---------------------------------------------------------
         */
        $query->chunkById(
            100,
            function ($jobs) use (
                $industrialJobSource,
                $limit,
                &$processed,
                &$imported,
                &$skipped,
                &$failed,
                &$candidateCount
            ) {
                foreach ($jobs as $job) {
                    if ($processed >= $limit) {
                        return false;
                    }

                    $candidateCount++;

                    $score = $this->industrialScore(
                        $job
                    );

                    /*
                     * Very weak candidates are rejected before
                     * expensive industrial-company matching.
                     */
                    if ($score < 20) {
                        continue;
                    }

                    $processed++;

                    if ($this->option('debug')) {
                        $this->line(
                            sprintf(
                                '  [%d] %s | %s | score=%d',
                                $processed,
                                $job->title,
                                $job->company?->name ?? 'Unknown',
                                $score
                            )
                        );
                    }

                    try {
                        $industrialJob =
                            $industrialJobSource->ingest(
                                $job
                            );

                        if ($industrialJob) {
                            $imported++;

                            $this->line(
                                "  ✓ {$job->title} → Industrial Job #{$industrialJob->id}"
                            );
                        } else {
                            $skipped++;

                            if ($this->option('debug')) {
                                $this->line(
                                    "  - {$job->title} → no verified industrial company mapping"
                                );
                            }
                        }
                    } catch (\Throwable $e) {
                        $failed++;

                        $this->error(
                            "  ✗ {$job->title} → {$e->getMessage()}"
                        );

                        report($e);
                    }
                }

                return true;
            },
            'id'
        );

        $this->newLine();

        $this->info(
            str_repeat('=', 60)
        );

        $this->info(
            'INDUSTRIAL JOB DISCOVERY RESULTS'
        );

        $this->info(
            str_repeat('=', 60)
        );

        $this->line(
            "Candidate jobs examined : {$candidateCount}"
        );

        $this->line(
            "Jobs processed          : {$processed}"
        );

        $this->line(
            "Jobs imported           : {$imported}"
        );

        $this->line(
            "Jobs skipped            : {$skipped}"
        );

        $this->line(
            "Jobs failed             : {$failed}"
        );

        $this->info(
            str_repeat('=', 60)
        );

        /*
         * Show the database count immediately.
         */
        $industrialCount = \App\Models\IndustrialJob::count();

        $this->newLine();

        $this->info(
            "Total industrial jobs now in database: {$industrialCount}"
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Calculate industrial relevance.
     */
    protected function industrialScore(Job $job): int
    {
        $title = $this->normalizeText(
            (string) $job->title
        );

        $text = $this->normalizeText(
            implode(' ', array_filter([
                $job->title,
                $job->role,
                $job->category,
                $job->department,
                $job->description,
                is_array($job->requirements)
                    ? implode(' ', $job->requirements)
                    : $job->requirements,
                $job->location,
            ]))
        );

        if ($text === '') {
            return 0;
        }

        $score = 0;

        /*
         * Title matches are much stronger than description matches.
         */
        foreach ($this->industrialKeywords as $keyword) {
            $normalizedKeyword =
                $this->normalizeText($keyword);

            if ($normalizedKeyword === '') {
                continue;
            }

            if (
                Str::contains(
                    $title,
                    $normalizedKeyword
                )
            ) {
                $score += 30;
                continue;
            }

            if (
                Str::contains(
                    $text,
                    $normalizedKeyword
                )
            ) {
                $score += 8;
            }
        }

        /*
         * Strong industrial job titles.
         */
        $strongTitlePatterns = [
            'cnc',
            'vmc',
            'machinist',
            'forging',
            'foundry',
            'furnace',
            'die maker',
            'tool maker',
            'maintenance engineer',
            'maintenance technician',
            'production engineer',
            'manufacturing engineer',
            'plant engineer',
            'quality engineer',
            'industrial engineer',
            'process engineer',
            'electrical maintenance',
            'mechanical maintenance',
            'welding',
            'fabrication',
            'machine operator',
            'production operator',
        ];

        foreach ($strongTitlePatterns as $pattern) {
            if (
                Str::contains(
                    $title,
                    $this->normalizeText($pattern)
                )
            ) {
                $score += 40;
            }
        }

        /*
         * Industrial location signal.
         */
        foreach (
            $this->industrialLocations()
            as $industrialLocation
        ) {
            if (
                Str::contains(
                    $text,
                    $this->normalizeText(
                        $industrialLocation
                    )
                )
            ) {
                $score += 20;
                break;
            }
        }

        return min(
            200,
            $score
        );
    }

    /**
     * Get known industrial cities/districts/areas.
     */
    protected function industrialLocations(): array
    {
        static $locations;

        if ($locations !== null) {
            return $locations;
        }

        $locations = [];

        IndustrialArea::query()
            ->visible()
            ->with('state')
            ->get()
            ->each(function ($area) use (&$locations) {
                foreach (
                    [
                        $area->name,
                        $area->city,
                        $area->district,
                        $area->state?->name,
                    ] as $value
                ) {
                    if (
                        is_string($value) &&
                        trim($value) !== ''
                    ) {
                        $locations[] = trim(
                            $value
                        );
                    }
                }
            });

        /*
         * Important Indian industrial locations that may occur
         * in jobs before their exact industrial area is mapped.
         */
        $locations = array_merge(
            $locations,
            [
                'Ludhiana',
                'Jalandhar',
                'Rajpura',
                'Mohali',
                'Amritsar',
                'Manesar',
                'Gurugram',
                'Gurgaon',
                'Bawal',
                'Dharuhera',
                'Faridabad',
                'Bhiwadi',
                'Khushkhera',
                'Neemrana',
                'Tapukara',
                'Ghiloth',
                'Sanand',
                'Ahmedabad',
                'Changodar',
                'Vadodara',
                'Bharuch',
                'Dahej',
                'Halol',
                'Hazira',
                'Vapi',
                'Ankleshwar',
                'Rudrapur',
                'Pantnagar',
                'Haridwar',
                'Kashipur',
                'Sitarganj',
                'Baddi',
                'Barotiwala',
                'Nalagarh',
                'Paonta Sahib',
                'Kala Amb',
                'Mehatpur',
                'Tahliwal',
                'Visakhapatnam',
                'Atchutapuram',
                'Sri City',
                'Kakinada',
                'Anantapur',
                'Tirupati',
                'Guwahati',
                'Kolkata',
                'Durgapur',
                'Asansol',
                'Haldia',
                'Pune',
                'Chakan',
                'Talegaon',
                'Nashik',
                'Nagpur',
                'Bengaluru',
                'Tumakuru',
                'Chennai',
                'Sriperumbudur',
                'Hosur',
                'Coimbatore',
                'Salem',
                'Hyderabad',
                'Medchal',
                'Adibatla',
                'Shamshabad',
                'Bhubaneswar',
                'Rourkela',
                'Angul',
                'Paradip',
                'Bhilai',
                'Raipur',
                'Jamshedpur',
                'Bokaro',
                'Dhanbad',
                'Noida',
                'Greater Noida',
                'Ghaziabad',
                'Kanpur',
                'Lucknow',
                'Indore',
                'Pithampur',
                'Bhopal',
                'Jabalpur',
                'Kochi',
                'Thiruvananthapuram',
                'Kozhikode',
                'Verna',
            ]
        );

        $locations = collect($locations)
            ->map(
                fn($value) => trim(
                    (string) $value
                )
            )
            ->filter()
            ->unique(
                fn($value) => Str::lower(
                    $value
                )
            )
            ->sortByDesc(
                fn($value) => mb_strlen($value)
            )
            ->values()
            ->all();

        return $locations;
    }

    /**
     * Apply location filter.
     */
    protected function applyLocationFilter(
        Builder $query,
        string $location
    ): void {
        $location = trim($location);

        $query->where(function (Builder $q) use (
            $location
        ) {
            $like = '%' .
                str_replace(
                    ['%', '_'],
                    ['\\%', '\\_'],
                    $location
                ) .
                '%';

            $q->where(
                'location',
                'like',
                $like
            );

            $q->orWhere(
                'title',
                'like',
                $like
            );

            $q->orWhere(
                'description',
                'like',
                $like
            );
        });
    }

    /**
     * Normalize searchable text.
     */
    protected function normalizeText(
        ?string $value
    ): string {
        $value = Str::lower(
            trim((string) $value)
        );

        $value = preg_replace(
            '/[^\pL\pN]+/u',
            ' ',
            $value
        );

        $value = preg_replace(
            '/\s+/u',
            ' ',
            $value
        );

        return trim(
            (string) $value
        );
    }
}
