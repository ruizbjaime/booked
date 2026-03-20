<?php

namespace App\Domain\Configuration\Enums;

enum ImageFormat: string
{
    case Webp = 'webp';
    case Avif = 'avif';
    case Jpeg = 'jpeg';
    case Png = 'png';

    public function extension(): string
    {
        return $this->value;
    }

    public function mimeType(): string
    {
        return match ($this) {
            self::Webp => 'image/webp',
            self::Avif => 'image/avif',
            self::Jpeg => 'image/jpeg',
            self::Png => 'image/png',
        };
    }
}
