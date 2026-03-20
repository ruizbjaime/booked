<?php

use App\Domain\Table\Filters\SelectFilter;

test('make creates a filter with the given name and correct defaults', function () {
    $filter = SelectFilter::make('status');

    expect($filter->name())->toBe('status')
        ->and($filter->type())->toBe('select')
        ->and($filter->isMultiple())->toBeFalse()
        ->and($filter->placeholder())->toBe('')
        ->and($filter->resolveOptions())->toBe([]);
});

test('fluent setters return the filter for chaining', function () {
    $filter = SelectFilter::make('role')
        ->multiple()
        ->placeholder('Filter by role')
        ->options(['admin' => 'Admin', 'user' => 'User']);

    expect($filter->isMultiple())->toBeTrue()
        ->and($filter->placeholder())->toBe('Filter by role')
        ->and($filter->resolveOptions())->toBe(['admin' => 'Admin', 'user' => 'User']);
});

test('options resolves a closure lazily', function () {
    $callCount = 0;

    $filter = SelectFilter::make('status')
        ->options(function () use (&$callCount) {
            $callCount++;

            return ['active' => 'Active', 'inactive' => 'Inactive'];
        });

    expect($callCount)->toBe(0);

    $result = $filter->resolveOptions();

    expect($result)->toBe(['active' => 'Active', 'inactive' => 'Inactive'])
        ->and($callCount)->toBe(1);
});

test('countActive returns count of selected items when multiple', function () {
    $filter = SelectFilter::make('roles')->multiple();

    expect($filter->countActive([]))->toBe(0)
        ->and($filter->countActive(['admin', 'user']))->toBe(2)
        ->and($filter->countActive(['admin']))->toBe(1)
        ->and($filter->countActive(null))->toBe(0)
        ->and($filter->countActive('not-an-array'))->toBe(0);
});

test('countActive returns 0 or 1 for single select', function () {
    $filter = SelectFilter::make('status');

    expect($filter->countActive(''))->toBe(0)
        ->and($filter->countActive(null))->toBe(0)
        ->and($filter->countActive('active'))->toBe(1)
        ->and($filter->countActive(0))->toBe(1)
        ->and($filter->countActive(false))->toBe(1);
});

test('multiple can be toggled off', function () {
    $filter = SelectFilter::make('role')
        ->multiple()
        ->multiple(false);

    expect($filter->isMultiple())->toBeFalse();
});

test('options can be replaced', function () {
    $filter = SelectFilter::make('status')
        ->options(['a' => 'A'])
        ->options(['b' => 'B', 'c' => 'C']);

    expect($filter->resolveOptions())->toBe(['b' => 'B', 'c' => 'C']);
});
