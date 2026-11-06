<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum PlanInterval: string
{
    case None = 'none';
    case Month = 'month';
    case SixMonths = 'six_months';
    case Year = 'year';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No expiry',
            self::Month => '1 month',
            self::SixMonths => '6 months',
            self::Year => '1 year',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::None => '',
            self::Month => '/month',
            self::SixMonths => '/6 months',
            self::Year => '/year',
        };
    }

    public function addTo(CarbonInterface $date): ?CarbonInterface
    {
        return match ($this) {
            self::None => null,
            self::Month => $date->copy()->addMonthNoOverflow(),
            self::SixMonths => $date->copy()->addMonthsNoOverflow(6),
            self::Year => $date->copy()->addYearNoOverflow(),
        };
    }
}
