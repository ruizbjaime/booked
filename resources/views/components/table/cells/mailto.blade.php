@php
    $value = $column->resolveValue($record);
@endphp

<flux:table.cell @class([$column->cellClass()])>
    @if ($value)
        <flux:link href="mailto:{{ $value }}">
            {{ $value }}
        </flux:link>
    @endif
</flux:table.cell>
