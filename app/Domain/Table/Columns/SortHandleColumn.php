<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\CardZone;
use App\Domain\Table\Column;

class SortHandleColumn extends Column
{
    protected ?string $headerClassValue = 'w-8';

    protected CardZone $cardZoneValue = CardZone::Hidden;

    public function type(): string
    {
        return 'sort-handle';
    }
}
