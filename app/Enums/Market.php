<?php

namespace App\Enums;

/**
 * Which pricing market a practitioner belongs to. Decided by the verified
 * practice country (Pakistan => Pk), never by the visitor's IP address.
 */
enum Market: string
{
    case Pk = 'pk';
    case Intl = 'intl';

    public function currency(): string
    {
        return $this === self::Pk ? 'PKR' : 'USD';
    }

    public function label(): string
    {
        return $this === self::Pk ? 'Pakistan' : 'International';
    }
}
