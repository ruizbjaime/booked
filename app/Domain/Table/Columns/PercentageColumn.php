<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;

class PercentageColumn extends Column
{
    protected string $align = 'end';

    protected int $decimalPlaces = 0;

    protected int|float $multiplierValue = 1;

    protected string $suffixText = '%';

    public function type(): string
    {
        return 'percentage';
    }

    /**
     * @return ($decimals is null ? int : static)
     */
    public function decimals(?int $decimals = null): static|int
    {
        if ($decimals === null) {
            return $this->decimalPlaces;
        }

        $this->decimalPlaces = $decimals;

        return $this;
    }

    /**
     * @return ($suffix is null ? string : static)
     */
    public function suffix(?string $suffix = null): static|string
    {
        if ($suffix === null) {
            return $this->suffixText;
        }

        $this->suffixText = $suffix;

        return $this;
    }

    /**
     * @return ($multiplier is null ? int|float : static)
     */
    public function multiplier(int|float|null $multiplier = null): static|int|float
    {
        if ($multiplier === null) {
            return $this->multiplierValue;
        }

        $this->multiplierValue = $multiplier;

        return $this;
    }

    public function formatPercentage(int|float|null $value): string
    {
        if ($value === null) {
            return '';
        }

        return number_format($value * $this->multiplierValue, $this->decimalPlaces).$this->suffixText;
    }
}
