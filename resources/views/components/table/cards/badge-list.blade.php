@php
    $items = $column->resolveValue($record);
@endphp

<x-table.card-field :label="$column->label()">
    <div class="flex flex-wrap justify-end gap-1">
        @forelse ($items as $item)
            <flux:badge size="sm" :color="$column->resolveItemColor($item)">
                {{ $column->resolveItemLabel($item) }}
            </flux:badge>
        @empty
            <flux:badge size="sm" :color="$column->emptyColor()">{{ $column->emptyLabel() }}</flux:badge>
        @endforelse
    </div>
</x-table.card-field>
