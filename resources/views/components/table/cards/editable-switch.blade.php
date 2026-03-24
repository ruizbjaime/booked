<x-table.card-field :label="$column->label()">
    <flux:switch
        :checked="(bool) $column->resolveValue($record)"
        wire:change="{{ $column->wireChange() }}({{ $record->getKey() }}, '{{ $column->name() }}', $event.target.checked)"
    />
</x-table.card-field>
