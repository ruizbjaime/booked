<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\Enums\HolidayGroup;
use App\Domain\Calendar\ValueObjects\ResolvedHoliday;
use Carbon\CarbonImmutable;

final class BridgeDayDetector
{
    /**
     * Detect bridge days around holidays observed on Mondays.
     * When a holiday is on Monday, Fri/Sat/Sun become bridge days (puente).
     * When a fixed holiday falls on Friday, Thu/Fri/Sat become bridge days.
     * Good Friday is the exception: only Holy Saturday may be treated as adjacent
     * and Easter Sunday remains checkout day.
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
                if ($holiday->group === HolidayGroup::Fixed && ! $holiday->wasMoved) {
                    $thursday = $observed->subDay();
                    $bridgeDays[$thursday->toDateString()] = $holiday->definitionId;
                    $bridgeDays[$observed->toDateString()] = $holiday->definitionId;
                    $bridgeDays[$observed->addDay()->toDateString()] = $holiday->definitionId;
                } elseif ($holiday->name === 'good_friday') {
                    $bridgeDays[$observed->addDay()->toDateString()] = $holiday->definitionId;
                }
            }
        }

        return $bridgeDays;
    }
}
