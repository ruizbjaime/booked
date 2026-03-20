<x-table.card-field :label="$column->label()">
    <span class="text-xs tabular-nums text-zinc-400">{{ $column->resolveValue($record) }}</span>
</x-table.card-field>
