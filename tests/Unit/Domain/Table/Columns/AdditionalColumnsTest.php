<?php

use App\Domain\Table\CardZone;
use App\Domain\Table\Columns\CustomColumn;
use App\Domain\Table\Columns\EditableColorColumn;
use App\Domain\Table\Columns\EditableSelectColumn;
use App\Domain\Table\Columns\ImageColumn;
use App\Domain\Table\Columns\ProgressColumn;
use App\Domain\Table\Columns\SortHandleColumn;
use Illuminate\Database\Eloquent\Model;

it('configures custom columns with a dedicated view', function () {
    $column = CustomColumn::make('preview')->view('tables.columns.preview');

    expect($column->type())->toBe('custom')
        ->and($column->view())->toBe('tables.columns.preview');
});

it('stores the wire change action for editable color columns', function () {
    $column = EditableColorColumn::make('color')->wireChange('updateColor');

    expect($column->type())->toBe('editable-color')
        ->and($column->wireChange())->toBe('updateColor');
});

it('stores selectable options for editable select columns', function () {
    $column = EditableSelectColumn::make('status')->options(['draft' => 'Draft', 'live' => 'Live']);

    expect($column->type())->toBe('editable-select')
        ->and($column->options())->toBe(['draft' => 'Draft', 'live' => 'Live']);
});

it('configures image columns and resolves callbacks safely', function () {
    $record = Mockery::mock(Model::class);

    $column = ImageColumn::make('avatar')
        ->src(fn () => 'avatar.png')
        ->alt(fn () => 'User avatar')
        ->width(64)
        ->height(64)
        ->rounded();

    expect($column->type())->toBe('image')
        ->and($column->resolveSrc($record))->toBe('avatar.png')
        ->and($column->resolveAlt($record))->toBe('User avatar')
        ->and($column->width())->toBe(64)
        ->and($column->height())->toBe(64)
        ->and($column->isRounded())->toBeTrue();
});

it('falls back safely when image column callbacks return invalid values', function () {
    $record = Mockery::mock(Model::class);

    $column = ImageColumn::make('avatar')
        ->src(fn () => 123)
        ->alt(fn () => ['invalid']);

    expect($column->resolveSrc($record))->toBeNull()
        ->and($column->resolveAlt($record))->toBe('');
});

it('configures progress columns and resolves dynamic colors', function () {
    $record = Mockery::mock(Model::class);

    $column = ProgressColumn::make('progress')
        ->max(250)
        ->color(fn () => 'emerald')
        ->showLabel();

    expect($column->type())->toBe('progress')
        ->and($column->max())->toBe(250)
        ->and($column->resolveColor($record))->toBe('emerald')
        ->and($column->shouldShowLabel())->toBeTrue();
});

it('uses the default color when progress callback output is invalid', function () {
    $record = Mockery::mock(Model::class);
    $column = ProgressColumn::make('progress')->color(fn () => ['bad']);

    expect($column->resolveColor($record))->toBe('blue');
});

it('uses hidden card zone defaults for sort handle columns', function () {
    $column = SortHandleColumn::make('sort_order');

    expect($column->type())->toBe('sort-handle')
        ->and($column->headerClass())->toBe('w-8')
        ->and($column->cardZone())->toBe(CardZone::Hidden);
});
