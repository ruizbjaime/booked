<?php

namespace App\Actions\Calendar;

use App\Models\CalendarDay;
use Carbon\CarbonImmutable;
use Closure;

class GenerateCalendarDays
{
    public function __construct(
        private readonly AnalyzeCalendarRange $analyzeCalendarRange = new AnalyzeCalendarRange,
        private readonly ResolveCalendarFreshnessTimestamp $freshnessTimestamp = new ResolveCalendarFreshnessTimestamp,
    ) {}

    /**
     * Generate calendar days for a date range.
     *
     * @param  Closure(int $processed, int $total): void|null  $onProgress
     */
    public function handle(CarbonImmutable $from, CarbonImmutable $to, ?Closure $onProgress = null): int
    {
        $analysis = $this->analyzeCalendarRange->handle($from, $to);
        $timestamp = $this->freshnessTimestamp->forCalendarGeneration();
        $total = count($analysis);
        $processed = 0;
        $batch = [];

        foreach ($analysis as $day) {
            $batch[] = [
                'date' => $day->date->toDateString(),
                'year' => $day->date->year,
                'month' => $day->date->month,
                'day_of_week' => $day->dayOfWeek,
                'day_of_week_name' => $day->dayOfWeekName,
                'is_holiday' => $day->isHoliday,
                'holiday_definition_id' => $day->holidayDefinitionId,
                'holiday_original_date' => $day->holidayOriginalDate?->toDateString(),
                'holiday_observed_date' => $day->holidayObservedDate?->toDateString(),
                'holiday_group' => $day->holidayGroup,
                'holiday_impact' => $day->holidayImpact,
                'is_bridge_day' => $day->isBridgeDay,
                'season_block_id' => $day->seasonBlockId,
                'season_block_name' => $day->seasonBlockName,
                'pricing_category_id' => $day->pricingCategoryId,
                'pricing_category_level' => $day->pricingCategoryLevel,
                'is_quincena_adjacent' => $day->isQuincenaAdjacent,
                'notes' => $day->notes,
                'updated_at' => $timestamp,
                'created_at' => $timestamp,
            ];

            if (count($batch) >= 100) {
                $this->upsertBatch($batch);
                $batch = [];
            }

            $processed++;

            if ($onProgress !== null && $processed % 50 === 0) {
                $onProgress($processed, $total);
            }
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
