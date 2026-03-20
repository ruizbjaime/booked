<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;

class BooleanColumn extends Column
{
    protected string $trueLabelText = '';

    protected string $falseLabelText = '';

    protected string $trueColorValue = 'green';

    protected string $falseColorValue = 'red';

    protected string $trueIconValue = '';

    protected string $falseIconValue = '';

    public function type(): string
    {
        return 'boolean';
    }

    /**
     * @return ($label is null ? string : static)
     */
    public function trueLabel(?string $label = null): static|string
    {
        if ($label === null) {
            return $this->trueLabelText;
        }

        $this->trueLabelText = $label;

        return $this;
    }

    /**
     * @return ($label is null ? string : static)
     */
    public function falseLabel(?string $label = null): static|string
    {
        if ($label === null) {
            return $this->falseLabelText;
        }

        $this->falseLabelText = $label;

        return $this;
    }

    /**
     * @return ($color is null ? string : static)
     */
    public function trueColor(?string $color = null): static|string
    {
        if ($color === null) {
            return $this->trueColorValue;
        }

        $this->trueColorValue = $color;

        return $this;
    }

    /**
     * @return ($color is null ? string : static)
     */
    public function falseColor(?string $color = null): static|string
    {
        if ($color === null) {
            return $this->falseColorValue;
        }

        $this->falseColorValue = $color;

        return $this;
    }

    /**
     * @return ($icon is null ? string : static)
     */
    public function trueIcon(?string $icon = null): static|string
    {
        if ($icon === null) {
            return $this->trueIconValue;
        }

        $this->trueIconValue = $icon;

        return $this;
    }

    /**
     * @return ($icon is null ? string : static)
     */
    public function falseIcon(?string $icon = null): static|string
    {
        if ($icon === null) {
            return $this->falseIconValue;
        }

        $this->falseIconValue = $icon;

        return $this;
    }
}
