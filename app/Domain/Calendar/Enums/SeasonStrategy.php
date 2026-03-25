<?php

namespace App\Domain\Calendar\Enums;

enum SeasonStrategy: string
{
    case HolyWeek = 'holy_week';
    case OctoberRecess = 'october_recess';
    case YearEnd = 'year_end';
    case FixedRange = 'fixed_range';
}
