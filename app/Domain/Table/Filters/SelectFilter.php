<?php

namespace App\Domain\Table\Filters;

use App\Domain\Table\Filter;
use Closure;

class SelectFilter extends Filter
{
    protected bool $multiple = false;

    /** @var array<string, string>|Closure(): array<string, string> */
    protected array|Closure $options = [];

    public function type(): string
    {
        return 'select';
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * @param  array<string, string>|Closure(): array<string, string>  $options
     */
    public function options(array|Closure $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function resolveOptions(): array
    {
        return $this->options instanceof Closure
            ? ($this->options)()
            : $this->options;
    }

    public function countActive(mixed $value): int
    {
        if ($this->multiple) {
            return is_array($value) ? count($value) : 0;
        }

        return filled($value) ? 1 : 0;
    }
}
