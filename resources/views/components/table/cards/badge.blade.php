@php
    $value = $column->resolveValue($record);
    $color = $column->resolveColor($record);
    $icon = $column->resolveIcon($record);
@endphp

@if ($value !== null)
    <x-table.card-field :label="$column->label()">
        <flux:badge :color="$color" size="sm" :icon="$icon">
            {{ $value }}
        </flux:badge>
    </x-table.card-field>
@endif
