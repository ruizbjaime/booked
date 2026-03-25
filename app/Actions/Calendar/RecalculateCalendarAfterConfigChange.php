<?php

namespace App\Actions\Calendar;

use App\Models\CalendarDay;
use Carbon\CarbonImmutable;

class RecalculateCalendarAfterConfigChange
{
    public function __construct(
        private readonly GenerateCalendarDays $generateCalendarDays,
    ) {}

    public function handle(): int
    {
        $now = CarbonImmutable::now();
        /** @var object{min_year:int|string|null, max_year:int|string|null}|null $generatedRange */
        $generatedRange = CalendarDay::query()
            ->toBase()
            ->selectRaw('MIN(year) as min_year, MAX(year) as max_year')
            ->first();

        $minGeneratedYear = is_numeric($generatedRange?->min_year) ? (int) $generatedRange->min_year : null;
        $maxGeneratedYear = is_numeric($generatedRange?->max_year) ? (int) $generatedRange->max_year : null;

        $fromYear = min($minGeneratedYear ?? $now->year, $now->year);
        $toYear = max($maxGeneratedYear ?? $now->addYear()->year, $now->addYear()->year);

        $from = CarbonImmutable::createStrict($fromYear, 1, 1);
        $to = CarbonImmutable::createStrict($toYear, 12, 31);

        return $this->generateCalendarDays->handle($from, $to);
    }
}
