<section class="container mx-auto space-y-6">
    <x-heading :heading="__('users.index.title')" :subheading="__('users.index.description')" />

    <x-table.data-table
        :columns="$this->tableColumns()"
        :records="$this->users"
        :sort-by="$sortBy"
        :sort-direction="$sortDirection"
        :filters="$this->tableFilters()"
        :actions="$this->tableActions()"
        :active-filter-count="$this->tableActiveFilterCount()"
        :search-placeholder="__('users.index.search_placeholder')"
        search-name="users_search"
        :per-page-options="$this->perPageOptions()"
        :mobile-viewport="$this->tableMobileViewport()"
        :key-prefix="'users-index-table-'.$this->getId()"
    />
</section>
