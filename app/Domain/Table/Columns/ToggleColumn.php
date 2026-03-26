<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Concerns\HasSwitchBehavior;

class ToggleColumn extends EditableColumn
{
    use HasSwitchBehavior;

    protected ?string $headerClassValue = 'w-20';

    public function type(): string
    {
        return 'toggle';
    }
}
