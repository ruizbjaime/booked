<?php

namespace Tests\Fixtures\Table\Components;

class SortableCardViewTableComponent extends CardViewTableComponent
{
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
                :sortable="true"
                :sortable-active="true"
                sort-method="reorderRows"
                :simple="true"
                :key-prefix="'sortable-card-view-table-'.$this->getId()"
            />
        </div>
        BLADE;
    }
}
