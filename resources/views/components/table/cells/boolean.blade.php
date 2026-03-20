@php
    $value = (bool) $column->resolveValue($record);
@endphp

<flux:table.cell @class([$column->cellClass()])>
    @if ($value)
        <flux:badge :color="$column->trueColor()" size="sm" :icon="$column->trueIcon()">
            {{ $column->trueLabel() }}
        </flux:badge>
    @else
        <flux:badge :color="$column->falseColor()" size="sm" :icon="$column->falseIcon()">
            {{ $column->falseLabel() }}
        </flux:badge>
    @endif
</flux:table.cell>
