<section class="container mx-auto space-y-6">
    <x-heading :heading="__('bed_types.index.title')" :subheading="__('bed_types.index.description')" />

    <x-table.data-table
        :columns="$this->tableColumns()"
        :records="$this->bedTypes"
        :sort-by="$sortBy"
        :sort-direction="$sortDirection"
        :actions="$this->tableActions()"
        :search-placeholder="__('bed_types.index.search_placeholder')"
        search-name="bed_types_search"
        :per-page-options="$this->perPageOptions()"
        :mobile-viewport="$this->tableMobileViewport()"
        :key-prefix="'bed-types-index-table-'.$this->getId()"
        :sortable="true"
        :sortable-active="$this->isSortableActive()"
    />
</section>
