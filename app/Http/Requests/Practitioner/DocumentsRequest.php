<?php

namespace App\Http\Requests\Practitioner;

use App\Enums\DocumentType;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class DocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->practitionerProfile?->verification_status->isEditable() ?? false;
    }

    public function rules(): array
    {
        $maxKb = (int) Setting::get('document_max_kb', 3072);
        $rule = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:'.$maxKb];

        return collect(DocumentType::cases())->mapWithKeys(fn ($t) => [$t->value => $rule])->all();
    }

    public function messages(): array
    {
        $mb = round(Setting::get('document_max_kb', 3072) / 1024);
        $messages = [];
        foreach (DocumentType::cases() as $t) {
            // Exact wording from the client document.
            $messages[$t->value.'.max'] = "File size exceeds {$mb}MB. Please upload a compressed document to ensure fast processing";
            $messages[$t->value.'.mimes'] = 'Please upload a PDF, JPG or PNG file.';
            $messages[$t->value.'.mimetypes'] = 'Please upload a PDF, JPG or PNG file.';
        }

        return $messages;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (! collect(DocumentType::cases())->contains(fn ($t) => $this->hasFile($t->value))) {
                $v->errors()->add('documents', 'Choose at least one document to upload.');
            }
        });
    }
}
