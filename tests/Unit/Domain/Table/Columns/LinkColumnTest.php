<?php

use App\Domain\Table\Columns\LinkColumn;
use Illuminate\Database\Eloquent\Model;

it('configures link columns with href target and classes', function () {
    $record = Mockery::mock(Model::class);

    $column = LinkColumn::make('profile')
        ->href(fn () => '/users/1')
        ->wireNavigate()
        ->target('_blank')
        ->linkClass('font-medium');

    expect($column->type())->toBe('link')
        ->and($column->resolveHref($record))->toBe('/users/1')
        ->and($column->shouldWireNavigate())->toBeTrue()
        ->and($column->target())->toBe('_blank')
        ->and($column->linkClass())->toBe('font-medium');
});

it('falls back safely for link columns without href or with invalid callback output', function () {
    $record = Mockery::mock(Model::class);

    $columnWithoutHref = LinkColumn::make('profile');
    $columnWithInvalidHref = LinkColumn::make('profile')->href(fn () => ['invalid']);

    expect($columnWithoutHref->resolveHref($record))->toBeNull()
        ->and($columnWithInvalidHref->resolveHref($record))->toBeNull()
        ->and($columnWithoutHref->shouldWireNavigate())->toBeFalse();
});

it('allows clearing target and link classes', function () {
    $column = LinkColumn::make('profile')
        ->target('_blank')
        ->linkClass('font-medium')
        ->wireNavigate(false);

    $column->target(null);
    $column->linkClass(null);

    expect($column->target())->toBeNull()
        ->and($column->linkClass())->toBeNull()
        ->and($column->shouldWireNavigate())->toBeFalse();
});
