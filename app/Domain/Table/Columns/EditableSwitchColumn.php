<?php

namespace App\Domain\Table\Columns;

class EditableSwitchColumn extends EditableColumn
{
    protected ?string $headerClassValue = 'w-20';

    public function type(): string
    {
        return 'editable-switch';
    }
}
