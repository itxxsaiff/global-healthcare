<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Profile incomplete',
            self::Pending => 'Pending verification',
            self::Approved => 'Verified',
            self::Rejected => 'Changes requested',
            self::Suspended => 'Suspended',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected, self::Suspended => 'danger',
        };
    }

    /** Practitioner may still edit and (re)submit the profile. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Rejected, self::Approved], true);
    }
}
