<?php

namespace App\Services\Verification;

use App\Enums\ModerationStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\PractitionerProfile;
use App\Models\User;
use App\Notifications\PractitionerApproved;
use App\Notifications\PractitionerRejected;
use App\Notifications\PractitionerSubmitted;
use App\Services\Membership\VisibilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Practitioner verification workflow (client doc, steps 1–3):
 *   draft → pending → approved | rejected (→ edit → pending) ; approved ⇄ suspended
 */
class VerificationService
{
    public function __construct(
        private readonly ProfileChecklist $checklist,
        private readonly VisibilityService $visibility,
    ) {}

    public function submit(PractitionerProfile $profile): void
    {
        if (! in_array($profile->verification_status, [VerificationStatus::Draft, VerificationStatus::Rejected], true)) {
            throw ValidationException::withMessages(['profile' => 'This profile has already been submitted.']);
        }
        if (! $this->checklist->isComplete($profile)) {
            throw ValidationException::withMessages(['profile' => 'Please complete every step before submitting.']);
        }

        $profile->forceFill([
            'verification_status' => VerificationStatus::Pending,
            'submitted_at' => now(),
            'rejection_reason' => null,
        ])->save();

        AuditLog::record('practitioner.submitted', $profile);
        Notification::send(User::query()->where('role', Role::Admin)->get(), new PractitionerSubmitted($profile));
    }

    public function approve(PractitionerProfile $profile, User $admin): void
    {
        $this->assertStatus($profile, [VerificationStatus::Pending]);

        DB::transaction(function () use ($profile, $admin) {
            $profile->forceFill([
                'verification_status' => VerificationStatus::Approved,
                'verified_at' => now(),
                'verified_by' => $admin->id,
                'rejection_reason' => null,
            ])->save();

            $profile->documents()->update([
                'status' => ModerationStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            // Approving any custom tags the practitioner suggested is a separate admin action.
            AuditLog::record('practitioner.approved', $profile);
        });

        $this->visibility->refresh($profile);
        $profile->user->notify(new PractitionerApproved($profile));
    }

    public function reject(PractitionerProfile $profile, User $admin, string $reason): void
    {
        $this->assertStatus($profile, [VerificationStatus::Pending]);

        $profile->forceFill([
            'verification_status' => VerificationStatus::Rejected,
            'rejection_reason' => $reason,
            'verified_by' => $admin->id,
        ])->save();

        AuditLog::record('practitioner.rejected', $profile, ['reason' => $reason]);
        $this->visibility->refresh($profile);
        $profile->user->notify(new PractitionerRejected($profile));
    }

    public function suspend(PractitionerProfile $profile, string $reason): void
    {
        $this->assertStatus($profile, [VerificationStatus::Approved]);
        $profile->forceFill(['verification_status' => VerificationStatus::Suspended, 'rejection_reason' => $reason])->save();
        AuditLog::record('practitioner.suspended', $profile, ['reason' => $reason]);
        $this->visibility->refresh($profile);
    }

    public function reinstate(PractitionerProfile $profile): void
    {
        $this->assertStatus($profile, [VerificationStatus::Suspended]);
        $profile->forceFill(['verification_status' => VerificationStatus::Approved, 'rejection_reason' => null])->save();
        AuditLog::record('practitioner.reinstated', $profile);
        $this->visibility->refresh($profile);
    }

    /**
     * An approved practitioner changed something we verified (name, licence, documents):
     * back to the queue and off the public directory until re-checked.
     */
    public function requireReverification(PractitionerProfile $profile, string $what): void
    {
        if ($profile->verification_status !== VerificationStatus::Approved) {
            return;
        }

        $profile->forceFill([
            'verification_status' => VerificationStatus::Pending,
            'submitted_at' => now(),
        ])->save();

        AuditLog::record('practitioner.reverification', $profile, ['changed' => $what]);
        $this->visibility->refresh($profile);
        Notification::send(User::query()->where('role', Role::Admin)->get(), new PractitionerSubmitted($profile, reverification: true));
    }

    private function assertStatus(PractitionerProfile $profile, array $allowed): void
    {
        if (! in_array($profile->verification_status, $allowed, true)) {
            throw ValidationException::withMessages([
                'profile' => 'This action is not available while the profile is "'.$profile->verification_status->label().'".',
            ]);
        }
    }
}
