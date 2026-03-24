<?php

use App\Domain\Table\Columns\EditableNumberColumn;

it('has correct type', function () {
    expect(EditableNumberColumn::make('sort_order')->type())->toBe('editable-number');
});

it('stores min and max values', function () {
    $column = EditableNumberColumn::make('sort_order')->min(0)->max(9999);

    expect($column->min())->toBe(0)
        ->and($column->max())->toBe(9999);
});

it('stores step value', function () {
    $column = EditableNumberColumn::make('multiplier')->step('0.01');

    expect($column->step())->toBe('0.01');
});

it('stores input class', function () {
    $column = EditableNumberColumn::make('sort_order')->inputClass('w-20');

    expect($column->inputClass())->toBe('w-20');
});

it('stores wireChange method', function () {
    $column = EditableNumberColumn::make('sort_order')->wireChange('updateHoliday');

    expect($column->wireChange())->toBe('updateHoliday');
});

it('defaults to null for min max step', function () {
    $column = EditableNumberColumn::make('sort_order');

    expect($column->min())->toBeNull()
        ->and($column->max())->toBeNull()
        ->and($column->step())->toBeNull();
});
