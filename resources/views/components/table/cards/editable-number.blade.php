<x-table.card-field :label="$column->label()">
    <flux:input
        size="sm"
        type="number"
        :min="$column->min()"
        :max="$column->max()"
        :step="$column->step()"
        @class([$column->inputClass()])
        :value="$column->resolveValue($record)"
        wire:change="{{ $column->wireChange() }}({{ $record->getKey() }}, '{{ $column->name() }}', $event.target.value)"
    />
</x-table.card-field>
