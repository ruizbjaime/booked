<?php

namespace Tests\Fixtures\Table\Components;

use App\Concerns\InteractsWithTable;
use App\Domain\Table\ActionItem;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\ActionsColumn;
use App\Domain\Table\Columns\AvatarColumn;
use App\Domain\Table\Columns\DateColumn;
use App\Domain\Table\Columns\IdColumn;
use App\Domain\Table\Columns\TextColumn;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CardViewTableComponent extends Component
{
    use InteractsWithTable;

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
            TextColumn::make('email')->label('Email')->sortable(),
            DateColumn::make('created_at')->label('Created'),
            ActionsColumn::make('actions')->actions(fn (User $user) => [
                ActionItem::link('Edit', '/users/'.$user->id.'/edit', 'pencil-square', wireNavigate: true),
                ActionItem::separator(),
                ActionItem::button('Delete', 'confirmDelete', 'trash', 'danger'),
            ]),
        ];
    }

    protected function defaultSortBy(): string
    {
        return 'name';
    }

    protected function defaultSortDirection(): string
    {
        return 'asc';
    }

    /**
     * @return list<string>
     */
    protected function searchableFields(): array
    {
        return ['name', 'email'];
    }

    #[Computed]
    public function records(): LengthAwarePaginator
    {
        return $this->paginatedQuery(User::query());
    }

    public function render(): string
    {
        return <<<'BLADE'
        <div>
            <x-table.data-table
                :columns="$this->tableColumns()"
                :records="$this->records"
                :sort-by="$sortBy"
                :sort-direction="$sortDirection"
                :mobile-viewport="$this->tableMobileViewport()"
                :key-prefix="'card-view-table-'.$this->getId()"
            />
        </div>
        BLADE;
    }
}
