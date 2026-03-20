<?php

use App\Domain\Table\ActionItem;
use App\Domain\Table\Columns\ActionsColumn;
use Illuminate\Database\Eloquent\Model;

function actionsTestRecord(): Model
{
    return Mockery::mock(Model::class);
}

describe('actions column', function () {
    test('has correct type', function () {
        $column = ActionsColumn::make('actions');

        expect($column->type())->toBe('actions');
    });

    test('returns empty array when no actions callback is set', function () {
        $column = ActionsColumn::make('actions');

        expect($column->resolveActions(actionsTestRecord()))->toBe([]);
    });

    test('resolves actions from callback', function () {
        $column = ActionsColumn::make('actions')
            ->actions(fn () => [
                ActionItem::link('View', '/test', 'eye'),
                ActionItem::button('Delete', 'delete', 'trash', 'danger'),
            ]);

        $actions = $column->resolveActions(actionsTestRecord());

        expect($actions)->toHaveCount(2)
            ->and($actions[0]->label())->toBe('View')
            ->and($actions[1]->label())->toBe('Delete');
    });

    test('filters out invisible actions', function () {
        $column = ActionsColumn::make('actions')
            ->actions(fn () => [
                ActionItem::link('View', '/test', 'eye'),
                ActionItem::link('Hidden', '/secret', 'eye-off')->visible(false),
                ActionItem::button('Delete', 'delete', 'trash', 'danger'),
            ]);

        $actions = $column->resolveActions(actionsTestRecord());

        expect($actions)->toHaveCount(2)
            ->and($actions[0]->label())->toBe('View')
            ->and($actions[1]->label())->toBe('Delete');
    });

    test('filters actions using closure-based visibility', function () {
        $column = ActionsColumn::make('actions')
            ->actions(fn () => [
                ActionItem::link('View', '/test', 'eye')
                    ->visible(fn (Model $record) => false),
                ActionItem::button('Delete', 'delete', 'trash'),
            ]);

        $actions = $column->resolveActions(actionsTestRecord());

        expect($actions)->toHaveCount(1)
            ->and($actions[0]->label())->toBe('Delete');
    });

    test('removes leading and trailing separators after visibility filtering', function () {
        $column = ActionsColumn::make('actions')
            ->actions(fn () => [
                ActionItem::separator(),
                ActionItem::link('Hidden', '/secret', 'eye-off')->visible(false),
                ActionItem::button('Delete', 'delete', 'trash'),
                ActionItem::separator(),
            ]);

        $actions = $column->resolveActions(actionsTestRecord());

        expect($actions)->toHaveCount(1)
            ->and($actions[0]->isButton())->toBeTrue()
            ->and($actions[0]->label())->toBe('Delete');
    });

    test('collapses consecutive separators into a single separator', function () {
        $column = ActionsColumn::make('actions')
            ->actions(fn () => [
                ActionItem::link('View', '/test', 'eye'),
                ActionItem::separator(),
                ActionItem::link('Hidden', '/secret', 'eye-off')->visible(false),
                ActionItem::separator(),
                ActionItem::button('Delete', 'delete', 'trash'),
            ]);

        $actions = $column->resolveActions(actionsTestRecord());

        expect($actions)->toHaveCount(3)
            ->and($actions[0]->label())->toBe('View')
            ->and($actions[1]->isSeparator())->toBeTrue()
            ->and($actions[2]->label())->toBe('Delete');
    });

    test('has sensible dropdown defaults', function () {
        $column = ActionsColumn::make('actions');

        expect($column)
            ->dropdownPosition()->toBe('bottom')
            ->dropdownAlign()->toBe('end')
            ->align()->toBe('end');
    });

    test('supports custom dropdown position and align', function () {
        $column = ActionsColumn::make('actions')
            ->dropdownPosition('top')
            ->dropdownAlign('start');

        expect($column)
            ->dropdownPosition()->toBe('top')
            ->dropdownAlign()->toBe('start');
    });
});
