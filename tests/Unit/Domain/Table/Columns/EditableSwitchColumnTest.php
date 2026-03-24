<?php

use App\Domain\Table\Columns\EditableSwitchColumn;
use App\Models\BedType;

it('has correct type', function () {
    expect(EditableSwitchColumn::make('is_active')->type())->toBe('editable-switch');
});

it('stores wireChange method', function () {
    $column = EditableSwitchColumn::make('is_active')->wireChange('updateHoliday');

    expect($column->wireChange())->toBe('updateHoliday');
});

it('resolves boolean value from model', function () {
    $model = new BedType(['is_active' => true]);

    $column = EditableSwitchColumn::make('is_active');

    expect($column->resolveValue($model))->toBeTrue();
});

it('has default header class w-20', function () {
    $column = EditableSwitchColumn::make('is_active');

    expect($column->headerClass())->toBe('w-20');
});
