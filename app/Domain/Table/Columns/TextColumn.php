<?php

namespace App\Domain\Table\Columns;

use App\Domain\Table\Column;

class TextColumn extends Column
{
    public function type(): string
    {
        return 'text';
    }
}
