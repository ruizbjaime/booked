<?php

namespace App\Domain\Calendar\Services;

use App\Domain\Calendar\Data\SeasonBlockData;
use App\Domain\Calendar\Enums\SeasonStrategy;
use App\Domain\Calendar\ValueObjects\ResolvedHoliday;
use App\Domain\Calendar\ValueObjects\SeasonBlockRange;
use Carbon\CarbonImmutable;

final class SeasonBlockResolver
{
    /**
     * @param  list<SeasonBlockData>  $blocks
     * @param  list<ResolvedHoliday>  $resolvedHolidays
     * @param  list<ResolvedHoliday>  $nextYearHolidays  Holidays for year+1 (needed for cross-year blocks)
     * @return list<SeasonBlockRange>
     */
    public function resolve(
        array $blocks,
        int $year,
        CarbonImmutable $easter,
        array $resolvedHolidays,
        array $nextYearHolidays = [],
    ): array {
        $ranges = [];

        foreach ($blocks as $block) {
            $range = match ($block->calculationStrategy) {
                SeasonStrategy::DecemberSeason => $this->resolveDecemberSeason($block, $year, $nextYearHolidays),
                SeasonStrategy::HolyWeek => $this->resolveHolyWeek($block, $easter),
                SeasonStrategy::OctoberRecess => $this->resolveOctoberRecess($block, $resolvedHolidays),
                SeasonStrategy::FixedRange => $this->resolveFixedRange($block, $year),
            };

            if ($range !== null) {
                $ranges[] = $range;
            }
        }

        return $ranges;
    }

    /**
     * Friday before Palm Sunday through Holy Saturday (Easter - 1).
     * Easter Sunday is excluded (checkout day).
     */
    private function resolveHolyWeek(SeasonBlockData $block, CarbonImmutable $easter): SeasonBlockRange
    {
        $start = $easter->subDays(9); // Friday before Palm Sunday
        $end = $easter->subDay(); // Holy Saturday

        return new SeasonBlockRange(
            blockId: $block->id,
            name: $block->name,
            start: $start,
            end: $end,
            priority: $block->priority,
        );
    }

    /**
     * Dec 1 through the Thursday immediately before the observed Epiphany Monday in next year.
     *
     * @param  list<ResolvedHoliday>  $nextYearHolidays
     */
    private function resolveDecemberSeason(SeasonBlockData $block, int $year, array $nextYearHolidays): SeasonBlockRange
    {
        $start = CarbonImmutable::createStrict($year, 12, 1);
        $end = $this->resolveObservedEpiphanyDate($year, $nextYearHolidays)->subDays(4);

        return new SeasonBlockRange(
            blockId: $block->id,
            name: $block->name,
            start: $start,
            end: $end,
            priority: $block->priority,
        );
    }

    /**
     * @param  list<ResolvedHoliday>  $nextYearHolidays
     */
    private function resolveObservedEpiphanyDate(int $year, array $nextYearHolidays): CarbonImmutable
    {
        foreach ($nextYearHolidays as $holiday) {
            if ($holiday->name === 'epiphany') {
                return $holiday->observedDate;
            }
        }

        $nextJan6 = CarbonImmutable::createStrict($year + 1, 1, 6);

        return $nextJan6->isMonday() ? $nextJan6 : $nextJan6->next(CarbonImmutable::MONDAY);
    }

    /**
     * The ~10 days prior to the Columbus Day long weekend (Oct 12, Emiliani -> Monday).
     * Starts the Friday before the preceding week and ends the Sunday before the holiday Monday.
     *
     * @param  list<ResolvedHoliday>  $resolvedHolidays
     */
    private function resolveOctoberRecess(SeasonBlockData $block, array $resolvedHolidays): ?SeasonBlockRange
    {
        $columbusDay = null;
        foreach ($resolvedHolidays as $holiday) {
            if ($holiday->name === 'columbus_day') {
                $columbusDay = $holiday;
                break;
            }
        }

        if ($columbusDay === null) {
            return null;
        }

        $observedMonday = $columbusDay->observedDate;
        $start = $observedMonday->subDays(10); // Friday before the preceding week
        $end = $observedMonday->subDay();

        return new SeasonBlockRange(
            blockId: $block->id,
            name: $block->name,
            start: $start,
            end: $end,
            priority: $block->priority,
        );
    }

    /**
     * Fixed date range defined in the block data.
     */
    private function resolveFixedRange(SeasonBlockData $block, int $year): ?SeasonBlockRange
    {
        if ($block->fixedStartMonth === null || $block->fixedStartDay === null
            || $block->fixedEndMonth === null || $block->fixedEndDay === null) {
            return null;
        }

        $start = CarbonImmutable::createStrict($year, $block->fixedStartMonth, $block->fixedStartDay);
        $end = CarbonImmutable::createStrict($year, $block->fixedEndMonth, $block->fixedEndDay);

        return new SeasonBlockRange(
            blockId: $block->id,
            name: $block->name,
            start: $start,
            end: $end,
            priority: $block->priority,
        );
    }
}
