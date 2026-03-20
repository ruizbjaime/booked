<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\CardZone;
use App\Domain\Table\Column;
use Carbon\CarbonInterface;

class DateColumn extends Column
{
    protected CardZone $cardZoneValue = CardZone::Footer;

    protected string $displayFormat = 'll';

    protected string $tooltipFormatValue = 'llll';

    public function type(): string
    {
        return 'date';
    }

    /**
     * @return ($format is null ? string : static)
     */
    public function format(?string $format = null): static|string
    {
        if ($format === null) {
            return $this->displayFormat;
        }

        $this->displayFormat = $format;

        return $this;
    }

    /**
     * @return ($format is null ? string : static)
     */
    public function tooltipFormat(?string $format = null): static|string
    {
        if ($format === null) {
            return $this->tooltipFormatValue;
        }

        $this->tooltipFormatValue = $format;

        return $this;
    }

    public function formatDisplay(CarbonInterface $date): string
    {
        return $this->localizedIsoFormat($date, $this->displayFormat);
    }

    public function formatTooltip(CarbonInterface $date): string
    {
        return $this->localizedIsoFormat($date, $this->tooltipFormatValue);
    }

    private function localizedIsoFormat(CarbonInterface $date, string $format): string
    {
        $localized = $date->copy();
        $localized->locale(app()->getLocale());

        return $localized->isoFormat($format);
    }
}
