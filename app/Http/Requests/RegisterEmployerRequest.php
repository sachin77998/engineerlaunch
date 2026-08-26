<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterEmployerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_phone' => preg_replace('/\D+/', '', (string) $this->company_phone),
            'phone_country_code' => '+'.ltrim((string) $this->phone_country_code, '+'),
        ]);
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required','string','min:2','max:150',Rule::unique('companies','name')],
            'first_name' => ['required','string','max:80'],
            'last_name' => ['required','string','max:80'],
            'phone_country_code' => ['required',Rule::in(['+91','+1','+44','+61','+49','+33','+971'])],
            'company_phone' => ['required','digits_between:7,15',function ($attribute,$value,$fail) { if ($this->phone_country_code === '+91' && !preg_match('/^[6-9]\d{9}$/', $value)) $fail('The Indian company phone number must contain 10 digits and begin with 6, 7, 8 or 9.'); }],
            'phone_verification_token' => ['required','string','size:64'],
            'organization_type' => ['required',Rule::in(['private_limited','public_limited','llp','partnership','sole_proprietorship','government','nonprofit','other'])],
            'business_type' => ['nullable',Rule::in(['services','consulting','finance','brokerage','commission','software','hardware','mechanical','chemical','other'])],
            'company_email' => ['required','email:rfc','max:190',Rule::unique('companies','company_email')],
            'email' => ['required','email:rfc','max:190',Rule::unique('users','email')],
            'website' => ['nullable','url:http,https','max:255'],
            'designation' => ['nullable','string','max:100'],
            'password' => ['required','string','min:8','confirmed'],
        ];
    }
}
