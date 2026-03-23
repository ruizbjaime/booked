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

    public static function isHexColor(string $color): bool
    {
        return (bool) preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $color);
    }

    /**
     * Resolve Tailwind classes for a custom hex-color badge matching flux:badge sizing.
     */
    public static function hexBadgeClasses(string $size = 'sm'): string
    {
        $sizeClasses = match ($size) {
            'lg' => 'text-sm py-1.5',
            'sm' => 'text-xs py-1',
            default => 'text-sm py-1',
        };

        return "inline-flex items-center font-medium whitespace-nowrap rounded-md px-2 text-white {$sizeClasses}";
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
