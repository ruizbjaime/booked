<x-table.card-field :label="$column->label()">
    @php
        $switchId = $column->idPrefix().'-'.$record->getKey();
        $isChecked = (bool) $column->resolveValue($record);
        $isDisabled = $column->isDisabled($record);
    @endphp

    <flux:switch
        id="{{ $switchId }}"
        wire:key="{{ $switchId }}-{{ (int) $isChecked }}"
        :checked="$isChecked"
        :disabled="$isDisabled"
        data-disabled="{{ $isDisabled ? 'true' : 'false' }}"
        wire:change="{{ $column->wireChange() }}({{ $record->getKey() }}, '{{ $column->name() }}', $event.target.checked)"
    />
</x-table.card-field>
