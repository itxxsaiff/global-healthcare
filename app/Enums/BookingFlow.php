<?php

namespace App\Enums;

enum BookingFlow: string
{
    /** PK Plan 1: patient pays through the platform, 20% commission. */
    case PlatformPayment = 'platform_payment';

    /** Paid plans: slot booked through the platform, payment arranged directly with the practitioner. */
    case Direct = 'direct';
}
