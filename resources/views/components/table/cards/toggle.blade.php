<x-table.card-field :label="$column->label()">
    <flux:switch
        id="card-{{ $column->idPrefix() }}-{{ $record->getKey() }}"
        :checked="(bool) $column->resolveValue($record)"
        :disabled="$column->isDisabled($record)"
        data-disabled="{{ $column->isDisabled($record) ? 'true' : 'false' }}"
        wire:change="{{ $column->wireChange() }}({{ $record->getKey() }}, $event.target.checked)"
    />
</x-table.card-field>
