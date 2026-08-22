<?php

namespace App\Domain\Pricing\Enums;

enum PricingRuleType: string
{
    case Base = 'base';
    case Weekday = 'weekday';
    case Weekend = 'weekend';
    case PeakHour = 'peak_hour';
    case SpecialDate = 'special_date';

    public function specificity(): int
    {
        return match ($this) {
            self::SpecialDate => 1,
            self::PeakHour => 2,
            self::Weekday, self::Weekend => 3,
            self::Base => 4,
        };
    }
}
