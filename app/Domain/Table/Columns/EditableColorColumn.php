<?php

namespace App\Domain\Table\Columns;

class EditableColorColumn extends EditableColumn
{
    public function type(): string
    {
        return 'editable-color';
    }
}
