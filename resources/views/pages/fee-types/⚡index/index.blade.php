<section class="container mx-auto space-y-6">
    <x-heading :heading="__('fee_types.index.title')" :subheading="__('fee_types.index.description')" />

    <x-table.data-table
        :columns="$this->tableColumns()"
        :records="$this->feeTypes"
        :sort-by="$sortBy"
        :sort-direction="$sortDirection"
        :actions="$this->tableActions()"
        :search-placeholder="__('fee_types.index.search_placeholder')"
        search-name="fee_types_search"
        :per-page-options="$this->perPageOptions()"
        :mobile-viewport="$this->tableMobileViewport()"
        :key-prefix="'fee-types-index-table-'.$this->getId()"
        :sortable="true"
        :sortable-active="$this->isSortableActive()"
    />
</section>
