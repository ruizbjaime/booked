<?php

use App\Domain\Table\Columns\BadgeListColumn;

it('uses the badge list column type', function () {
    expect(BadgeListColumn::make('roles')->type())->toBe('badge-list');
});

it('stores empty state label and color values', function () {
    $column = BadgeListColumn::make('roles')
        ->emptyLabel('No roles')
        ->emptyColor('slate');

    expect($column->emptyLabel())->toBe('No roles')
        ->and($column->emptyColor())->toBe('slate');
});

it('resolves item label and color safely for default and callback values', function () {
    $column = BadgeListColumn::make('roles')
        ->itemLabel(fn (array $item) => $item['label'] ?? null)
        ->itemColor(fn (array $item) => $item['color'] ?? null);

    expect(BadgeListColumn::make('tags')->resolveItemLabel('Admin'))->toBe('Admin')
        ->and(BadgeListColumn::make('tags')->resolveItemLabel(['invalid']))->toBe('')
        ->and($column->resolveItemLabel(['label' => 'Host']))->toBe('Host')
        ->and($column->resolveItemLabel(['label' => ['bad']]))->toBe('')
        ->and(BadgeListColumn::make('tags')->resolveItemColor('Admin'))->toBe('zinc')
        ->and($column->resolveItemColor(['color' => 'emerald']))->toBe('emerald')
        ->and($column->resolveItemColor(['color' => ['bad']]))->toBe('zinc');
});
