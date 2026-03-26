<?php

namespace App\Domain\Calendar\Enums;

enum SeasonStrategy: string
{
    case DecemberSeason = 'december_season';
    case HolyWeek = 'holy_week';
    case OctoberRecess = 'october_recess';
    case FixedRange = 'fixed_range';
}
