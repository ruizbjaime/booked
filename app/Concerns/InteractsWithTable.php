<?php

namespace App\Concerns;

use App\Domain\Table\Column;
use App\Domain\Table\Filter;
use App\Domain\Table\TableAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

trait InteractsWithTable
{
    use FormatsLocalizedDates;
    use WithPagination;
    use WithTablePagination;
    use WithTableSearch;
    use WithTableSorting;

    public ?bool $tableIsMobileViewport = null;

    public function mountInteractsWithTable(): void
    {
        $value = session('tableIsMobileViewport');
        $this->tableIsMobileViewport = is_bool($value) ? $value : null;
    }

    /**
     * @return list<Column>
     */
    abstract protected function columns(): array;

    /**
     * @return list<Column>
     */
    public function tableColumns(): array
    {
        return once(fn () => $this->columns());
    }

    /**
     * @return list<Filter>
     */
    protected function filters(): array
    {
        return [];
    }

    /**
     * @return list<Filter>
     */
    public function tableFilters(): array
    {
        return once(fn () => $this->filters());
    }

    /**
     * @return list<TableAction>
     */
    protected function actions(): array
    {
        return [];
    }

    /**
     * @return list<TableAction>
     */
    public function tableActions(): array
    {
        return once(fn () => $this->actions());
    }

    public function tableActiveFilterCount(): int
    {
        return (int) collect($this->tableFilters())
            ->sum(fn (Filter $filter) => $filter->countActive(
                data_get($this, $filter->name())
            ));
    }

    public function syncTableViewport(bool $isMobile): void
    {
        if ($this->tableIsMobileViewport === $isMobile) {
            return;
        }

        $this->tableIsMobileViewport = $isMobile;
        session(['tableIsMobileViewport' => $isMobile]);
    }

    public function tableMobileViewport(): ?bool
    {
        return $this->tableIsMobileViewport;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return LengthAwarePaginator<int, TModel>
     */
    protected function paginatedQuery(Builder $query): LengthAwarePaginator
    {
        $sortBy = $this->resolvedSortBy();
        $direction = $this->resolvedSortDirection();
        $qualifiedKeyColumn = $query->qualifyColumn($query->getModel()->getKeyName());

        $tableQuery = $this->applySearch($query)
            ->orderBy($sortBy, $direction);

        if ($sortBy !== $query->getModel()->getKeyName() && $sortBy !== $qualifiedKeyColumn) {
            $tableQuery->orderBy($qualifiedKeyColumn, $direction);
        }

        return $tableQuery->paginate($this->resolvedPerPage());
    }
}
