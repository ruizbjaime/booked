<?php

namespace App\Domain\Calendar\Enums;

enum PricingRuleType: string
{
    case SeasonDays = 'season_days';
    case Holiday = 'holiday';
    case HolidayBridge = 'holiday_bridge';
    case NormalWeekend = 'normal_weekend';
    case EconomyDefault = 'economy_default';
}
