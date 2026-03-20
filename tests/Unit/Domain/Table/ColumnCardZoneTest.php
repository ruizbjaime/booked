<?php

use App\Domain\Table\ActionItem;
use App\Domain\Table\CardLayout;
use App\Domain\Table\CardZone;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\AvatarColumn;
use App\Domain\Table\Columns\BadgeColumn;
use App\Domain\Table\Columns\BadgeListColumn;
use App\Domain\Table\Columns\BooleanColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\LinkColumn;
use App\Domain\Table\Columns\MailtoColumn;
use App\Domain\Table\Columns\MoneyColumn;
use App\Domain\Table\Columns\PercentageColumn;
use App\Domain\Table\Columns\ProgressColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Domain\Table\Columns\ToggleColumn;
use App\Models\User;

test('avatar column defaults to header zone', function () {
    expect(AvatarColumn::make('name')->cardZone())->toBe(CardZone::Header);
});

test('id column defaults to footer zone', function () {
    expect(IdColumn::make('id')->cardZone())->toBe(CardZone::Footer);
});

test('date column defaults to footer zone', function () {
    expect(DateColumn::make('created_at')->cardZone())->toBe(CardZone::Footer);
});

test('actions column defaults to hidden zone', function () {
    expect(ActionsColumn::make('actions')->cardZone())->toBe(CardZone::Hidden);
});

test('body-default columns have body zone', function (string $columnClass) {
    expect($columnClass::make('test')->cardZone())->toBe(CardZone::Body);
})->with([
    TextColumn::class,
    BadgeColumn::class,
    BadgeListColumn::class,
    BooleanColumn::class,
    LinkColumn::class,
    MailtoColumn::class,
    MoneyColumn::class,
    PercentageColumn::class,
    ProgressColumn::class,
    ToggleColumn::class,
]);

test('cardZone setter overrides default', function () {
    $column = TextColumn::make('name')->cardZone(CardZone::Footer);

    expect($column->cardZone())->toBe(CardZone::Footer);
});

test('cardZone setter is fluent', function () {
    $column = TextColumn::make('name');

    expect($column->cardZone(CardZone::Hidden))->toBe($column);
});

test('card layout groups visible columns by zone', function () {
    $avatarColumn = AvatarColumn::make('name');
    $textColumn = TextColumn::make('email');
    $dateColumn = DateColumn::make('created_at');

    $columnsByZone = CardLayout::columnsByZone([
        $avatarColumn,
        ActionsColumn::make('hidden-actions'),
        $textColumn,
        $dateColumn,
        ActionsColumn::make('actions'),
    ]);

    expect($columnsByZone)->toHaveKeys(['header', 'body', 'footer'])
        ->and($columnsByZone['header'])->toBe([$avatarColumn])
        ->and($columnsByZone['body'])->toBe([$textColumn])
        ->and($columnsByZone['footer'])->toBe([$dateColumn]);
});

test('card layout returns hidden action columns in their original order', function () {
    $visibleActions = ActionsColumn::make('visible')->cardZone(CardZone::Footer);
    $primaryActions = ActionsColumn::make('primary');
    $secondaryActions = ActionsColumn::make('secondary');

    $actionColumns = CardLayout::actionColumns([
        TextColumn::make('email'),
        $visibleActions,
        $primaryActions,
        $secondaryActions,
    ]);

    expect($actionColumns)->toBe([$primaryActions, $secondaryActions]);
});

test('card layout flattens mobile action items without separators', function () {
    $user = new User([
        'id' => 1,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
    $user->exists = true;

    $actionItems = CardLayout::actionItems([
        ActionsColumn::make('primary')->actions(fn (User $record) => [
            ActionItem::link('View', '/users/'.$record->id, 'eye', wireNavigate: true),
            ActionItem::separator(),
        ]),
        ActionsColumn::make('secondary')->actions(fn (User $record) => [
            ActionItem::button('Archive', 'confirmArchive', 'archive-box'),
        ]),
    ], $user);

    expect(array_map(fn (ActionItem $action): string => $action->label(), $actionItems))
        ->toBe(['View', 'Archive']);
});
