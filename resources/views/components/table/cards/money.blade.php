@php
    $value = $column->resolveValue($record);
@endphp

@if ($value !== null)
    <x-table.card-field :label="$column->label()">
        {{ $column->formatMoney($value) }}
    </x-table.card-field>
@endif
