<?php

namespace App\Concerns;

use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

trait WithTablePagination
{
    #[Url(as: 'per_page', except: 10)]
    public int $perPage = 10;

    public function mountWithTablePagination(): void
    {
        if ($this->perPage === 10) {
            $this->perPage = SystemSetting::instance()->default_per_page;
        }

        $this->perPage = $this->resolvedPerPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = $this->resolvedPerPage();
        $this->resetPage();
    }

    /**
     * @return list<int>
     */
    public function perPageOptions(): array
    {
        return [10, 15, 25, 50, 100];
    }

    protected function resolvedPerPage(): int
    {
        return in_array($this->perPage, $this->perPageOptions(), true)
            ? $this->perPage
            : $this->perPageOptions()[0];
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    protected function syncCurrentPage(Builder $query): void
    {
        $lastPage = max((int) ceil($query->count() / $this->resolvedPerPage()), 1);

        if ($this->getPage() > $lastPage) {
            $this->setPage($lastPage);
        }
    }
}
