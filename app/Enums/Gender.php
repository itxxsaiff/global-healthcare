<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
            self::Other => 'Other',
            self::PreferNotToSay => 'Prefer not to say',
        };
    }

    /** Options offered to practitioners (client doc: Male / Female / Other). */
    public static function forPractitioners(): array
    {
        return [self::Male, self::Female, self::Other];
    }

    /** Options offered to patients (client doc: Male / Female / Prefer not to say). */
    public static function forPatients(): array
    {
        return [self::Male, self::Female, self::PreferNotToSay];
    }
}
