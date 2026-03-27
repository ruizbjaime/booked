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
     * @return array<string, array{definitionId: int, impact: float}> Map of date string (Y-m-d) to bridge day data
     */
    public function detect(array $resolvedHolidays): array
    {
        $bridgeDays = [];

        foreach ($resolvedHolidays as $holiday) {
            $observed = $holiday->observedDate;
            $entry = ['definitionId' => $holiday->definitionId, 'impact' => $holiday->impact];

            if ($observed->isMonday()) {
                $friday = $observed->previous(CarbonImmutable::FRIDAY);
                $saturday = $observed->previous(CarbonImmutable::SATURDAY);
                $sunday = $observed->subDay();

                $bridgeDays[$friday->toDateString()] = $entry;
                $bridgeDays[$saturday->toDateString()] = $entry;
                $bridgeDays[$sunday->toDateString()] = $entry;
            } elseif ($observed->isFriday()) {
                if ($holiday->group === HolidayGroup::Fixed && ! $holiday->wasMoved) {
                    $thursday = $observed->subDay();
                    $bridgeDays[$thursday->toDateString()] = $entry;
                    $bridgeDays[$observed->toDateString()] = $entry;
                    $bridgeDays[$observed->addDay()->toDateString()] = $entry;
                } elseif ($holiday->name === 'good_friday') {
                    $bridgeDays[$observed->addDay()->toDateString()] = $entry;
                }
            }
        }

        return $bridgeDays;
    }
}
