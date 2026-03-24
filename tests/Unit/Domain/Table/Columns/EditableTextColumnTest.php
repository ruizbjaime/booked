<?php

use App\Domain\Table\Columns\EditableTextColumn;
use App\Models\BedType;

it('has correct type', function () {
    expect(EditableTextColumn::make('name')->type())->toBe('editable-text');
});

it('stores and retrieves wireChange method', function () {
    $column = EditableTextColumn::make('en_name')->wireChange('updateHoliday');

    expect($column->wireChange())->toBe('updateHoliday');
});

it('resolves value from model', function () {
    $model = new BedType(['name_en' => 'King Bed']);

    $column = EditableTextColumn::make('name_en');

    expect($column->resolveValue($model))->toBe('King Bed');
});

it('supports fluent label', function () {
    $column = EditableTextColumn::make('en_name')->label('Name (EN)');

    expect($column->label())->toBe('Name (EN)');
});
