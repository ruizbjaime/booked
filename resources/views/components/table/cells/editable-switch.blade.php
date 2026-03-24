<flux:table.cell @class([$column->cellClass()])>
    <div class="flex justify-start">
        <flux:switch
            :checked="(bool) $column->resolveValue($record)"
            wire:change="{{ $column->wireChange() }}({{ $record->getKey() }}, '{{ $column->name() }}', $event.target.checked)"
        />
    </div>
</flux:table.cell>
