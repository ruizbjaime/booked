<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\ValueObjects\ResolvedHoliday;
use Carbon\CarbonImmutable;

final class BridgeDayDetector
{
    /**
     * Detect bridge days around holidays observed on Mondays.
     * When a holiday is on Monday, Fri/Sat/Sun become bridge days (puente).
     * When a holiday is on Friday, Sat/Sun become bridge days.
     *
     * @param  list<ResolvedHoliday>  $resolvedHolidays
     * @return array<string, int> Map of date string (Y-m-d) to holiday definition ID
     */
    public function detect(array $resolvedHolidays): array
    {
        $bridgeDays = [];

        foreach ($resolvedHolidays as $holiday) {
            $observed = $holiday->observedDate;

            if ($observed->isMonday()) {
                $friday = $observed->previous(CarbonImmutable::FRIDAY);
                $saturday = $observed->previous(CarbonImmutable::SATURDAY);
                $sunday = $observed->subDay();

                $bridgeDays[$friday->toDateString()] = $holiday->definitionId;
                $bridgeDays[$saturday->toDateString()] = $holiday->definitionId;
                $bridgeDays[$sunday->toDateString()] = $holiday->definitionId;
            } elseif ($observed->isFriday()) {
                $saturday = $observed->addDay();
                $sunday = $observed->addDays(2);

                $bridgeDays[$saturday->toDateString()] = $holiday->definitionId;
                $bridgeDays[$sunday->toDateString()] = $holiday->definitionId;
            }
        }

        return $bridgeDays;
    }
}
