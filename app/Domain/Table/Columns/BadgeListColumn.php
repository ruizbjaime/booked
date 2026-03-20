<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;
use Closure;

class BadgeListColumn extends Column
{
    protected ?Closure $itemLabelCallback = null;

    protected ?Closure $itemColorCallback = null;

    protected string $emptyLabelText = '';

    protected string $emptyColorValue = 'zinc';

    public function type(): string
    {
        return 'badge-list';
    }

    public function itemLabel(Closure $callback): static
    {
        $this->itemLabelCallback = $callback;

        return $this;
    }

    public function itemColor(Closure $callback): static
    {
        $this->itemColorCallback = $callback;

        return $this;
    }

    /**
     * @return ($label is null ? string : static)
     */
    public function emptyLabel(?string $label = null): static|string
    {
        if ($label === null) {
            return $this->emptyLabelText;
        }

        $this->emptyLabelText = $label;

        return $this;
    }

    /**
     * @return ($color is null ? string : static)
     */
    public function emptyColor(?string $color = null): static|string
    {
        if ($color === null) {
            return $this->emptyColorValue;
        }

        $this->emptyColorValue = $color;

        return $this;
    }

    public function resolveItemLabel(mixed $item): string
    {
        if ($this->itemLabelCallback !== null) {
            $result = ($this->itemLabelCallback)($item);

            return is_string($result) ? $result : '';
        }

        return is_string($item) ? $item : '';
    }

    public function resolveItemColor(mixed $item): string
    {
        if ($this->itemColorCallback !== null) {
            $result = ($this->itemColorCallback)($item);

            return is_string($result) ? $result : 'zinc';
        }

        return 'zinc';
    }
}
