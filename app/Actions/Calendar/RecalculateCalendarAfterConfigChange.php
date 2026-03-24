<?php

namespace App\Actions\Calendar;

use Carbon\CarbonImmutable;

class RecalculateCalendarAfterConfigChange
{
    public function handle(): int
    {
        $now = CarbonImmutable::now();
        $from = $now->startOfYear();
        $to = $now->addYear()->endOfYear();

        return app(GenerateCalendarDays::class)->handle($from, $to);
    }
}
