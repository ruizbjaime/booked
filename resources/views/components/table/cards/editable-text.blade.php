<x-table.card-field :label="$column->label()">
    <flux:input
        size="sm"
        :value="$column->resolveValue($record)"
        wire:change="{{ $column->wireChange() }}({{ $record->getKey() }}, '{{ $column->name() }}', $event.target.value)"
    />
</x-table.card-field>
