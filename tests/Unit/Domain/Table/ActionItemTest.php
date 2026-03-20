<?php

use App\Domain\Table\ActionItem;
use Illuminate\Database\Eloquent\Model;

test('link factory creates a link action item', function () {
    $action = ActionItem::link('View', '/users/1', 'eye', wireNavigate: true);

    expect($action->isLink())->toBeTrue()
        ->and($action->isButton())->toBeFalse()
        ->and($action->isSeparator())->toBeFalse()
        ->and($action->label())->toBe('View')
        ->and($action->href())->toBe('/users/1')
        ->and($action->icon())->toBe('eye')
        ->and($action->shouldWireNavigate())->toBeTrue()
        ->and($action->variant())->toBe('default');
});

test('button factory creates a button action item', function () {
    $action = ActionItem::button('Delete', 'confirmDeletion', 'trash', 'danger');

    expect($action->isButton())->toBeTrue()
        ->and($action->isLink())->toBeFalse()
        ->and($action->isSeparator())->toBeFalse()
        ->and($action->label())->toBe('Delete')
        ->and($action->wireClick())->toBe('confirmDeletion')
        ->and($action->icon())->toBe('trash')
        ->and($action->variant())->toBe('danger');
});

test('separator factory creates a separator action item', function () {
    $action = ActionItem::separator();

    expect($action->isSeparator())->toBeTrue()
        ->and($action->isLink())->toBeFalse()
        ->and($action->isButton())->toBeFalse();
});

test('action items are visible by default', function () {
    $action = ActionItem::link('View', '/test', 'eye');
    $record = Mockery::mock(Model::class);

    expect($action->isVisible($record))->toBeTrue();
});

test('visible with boolean controls visibility', function () {
    $action = ActionItem::link('View', '/test', 'eye')->visible(false);
    $record = Mockery::mock(Model::class);

    expect($action->isVisible($record))->toBeFalse();
});

test('visible with closure receives the record', function () {
    $action = ActionItem::link('View', '/test', 'eye')
        ->visible(fn (Model $record) => false);

    $record = Mockery::mock(Model::class);

    expect($action->isVisible($record))->toBeFalse();
});

test('link without wire navigate defaults to false', function () {
    $action = ActionItem::link('View', '/test', 'eye');

    expect($action->shouldWireNavigate())->toBeFalse();
});
