<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\CardZone;
use App\Domain\Table\Column;

class IdColumn extends Column
{
    protected ?string $headerClassValue = 'w-16';

    protected CardZone $cardZoneValue = CardZone::Footer;

    public function type(): string
    {
        return 'id';
    }
}
