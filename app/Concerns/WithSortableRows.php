<?php

namespace App\Concerns;

use App\Actions\Shared\ReorderModel;
use App\Infrastructure\UiFeedback\ToastService;
use Illuminate\Database\Eloquent\Model;

/**
 * Enables drag-and-drop row reordering via wire:sort on index tables.
 *
 * Requires the component to also use:
 * - InteractsWithTable (for sort/search/pagination state)
 * - ResolvesAuthenticatedUser (for actor())
 * - ThrottlesFormActions (for throttle())
 */
trait WithSortableRows
{
    /**
     * The database column that stores the display order.
     */
    abstract protected function orderColumnName(): string;

    /**
     * The fully qualified model class name.
     *
     * @return class-string<Model>
     */
    abstract protected function orderModelClass(): string;

    /**
     * Whether drag-and-drop reordering is currently active.
     *
     * Only active when sorted by the order column ascending with no search.
     */
    public function isSortableActive(): bool
    {
        return $this->resolvedSortBy() === $this->orderColumnName()
            && $this->resolvedSortDirection() === 'asc'
            && trim($this->search) === ''
            && $this->actor()->can('update', new ($this->orderModelClass()));
    }

    /**
     * Handle wire:sort callback from drag-and-drop.
     *
     * @param  int|string  $id  The record ID being moved
     * @param  int|string  $position  Zero-based position within the visible page
     */
    public function reorderRows(int|string $id, int|string $position): void
    {
        if ($this->throttle('reorder')) {
            return;
        }

        $id = (int) $id;
        $position = (int) $position;

        /** @var int|string $page */
        $page = $this->getPage();
        $currentPage = max(1, (int) $page);
        $absolutePosition = (($currentPage - 1) * $this->resolvedPerPage()) + $position;

        /** @var Model $record */
        $record = ($this->orderModelClass())::query()->findOrFail($id);

        app(ReorderModel::class)->handle(
            $this->actor(),
            $record,
            $this->orderColumnName(),
            $absolutePosition,
        );

        ToastService::success(__('actions.reorder_success'));
    }
}
