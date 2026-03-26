<?php

use App\Domain\Table\Columns\EditableSwitchColumn;
use App\Models\BedType;
use Illuminate\Database\Eloquent\Model;

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

it('supports disabled state and id prefix', function () {
    $column = EditableSwitchColumn::make('is_active')
        ->wireChange('updateHoliday')
        ->disabled(true)
        ->idPrefix('holiday-active');

    expect($column->wireChange())->toBe('updateHoliday')
        ->and($column->idPrefix())->toBe('holiday-active')
        ->and($column->isDisabled(new BedType))->toBeTrue();
});

it('accepts a disabled closure', function () {
    $column = EditableSwitchColumn::make('is_active')
        ->disabled(fn (Model $record) => $record->is_active);

    expect($column->isDisabled(new BedType(['is_active' => true])))->toBeTrue()
        ->and($column->isDisabled(new BedType(['is_active' => false])))->toBeFalse();
});
