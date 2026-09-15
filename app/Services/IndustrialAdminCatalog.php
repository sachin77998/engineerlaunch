<?php

namespace App\Services;

use App\Models\IndustrialArea;
use App\Models\IndustrialCompany;
use App\Models\IndustrialDepartment;
use App\Models\IndustrialJob;
use App\Models\IndustrialJobRole;
use App\Models\IndustrialState;

class IndustrialAdminCatalog
{
    public const LABELS = ['states' => 'States', 'industrial_areas' => 'Industrial Areas', 'industrial_companies' => 'Companies / Facilities', 'industrial_departments' => 'Departments', 'industrial_job_roles' => 'Job Roles', 'industrial_jobs' => 'Openings'];
    public const MODELS = ['states' => IndustrialState::class, 'industrial_areas' => IndustrialArea::class, 'industrial_companies' => IndustrialCompany::class, 'industrial_departments' => IndustrialDepartment::class, 'industrial_job_roles' => IndustrialJobRole::class, 'industrial_jobs' => IndustrialJob::class];
    public function model(string $type): string
    {
        abort_unless(isset(self::MODELS[$type]), 404);
        return self::MODELS[$type];
    }

    public function fields(string $type): array
    {
        $model = $this->model($type);
        $fields = [];
        if (in_array($type, ['industrial_areas', 'industrial_companies', 'industrial_jobs'], true)) {
            $fields['state_slug'] = 'select';
        }
        if (in_array($type, ['industrial_companies', 'industrial_jobs'], true)) {
            $fields['area_slug'] = 'select';
        }
        if ($type === 'industrial_jobs') {
            $fields['company_slug'] = 'select';
        }
        if (in_array($type, ['industrial_job_roles', 'industrial_jobs'], true)) {
            $fields['department_slug'] = 'select';
        }
        if ($type === 'industrial_jobs') {
            $fields['role_slug'] = 'select';
        }
        foreach ((new $model)->getFillable() as $field) {
            if (in_array($field, ['state_id', 'industrial_area_id', 'industrial_company_id', 'department_id', 'job_role_id'], true)) {
                continue;
            }
            $fields[$field] = match ($field) {
                'is_active', 'is_featured', 'is_verified' => 'checkbox',
                'description' => 'textarea',
                'source_url', 'website' => 'url',
                'last_verified_at', 'application_deadline' => 'datetime-local',
                'area_type', 'facility_type', 'verification_status', 'employment_type', 'salary_period' => 'select',
                'latitude', 'longitude', 'salary_min', 'salary_max', 'experience_min', 'experience_max' => 'number',
                default => 'text',
            };
        }

        return $fields;
    }

    public function values($record): array
    {
        $values = [];
        foreach ($record->getFillable() as $field) {
            $value = $record->{$field};
            $values[$field] = $value instanceof \DateTimeInterface ? $value->format('Y-m-d\TH:i') : (is_array($value) ? implode(';', $value) : $value);
        }
        if ($record instanceof IndustrialArea) {
            $values['state_slug'] = $record->state?->slug;
        }
        if ($record instanceof IndustrialCompany || $record instanceof IndustrialJob) {
            $values['state_slug'] = $record->area?->state?->slug;
            $values['area_slug'] = $record->area?->slug;
        }
        if ($record instanceof IndustrialJobRole || $record instanceof IndustrialJob) {
            $values['department_slug'] = $record->department?->slug;
        }
        if ($record instanceof IndustrialJob) {
            $values['company_slug'] = $record->company?->slug;
            $values['role_slug'] = $record->role?->slug;
        }

        return $values;
    }

    public function options(array $values): array
    {
        $pairs = fn (array $items) => array_combine($items, array_map(fn ($s) => ucwords(str_replace('_', ' ', $s)), $items));

        return [
            'state_slug' => IndustrialState::orderBy('name')->pluck('name', 'slug'),
            'area_slug' => empty($values['state_slug']) ? [] : IndustrialArea::whereHas('state', fn ($q) => $q->where('slug', $values['state_slug']))->orderBy('name')->pluck('name', 'slug'),
            'company_slug' => empty($values['area_slug']) || empty($values['state_slug']) ? [] : IndustrialCompany::whereHas('area', fn ($q) => $q->where('slug', $values['area_slug'])->whereHas('state', fn ($s) => $s->where('slug', $values['state_slug'])))->orderBy('name')->pluck('name', 'slug'),
            'department_slug' => IndustrialDepartment::orderBy('name')->pluck('name', 'slug'),
            'role_slug' => empty($values['department_slug']) ? [] : IndustrialJobRole::whereHas('department', fn ($q) => $q->where('slug', $values['department_slug']))->orderBy('name')->pluck('name', 'slug'),
            'area_type' => $pairs(IndustrialCsvImporter::AREA_TYPES),
            'facility_type' => $pairs(['Plant', 'Factory', 'Warehouse', 'Depot', 'Distribution Centre', 'Office', 'R&D Centre', 'Service Centre', 'Industrial Unit', 'Logistics Hub', 'Manufacturing plant', 'SEZ unit']),
            'verification_status' => $pairs(['unverified', 'government_source', 'company_source', 'admin_verified', 'community_verified']),
            'employment_type' => $pairs(['Full Time', 'Contract', 'Apprenticeship', 'Trainee', 'Part Time']),
            'salary_period' => $pairs(['hourly', 'daily', 'monthly', 'annual']),
        ];
    }
}
