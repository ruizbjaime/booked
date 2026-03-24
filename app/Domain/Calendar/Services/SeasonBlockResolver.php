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
     * @param  list<ResolvedHoliday>  $nextYearHolidays  Holidays for year+1 (needed for year_end block)
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
                SeasonStrategy::HolyWeek => $this->resolveHolyWeek($block, $easter),
                SeasonStrategy::YearEnd => $this->resolveYearEnd($block, $year, $nextYearHolidays),
                SeasonStrategy::OctoberRecess => $this->resolveOctoberRecess($block, $resolvedHolidays),
                SeasonStrategy::ForeignTourist => $this->resolveForeignTourist($block, $year),
                SeasonStrategy::FixedRange => $this->resolveFixedRange($block, $year),
            };

            if ($range !== null) {
                $ranges[] = $range;
            }
        }

        return $ranges;
    }

    /**
     * Palm Sunday through Easter Sunday.
     */
    private function resolveHolyWeek(SeasonBlockData $block, CarbonImmutable $easter): SeasonBlockRange
    {
        $palmSunday = $easter->subDays(7);

        return new SeasonBlockRange(
            blockId: $block->id,
            name: $block->name,
            start: $palmSunday,
            end: $easter,
            priority: $block->priority,
        );
    }

    /**
     * Dec 15 through the end of the Epiphany bridge weekend in next year.
     * The Epiphany (Jan 6) is Emiliani, so it moves to Monday.
     * The block ends on that Monday (the observed Epiphany).
     *
     * @param  list<ResolvedHoliday>  $nextYearHolidays
     */
    private function resolveYearEnd(SeasonBlockData $block, int $year, array $nextYearHolidays): SeasonBlockRange
    {
        $start = CarbonImmutable::createStrict($year, 12, 15);

        $epiphanyEnd = null;
        foreach ($nextYearHolidays as $holiday) {
            if ($holiday->name === 'epiphany') {
                $epiphanyEnd = $holiday->observedDate;
                break;
            }
        }

        if ($epiphanyEnd === null) {
            $nextJan6 = CarbonImmutable::createStrict($year + 1, 1, 6);
            $epiphanyEnd = $nextJan6->isMonday() ? $nextJan6 : $nextJan6->next(CarbonImmutable::MONDAY);
        }

        return new SeasonBlockRange(
            blockId: $block->id,
            name: $block->name,
            start: $start,
            end: $epiphanyEnd,
            priority: $block->priority,
        );
    }

    /**
     * The week surrounding the Columbus Day (Oct 12, Emiliani -> Monday).
     * Saturday before through the following Sunday.
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
        $start = $observedMonday->previous(CarbonImmutable::SATURDAY);
        $end = $observedMonday->next(CarbonImmutable::SUNDAY);

        return new SeasonBlockRange(
            blockId: $block->id,
            name: $block->name,
            start: $start,
            end: $end,
            priority: $block->priority,
        );
    }

    /**
     * Foreign tourist high season: Jan 15 through end of February.
     */
    private function resolveForeignTourist(SeasonBlockData $block, int $year): SeasonBlockRange
    {
        $start = CarbonImmutable::createStrict($year, 1, 15);
        $endOfFeb = CarbonImmutable::createStrict($year, 2, 1)->endOfMonth()->startOfDay();

        return new SeasonBlockRange(
            blockId: $block->id,
            name: $block->name,
            start: $start,
            end: $endOfFeb,
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
