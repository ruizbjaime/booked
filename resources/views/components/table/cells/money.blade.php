@php
    $value = $column->resolveValue($record);
@endphp

<flux:table.cell @class([$column->cellClass()])>
    @if ($value !== null)
        {{ $column->formatMoney($value) }}
    @endif
</flux:table.cell>
