<?php

use App\Domain\Table\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Model;

it('uses the badge column type', function () {
    expect(BadgeColumn::make('status')->type())->toBe('badge');
});

it('resolves static and callback badge colors safely', function () {
    $record = Mockery::mock(Model::class);

    $staticColumn = BadgeColumn::make('status')->color('emerald');
    $callbackColumn = BadgeColumn::make('status')->color(fn () => 'amber');
    $fallbackColumn = BadgeColumn::make('status')->color(fn () => ['invalid']);

    expect($staticColumn->resolveColor($record))->toBe('emerald')
        ->and($callbackColumn->resolveColor($record))->toBe('amber')
        ->and($fallbackColumn->resolveColor($record))->toBe('zinc');
});

it('returns hex badge classes for each supported size', function (string $size, string $expectedClass) {
    expect(BadgeColumn::hexBadgeClasses($size))
        ->toContain('inline-flex items-center font-medium whitespace-nowrap rounded-md px-2 text-white')
        ->toContain($expectedClass);
})->with([
    'small' => ['sm', 'text-xs py-1'],
    'large' => ['lg', 'text-sm py-1.5'],
    'default' => ['md', 'text-sm py-1'],
]);

it('resolves static and callback badge icons safely', function () {
    $record = Mockery::mock(Model::class);

    $staticColumn = BadgeColumn::make('status')->icon('heroicon-o-check');
    $callbackColumn = BadgeColumn::make('status')->icon(fn () => 'heroicon-o-clock');
    $fallbackColumn = BadgeColumn::make('status')->icon(fn () => ['invalid']);

    expect($staticColumn->resolveIcon($record))->toBe('heroicon-o-check')
        ->and($callbackColumn->resolveIcon($record))->toBe('heroicon-o-clock')
        ->and($fallbackColumn->resolveIcon($record))->toBe('');
});

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
