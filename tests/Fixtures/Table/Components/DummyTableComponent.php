<?php

namespace Tests\Fixtures\Table\Components;

use App\Concerns\InteractsWithTable;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\TextColumn;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DummyTableComponent extends Component
{
    use InteractsWithTable;

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('id'),
            TextColumn::make('name')->sortable(),
            TextColumn::make('email')->sortable(),
            TextColumn::make('created_at')->sortable()->defaultSortDirection('desc'),
        ];
    }

    protected function defaultSortBy(): string
    {
        return 'created_at';
    }

    protected function defaultSortDirection(): string
    {
        return 'desc';
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
            @foreach ($this->records as $record)
                <span>{{ $record->name }}</span>
            @endforeach
        </div>
        BLADE;
    }
}
