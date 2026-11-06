<?php

namespace App\Http\Requests\Practitioner;

use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->practitionerProfile?->verification_status->isEditable() ?? false;
    }

    public function rules(): array
    {
        $countryId = $this->user()->practitionerProfile->country_id;

        return [
            'title' => ['nullable', Rule::in(['Dr.', 'Prof.', 'Mr.', 'Ms.', 'Mrs.'])],
            'gender' => ['required', Rule::in(collect(Gender::forPractitioners())->map->value)],
            'primary_specialty_id' => ['required', Rule::exists('specialties', 'id')->whereNull('parent_id')->where('is_active', true)],
            'qualifications' => ['required', 'string', 'max:300'],
            'license_number' => ['required', 'string', 'max:80'],
            'licensing_body' => ['required', 'string', 'max:120'],
            'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'city_id' => ['nullable', Rule::exists('cities', 'id')->where('country_id', $countryId)],
            'timezone' => ['required', 'timezone:all'],
            'fee' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'session_minutes' => ['required', Rule::in([20, 30, 45, 60])],
            'linkedin_url' => ['nullable', 'url:https', 'max:255', 'regex:~^https://([a-z]+\.)?linkedin\.com/~i'],
            'intro_video_url' => ['nullable', 'url:https', 'max:255', 'regex:~^https://(www\.)?(youtube\.com|youtu\.be|vimeo\.com)/~i'],
            'whatsapp_number' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s()-]{7,30}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'linkedin_url.regex' => 'Please paste a linkedin.com profile link.',
            'intro_video_url.regex' => 'Only YouTube or Vimeo links can be embedded.',
            'primary_specialty_id.required' => 'Please choose your main specialty.',
        ];
    }
}
