@php
    $value = $column->resolveValue($record);
@endphp

@if ($value)
    <x-table.card-field :label="$column->label()">
        <flux:tooltip :content="$column->formatTooltip($value)">
            <span class="inline-flex cursor-help text-xs text-zinc-500 decoration-dotted underline-offset-4 hover:underline dark:text-zinc-400">
                {{ $column->formatDisplay($value) }}
            </span>
        </flux:tooltip>
    </x-table.card-field>
@endif
