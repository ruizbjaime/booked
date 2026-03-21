<section class="container mx-auto space-y-6">
    <x-heading :heading="__('identification_document_types.index.title')" :subheading="__('identification_document_types.index.description')" />

    <x-table.data-table
        :columns="$this->tableColumns()"
        :records="$this->docTypes"
        :sort-by="$sortBy"
        :sort-direction="$sortDirection"
        :actions="$this->tableActions()"
        :search-placeholder="__('identification_document_types.index.search_placeholder')"
        search-name="doc_types_search"
        :per-page-options="$this->perPageOptions()"
        :mobile-viewport="$this->tableMobileViewport()"
        :key-prefix="'doc-types-index-table-'.$this->getId()"
        :sortable="true"
        :sortable-active="$this->isSortableActive()"
    />
</section>
