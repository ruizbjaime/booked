<?php

namespace App\Domain\Table\Columns;

class EditableNumberColumn extends EditableColumn
{
    protected ?int $minValue = null;

    protected ?int $maxValue = null;

    protected ?string $stepValue = null;

    protected string $inputClassValue = '';

    public function type(): string
    {
        return 'editable-number';
    }

    /**
     * @return ($value is null ? int|null : static)
     */
    public function min(?int $value = null): static|int|null
    {
        if (func_num_args() === 0) {
            return $this->minValue;
        }

        $this->minValue = $value;

        return $this;
    }

    /**
     * @return ($value is null ? int|null : static)
     */
    public function max(?int $value = null): static|int|null
    {
        if (func_num_args() === 0) {
            return $this->maxValue;
        }

        $this->maxValue = $value;

        return $this;
    }

    /**
     * @return ($value is null ? string|null : static)
     */
    public function step(?string $value = null): static|string|null
    {
        if (func_num_args() === 0) {
            return $this->stepValue;
        }

        $this->stepValue = $value;

        return $this;
    }

    /**
     * @return ($class is null ? string : static)
     */
    public function inputClass(?string $class = null): static|string
    {
        if ($class === null) {
            return $this->inputClassValue;
        }

        $this->inputClassValue = $class;

        return $this;
    }
}
