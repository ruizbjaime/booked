<?php

use App\Domain\Table\Columns\BooleanColumn;

it('has correct type', function () {
    expect(BooleanColumn::make('is_active')->type())->toBe('boolean');
});

it('stores and retrieves true and false labels', function () {
    $column = BooleanColumn::make('is_active')
        ->trueLabel('Active')
        ->falseLabel('Inactive');

    expect($column->trueLabel())->toBe('Active')
        ->and($column->falseLabel())->toBe('Inactive');
});

it('stores and retrieves true and false colors', function () {
    $column = BooleanColumn::make('is_active')
        ->trueColor('emerald')
        ->falseColor('zinc');

    expect($column->trueColor())->toBe('emerald')
        ->and($column->falseColor())->toBe('zinc');
});

it('has default colors green and red', function () {
    $column = BooleanColumn::make('is_active');

    expect($column->trueColor())->toBe('green')
        ->and($column->falseColor())->toBe('red');
});

it('stores and retrieves true and false icons', function () {
    $column = BooleanColumn::make('is_active')
        ->trueIcon('check')
        ->falseIcon('x-mark');

    expect($column->trueIcon())->toBe('check')
        ->and($column->falseIcon())->toBe('x-mark');
});
