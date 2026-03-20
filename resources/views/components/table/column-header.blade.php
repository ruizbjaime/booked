@props(['column', 'sortBy' => '', 'sortDirection' => ''])

@if ($column->isSortable())
    <flux:table.column
        sortable
        :sorted="$sortBy === $column->name()"
        :direction="$sortDirection"
        :align="$column->align()"
        :class="$column->headerClass()"
        wire:click="sort('{{ $column->name() }}')"
    >
        {{ $column->label() }}
    </flux:table.column>
@else
    <flux:table.column
        :align="$column->align()"
        :class="$column->headerClass()"
    >
        {{ $column->label() }}
    </flux:table.column>
@endif
