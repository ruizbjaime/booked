<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;
use Closure;
use Illuminate\Database\Eloquent\Model;

class ToggleColumn extends Column
{
    protected ?string $headerClassValue = 'w-20';

    protected string $wireChangeMethod = '';

    protected Closure|bool $disabledCondition = false;

    protected string $idPrefixValue = '';

    public function type(): string
    {
        return 'toggle';
    }

    /**
     * @return ($method is null ? string : static)
     */
    public function wireChange(?string $method = null): static|string
    {
        if ($method === null) {
            return $this->wireChangeMethod;
        }

        $this->wireChangeMethod = $method;

        return $this;
    }

    public function disabled(Closure|bool $condition): static
    {
        $this->disabledCondition = $condition;

        return $this;
    }

    public function isDisabled(Model $record): bool
    {
        if ($this->disabledCondition instanceof Closure) {
            return (bool) ($this->disabledCondition)($record);
        }

        return $this->disabledCondition;
    }

    /**
     * @return ($prefix is null ? string : static)
     */
    public function idPrefix(?string $prefix = null): static|string
    {
        if ($prefix === null) {
            return $this->idPrefixValue;
        }

        $this->idPrefixValue = $prefix;

        return $this;
    }
}
