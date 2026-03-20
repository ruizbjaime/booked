<?php

use App\Domain\Table\Columns\MoneyColumn;

test('money column formats currency values', function () {
    $column = MoneyColumn::make('amount')
        ->currency('USD')
        ->locale('en_US');

    $formatted = $column->formatMoney(1234.56);

    expect($formatted)->toContain('1,234.56');
});

test('money column defaults to USD currency', function () {
    $column = MoneyColumn::make('amount');

    expect($column->currency())->toBe('USD')
        ->and($column->type())->toBe('money');
});

test('money column supports custom currency', function () {
    $column = MoneyColumn::make('amount')->currency('COP');

    expect($column->currency())->toBe('COP');
});

test('money column formats zero correctly', function () {
    $column = MoneyColumn::make('amount')
        ->currency('USD')
        ->locale('en_US');

    $formatted = $column->formatMoney(0);

    expect($formatted)->toContain('0.00');
});
