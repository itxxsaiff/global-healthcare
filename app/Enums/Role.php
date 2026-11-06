<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Practitioner = 'practitioner';
    case Patient = 'patient';

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Practitioner => 'practitioner.dashboard',
            self::Patient => 'patient.dashboard',
        };
    }
}
