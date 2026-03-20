<flux:table.cell :align="$column->align()" @class([$column->cellClass()])>
    {{ $column->formatPercentage($column->resolveValue($record)) }}
</flux:table.cell>
