<section class="container mx-auto space-y-6">
    <x-heading :heading="__('properties.index.title')" :subheading="__('properties.index.description')" />

    <x-table.data-table
        :columns="$this->tableColumns()"
        :records="$this->properties"
        :actions="$this->actions()"
        :sort-by="$sortBy"
        :sort-direction="$sortDirection"
        :search-placeholder="__('properties.index.search_placeholder')"
        search-name="properties_search"
        :per-page-options="$this->perPageOptions()"
        :mobile-viewport="$this->tableMobileViewport()"
        :key-prefix="'properties-index-table-'.$this->getId()"
    />
</section>
