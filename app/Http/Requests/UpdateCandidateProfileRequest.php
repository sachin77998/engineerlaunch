<?php
namespace App\Http\Requests;
use App\Models\CandidateProfile;use Illuminate\Foundation\Http\FormRequest;use Illuminate\Validation\Rule;use libphonenumber\PhoneNumberFormat;use libphonenumber\PhoneNumberUtil;
class UpdateCandidateProfileRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        $currencies = ['INR','USD','CAD','AUD','EUR','GBP','AED','JMD','JPY'];
        return [
            'profile_id' => ['nullable','integer','exists:candidate_profiles,id'],
            'email' => ['required','email','max:190',Rule::in([$this->user()->email])],
            'first_name' => ['required','string','max:100'],
            'last_name' => ['nullable','string','max:100'],
            'phone_country_code' => ['required','string','size:2'],
            'phone' => ['required','string','max:30',function ($attribute, $value, $fail) {
                try {
                    $region = strtoupper($this->input('phone_country_code','IN'));
                    $util = PhoneNumberUtil::getInstance();
                    $phone = $util->parse($value, $region);
                    if (!$util->isValidNumberForRegion($phone, $region)) {
                        $fail("Enter a valid phone number for the selected country ({$region}).");
                        return;
                    }
                    $e164 = $util->format($phone, PhoneNumberFormat::E164);
                    $national = (string) $phone->getNationalNumber();
                    if (CandidateProfile::where('user_id','<>',$this->user()->id)->whereIn('phone',array_unique([$value,$e164,$national]))->exists()) {
                        $fail('A candidate profile is already created with this phone number.');
                    }
                } catch (\Throwable) {
                    $fail('Enter a valid international phone number.');
                }
            }],
            'country_code' => ['nullable','string','size:2'], 'state' => ['nullable','string','max:150'], 'city' => ['nullable','string','max:150'],
            'address' => ['nullable','string','max:1000'], 'headline' => ['nullable','string','max:255'], 'bio' => ['nullable','string','max:3000'],
            'current_company' => ['nullable','string','max:150'], 'current_designation' => ['nullable','string','max:150'],
            'experience_years' => ['nullable','integer','min:0','max:60'], 'current_salary' => ['nullable','numeric','min:0'], 'expected_salary' => ['nullable','numeric','min:0'],
            'salary_currency' => ['nullable',Rule::in($currencies)], 'current_salary_currency' => ['nullable',Rule::in($currencies)], 'expected_salary_currency' => ['nullable',Rule::in($currencies)],
            'notice_period' => ['nullable',Rule::in(['Immediate','15 days','1 month','2 months','3 months','More than 3 months'])],
            'preferred_role' => ['nullable','string','max:150'], 'education' => ['nullable','string','max:2000'], 'avatar' => ['nullable','image','max:3072'],
        ];
    }

    public function messages(): array
    {
        return ['email.in' => 'This profile belongs to your verified account email and cannot use another email.', 'phone.required' => 'Phone number is required.'];
    }
}
