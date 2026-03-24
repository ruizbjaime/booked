<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;

abstract class EditableColumn extends Column
{
    protected string $wireChangeMethod = '';

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
}
