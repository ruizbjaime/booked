@php $currentValue = $column->resolveValue($record); @endphp

<flux:table.cell @class([$column->cellClass()])>
    <flux:select
        size="sm"
        wire:change="{{ $column->wireChange() }}({{ $record->getKey() }}, '{{ $column->name() }}', $event.target.value)"
    >
        @foreach ($column->options() as $optionValue => $optionLabel)
            <flux:select.option :value="$optionValue" :selected="$optionValue == $currentValue">{{ $optionLabel }}</flux:select.option>
        @endforeach
    </flux:select>
</flux:table.cell>
