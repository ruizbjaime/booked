<x-table.card-field :label="$column->label()">
    {{ $column->resolveValue($record) }}
</x-table.card-field>
