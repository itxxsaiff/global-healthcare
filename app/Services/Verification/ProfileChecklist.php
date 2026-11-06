<?php

namespace App\Services\Verification;

use App\Enums\DocumentType;
use App\Models\PractitionerProfile;

/**
 * What a practitioner must complete before submitting for verification.
 * Used by the dashboard checklist and enforced on submit.
 */
class ProfileChecklist
{
    /** @return array<string, array{label: string, done: bool, step: string}> */
    public function for(PractitionerProfile $p): array
    {
        $docs = $p->relationLoaded('documents') ? $p->documents : $p->documents()->get();
        $hasDoc = fn (DocumentType $t) => $docs->contains(fn ($d) => $d->type === $t);

        return [
            'details' => ['label' => 'Professional details & fee', 'step' => 'details', 'done' => filled($p->primary_specialty_id) && filled($p->qualifications) && filled($p->license_number) && filled($p->fee_minor)],
            'focus' => ['label' => 'Languages, sub-specialties & symptoms', 'step' => 'focus', 'done' => $p->languages()->exists() && ($p->subSpecialties()->exists() || $p->conditions()->exists())],
            'bio' => ['label' => 'Bio & headline (max 200 words)', 'step' => 'about', 'done' => filled($p->bio) && filled($p->headline)],
            'photo' => ['label' => 'Professional headshot', 'step' => 'about', 'done' => filled($p->headshot_path)],
            'documents' => ['label' => 'ID, degree and licence documents', 'step' => 'documents', 'done' => $hasDoc(DocumentType::GovernmentId) && $hasDoc(DocumentType::Degree) && $hasDoc(DocumentType::License)],
        ];
    }

    public function isComplete(PractitionerProfile $p): bool
    {
        return collect($this->for($p))->every(fn ($item) => $item['done']);
    }

    public function percent(PractitionerProfile $p): int
    {
        $items = $this->for($p);

        return (int) round(collect($items)->where('done', true)->count() / count($items) * 100);
    }
}
