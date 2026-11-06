<?php

namespace App\Http\Requests\Practitioner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FocusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->practitionerProfile?->verification_status->isEditable() ?? false;
    }

    public function rules(): array
    {
        $primary = $this->user()->practitionerProfile->primary_specialty_id;

        return [
            'sub_specialties' => ['nullable', 'array', 'max:10'],
            'sub_specialties.*' => ['integer', Rule::exists('specialties', 'id')->where('parent_id', $primary)],
            'conditions' => ['nullable', 'array', 'max:15'],
            'conditions.*' => ['integer', Rule::exists('conditions', 'id')->where('is_active', true)],
            'languages' => ['required', 'array', 'min:1'],
            'languages.*' => ['integer', Rule::exists('languages', 'id')],
            'other_languages' => ['nullable', 'string', 'max:120'],
            'custom_tags' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return ['languages.required' => 'Choose at least one language you consult in.'];
    }
}
