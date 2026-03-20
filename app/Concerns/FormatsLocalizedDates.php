<?php

namespace App\Concerns;

use Carbon\CarbonInterface;

trait FormatsLocalizedDates
{
    public function formatDate(?CarbonInterface $date): string
    {
        return $this->formatLocalizedDate($date, 'll');
    }

    public function formatDateTooltip(?CarbonInterface $date): string
    {
        return $this->formatLocalizedDate($date, 'llll');
    }

    private function formatLocalizedDate(?CarbonInterface $date, string $format): string
    {
        if ($date === null) {
            return '';
        }

        $localized = $date->copy();
        $localized->locale(app()->getLocale());

        return $localized->isoFormat($format);
    }
}
