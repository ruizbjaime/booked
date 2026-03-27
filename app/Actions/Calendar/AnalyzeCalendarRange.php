<?php

namespace App\Actions\Calendar;

use App\Domain\Calendar\Data\HolidayDefinitionData;
use App\Domain\Calendar\Data\PricingRuleData;
use App\Domain\Calendar\Data\SeasonBlockData;
use App\Domain\Calendar\Services\BridgeDayDetector;
use App\Domain\Calendar\Services\EasterCalculator;
use App\Domain\Calendar\Services\HolidayResolver;
use App\Domain\Calendar\Services\PricingCategoryMatcher;
use App\Domain\Calendar\Services\QuincenaCalculator;
use App\Domain\Calendar\Services\SeasonBlockResolver;
use App\Domain\Calendar\ValueObjects\BridgeDayInfo;
use App\Domain\Calendar\ValueObjects\DayAnalysis;
use App\Domain\Calendar\ValueObjects\DayMatchContext;
use App\Domain\Calendar\ValueObjects\ResolvedHoliday;
use App\Domain\Calendar\ValueObjects\SeasonBlockRange;
use App\Models\HolidayDefinition;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class AnalyzeCalendarRange
{
    public function __construct(
        private readonly HolidayResolver $holidayResolver = new HolidayResolver,
        private readonly SeasonBlockResolver $seasonBlockResolver = new SeasonBlockResolver,
        private readonly BridgeDayDetector $bridgeDayDetector = new BridgeDayDetector,
        private readonly PricingCategoryMatcher $pricingMatcher = new PricingCategoryMatcher,
    ) {}

    /**
     * @param  list<PricingRuleData>|null  $pricingRules
     * @return list<DayAnalysis>
     */
    public function handle(CarbonImmutable $from, CarbonImmutable $to, ?array $pricingRules = null): array
    {
        $definitions = $this->loadHolidayDefinitions();
        $blockDtos = $this->loadSeasonBlocks();
        $rules = $pricingRules ?? $this->loadPricingRules();

        $yearsNeeded = range($from->year - 1, $to->year + 1);
        $yearData = $this->resolveAllYears($yearsNeeded, $definitions, $blockDtos);

        $analysis = [];
        $date = $from;

        while ($date->lte($to)) {
            $year = $date->year;
            $dateStr = $date->toDateString();

            $holidays = $yearData[$year]['holidays'] ?? [];
            $seasonBlocks = $this->seasonBlocksForDate($yearData, $date);
            $bridgeDays = $yearData[$year]['bridgeDays'] ?? [];
            $firstBridgeDays = $yearData[$year]['firstBridgeDays'] ?? [];
            $holidayEves = $yearData[$year]['holidayEves'] ?? [];

            $holidayMatch = $this->findHoliday($holidays, $dateStr);
            $isBridgeDay = isset($bridgeDays[$dateStr]);
            $isFirstBridgeDay = isset($firstBridgeDays[$dateStr]);
            $isHolidayEve = isset($holidayEves[$dateStr]);
            $seasonBlock = $this->findSeasonBlock($seasonBlocks, $date);

            $isCheckoutDay = $holidayMatch !== null
                && $date->dayOfWeek >= CarbonImmutable::MONDAY
                && $date->dayOfWeek <= CarbonImmutable::THURSDAY;

            $context = new DayMatchContext(
                isHoliday: $holidayMatch !== null,
                isBridgeDay: $isBridgeDay,
                isFirstBridgeDay: $isFirstBridgeDay,
                isCheckoutDay: $isCheckoutDay,
                isHolidayEve: $isHolidayEve,
                seasonBlock: $seasonBlock,
                holidayImpact: match (true) {
                    $isBridgeDay => $bridgeDays[$dateStr]->impact,
                    $isHolidayEve => $holidayEves[$dateStr],
                    default => $holidayMatch?->impact,
                },
            );

            $matchedRule = $this->pricingMatcher->matchRule($date, $rules, $context);

            $analysis[] = new DayAnalysis(
                date: $date,
                dayOfWeek: $date->dayOfWeek,
                dayOfWeekName: strtolower($date->format('l')),
                isHoliday: $holidayMatch !== null,
                holidayDefinitionId: $holidayMatch?->definitionId,
                holidayOriginalDate: $holidayMatch?->originalDate,
                holidayObservedDate: $holidayMatch?->observedDate,
                holidayGroup: $holidayMatch?->group->value,
                holidayImpact: $holidayMatch?->impact,
                isBridgeDay: $isBridgeDay,
                isFirstBridgeDay: $isFirstBridgeDay,
                seasonBlockId: $seasonBlock?->blockId,
                seasonBlockName: $seasonBlock?->name,
                pricingCategoryId: $matchedRule?->pricingCategoryId,
                pricingCategoryLevel: $matchedRule?->pricingCategoryLevel,
                matchedPricingRuleId: $matchedRule?->id,
                isQuincenaAdjacent: QuincenaCalculator::isQuincenaAdjacent($date),
                notes: $this->buildNotes($holidayMatch, $isBridgeDay, $isHolidayEve),
            );

            $date = $date->addDay();
        }

        return $analysis;
    }

    /**
     * @return list<PricingRuleData>
     */
    public function loadPricingRules(): array
    {
        return array_values(
            PricingRule::query()
                ->active()
                ->whereHas('pricingCategory', fn (Builder $query) => $query->where('is_active', true))
                ->with('pricingCategory:id,level')
                ->orderBy('priority')
                ->get()
                ->map(fn (PricingRule $rule) => new PricingRuleData(
                    id: $rule->id,
                    name: $rule->name,
                    pricingCategoryId: $rule->pricing_category_id,
                    pricingCategoryLevel: $rule->pricingCategory->level ?? 0,
                    ruleType: $rule->rule_type,
                    conditions: $rule->conditions,
                    priority: $rule->priority,
                ))
                ->all(),
        );
    }

    /**
     * @return list<HolidayDefinitionData>
     */
    private function loadHolidayDefinitions(): array
    {
        return array_values(
            HolidayDefinition::query()
                ->active()
                ->orderBy('sort_order')
                ->get()
                ->map(fn (HolidayDefinition $holiday) => new HolidayDefinitionData(
                    id: $holiday->id,
                    name: $holiday->name,
                    group: $holiday->group,
                    month: $holiday->month,
                    day: $holiday->day,
                    easterOffset: $holiday->easter_offset,
                    movesToMonday: $holiday->moves_to_monday,
                    baseImpactWeights: $holiday->base_impact_weights,
                    specialOverrides: $holiday->special_overrides,
                ))
                ->all(),
        );
    }

    /**
     * @return list<SeasonBlockData>
     */
    private function loadSeasonBlocks(): array
    {
        return array_values(
            SeasonBlock::query()
                ->active()
                ->orderBy('priority')
                ->get()
                ->map(fn (SeasonBlock $block) => new SeasonBlockData(
                    id: $block->id,
                    name: $block->name,
                    calculationStrategy: $block->calculation_strategy,
                    fixedStartMonth: $block->fixed_start_month,
                    fixedStartDay: $block->fixed_start_day,
                    fixedEndMonth: $block->fixed_end_month,
                    fixedEndDay: $block->fixed_end_day,
                    priority: $block->priority,
                ))
                ->all(),
        );
    }

    /**
     * @param  list<int>  $years
     * @param  list<HolidayDefinitionData>  $definitions
     * @param  list<SeasonBlockData>  $blockDtos
     * @return array<int, array{holidays: list<ResolvedHoliday>, seasonBlocks: list<SeasonBlockRange>, bridgeDays: array<string, BridgeDayInfo>, firstBridgeDays: array<string, true>}>
     */
    private function resolveAllYears(array $years, array $definitions, array $blockDtos): array
    {
        /** @var array<int, CarbonImmutable> $easters */
        $easters = [];
        /** @var array<int, list<ResolvedHoliday>> $holidaysByYear */
        $holidaysByYear = [];

        foreach ($years as $year) {
            $easter = EasterCalculator::forYear($year);
            $easters[$year] = $easter;
            $holidaysByYear[$year] = $this->holidayResolver->resolve($definitions, $year, $easter);
        }

        $yearData = [];

        foreach ($years as $year) {
            $holidays = $holidaysByYear[$year];
            $bridgeDays = $this->bridgeDayDetector->detect($holidays);

            $yearData[$year] = [
                'holidays' => $holidays,
                'seasonBlocks' => $this->seasonBlockResolver->resolve(
                    $blockDtos,
                    $year,
                    $easters[$year],
                    $holidays,
                    $holidaysByYear[$year + 1] ?? [],
                ),
                'bridgeDays' => $bridgeDays,
                'firstBridgeDays' => $this->findFirstBridgeDays($bridgeDays),
                'holidayEves' => $this->findHolidayEves($holidays),
            ];
        }

        return $yearData;
    }

    /**
     * @param  array<int, array{holidays: list<ResolvedHoliday>, seasonBlocks: list<SeasonBlockRange>, bridgeDays: array<string, BridgeDayInfo>, firstBridgeDays: array<string, true>}>  $yearData
     * @return list<SeasonBlockRange>
     */
    private function seasonBlocksForDate(array $yearData, CarbonImmutable $date): array
    {
        return array_merge(
            $yearData[$date->year - 1]['seasonBlocks'] ?? [],
            $yearData[$date->year]['seasonBlocks'] ?? [],
        );
    }

    /**
     * @param  list<ResolvedHoliday>  $holidays
     */
    private function findHoliday(array $holidays, string $dateStr): ?ResolvedHoliday
    {
        foreach ($holidays as $holiday) {
            if ($holiday->observedDate->toDateString() === $dateStr) {
                return $holiday;
            }
        }

        return null;
    }

    /**
     * @param  list<SeasonBlockRange>  $seasonBlocks
     */
    private function findSeasonBlock(array $seasonBlocks, CarbonImmutable $date): ?SeasonBlockRange
    {
        $matchedBlock = null;

        foreach ($seasonBlocks as $block) {
            if (! $block->contains($date)) {
                continue;
            }

            if ($matchedBlock === null || $block->priority < $matchedBlock->priority) {
                $matchedBlock = $block;
            }
        }

        return $matchedBlock;
    }

    /**
     * @param  array<string, BridgeDayInfo>  $bridgeDays
     * @return array<string, true>
     */
    private function findFirstBridgeDays(array $bridgeDays): array
    {
        $grouped = [];

        foreach (array_keys($bridgeDays) as $date) {
            $carbonDate = CarbonImmutable::parse($date);
            $key = $carbonDate->format('o-W');

            $grouped[$key] ??= [];
            $grouped[$key][] = $date;
        }

        $firstBridgeDays = [];

        foreach ($grouped as $dates) {
            sort($dates);
            $firstBridgeDays[$dates[0]] = true;
        }

        return $firstBridgeDays;
    }

    /**
     * Mid-week holidays (Tue–Thu) are checkout days; the previous day is the holiday eve (víspera).
     *
     * @param  list<ResolvedHoliday>  $holidays
     * @return array<string, int> Map of eve date string to holiday impact
     */
    private function findHolidayEves(array $holidays): array
    {
        $eves = [];

        foreach ($holidays as $holiday) {
            $dow = $holiday->observedDate->dayOfWeek;

            if ($dow >= CarbonImmutable::TUESDAY && $dow <= CarbonImmutable::THURSDAY) {
                $eveDate = $holiday->observedDate->subDay()->toDateString();
                $eves[$eveDate] = $holiday->impact;
            }
        }

        return $eves;
    }

    private function buildNotes(?ResolvedHoliday $holidayMatch, bool $isBridgeDay, bool $isHolidayEve): ?string
    {
        $notes = [];

        if ($holidayMatch !== null) {
            $notes[] = "Holiday: {$holidayMatch->name}";
        }

        if ($isBridgeDay) {
            $notes[] = 'Bridge day';
        }

        if ($isHolidayEve) {
            $notes[] = 'Holiday eve';
        }

        return $notes === [] ? null : implode(' • ', $notes);
    }
}
