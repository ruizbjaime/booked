<?php

namespace App\Domain\Table;

abstract class Filter
{
    protected string $placeholder = '';

    final public function __construct(
        protected string $name,
    ) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    abstract public function type(): string;

    abstract public function countActive(mixed $value): int;

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return ($placeholder is null ? string : static)
     */
    public function placeholder(?string $placeholder = null): static|string
    {
        if ($placeholder === null) {
            return $this->placeholder;
        }

        $this->placeholder = $placeholder;

        return $this;
    }
}
