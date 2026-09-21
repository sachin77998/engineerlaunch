<?php

namespace App\Services;

use App\Models\IndustrialArea;
use App\Models\IndustrialCompany;
use App\Models\IndustrialDepartment;
use App\Models\IndustrialJob;
use App\Models\IndustrialJobRole;
use App\Models\IndustrialState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class IndustrialCsvImporter
{
    public const TYPES = ['states','industrial_areas','industrial_companies','industrial_departments','industrial_job_roles','industrial_jobs',];
    public const AREA_TYPES = ['industrial_area','industrial_estate','industrial_park','industrial_cluster','manufacturing_hub','textile_hub','automobile_hub','pharma_hub','steel_hub','electronics_hub','logistics_hub','food_processing_hub',];
    public function import(string $type, string $path, bool $dryRun = false): int
    {
        if (! in_array($type, self::TYPES, true)) {throw new RuntimeException('Unsupported import type.');}
        if (! is_file($path) || ! is_readable($path)) {throw new RuntimeException('CSV file is not readable: ' . $path);}
        $handle = fopen($path, 'rb');
        $line = 1;
        try {$header = fgetcsv($handle, 0, ',', '"', '');
            if (! $header) {throw new RuntimeException('CSV requires a header.');}
            $header = array_map(fn($v) => trim((string) $v),$header);
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $header = $this->normalizeHeader($type, $header);
            $required = match ($type) {
                'states', 'industrial_departments' => ['name'],
                'industrial_areas' => ['state_slug', 'name'],
                'industrial_companies' => ['state_slug', 'area_slug', 'name'],
                'industrial_job_roles' => ['department_slug', 'name', 'slug'],
                'industrial_jobs' => ['external_id','state_slug','area_slug','job_title',],
            };
            $model = $this->model($type);
            $allowed = array_merge((new $model)->getFillable(),['state_slug','area_slug','company_slug','department_slug','role_slug',]);
            $allowed = array_diff($allowed,['state_id','industrial_area_id','industrial_company_id','department_id','job_role_id',]);
            if (count($header) !== count(array_unique($header)) || array_diff($required, $header) || array_diff($header, $allowed)) {throw new RuntimeException('Invalid headers. Required: ' .implode(', ', $required) .'. Unknown or duplicate columns are not allowed.');
            }
            DB::beginTransaction();
            try {
                $count = 0;

                while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                    $line++;

                    if ($values === [null]) {
                        continue;
                    }

                    if (count($values) !== count($header)) {
                        throw new RuntimeException(
                            'Column count does not match header.'
                        );
                    }

                    $row = array_combine(
                        $header,
                        array_map(
                            fn($v) => trim((string) $v) === ''
                                ? null
                                : trim((string) $v),
                            $values
                        )
                    );

                    $this->save(
                        $type,
                        $this->normalizeRow($type, $row)
                    );

                    $count++;
                }

                if ($dryRun) {
                    DB::rollBack();
                } else {
                    DB::commit();
                }

                return $count;
            } catch (\Throwable $e) {
                DB::rollBack();

                throw $e;
            }
        } catch (\Throwable $e) {
            throw new RuntimeException(
                basename($path) .
                    ' row ' .
                    $line .
                    ': ' .
                    $e->getMessage(),
                0,
                $e
            );
        } finally {
            fclose($handle);
        }
    }

    public function saveRecord(
        string $type,
        array $row,
        ?int $id = null
    ): \Illuminate\Database\Eloquent\Model {
        if (! in_array($type, self::TYPES, true)) {
            throw new RuntimeException('Unsupported record type.');
        }

        $model = $this->model($type);

        $existing = $id !== null
            ? $model::findOrFail($id)
            : null;

        foreach ($row as $field => $value) {
            if (
                is_array($value) &&
                in_array($field, ['sectors', 'skills'], true)
            ) {
                Validator::make(
                    [$field => $value],
                    [
                        $field => 'array|max:100',
                        $field . '.*' => 'string|max:120',
                    ]
                )->validate();

                $row[$field] = implode(';', $value);
            } elseif (! is_scalar($value) && $value !== null) {
                throw new RuntimeException(
                    $field . ' must be a single value.'
                );
            }
        }

        return DB::transaction(
            fn() => $this->save(
                $type,
                $this->normalizeRow($type, $row),
                $existing,
                $id === null
            )
        );
    }

    private function normalizeHeader(
        string $type,
        array $header
    ): array {
        $aliases = [
            'state' => 'state_slug',
            'area' => 'area_slug',
            'company' => 'name',
        ];

        return array_map(
            fn($key) => $aliases[$key] ?? $key,
            $header
        );
    }

    private function normalizeRow(
        string $type,
        array $row
    ): array {
        foreach (
            [
                'state_slug',
                'area_slug',
                'company_slug',
                'department_slug',
                'role_slug',
            ] as $key
        ) {
            if (! empty($row[$key])) {
                $row[$key] = Str::slug($row[$key]);
            }
        }

        return $row;
    }

    private function model(string $type): string
    {
        return match ($type) {
            'states' => IndustrialState::class,
            'industrial_areas' => IndustrialArea::class,
            'industrial_companies' => IndustrialCompany::class,
            'industrial_departments' => IndustrialDepartment::class,
            'industrial_job_roles' => IndustrialJobRole::class,
            'industrial_jobs' => IndustrialJob::class,
        };
    }

    private function save(
        string $type,
        array $row,
        ?\Illuminate\Database\Eloquent\Model $existing = null,
        bool $createOnly = false
    ): \Illuminate\Database\Eloquent\Model {
        foreach (
            [
                'is_active',
                'is_featured',
                'is_verified',
            ] as $key
        ) {
            if (array_key_exists($key, $row)) {
                $value = strtolower((string) $row[$key]);

                if (
                    ! in_array(
                        $value,
                        ['1', '0', 'true', 'false'],
                        true
                    )
                ) {
                    throw new RuntimeException(
                        $key .
                            ' must be 1, 0, true or false.'
                    );
                }

                $row[$key] = in_array(
                    $value,
                    ['1', 'true'],
                    true
                );
            }
        }

        foreach (['sectors', 'skills'] as $key) {
            if (isset($row[$key])) {
                $row[$key] = array_values(
                    array_filter(
                        array_map(
                            'trim',
                            preg_split('/[;|]/', $row[$key])
                        )
                    )
                );
            }
        }

        if (
            $type !== 'industrial_jobs' &&
            empty($row['slug'])
        ) {
            $row['slug'] = Str::slug(
                $row['name'] ?? ''
            );
        }

        $rules = [];

        foreach ($row as $key => $value) {
            $rules[$key] = 'nullable|string|max:255';
        }

        $rules['description'] = [
            'nullable',
            'string',
            'max:100000',
        ];

        /*
         * URL fields
         *
         * careers_url has been added to industrial_companies
         * and must contain an official HTTP/HTTPS URL.
         */
        foreach (['source_url', 'website', 'careers_url'] as $key) {
            $rules[$key] = [
                'nullable',
                'url',
                'regex:~^https?://~i',
                'max:2000',
            ];
        }

        foreach (
            [
                'is_active',
                'is_verified',
                'is_featured',
            ] as $key
        ) {
            $rules[$key] = 'sometimes|boolean';
        }

        foreach (['sectors', 'skills'] as $key) {
            $rules[$key] = 'nullable|array|max:100';
            $rules[$key . '.*'] = 'string|max:120';
        }

        foreach (
            [
                'salary_min',
                'salary_max',
            ] as $key
        ) {
            $rules[$key] =
                'nullable|numeric|min:0|max:9999999999.99';
        }

        foreach (
            [
                'experience_min',
                'experience_max',
            ] as $key
        ) {
            $rules[$key] =
                'nullable|numeric|min:0|max:99';
        }

        $rules['latitude'] =
            'nullable|numeric|between:-90,90';

        $rules['longitude'] =
            'nullable|numeric|between:-180,180';

        $rules['pincode'] = [
            'nullable',
            'regex:/^[1-9][0-9]{5}$/',
        ];

        $rules['code'] =
            'nullable|string|max:10';

        $rules['application_deadline'] =
            'nullable|date';

        $rules['last_verified_at'] =
            'nullable|date|before_or_equal:now';

        $rules['verification_status'] = [
            'sometimes',
            Rule::in([
                'unverified',
                'government_source',
                'company_source',
                'admin_verified',
                'community_verified',
            ]),
        ];

        $rules['facility_type'] = [
            'nullable',
            Rule::in([
                'Plant',
                'Factory',
                'Warehouse',
                'Depot',
                'Distribution Centre',
                'Office',
                'R&D Centre',
                'Service Centre',
                'Industrial Unit',
                'Logistics Hub',
                'Manufacturing plant',
                'SEZ unit',
            ]),
        ];

        $rules['area_type'] = [
            'nullable',
            Rule::in(self::AREA_TYPES),
        ];

        $rules['employment_type'] = [
            'nullable',
            Rule::in([
                'Full Time',
                'Contract',
                'Apprenticeship',
                'Trainee',
                'Part Time',
            ]),
        ];

        $rules['salary_period'] = [
            'sometimes',
            Rule::in([
                'hourly',
                'daily',
                'monthly',
                'annual',
            ]),
        ];

        $rules[$type === 'industrial_jobs'
            ? 'job_title'
            : 'name'] = 'required|string|max:255';

        if ($type === 'industrial_jobs') {
            $rules['external_id'] =
                'required|string|max:255';
        } else {
            $rules['slug'] = [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ];
        }

        Validator::make($row, $rules)->validate();

        if (
            ($row['verification_status'] ?? 'unverified') !==
            'unverified' &&
            (
                empty($row['source_name']) ||
                empty($row['source_url']) ||
                empty($row['last_verified_at'])
            )
        ) {
            throw new RuntimeException(
                'Verified/reported records require source_name, source_url and last_verified_at.'
            );
        }

        if (
            ! empty($row['is_verified']) &&
            ! in_array(
                $row['verification_status'] ?? '',
                [
                    'government_source',
                    'company_source',
                    'admin_verified',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'is_verified requires government_source, company_source or admin_verified provenance.'
            );
        }

        if (
            array_key_exists(
                'verification_status',
                $row
            ) &&
            ! in_array(
                $row['verification_status'],
                [
                    'government_source',
                    'company_source',
                    'admin_verified',
                ],
                true
            ) &&
            in_array(
                $type,
                [
                    'industrial_companies',
                    'industrial_jobs',
                ],
                true
            )
        ) {
            $row['is_verified'] = false;
        }

        foreach (
            ['salary', 'experience'] as $range
        ) {
            if (
                isset(
                    $row[$range . '_min'],
                    $row[$range . '_max']
                ) &&
                $row[$range . '_min'] >
                $row[$range . '_max']
            ) {
                throw new RuntimeException(
                    $range .
                        ' minimum exceeds maximum.'
                );
            }
        }

        $key = $type === 'industrial_jobs'
            ? [
                'external_id' =>
                $row['external_id'],
            ]
            : [
                'slug' =>
                $row['slug'],
            ];

        if (
            in_array(
                $type,
                [
                    'industrial_areas',
                    'industrial_companies',
                    'industrial_jobs',
                ],
                true
            )
        ) {
            $state = IndustrialState::where(
                'slug',
                $row['state_slug'] ?? ''
            )->first();

            if (! $state) {
                throw new RuntimeException(
                    'Unknown state_slug; import states first.'
                );
            }

            if ($type === 'industrial_areas') {
                if (
                    empty($row['city']) &&
                    empty($row['district'])
                ) {
                    throw new RuntimeException(
                        'An area requires a city or district.'
                    );
                }

                $row['state_id'] = $state->id;
                $key['state_id'] = $state->id;
            } else {
                $area = $state->areas()
                    ->where(
                        'slug',
                        $row['area_slug'] ?? ''
                    )
                    ->first();

                if (! $area) {
                    throw new RuntimeException(
                        'Unknown area_slug for this state.'
                    );
                }

                $row['industrial_area_id'] =
                    $area->id;

                if (
                    $type ===
                    'industrial_companies'
                ) {
                    $key['industrial_area_id'] = $area->id;
                }

                if (
                    $type === 'industrial_jobs'
                ) {
                    $company = empty($row['company_slug'])
                        ? null
                        : $area->companies()
                        ->where(
                            'slug',
                            $row['company_slug']
                        )
                        ->first();

                    if (
                        ! empty($row['company_slug']) &&
                        ! $company
                    ) {
                        throw new RuntimeException(
                            'Company does not belong to the selected area.'
                        );
                    }

                    $row['industrial_company_id'] = $company?->id;
                }
            }
        }

        if (
            in_array(
                $type,
                [
                    'industrial_job_roles',
                    'industrial_jobs',
                ],
                true
            )
        ) {
            $department =
                empty($row['department_slug'])
                ? null
                : IndustrialDepartment::where(
                    'slug',
                    $row['department_slug']
                )->first();

            if (
                (
                    ! empty($row['department_slug']) ||
                    $type ===
                    'industrial_job_roles'
                ) &&
                ! $department
            ) {
                throw new RuntimeException(
                    'Unknown department_slug.'
                );
            }

            $row['department_id'] =
                $department?->id;

            if ($type === 'industrial_jobs') {
                $role = empty($row['role_slug'])
                    ? null
                    : IndustrialJobRole::where(
                        'slug',
                        $row['role_slug']
                    )->first();

                if (
                    ! empty($row['role_slug']) &&
                    ! $role
                ) {
                    throw new RuntimeException(
                        'Unknown role_slug.'
                    );
                }

                if (
                    $role &&
                    $department &&
                    $role->department_id !==
                    $department->id
                ) {
                    throw new RuntimeException(
                        'Role does not belong to the selected department.'
                    );
                }

                $row['job_role_id'] =
                    $role?->id;

                if ($role) {
                    $row['department_id'] =
                        $role->department_id;
                }
            }
        }

        $model = $this->model($type);

        $row = array_intersect_key(
            $row,
            array_flip(
                (new $model)->getFillable()
            )
        );

        $record = $existing ??
            $model::firstOrNew($key);

        if (
            $createOnly &&
            $record->exists
        ) {
            throw new RuntimeException(
                'This record already exists. Use Edit to update it.'
            );
        }

        if (
            $existing &&
            $model::where($key)
            ->where(
                'id',
                '<>',
                $existing->id
            )
            ->exists()
        ) {
            throw new RuntimeException(
                'Another record already uses this slug or external reference in the selected parent.'
            );
        }

        if (
            $record->exists &&
            $type === 'industrial_companies' &&
            $record->industrial_area_id !==
            $row['industrial_area_id'] &&
            $record->jobs()->exists()
        ) {
            throw new RuntimeException(
                'This facility has openings. Create a separate facility for a different area instead of moving its openings.'
            );
        }

        if (
            $record->exists &&
            $type === 'industrial_job_roles' &&
            $record->department_id !==
            $row['department_id'] &&
            $record->jobs()->exists()
        ) {
            throw new RuntimeException(
                'This role has openings. Reassign those openings before changing its department.'
            );
        }

        $effective = array_merge(
            $record->attributesToArray(),
            $row
        );

        foreach (
            ['salary', 'experience'] as $range
        ) {
            if (
                isset(
                    $effective[$range . '_min'],
                    $effective[$range . '_max']
                ) &&
                $effective[$range . '_min'] >
                $effective[$range . '_max']
            ) {
                throw new RuntimeException(
                    $range .
                        ' minimum exceeds maximum after applying the update.'
                );
            }
        }

        if (
            (
                $effective['verification_status'] ??
                'unverified'
            ) !== 'unverified' &&
            (
                empty($effective['source_name']) ||
                empty($effective['source_url']) ||
                empty($effective['last_verified_at'])
            )
        ) {
            throw new RuntimeException(
                'Verified/reported records require source_name, source_url and last_verified_at.'
            );
        }

        $record->fill($row)->save();

        return $record;
    }
}
