<?php

namespace Tests\Fixtures\Table\Components;

use App\Domain\Table\ActionItem;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\AvatarColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Models\User;

class SeparatorOnlyCardViewTableComponent extends CardViewTableComponent
{
    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            AvatarColumn::make('name')
                ->label('User')
                ->initials(fn (User $user) => strtoupper(substr($user->name, 0, 2)))
                ->colorSeed(fn (User $user) => $user->id),
            TextColumn::make('email')->label('Email'),
            ActionsColumn::make('actions')->actions(fn (User $user) => [
                ActionItem::separator(),
                ActionItem::button('Delete', 'confirmDelete', 'trash', 'danger')->visible(false),
            ]),
        ];
    }
}
