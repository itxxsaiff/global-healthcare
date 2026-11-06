<?php

namespace App\Http\Requests\Practitioner;

use App\Models\PractitionerProfile;
use App\Models\Setting;
use App\Rules\MaxWords;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AboutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->practitionerProfile?->verification_status->isEditable() ?? false;
    }

    public function rules(): array
    {
        $hasPhoto = filled($this->user()->practitionerProfile->headshot_path);

        return [
            'headline' => ['required', 'string', 'min:20', 'max:160'],
            'bio' => ['required', 'string', 'max:2000', new MaxWords(PractitionerProfile::BIO_MAX_WORDS, 40)],
            'headshot' => [
                Rule::requiredIf(! $hasPhoto), 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.Setting::get('headshot_max_kb', 2048), 'dimensions:min_width=300,min_height=300',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'headshot.max' => 'File size exceeds 2MB. Please upload a smaller photo.',
            'headshot.dimensions' => 'Please use a photo at least 300×300 pixels.',
            'headshot.mimes' => 'Please upload a JPG, PNG or WebP photo.',
            'headshot.required' => 'A professional headshot is required.',
        ];
    }
}
