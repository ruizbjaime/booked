@php
    $value = $column->resolveValue($record);
    $color = $column->resolveColor($record);
    $icon = $column->resolveIcon($record);
@endphp

<flux:table.cell @class([$column->cellClass()])>
    @if ($value !== null)
        <flux:badge :color="$color" size="sm" :icon="$icon ?: null">
            {{ $value }}
        </flux:badge>
    @endif
</flux:table.cell>
