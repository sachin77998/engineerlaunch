<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IngestJobsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expected = (string) config('services.job_ingestion.key');
        $provided = (string) $this->header('X-Ingestion-Key');

        return $expected !== '' && hash_equals($expected, $provided);
    }

    public function rules(): array
    {
        return [
            'jobs' => ['required', 'array', 'min:1', 'max:500'],
            'jobs.*.external_job_id' => ['required', 'string', 'max:191'],
            'jobs.*.source' => ['required', 'string', 'max:50'],
            'jobs.*.company_name' => ['required', 'string', 'max:150'],
            'jobs.*.title' => ['required', 'string', 'max:255'],
            'jobs.*.description' => ['nullable', 'string'],
            'jobs.*.location' => ['required', 'string', 'max:255'],
            'jobs.*.country' => ['nullable', 'string', 'max:100'],
            'jobs.*.experience_min' => ['nullable', 'integer', 'min:0', 'max:60'],
            'jobs.*.experience_max' => ['nullable', 'integer', 'gte:jobs.*.experience_min', 'max:60'],
            'jobs.*.salary_min' => ['nullable', 'numeric', 'min:0'],
            'jobs.*.salary_max' => ['nullable', 'numeric', 'gte:jobs.*.salary_min'],
            'jobs.*.salary_currency' => ['nullable', 'string', 'size:3'],
            'jobs.*.employment_type' => ['nullable', 'in:FULL_TIME,PART_TIME,CONTRACT,INTERNSHIP,TEMPORARY'],
            'jobs.*.work_mode' => ['nullable', 'in:OFFICE,HYBRID,REMOTE'],
            'jobs.*.skills' => ['nullable', 'array', 'max:50'],
            'jobs.*.skills.*' => ['string', 'max:80'],
            'jobs.*.apply_url' => ['required', 'url:http,https', 'max:2048'],
            'jobs.*.posted_at' => ['nullable', 'date'],
            'jobs.*.expires_at' => ['nullable', 'date', 'after:jobs.*.posted_at'],
        ];
    }
}
