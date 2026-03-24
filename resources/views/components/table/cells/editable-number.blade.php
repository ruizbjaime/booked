<flux:table.cell @class([$column->cellClass()])>
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
</flux:table.cell>
