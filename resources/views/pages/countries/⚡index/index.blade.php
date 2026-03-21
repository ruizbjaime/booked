<section class="container mx-auto space-y-6">
    <x-heading :heading="__('countries.index.title')" :subheading="__('countries.index.description')" />

    <x-table.data-table
        :columns="$this->tableColumns()"
        :records="$this->countries"
        :sort-by="$sortBy"
        :sort-direction="$sortDirection"
        :actions="$this->tableActions()"
        :search-placeholder="__('countries.index.search_placeholder')"
        search-name="countries_search"
        :per-page-options="$this->perPageOptions()"
        :mobile-viewport="$this->tableMobileViewport()"
        :key-prefix="'countries-index-table-'.$this->getId()"
        :sortable="true"
        :sortable-active="$this->isSortableActive()"
    />
</section>
