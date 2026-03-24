<?php

namespace App\Domain\Table\Columns;

class EditableTextColumn extends EditableColumn
{
    public function type(): string
    {
        return 'editable-text';
    }
}
