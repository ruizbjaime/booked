@php
    $items = $column->resolveValue($record);
@endphp

<flux:table.cell @class([$column->cellClass()])>
    <div class="flex flex-wrap gap-1">
        @forelse ($items as $item)
            <flux:badge size="sm" :color="$column->resolveItemColor($item)">
                {{ $column->resolveItemLabel($item) }}
            </flux:badge>
        @empty
            <flux:badge size="sm" :color="$column->emptyColor()">{{ $column->emptyLabel() }}</flux:badge>
        @endforelse
    </div>
</flux:table.cell>
