<?php

namespace App\Domain\Calendar\ValueObjects;

final readonly class BridgeDayInfo
{
    public function __construct(
        public int $definitionId,
        public int $impact,
    ) {}
}
