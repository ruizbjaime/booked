<?php

namespace App\Domain\Table\Columns;

class MailtoColumn extends LinkColumn
{
    public function type(): string
    {
        return 'mailto';
    }
}
