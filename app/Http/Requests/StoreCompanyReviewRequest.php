<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && ! in_array($user->role, ['employer', 'admin'], true)
            && ! in_array((int) $user->role_code, [0, 2], true);
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'review' => ['required', 'string', 'min:20', 'max:2000'],
            'pros' => ['nullable', 'string', 'max:1500'],
            'cons' => ['nullable', 'string', 'max:1500'],
            'relationship' => ['nullable', Rule::in([
                'current_employee', 'former_employee', 'interview_candidate',
                'job_applicant', 'other',
            ])],
            'job_id' => ['nullable', 'integer', 'exists:jobs,id'],
        ];
    }
}
