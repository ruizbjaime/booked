<?php

namespace App\Domain\Calendar\Services;

use Carbon\CarbonImmutable;

final class QuincenaCalculator
{
    private const int ADJACENCY_DAYS = 2;

    /**
     * Check if a date is within 2 days of the 15th or last day of its month.
     */
    public static function isQuincenaAdjacent(CarbonImmutable $date): bool
    {
        $day = $date->day;
        $lastDay = $date->daysInMonth;

        return abs($day - 15) <= self::ADJACENCY_DAYS
            || ($lastDay - $day) <= self::ADJACENCY_DAYS;
    }
}
