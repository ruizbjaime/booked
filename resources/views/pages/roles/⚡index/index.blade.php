<section class="container mx-auto space-y-6">
    <x-heading :heading="__('roles.index.title')" :subheading="__('roles.index.description')" />

    <x-table.data-table
        :columns="$this->tableColumns()"
        :records="$this->roles"
        :sort-by="$sortBy"
        :sort-direction="$sortDirection"
        :actions="$this->tableActions()"
        :search-placeholder="__('roles.index.search_placeholder')"
        search-name="roles_search"
        :per-page-options="$this->perPageOptions()"
        :mobile-viewport="$this->tableMobileViewport()"
        :key-prefix="'roles-index-table-'.$this->getId()"
        :sortable="true"
        :sortable-active="$this->isSortableActive()"
    />
</section>
