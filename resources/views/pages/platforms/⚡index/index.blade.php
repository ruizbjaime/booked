<section class="container mx-auto space-y-6">
    <x-heading :heading="__('platforms.index.title')" :subheading="__('platforms.index.description')" />

    <x-table.data-table
        :columns="$this->tableColumns()"
        :records="$this->platforms"
        :sort-by="$sortBy"
        :sort-direction="$sortDirection"
        :actions="$this->tableActions()"
        :search-placeholder="__('platforms.index.search_placeholder')"
        search-name="platforms_search"
        :per-page-options="$this->perPageOptions()"
        :mobile-viewport="$this->tableMobileViewport()"
        :key-prefix="'platforms-index-table-'.$this->getId()"
        :sortable="true"
        :sortable-active="$this->isSortableActive()"
    />
</section>
