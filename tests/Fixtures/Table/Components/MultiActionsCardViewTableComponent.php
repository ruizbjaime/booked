<?php

namespace Tests\Fixtures\Table\Components;

use App\Domain\Table\ActionItem;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\AvatarColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Models\User;

class MultiActionsCardViewTableComponent extends CardViewTableComponent
{
    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            IdColumn::make('id')->label('ID'),
            AvatarColumn::make('name')
                ->label('User')
                ->initials(fn (User $user) => strtoupper(substr($user->name, 0, 2)))
                ->colorSeed(fn (User $user) => $user->id),
            TextColumn::make('email')->label('Email'),
            ActionsColumn::make('primary')->actions(fn (User $user) => [
                ActionItem::link('View', '/users/'.$user->id, 'eye', wireNavigate: true),
            ]),
            ActionsColumn::make('secondary')->actions(fn (User $user) => [
                ActionItem::button('Archive', 'confirmArchive', 'archive-box'),
            ]),
        ];
    }
}
