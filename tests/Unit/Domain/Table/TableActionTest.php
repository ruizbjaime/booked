<?php

use App\Domain\Table\TableAction;

test('make creates an action with the given name and correct defaults', function () {
    $action = TableAction::make('create');

    expect($action->name())->toBe('create')
        ->and($action->label())->toBe('')
        ->and($action->icon())->toBe('')
        ->and($action->wireClick())->toBeNull()
        ->and($action->variant())->toBe('primary')
        ->and($action->isResponsive())->toBeFalse();
});

test('fluent setters return the action for chaining', function () {
    $action = TableAction::make('create')
        ->label('Create User')
        ->icon('plus')
        ->wireClick('openCreateModal')
        ->variant('filled')
        ->responsive();

    expect($action->label())->toBe('Create User')
        ->and($action->icon())->toBe('plus')
        ->and($action->wireClick())->toBe('openCreateModal')
        ->and($action->variant())->toBe('filled')
        ->and($action->isResponsive())->toBeTrue();
});

test('wireClick can be reset to null', function () {
    $action = TableAction::make('create')
        ->wireClick('openModal')
        ->wireClick(null);

    expect($action->wireClick())->toBeNull();
});

test('responsive can be toggled off', function () {
    $action = TableAction::make('create')
        ->responsive()
        ->responsive(false);

    expect($action->isResponsive())->toBeFalse();
});

test('variant can be changed', function () {
    $action = TableAction::make('delete')
        ->variant('danger');

    expect($action->variant())->toBe('danger');
});
