<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;
use Closure;
use Illuminate\Database\Eloquent\Model;

class BadgeColumn extends Column
{
    protected Closure|string $colorValue = 'zinc';

    protected Closure|string $iconValue = '';

    public function type(): string
    {
        return 'badge';
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

            return is_string($result) ? $result : 'zinc';
        }

        return $this->colorValue;
    }

    public function icon(Closure|string $icon): static
    {
        $this->iconValue = $icon;

        return $this;
    }

    public function resolveIcon(Model $record): string
    {
        if ($this->iconValue instanceof Closure) {
            $result = ($this->iconValue)($record);

            return is_string($result) ? $result : '';
        }

        return $this->iconValue;
    }
}
