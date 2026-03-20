<?php

namespace App\Domain\Table;

enum CardZone: string
{
    case Header = 'header';
    case Body = 'body';
    case Footer = 'footer';
    case Hidden = 'hidden';
}
