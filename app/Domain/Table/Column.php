<?php

namespace App\Domain\Table;

use Illuminate\Database\Eloquent\Model;

abstract class Column
{
    protected string $label = '';

    protected bool $sortable = false;

    protected string $defaultSortDir = 'asc';

    protected string $align = 'start';

    protected ?string $headerClassValue = null;

    protected ?string $cellClassValue = null;

    protected CardZone $cardZoneValue = CardZone::Body;

    final public function __construct(
        protected string $name,
    ) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    abstract public function type(): string;

    public function resolveValue(Model $record): mixed
    {
        return data_get($record, $this->name);
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

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    /**
     * @return ($direction is null ? string : static)
     */
    public function defaultSortDirection(?string $direction = null): static|string
    {
        if ($direction === null) {
            return $this->defaultSortDir;
        }

        $this->defaultSortDir = $direction;

        return $this;
    }

    /**
     * @return ($align is null ? string : static)
     */
    public function align(?string $align = null): static|string
    {
        if ($align === null) {
            return $this->align;
        }

        $this->align = $align;

        return $this;
    }

    /**
     * Get or set the header CSS class.
     *
     * Uses func_num_args() because null is a valid setter value (clears the class).
     */
    public function headerClass(?string $class = null): static|string|null
    {
        if (func_num_args() === 0) {
            return $this->headerClassValue;
        }

        $this->headerClassValue = $class;

        return $this;
    }

    /**
     * Get or set the cell CSS class.
     *
     * Uses func_num_args() because null is a valid setter value (clears the class).
     */
    public function cellClass(?string $class = null): static|string|null
    {
        if (func_num_args() === 0) {
            return $this->cellClassValue;
        }

        $this->cellClassValue = $class;

        return $this;
    }

    /**
     * @return ($zone is null ? CardZone : static)
     */
    public function cardZone(?CardZone $zone = null): static|CardZone
    {
        if ($zone === null) {
            return $this->cardZoneValue;
        }

        $this->cardZoneValue = $zone;

        return $this;
    }
}
