@php
    $value = (bool) $column->resolveValue($record);
@endphp

<x-table.card-field :label="$column->label()">
    @if ($value)
        <flux:badge :color="$column->trueColor()" size="sm" :icon="$column->trueIcon()">
            {{ $column->trueLabel() }}
        </flux:badge>
    @else
        <flux:badge :color="$column->falseColor()" size="sm" :icon="$column->falseIcon()">
            {{ $column->falseLabel() }}
        </flux:badge>
    @endif
</x-table.card-field>
