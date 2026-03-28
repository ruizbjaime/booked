<?php

use App\Domain\Table\CardZone;
use App\Domain\Table\Columns\DateColumn;

it('has correct type', function () {
    expect(DateColumn::make('created_at')->type())->toBe('date');
});

it('has default format ll and tooltip format llll', function () {
    $column = DateColumn::make('created_at');

    expect($column->format())->toBe('ll')
        ->and($column->tooltipFormat())->toBe('llll');
});

it('stores and retrieves custom format', function () {
    $column = DateColumn::make('created_at')->format('YYYY-MM-DD');

    expect($column->format())->toBe('YYYY-MM-DD');
});

it('stores and retrieves custom tooltip format', function () {
    $column = DateColumn::make('created_at')->tooltipFormat('dddd, MMMM D');

    expect($column->tooltipFormat())->toBe('dddd, MMMM D');
});

it('defaults to footer card zone', function () {
    $column = DateColumn::make('created_at');

    expect($column->cardZone())->toBe(CardZone::Footer);
});
