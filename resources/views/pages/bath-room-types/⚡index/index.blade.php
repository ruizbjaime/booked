<section class="container mx-auto space-y-6">
    <x-heading :heading="__('bath_room_types.index.title')" :subheading="__('bath_room_types.index.description')" />

    <x-table.data-table
        :columns="$this->tableColumns()"
        :records="$this->bathRoomTypes"
        :sort-by="$sortBy"
        :sort-direction="$sortDirection"
        :actions="$this->tableActions()"
        :search-placeholder="__('bath_room_types.index.search_placeholder')"
        search-name="bath_room_types_search"
        :per-page-options="$this->perPageOptions()"
        :mobile-viewport="$this->tableMobileViewport()"
        :key-prefix="'bath-room-types-index-table-'.$this->getId()"
    />
</section>
