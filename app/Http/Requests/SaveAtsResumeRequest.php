<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAtsResumeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        $roles = config('recruitment.roles', []);
        $degrees = config('resume.degrees', []);
        $links = config('resume.link_types', []);
        return [
            'full_name' => ['required','string','min:2','max:120','regex:/^[\pL\pM .\'-]+$/u'],
            'headline' => ['required', Rule::in($roles)],
            'email' => ['required','email:rfc','max:190'],
            'phone' => ['required','regex:/^\+?[0-9][0-9 ()-]{7,18}$/'],
            'location' => ['required','string','min:2','max:150'],
            'summary' => ['required','string','min:60','max:1200'],
            'template' => ['required', Rule::in(array_keys(config('resume.templates', [])))],
            'skills' => ['required','array','min:1','max:50'], 'skills.*' => ['required','string','max:60'],
            'experience' => ['nullable','array','max:20'],
            'experience.*.company' => ['required','string','min:2','max:150'],
            'experience.*.role' => ['required', Rule::in($roles)],
            'experience.*.location' => ['required','string','min:2','max:150'],
            'experience.*.start_date' => ['required','date_format:Y-m'],
            'experience.*.currently_working' => ['required','boolean'],
            'experience.*.end_date' => ['nullable','date_format:Y-m'],
            'experience.*.notice_period' => ['nullable', Rule::in(config('resume.notice_periods', []))],
            'experience.*.bullets' => ['required','array','min:1','max:12'],
            'experience.*.bullets.*' => ['required','string','min:15','max:300'],
            'education' => ['required','array','min:1','max:20'],
            'education.*.degree' => ['required', Rule::in($degrees)],
            'education.*.institution' => ['required','string','min:2','max:180'],
            'education.*.location' => ['required','string','min:2','max:150'],
            'education.*.end_date' => ['required','integer','min:1950','max:'.(now()->year + 10)],
            'education.*.gpa' => ['nullable','regex:/^(?:10(?:\.0{1,2})?|[0-9](?:\.\d{1,2})?|100(?:\.0{1,2})?|[1-9]\d(?:\.\d{1,2})?)$/'],
            'links' => ['nullable','array','max:15'],
            'links.*.label' => ['required', Rule::in($links)],
            'links.*.url' => ['required','url:http,https','max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('experience', []) as $i => $item) {
                $current = filter_var($item['currently_working'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if (!$current && empty($item['end_date'])) $validator->errors()->add("experience.$i.end_date", 'End date is required unless you currently work here.');
                if ($current && empty($item['notice_period'])) $validator->errors()->add("experience.$i.notice_period", 'Notice period is required for your current job.');
                if (!empty($item['start_date']) && !empty($item['end_date']) && $item['end_date'] < $item['start_date']) $validator->errors()->add("experience.$i.end_date", 'End date cannot be earlier than start date.');
            }
        });
    }
}
