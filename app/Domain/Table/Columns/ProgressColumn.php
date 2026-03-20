<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;
use Closure;
use Illuminate\Database\Eloquent\Model;

class ProgressColumn extends Column
{
    protected int|float $maxValue = 100;

    protected Closure|string $colorValue = 'blue';

    protected bool $showLabelEnabled = false;

    public function type(): string
    {
        return 'progress';
    }

    /**
     * @return ($max is null ? int|float : static)
     */
    public function max(int|float|null $max = null): static|int|float
    {
        if ($max === null) {
            return $this->maxValue;
        }

        $this->maxValue = $max;

        return $this;
    }

    public function color(Closure|string $color): static
    {
        $this->colorValue = $color;

        return $this;
    }

    public function resolveColor(Model $record): string
    {
        if ($this->colorValue instanceof Closure) {
            $result = ($this->colorValue)($record);

            return is_string($result) ? $result : 'blue';
        }

        return $this->colorValue;
    }

    public function showLabel(bool $show = true): static
    {
        $this->showLabelEnabled = $show;

        return $this;
    }

    public function shouldShowLabel(): bool
    {
        return $this->showLabelEnabled;
    }
}
