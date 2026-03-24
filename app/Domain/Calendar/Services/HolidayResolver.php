<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\Data\HolidayDefinitionData;
use App\Domain\Calendar\Enums\HolidayGroup;
use App\Domain\Calendar\ValueObjects\ResolvedHoliday;
use Carbon\CarbonImmutable;

final class HolidayResolver
{
    private const array DAY_NAMES = [
        0 => 'sunday',
        1 => 'monday',
        2 => 'tuesday',
        3 => 'wednesday',
        4 => 'thursday',
        5 => 'friday',
        6 => 'saturday',
    ];

    /**
     * @param  list<HolidayDefinitionData>  $definitions
     * @return list<ResolvedHoliday>
     */
    public function resolve(array $definitions, int $year, CarbonImmutable $easter): array
    {
        $holidays = [];

        foreach ($definitions as $definition) {
            $resolved = match ($definition->group) {
                HolidayGroup::Fixed => $this->resolveFixed($definition, $year),
                HolidayGroup::Emiliani => $this->resolveEmiliani($definition, $year),
                HolidayGroup::EasterBased => $this->resolveEasterBased($definition, $easter),
            };

            if ($resolved !== null) {
                $holidays[] = $resolved;
            }
        }

        return $holidays;
    }

    private function resolveFixed(HolidayDefinitionData $definition, int $year): ?ResolvedHoliday
    {
        if ($definition->month === null || $definition->day === null) {
            return null;
        }

        $originalDate = CarbonImmutable::createStrict($year, $definition->month, $definition->day);
        $impact = $this->resolveImpact($definition, $originalDate);

        return new ResolvedHoliday(
            definitionId: $definition->id,
            name: $definition->name,
            group: $definition->group,
            originalDate: $originalDate,
            observedDate: $originalDate,
            impact: $impact,
            wasMoved: false,
        );
    }

    private function resolveEmiliani(HolidayDefinitionData $definition, int $year): ?ResolvedHoliday
    {
        if ($definition->month === null || $definition->day === null) {
            return null;
        }

        $originalDate = CarbonImmutable::createStrict($year, $definition->month, $definition->day);
        $observedDate = $this->moveToNextMonday($originalDate);
        $wasMoved = ! $originalDate->equalTo($observedDate);

        $impact = $this->resolveImpact($definition, $observedDate);

        return new ResolvedHoliday(
            definitionId: $definition->id,
            name: $definition->name,
            group: $definition->group,
            originalDate: $originalDate,
            observedDate: $observedDate,
            impact: $impact,
            wasMoved: $wasMoved,
        );
    }

    private function resolveEasterBased(HolidayDefinitionData $definition, CarbonImmutable $easter): ?ResolvedHoliday
    {
        if ($definition->easterOffset === null) {
            return null;
        }

        $originalDate = $easter->addDays($definition->easterOffset);
        $observedDate = $definition->movesToMonday
            ? $this->moveToNextMonday($originalDate)
            : $originalDate;
        $wasMoved = ! $originalDate->equalTo($observedDate);

        $impact = $this->resolveImpact($definition, $observedDate);

        return new ResolvedHoliday(
            definitionId: $definition->id,
            name: $definition->name,
            group: $definition->group,
            originalDate: $originalDate,
            observedDate: $observedDate,
            impact: $impact,
            wasMoved: $wasMoved,
        );
    }

    /**
     * Ley Emiliani: if the holiday does not fall on Monday, move to next Monday.
     */
    private function moveToNextMonday(CarbonImmutable $date): CarbonImmutable
    {
        if ($date->isMonday()) {
            return $date;
        }

        return $date->next(CarbonImmutable::MONDAY);
    }

    private function resolveImpact(HolidayDefinitionData $definition, CarbonImmutable $observedDate): float
    {
        $weights = $definition->baseImpactWeights;
        $dayName = self::DAY_NAMES[$observedDate->dayOfWeek];

        return $weights[$dayName] ?? $weights['default'] ?? 0.0;
    }
}
