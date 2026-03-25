<?php

namespace App\Domain\Table\Columns;

class EditableSelectColumn extends EditableColumn
{
    /** @var array<int|string, string> */
    protected array $optionsValue = [];

    public function type(): string
    {
        return 'editable-select';
    }

    /**
     * @param  array<int|string, string>|null  $options
     * @return ($options is null ? array<int|string, string> : static)
     */
    public function options(?array $options = null): static|array
    {
        if (func_num_args() === 0) {
            return $this->optionsValue;
        }

        $this->optionsValue = $options ?? [];

        return $this;
    }
}
