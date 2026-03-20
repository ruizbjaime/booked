<?php

namespace Tests\Fixtures\Table\Components;

use App\Concerns\InteractsWithTable;
use App\Domain\Table\Column;
use App\Domain\Table\Columns\TextColumn;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PaginatedTableComponent extends Component
{
    use InteractsWithTable;

    /**
     * @return list<Column>
     */
    protected function columns(): array
    {
        return [
            TextColumn::make('name')->sortable(),
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
        return ['name'];
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
