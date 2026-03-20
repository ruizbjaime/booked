<?php

use App\Domain\Table\Columns\PercentageColumn;

test('percentage column formats values with defaults', function () {
    $column = PercentageColumn::make('rate');

    expect($column->formatPercentage(85))->toBe('85%')
        ->and($column->formatPercentage(0))->toBe('0%')
        ->and($column->formatPercentage(100))->toBe('100%');
});

test('percentage column formats with custom decimals', function () {
    $column = PercentageColumn::make('rate')->decimals(2);

    expect($column->formatPercentage(85.567))->toBe('85.57%');
});

test('percentage column supports custom suffix', function () {
    $column = PercentageColumn::make('rate')
        ->decimals(1)
        ->suffix(' %');

    expect($column->formatPercentage(85.5))->toBe('85.5 %');
});

test('percentage column returns empty string for null', function () {
    $column = PercentageColumn::make('rate');

    expect($column->formatPercentage(null))->toBe('');
});

test('percentage column type is correct', function () {
    $column = PercentageColumn::make('rate');

    expect($column->type())->toBe('percentage');
});
