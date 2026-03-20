<?php

use App\Domain\Table\Columns\AvatarColumn;
use App\Domain\Table\Columns\BooleanColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Model;

test('make creates a column with the given name', function () {
    $column = TextColumn::make('email');

    expect($column->name())->toBe('email')
        ->and($column->type())->toBe('text');
});

test('fluent setters return the column for chaining', function () {
    $column = TextColumn::make('name')
        ->label('Name')
        ->sortable()
        ->defaultSortDirection('desc')
        ->align('center')
        ->headerClass('w-32')
        ->cellClass('font-bold');

    expect($column->label())->toBe('Name')
        ->and($column->isSortable())->toBeTrue()
        ->and($column->defaultSortDirection())->toBe('desc')
        ->and($column->align())->toBe('center')
        ->and($column->headerClass())->toBe('w-32')
        ->and($column->cellClass())->toBe('font-bold');
});

test('column defaults are correct', function () {
    $column = TextColumn::make('test');

    expect($column->label())->toBe('')
        ->and($column->isSortable())->toBeFalse()
        ->and($column->defaultSortDirection())->toBe('asc')
        ->and($column->align())->toBe('start')
        ->and($column->headerClass())->toBeNull()
        ->and($column->cellClass())->toBeNull();
});

test('resolveValue uses data_get for dot notation', function () {
    $column = TextColumn::make('relation.field');

    $record = Mockery::mock(Model::class);
    $record->shouldReceive('offsetExists')->with('relation')->andReturn(true);
    $record->shouldReceive('offsetGet')->with('relation')->andReturn((object) ['field' => 'nested_value']);

    expect($column->resolveValue($record))->toBe('nested_value');
});

test('id column has w-16 header class by default', function () {
    $column = IdColumn::make('id');

    expect($column->type())->toBe('id')
        ->and($column->headerClass())->toBe('w-16');
});

test('boolean column supports true and false configuration', function () {
    $column = BooleanColumn::make('is_verified')
        ->trueLabel('Verified')
        ->falseLabel('Pending')
        ->trueColor('green')
        ->falseColor('yellow')
        ->trueIcon('check-circle')
        ->falseIcon('exclamation-circle');

    expect($column->type())->toBe('boolean')
        ->and($column->trueLabel())->toBe('Verified')
        ->and($column->falseLabel())->toBe('Pending')
        ->and($column->trueColor())->toBe('green')
        ->and($column->falseColor())->toBe('yellow')
        ->and($column->trueIcon())->toBe('check-circle')
        ->and($column->falseIcon())->toBe('exclamation-circle');
});

test('toggle column supports wire change and disabled', function () {
    $column = ToggleColumn::make('is_active')
        ->wireChange('toggleActive')
        ->disabled(true)
        ->idPrefix('active');

    expect($column->type())->toBe('toggle')
        ->and($column->wireChange())->toBe('toggleActive')
        ->and($column->idPrefix())->toBe('active');

    $record = Mockery::mock(Model::class);
    expect($column->isDisabled($record))->toBeTrue();
});

test('toggle column disabled accepts closure', function () {
    $column = ToggleColumn::make('is_active')
        ->disabled(fn (Model $record) => true);

    $record = Mockery::mock(Model::class);
    expect($column->isDisabled($record))->toBeTrue();
});

test('date column has correct type and default formats', function () {
    $column = DateColumn::make('created_at');

    expect($column->type())->toBe('date')
        ->and($column->format())->toBe('ll')
        ->and($column->tooltipFormat())->toBe('llll');
});

test('date column supports custom formats', function () {
    $column = DateColumn::make('created_at')
        ->format('L')
        ->tooltipFormat('LLLL');

    expect($column->format())->toBe('L')
        ->and($column->tooltipFormat())->toBe('LLLL');
});

test('avatar column resolves closures with record', function () {
    $column = AvatarColumn::make('name')
        ->avatarSrc(fn () => 'avatar.jpg')
        ->initials(fn () => 'JD')
        ->colorSeed(fn () => 42)
        ->recordUrl(fn () => '/users/1')
        ->wireNavigate();

    $record = Mockery::mock(Model::class);

    expect($column->type())->toBe('avatar')
        ->and($column->resolveAvatarSrc($record))->toBe('avatar.jpg')
        ->and($column->resolveInitials($record))->toBe('JD')
        ->and($column->resolveColorSeed($record))->toBe(42)
        ->and($column->resolveRecordUrl($record))->toBe('/users/1')
        ->and($column->hasRecordUrl())->toBeTrue()
        ->and($column->shouldWireNavigate())->toBeTrue();
});

test('avatar column without record url returns null', function () {
    $column = AvatarColumn::make('name');
    $record = Mockery::mock(Model::class);

    expect($column->hasRecordUrl())->toBeFalse()
        ->and($column->resolveRecordUrl($record))->toBeNull();
});
