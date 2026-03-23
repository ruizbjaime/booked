<?php

use App\Domain\Table\Columns\BadgeColumn;

describe('isHexColor', function () {
    it('accepts valid 6-digit hex colors', function (string $color) {
        expect(BadgeColumn::isHexColor($color))->toBeTrue();
    })->with([
        '#000000',
        '#FFFFFF',
        '#ff5733',
        '#aaBBcc',
    ]);

    it('accepts valid 3-digit hex colors', function (string $color) {
        expect(BadgeColumn::isHexColor($color))->toBeTrue();
    })->with([
        '#000',
        '#FFF',
        '#abc',
        '#A1B',
    ]);

    it('rejects invalid hex colors', function (string $color) {
        expect(BadgeColumn::isHexColor($color))->toBeFalse();
    })->with([
        '#',
        '#G00000',
        '#12345',
        '#1234567',
        '#GGGGGG',
        'red',
        '',
        '#ff0000); background-image: url(evil)',
    ]);
});
