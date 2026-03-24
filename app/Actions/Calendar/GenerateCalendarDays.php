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
use App\Domain\Calendar\ValueObjects\ResolvedHoliday;
use App\Domain\Calendar\ValueObjects\SeasonBlockRange;
use App\Models\CalendarDay;
use App\Models\HolidayDefinition;
use App\Models\PricingRule;
use App\Models\SeasonBlock;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class GenerateCalendarDays
{
    private HolidayResolver $holidayResolver;

    private SeasonBlockResolver $seasonBlockResolver;

    private BridgeDayDetector $bridgeDayDetector;

    private PricingCategoryMatcher $pricingMatcher;

    public function __construct()
    {
        $this->holidayResolver = new HolidayResolver;
        $this->seasonBlockResolver = new SeasonBlockResolver;
        $this->bridgeDayDetector = new BridgeDayDetector;
        $this->pricingMatcher = new PricingCategoryMatcher;
    }

    /**
     * Generate calendar days for a date range.
     *
     * @param  Closure(int $processed, int $total): void|null  $onProgress
     */
    public function handle(CarbonImmutable $from, CarbonImmutable $to, ?Closure $onProgress = null): int
    {
        $definitions = $this->loadHolidayDefinitions();
        $blockDtos = $this->loadSeasonBlocks();
        $rules = $this->loadPricingRules();

        $yearsNeeded = range($from->year, $to->year + 1);
        $yearData = $this->resolveAllYears($yearsNeeded, $definitions, $blockDtos);

        $total = (int) $from->diffInDays($to) + 1;
        $processed = 0;
        $batch = [];
        $date = $from;

        while ($date->lte($to)) {
            $year = $date->year;
            $dateStr = $date->toDateString();

            $holidays = $yearData[$year]['holidays'] ?? [];
            $seasonBlocks = $yearData[$year]['seasonBlocks'] ?? [];
            $bridgeDays = $yearData[$year]['bridgeDays'] ?? [];

            $holidayMatch = $this->findHoliday($holidays, $dateStr);
            $isBridgeDay = isset($bridgeDays[$dateStr]);
            $seasonBlock = $this->findSeasonBlock($seasonBlocks, $date);

            $pricing = $this->pricingMatcher->match(
                $date,
                $rules,
                isHoliday: $holidayMatch !== null,
                isBridgeDay: $isBridgeDay,
                seasonBlock: $seasonBlock,
            );

            $batch[] = [
                'date' => $dateStr,
                'year' => $year,
                'month' => $date->month,
                'day_of_week' => $date->dayOfWeek,
                'day_of_week_name' => strtolower($date->format('l')),
                'is_holiday' => $holidayMatch !== null,
                'holiday_definition_id' => $holidayMatch?->definitionId,
                'holiday_original_date' => $holidayMatch?->originalDate->toDateString(),
                'holiday_observed_date' => $holidayMatch?->observedDate->toDateString(),
                'holiday_group' => $holidayMatch?->group->value,
                'holiday_impact' => $holidayMatch?->impact,
                'is_bridge_day' => $isBridgeDay,
                'season_block_id' => $seasonBlock?->blockId,
                'season_block_name' => $seasonBlock?->name,
                'pricing_category_id' => $pricing['pricingCategoryId'] ?? null,
                'pricing_category_level' => $pricing['pricingCategoryLevel'] ?? null,
                'is_quincena_adjacent' => QuincenaCalculator::isQuincenaAdjacent($date),
                'notes' => $this->buildNotes($holidayMatch, $isBridgeDay),
                'updated_at' => now(),
                'created_at' => now(),
            ];

            if (count($batch) >= 100) {
                $this->upsertBatch($batch);
                $batch = [];
            }

            $processed++;
            if ($onProgress !== null && $processed % 50 === 0) {
                $onProgress($processed, $total);
            }

            $date = $date->addDay();
        }

        if ($batch !== []) {
            $this->upsertBatch($batch);
        }

        if ($onProgress !== null) {
            $onProgress($processed, $total);
        }

        return $processed;
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
                ->map(fn (HolidayDefinition $h) => new HolidayDefinitionData(
                    id: $h->id,
                    name: $h->name,
                    group: $h->group,
                    month: $h->month,
                    day: $h->day,
                    easterOffset: $h->easter_offset,
                    movesToMonday: $h->moves_to_monday,
                    baseImpactWeights: $h->base_impact_weights,
                    specialOverrides: $h->special_overrides,
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
                ->map(fn (SeasonBlock $s) => new SeasonBlockData(
                    id: $s->id,
                    name: $s->name,
                    calculationStrategy: $s->calculation_strategy,
                    fixedStartMonth: $s->fixed_start_month,
                    fixedStartDay: $s->fixed_start_day,
                    fixedEndMonth: $s->fixed_end_month,
                    fixedEndDay: $s->fixed_end_day,
                    priority: $s->priority,
                ))
                ->all(),
        );
    }

    /**
     * @return list<PricingRuleData>
     */
    private function loadPricingRules(): array
    {
        return array_values(
            PricingRule::query()
                ->where('is_active', true)
                ->whereHas('pricingCategory', fn (Builder $q) => $q->where('is_active', true))
                ->with('pricingCategory:id,level')
                ->orderBy('priority')
                ->get()
                ->map(fn (PricingRule $r) => new PricingRuleData(
                    id: $r->id,
                    name: $r->name,
                    pricingCategoryId: $r->pricing_category_id,
                    pricingCategoryLevel: $r->pricingCategory->level ?? 0,
                    ruleType: $r->rule_type,
                    conditions: $r->conditions,
                    priority: $r->priority,
                ))
                ->all(),
        );
    }

    /**
     * Pre-resolve holidays, season blocks, and bridge days for all needed years.
     *
     * @param  list<int>  $years
     * @param  list<HolidayDefinitionData>  $definitions
     * @param  list<SeasonBlockData>  $blockDtos
     * @return array<int, array{holidays: list<ResolvedHoliday>, seasonBlocks: list<SeasonBlockRange>, bridgeDays: array<string, int>}>
     */
    private function resolveAllYears(array $years, array $definitions, array $blockDtos): array
    {
        $yearData = [];
        /** @var array<int, CarbonImmutable> $easters */
        $easters = [];

        // First pass: resolve holidays for all years
        foreach ($years as $year) {
            $easter = EasterCalculator::forYear($year);
            $easters[$year] = $easter;
            $holidays = $this->holidayResolver->resolve($definitions, $year, $easter);
            $yearData[$year] = [
                'holidays' => $holidays,
                'seasonBlocks' => [],
                'bridgeDays' => [],
            ];
        }

        // Second pass: resolve season blocks (needs next year's holidays for year_end)
        foreach ($years as $year) {
            if (! isset($yearData[$year])) {
                continue;
            }

            $nextYearHolidays = isset($yearData[$year + 1]) ? $yearData[$year + 1]['holidays'] : [];
            $seasonBlocks = $this->seasonBlockResolver->resolve(
                $blockDtos,
                $year,
                $easters[$year],
                $yearData[$year]['holidays'],
                $nextYearHolidays,
            );
            $yearData[$year]['seasonBlocks'] = $seasonBlocks;
            $yearData[$year]['bridgeDays'] = $this->bridgeDayDetector->detect($yearData[$year]['holidays']);
        }

        return $yearData;
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
        foreach ($seasonBlocks as $block) {
            if ($block->contains($date)) {
                return $block;
            }
        }

        return null;
    }

    private function buildNotes(?ResolvedHoliday $holiday, bool $isBridgeDay): ?string
    {
        $parts = [];

        if ($holiday !== null && $holiday->wasMoved) {
            $parts[] = "Moved from {$holiday->originalDate->toDateString()}";
        }

        if ($isBridgeDay) {
            $parts[] = 'Bridge day';
        }

        return $parts !== [] ? implode('. ', $parts) : null;
    }

    /**
     * @param  list<array<string, mixed>>  $batch
     */
    private function upsertBatch(array $batch): void
    {
        CalendarDay::upsert(
            $batch,
            ['date'],
            [
                'year', 'month', 'day_of_week', 'day_of_week_name',
                'is_holiday', 'holiday_definition_id', 'holiday_original_date', 'holiday_observed_date',
                'holiday_group', 'holiday_impact', 'is_bridge_day',
                'season_block_id', 'season_block_name',
                'pricing_category_id', 'pricing_category_level',
                'is_quincena_adjacent', 'notes', 'updated_at',
            ],
        );
    }
}
