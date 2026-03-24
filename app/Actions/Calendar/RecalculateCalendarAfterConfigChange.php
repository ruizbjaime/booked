<?php

namespace App\Actions\Calendar;

use Carbon\CarbonImmutable;

class RecalculateCalendarAfterConfigChange
{
    public function __construct(
        private readonly GenerateCalendarDays $generateCalendarDays,
    ) {}

    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $from = $now->startOfYear();
        $to = $now->addYear()->endOfYear();

        return $this->generateCalendarDays->handle($from, $to);
    }
}
