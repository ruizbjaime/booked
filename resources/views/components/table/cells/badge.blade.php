@php
    $value = $column->resolveValue($record);
    $color = $column->resolveColor($record);
    $icon = $column->resolveIcon($record);
@endphp

<flux:table.cell @class([$column->cellClass()])>
    @if ($value !== null)
        <x-badge :color="$color" size="sm" :icon="$icon ?: null">
            {{ $value }}
        </x-badge>
    @endif
</flux:table.cell>
