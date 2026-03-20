<?php

namespace App\Concerns;

use App\Domain\Table\Column;
use Livewire\Attributes\Url;

trait WithTableSorting
{
    #[Url(as: 'sort', except: '')]
    public string $sortBy = '';

    #[Url(as: 'direction', except: '')]
    public string $sortDirection = '';

    public function mountWithTableSorting(): void
    {
        $this->sortBy = $this->resolvedSortBy();
        $this->sortDirection = $this->resolvedSortDirection();
    }

    public function sort(string $column): void
    {
        $sortableMap = $this->sortableColumnsMap();

        if (! array_key_exists($column, $sortableMap)) {
            return;
        }

        if ($this->resolvedSortBy() === $column) {
            $this->sortDirection = $this->resolvedSortDirection() === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = $sortableMap[$column];
        }

        $this->resetPage();
    }

    abstract protected function defaultSortBy(): string;

    abstract protected function defaultSortDirection(): string;

    /**
     * @return array<string, string>
     */
    protected function sortableColumnsMap(): array
    {
        return once(fn () => collect($this->tableColumns())
            ->filter(fn (Column $column) => $column->isSortable())
            ->mapWithKeys(fn (Column $column) => [$column->name() => $column->defaultSortDirection()])
            ->all());
    }

    protected function resolvedSortBy(): string
    {
        $sortable = $this->sortableColumnsMap();

        return array_key_exists($this->sortBy, $sortable)
            ? $this->sortBy
            : $this->defaultSortBy();
    }

    protected function resolvedSortDirection(): string
    {
        if (in_array($this->sortDirection, ['asc', 'desc'], true)) {
            return $this->sortDirection;
        }

        return $this->sortableColumnsMap()[$this->resolvedSortBy()] ?? $this->defaultSortDirection();
    }
}
