<?php

namespace App\Domain\Table;

enum ActionItemType: string
{
    case Link = 'link';
    case Button = 'button';
    case Separator = 'separator';
}
