<x-table.card-field :label="$column->label()">
    {{ $column->formatPercentage($column->resolveValue($record)) }}
</x-table.card-field>
