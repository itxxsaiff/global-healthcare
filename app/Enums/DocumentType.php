<?php

namespace App\Enums;

enum DocumentType: string
{
    case GovernmentId = 'government_id';
    case Degree = 'degree';
    case License = 'license';

    public function label(): string
    {
        return match ($this) {
            self::GovernmentId => 'Government ID card / Passport',
            self::Degree => 'Educational degree',
            self::License => 'Medical licence certificate (e.g. PMDC)',
        };
    }
}
