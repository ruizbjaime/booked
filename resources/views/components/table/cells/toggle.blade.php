<flux:table.cell @class([$column->cellClass()])>
    <div class="flex justify-start">
        <flux:switch
            id="{{ $column->idPrefix() }}-{{ $record->getKey() }}"
            wire:key="{{ $column->idPrefix() }}-{{ $record->getKey() }}-{{ (int) $column->resolveValue($record) }}"
            :checked="(bool) $column->resolveValue($record)"
            :disabled="$column->isDisabled($record)"
            data-disabled="{{ $column->isDisabled($record) ? 'true' : 'false' }}"
            wire:change="{{ $column->wireChange() }}({{ $record->getKey() }}, $event.target.checked)"
        />
    </div>
</flux:table.cell>
