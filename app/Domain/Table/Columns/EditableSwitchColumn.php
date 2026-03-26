<?php

namespace App\Domain\Table\Columns;

use Closure;
use Illuminate\Database\Eloquent\Model;

class EditableSwitchColumn extends EditableColumn
{
    protected ?string $headerClassValue = 'w-20';

    protected Closure|bool $disabledCondition = false;

    protected string $idPrefixValue = '';

    public function type(): string
    {
        return 'editable-switch';
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
