@php
    $value = $column->resolveValue($record);
@endphp

@if ($value)
    <x-table.card-field :label="$column->label()">
        <flux:link href="mailto:{{ $value }}">
            {{ $value }}
        </flux:link>
    </x-table.card-field>
@endif
