<?php

namespace App\Services\Membership;

use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\PractitionerProfile;

/**
 * The single place that decides whether a practitioner appears publicly:
 * verified + active account + a live membership (active, or past-due within grace).
 */
class VisibilityService
{
    public function shouldBePublic(PractitionerProfile $profile): bool
    {
        if ($profile->verification_status !== VerificationStatus::Approved) {
            return false;
        }

        if ($profile->user?->status !== UserStatus::Active) {
            return false;
        }

        $graceDays = (int) \App\Models\Setting::get('membership_grace_days', 3);

        return $profile->memberships()
            ->where(function ($q) use ($graceDays) {
                $q->where('status', MembershipStatus::Active)
                    ->orWhere(fn ($q) => $q->where('status', MembershipStatus::PastDue)
                        ->where('ends_at', '>', now()->subDays($graceDays)));
            })
            ->exists();
    }

    public function refresh(PractitionerProfile $profile): bool
    {
        $public = $this->shouldBePublic($profile);

        if ($profile->is_public !== $public) {
            $profile->forceFill(['is_public' => $public])->save();
        }

        return $public;
    }
}
