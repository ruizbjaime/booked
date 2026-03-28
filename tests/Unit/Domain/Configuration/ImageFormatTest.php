<?php

use App\Domain\Configuration\Enums\ImageFormat;

it('returns the expected extension for each image format', function (ImageFormat $format, string $extension) {
    expect($format->extension())->toBe($extension);
})->with([
    [ImageFormat::Webp, 'webp'],
    [ImageFormat::Avif, 'avif'],
    [ImageFormat::Jpeg, 'jpeg'],
    [ImageFormat::Png, 'png'],
]);

it('returns the expected mime type for each image format', function (ImageFormat $format, string $mimeType) {
    expect($format->mimeType())->toBe($mimeType);
})->with([
    [ImageFormat::Webp, 'image/webp'],
    [ImageFormat::Avif, 'image/avif'],
    [ImageFormat::Jpeg, 'image/jpeg'],
    [ImageFormat::Png, 'image/png'],
]);
