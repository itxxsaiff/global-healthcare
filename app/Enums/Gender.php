<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Transgender = 'transgender';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
            self::Transgender => 'Transgender',
            self::Other => 'Other',
            self::PreferNotToSay => 'Prefer not to say',
        };
    }

    /** Options offered to practitioners (client doc: Male / Female / Other; Transgender added at client request). */
    public static function forPractitioners(): array
    {
        return [self::Male, self::Female, self::Transgender, self::Other];
    }

    /** Options offered to patients (client doc: Male / Female / Prefer not to say; Transgender added at client request). */
    public static function forPatients(): array
    {
        return [self::Male, self::Female, self::Transgender, self::PreferNotToSay];
    }
}
