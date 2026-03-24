<flux:table.cell @class([$column->cellClass()])>
    <flux:input
        size="sm"
        :value="$column->resolveValue($record)"
        wire:change="{{ $column->wireChange() }}({{ $record->getKey() }}, '{{ $column->name() }}', $event.target.value)"
    />
</flux:table.cell>
