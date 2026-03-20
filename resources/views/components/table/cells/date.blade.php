@php
    $value = $column->resolveValue($record);
@endphp

<flux:table.cell @class(['text-zinc-500 dark:text-zinc-400', $column->cellClass()])>
    @if ($value)
        <flux:tooltip :content="$column->formatTooltip($value)">
            <span class="inline-flex cursor-help decoration-dotted underline-offset-4 hover:underline">
                {{ $column->formatDisplay($value) }}
            </span>
        </flux:tooltip>
    @endif
</flux:table.cell>
