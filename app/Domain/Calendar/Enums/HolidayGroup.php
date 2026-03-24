<?php

namespace App\Domain\Calendar\Enums;

enum HolidayGroup: string
{
    case Fixed = 'fixed';
    case Emiliani = 'emiliani';
    case EasterBased = 'easter_based';
}
