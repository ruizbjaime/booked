<section class="container mx-auto space-y-6">
    <x-heading :heading="__('charge_bases.index.title')" :subheading="__('charge_bases.index.description')" />

    <x-table.data-table
        :columns="$this->tableColumns()"
        :records="$this->chargeBases"
        :sort-by="$sortBy"
        :sort-direction="$sortDirection"
        :actions="$this->tableActions()"
        :search-placeholder="__('charge_bases.index.search_placeholder')"
        search-name="charge_bases_search"
        :per-page-options="$this->perPageOptions()"
        :mobile-viewport="$this->tableMobileViewport()"
        :key-prefix="'charge-bases-index-table-'.$this->getId()"
        :sortable="true"
        :sortable-active="$this->isSortableActive()"
    />
</section>
