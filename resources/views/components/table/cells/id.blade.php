<flux:table.cell @class(['text-zinc-400 tabular-nums', $column->cellClass()])>
    {{ $column->resolveValue($record) }}
</flux:table.cell>
