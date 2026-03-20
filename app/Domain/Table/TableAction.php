<?php

namespace App\Domain\Table;

class TableAction
{
    protected string $label = '';

    protected string $icon = '';

    protected ?string $wireClick = null;

    protected string $variant = 'primary';

    protected bool $responsive = false;

    final public function __construct(
        protected string $name,
    ) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return ($label is null ? string : static)
     */
    public function label(?string $label = null): static|string
    {
        if ($label === null) {
            return $this->label;
        }

        $this->label = $label;

        return $this;
    }

    /**
     * @return ($icon is null ? string : static)
     */
    public function icon(?string $icon = null): static|string
    {
        if ($icon === null) {
            return $this->icon;
        }

        $this->icon = $icon;

        return $this;
    }

    /**
     * Get or set the wire:click handler.
     *
     * Pass null to clear the handler; omit the argument (or pass false) to read the current value.
     *
     * @return ($wireClick is false ? string|null : static)
     */
    public function wireClick(string|false|null $wireClick = false): static|string|null
    {
        if ($wireClick === false) {
            return $this->wireClick;
        }

        $this->wireClick = $wireClick;

        return $this;
    }

    /**
     * @return ($variant is null ? string : static)
     */
    public function variant(?string $variant = null): static|string
    {
        if ($variant === null) {
            return $this->variant;
        }

        $this->variant = $variant;

        return $this;
    }

    public function responsive(bool $responsive = true): static
    {
        $this->responsive = $responsive;

        return $this;
    }

    public function isResponsive(): bool
    {
        return $this->responsive;
    }
}
