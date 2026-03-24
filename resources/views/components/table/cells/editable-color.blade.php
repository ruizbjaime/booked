<flux:table.cell @class([$column->cellClass()])>
    <div class="flex items-center gap-2">
        <span class="inline-block size-5 shrink-0 rounded" style="background-color: {{ $column->resolveValue($record) }}"></span>
        <flux:input
            size="sm"
            class="w-24"
            :value="$column->resolveValue($record)"
            wire:change="{{ $column->wireChange() }}({{ $record->getKey() }}, '{{ $column->name() }}', $event.target.value)"
        />
    </div>
</flux:table.cell>
